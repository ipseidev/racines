<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\BuildQuizPreview;
use App\Actions\ClaimWelcomeOffer;
use App\Actions\SaveQuizAnswers;
use App\Enums\BookCover;
use App\Enums\BookTitle;
use App\Enums\Channel;
use App\Enums\QuestionTheme;
use App\Enums\QuizAgeBand;
use App\Enums\QuizDistance;
use App\Enums\QuizOccasion;
use App\Enums\QuizRelationship;
use App\Enums\QuizStorytellerStyle;
use App\Enums\TechComfort;
use App\Models\CheckoutDraft;
use App\Models\Lead;
use App\Settings\PilotSettings;
use App\Support\AudioSample;
use App\Support\Brand;
use App\Support\Drafts;
use App\Support\LocalizedRoutes;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

/**
 * Le tunnel de découverte : quatorze écrans, puis l'aperçu.
 *
 * Ce que c'est, et ce que ce n'est pas. Ce n'en est pas un second tunnel
 * posé devant `/acheter` : dix questions ici puis six étapes là-bas
 * feraient perdre au raccord plus que le quiz ne gagne. Il **absorbe** les
 * deux premières étapes du tunnel, écrit dans le même brouillon, et rend la
 * main à l'étape qui manque encore — les coordonnées du narrateur.
 *
 * Les quatorze écrans vivent côté client, et c'est délibéré : un aller-retour
 * serveur par réponse ferait quatorze attentes là où l'effet tient justement à
 * ce qu'une réponse en appelle une autre sans rien qui clignote. Le serveur
 * n'est appelé que deux fois — à la fin du quiz, puis pour l'adresse — et
 * l'abandon se mesure côté client, écran par écran.
 *
 * Une seule adresse pour le quiz et son aperçu. L'aperçu n'est pas une page :
 * c'est l'état du quiz une fois répondu, lu dans le brouillon. Deux adresses
 * auraient donné une page indexable qui ne montre rien à qui la découvre.
 */
final readonly class QuizController
{
    /** Le champ que personne ne voit et que personne ne remplit. */
    public const HONEYPOT = 'website';

    public function __construct(
        private SaveQuizAnswers $answers,
        private BuildQuizPreview $questions,
    ) {}

    public function show(Request $request): Response
    {
        $draft = Drafts::current($request);

        return inertia('public/Quiz', [
            'preview' => $this->preview($draft),
            'checkoutUrl' => LocalizedRoutes::route('checkout.show', ['step' => SaveQuizAnswers::RESUME_STEP]),
            'emailSaved' => $request->session()->get('quiz_email_saved', false),
            'welcomeOffer' => app(PilotSettings::class)->welcomeOfferActive(),
            // L'extrait du deuxième écran : notre preuve à nous, à la place
            // des « 4,7 sur 2 389 avis » que nous n'avons pas.
            'sample' => AudioSample::hero(),
            'minThemes' => SaveQuizAnswers::MIN_THEMES,

            // Les choix viennent du serveur, déjà traduits : une liste écrite
            // dans le composant ne serait traduite nulle part (cf. Options).
            'relationships' => Options::of(QuizRelationship::class),
            'subjects' => self::subjects(),
            'ageBands' => Options::of(QuizAgeBand::class),
            'distances' => Options::of(QuizDistance::class),
            'themes' => self::themes(),
            'storytellers' => Options::of(QuizStorytellerStyle::class),
            'techComforts' => Options::of(TechComfort::class),
            'channels' => Options::only(Channel::class, Channel::narratorPreferences()),
            'covers' => BookCover::palette(),
            'titles' => BookTitle::options(),
            /*
             * Le sous-titre de la couverture, composé par le serveur depuis
             * `lang/*\/book.php` — le catalogue du livre imprimé, qui ne part
             * pas au front. Le mot pour mot compte : la vignette doit dire ce
             * que l'imprimeur composera, pas une approximation écrite à côté.
             */
            'bookSubtitle' => __('book.collected', ['year' => now()->year]),
            'occasions' => Options::of(QuizOccasion::class),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(SaveQuizAnswers::rules());

        $draft = Drafts::open($request);

        $this->answers->handle($draft, $validated);

        return redirect()
            ->to(LocalizedRoutes::route('quiz'))
            ->withCookie(Drafts::cookie($draft));
    }

    /**
     * Recommencer : les réponses s'effacent, la commande reste.
     *
     * Le brouillon peut déjà porter autre chose — un code de réduction, une
     * étape franchie —, et « refaire le questionnaire » ne demande pas qu'on
     * jette le reste.
     */
    public function restart(Request $request): RedirectResponse
    {
        $draft = Drafts::current($request);

        if ($draft instanceof CheckoutDraft) {
            $this->answers->forget($draft);
        }

        return redirect()->to(LocalizedRoutes::route('quiz'));
    }

    /**
     * L'adresse laissée sous l'aperçu.
     *
     * Le même geste que la fenêtre de bienvenue (T-141), et la même règle :
     * le code part par courriel et jamais à l'écran, la case des nouvelles
     * est distincte et décochée. Ce qui change est l'origine, gardée sur le
     * contact : une adresse laissée après dix questions ne vaut pas la même
     * chose qu'une adresse laissée en trois secondes sur l'accueil.
     */
    public function email(Request $request, ClaimWelcomeOffer $claim): RedirectResponse
    {
        // Un robot qui reçoit une erreur apprend ; un robot qui reçoit un
        // merci s'en va.
        if ((string) $request->input(self::HONEYPOT, '') !== '') {
            return back();
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'news' => ['sometimes', 'boolean'],
        ]);

        try {
            $claim->handle(
                email: (string) $validated['email'],
                wantsNews: (bool) ($validated['news'] ?? false),
                context: ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
                source: Lead::SOURCE_QUIZ,
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => __('public.welcome_offer.errors.send_failed')]);
        }

        return back()->with('quiz_email_saved', true);
    }

    /**
     * Ce que l'aperçu montre, ou `null` tant que le quiz n'est pas fini.
     *
     * @return array{
     *     firstName: string, nickname: string|null, sender: string,
     *     channel: string, sendAt: string, sendTime: string, cover: string,
     *     title: string, titleCustom: string,
     *     first: string|null, next: list<string>, themes: list<string>
     * }|null
     */
    private function preview(?CheckoutDraft $draft): ?array
    {
        if (! $draft instanceof CheckoutDraft || $draft->value('quiz.completed_at') === null) {
            return null;
        }

        /** @var list<string> $themes */
        $themes = (array) $draft->value('quiz.themes', []);

        $picked = $this->questions->handle(array_values(array_filter(array_map(
            static fn (string $theme): ?QuestionTheme => QuestionTheme::tryFrom($theme),
            $themes,
        ))));

        return [
            'firstName' => (string) $draft->value('narrator_first_name', ''),
            'nickname' => $draft->value('quiz.nickname') === null ? null : (string) $draft->value('quiz.nickname'),
            // L'expéditeur réel du SMS, pas un nom de marque écrit à la main :
            // la bulle montrée doit être celle qui arrivera.
            'sender' => Brand::smsSenderId(),
            'channel' => (string) $draft->value('preferred_channel', Channel::Sms->value),
            'sendAt' => (string) $draft->value('gift_send_at', ''),
            'cover' => (string) $draft->value('book_cover', BookCover::default()->value),
            'title' => (string) $draft->value('book_title', BookTitle::default()->value),
            'titleCustom' => (string) $draft->value('book_title_custom', ''),
            'sendTime' => (string) $draft->value('gift_send_time', ''),
            'first' => $picked['first'],
            'next' => $picked['next'],
            'themes' => $themes,
        ];
    }

    /**
     * Le sujet dont parlent les écrans : « votre mère », « vos grands-parents ».
     *
     * @return array<string, string>
     */
    private static function subjects(): array
    {
        $subjects = [];

        foreach (QuizRelationship::cases() as $case) {
            $subjects[$case->value] = __($case->subject());
        }

        return $subjects;
    }

    /**
     * Les thèmes, avec les libellés du quiz et non ceux du back-office.
     *
     * « Son enfance » plutôt que « Enfance » : le même thème, dit à quelqu'un
     * qui hésite à acheter plutôt qu'à quelqu'un qui administre un corpus.
     *
     * @return list<array{value: string, label: string}>
     */
    private static function themes(): array
    {
        return array_map(
            static fn (QuestionTheme $theme): array => [
                'value' => $theme->value,
                'label' => __('enums.quiz_theme.'.$theme->value),
            ],
            QuestionTheme::cases(),
        );
    }
}
