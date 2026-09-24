<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Actions\PickNextQuestion;
use App\Audit\AuditLog;
use App\Enums\BuyerFocus;
use App\Enums\BuyerRelation;
use App\Enums\GrammaticalGender;
use App\Enums\QuestionCondition;
use App\Enums\QuestionTheme;
use App\Enums\SensitiveTopic;
use App\Models\Project;
use App\Models\ProjectProfile;
use App\Models\ProjectQuestionSetting;
use App\Models\Question;
use App\Support\Options;
use App\Support\QuestionProfile;
use App\Support\QuestionWording;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * Le tunnel de personnalisation, juste après l'achat.
 *
 * Deux minutes pour dire qui est la narratrice, ce qu'a été sa vie, ce qu'il
 * vaut mieux éviter et ce qu'on aimerait l'entendre raconter. Tout se passe
 * côté navigateur jusqu'au dernier écran, puis une seule écriture remplit le
 * profil (`ProjectProfile`) que lit le choix des questions.
 *
 * Les trois premières questions qu'on montre ensuite sont calculées **après**
 * cette écriture, par le vrai choix des questions : une promesse faite ici est
 * tenue à l'envoi. Celle qu'on choisit passe devant, comme une question
 * avancée à la main.
 *
 * Passer le tunnel ne retire rien : un profil vide envoie exactement ce qu'on
 * enverrait sans lui.
 */
final readonly class PersonalizeController
{
    /** Les exemples de question sur l'acheteur, un par lien. */
    private const PREVIEWS = [
        'child' => 'prenom-naissance',
        'grandchild' => 'prenom-petit-avec-moi',
        'other' => 'prenom-premier-souvenir',
    ];

    /** Les trois cartes de l'accueil : des questions du corpus, pour tout le monde. */
    private const DECK = ['rencontre-conjoint', 'maison-enfance', 'chanson-par-coeur'];

    private const RELATIONS = ['child', 'grandchild', 'partner', 'other'];

    public function __construct(private PickNextQuestion $picker) {}

    public function show(Request $request, Project $project): Response|RedirectResponse
    {
        $profile = $project->profile;

        // Qui raconte sa propre histoire n'a personne à décrire ici.
        if ($profile?->buyer_relation === BuyerRelation::Myself) {
            return redirect()->route('initiator.dashboard', ['project' => $project]);
        }

        $narrator = $project->primaryNarrator;
        $step = match ($request->query('etape')) {
            'premieres' => $profile?->completed_at !== null ? 'premieres' : null,
            'fin' => 'fin',
            default => null,
        };

        return inertia('initiator/Personalize', [
            'narratorFirstName' => $narrator?->first_name,
            'step' => $step,
            'skipped' => $profile !== null && $profile->skipped_at !== null
                && ($profile->completed_at === null || $profile->skipped_at->greaterThan($profile->completed_at)),
            'profile' => $profile === null || $profile->completed_at === null ? null : [
                'relation' => $profile->buyer_relation?->value,
                'narratorGender' => $narrator?->grammatical_gender?->value,
                'buyerFocus' => $profile->buyer_focus?->value,
                'buyerFirstName' => $profile->buyer_first_name,
                'buyerGender' => $profile->buyer_gender?->value,
                'facts' => (object) $profile->facts,
                'avoidedTopics' => $profile->avoided_topics,
                'favoredThemes' => $profile->favored_themes,
            ],
            'themes' => array_map(
                static fn (QuestionTheme $theme): array => ['value' => $theme->value, 'label' => Options::label($theme)],
                QuestionTheme::cases(),
            ),
            'facts' => array_map(static fn (QuestionCondition $fact): string => $fact->value, self::askedFacts()),
            'topics' => array_map(
                static fn (SensitiveTopic $topic): array => ['value' => $topic->value, 'label' => Options::label($topic)],
                SensitiveTopic::cases(),
            ),
            'deck' => $this->deck($project),
            'previews' => $this->previews($project),
            'firstQuestions' => $step === 'premieres' ? $this->firstQuestions($project) : [],
            'dashboardUrl' => route('initiator.dashboard', ['project' => $project], false),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'relation' => ['required', Rule::in(self::RELATIONS)],
            'narrator_gender' => ['required', new Enum(GrammaticalGender::class)],
            'buyer_focus' => ['nullable', new Enum(BuyerFocus::class)],
            'buyer_first_name' => ['nullable', 'required_if:buyer_focus,buyer', 'string', 'max:80'],
            'buyer_gender' => ['nullable', 'required_if:buyer_focus,buyer', new Enum(GrammaticalGender::class)],
            'facts' => ['array', $this->onlyLifeFacts()],
            'facts.*' => ['boolean'],
            'avoided_topics' => ['array'],
            'avoided_topics.*' => [new Enum(SensitiveTopic::class)],
            'favored_themes' => ['array', 'max:3'],
            'favored_themes.*' => [new Enum(QuestionTheme::class)],
        ]);

        $aboutBuyer = ($data['buyer_focus'] ?? null) === BuyerFocus::Buyer->value;

        DB::transaction(function () use ($project, $data, $aboutBuyer): void {
            $project->primaryNarrator()->update(['grammatical_gender' => $data['narrator_gender']]);

            ProjectProfile::query()->updateOrCreate(['project_id' => $project->id], [
                'buyer_relation' => $data['relation'],
                'buyer_focus' => $data['buyer_focus'] ?? null,
                // Sans questions sur lui, son prénom n'a rien à faire ici.
                'buyer_first_name' => $aboutBuyer ? trim((string) $data['buyer_first_name']) : null,
                'buyer_gender' => $aboutBuyer ? $data['buyer_gender'] : null,
                'facts' => array_map(static fn (mixed $value): bool => (bool) $value, $data['facts'] ?? []),
                'avoided_topics' => array_values(array_unique($data['avoided_topics'] ?? [])),
                'favored_themes' => array_values(array_unique($data['favored_themes'] ?? [])),
                'completed_at' => now(),
                'skipped_at' => null,
            ]);
        });

        AuditLog::record('personalized Project', $project, ['relation' => $data['relation'], 'about_buyer' => $aboutBuyer]);

        return redirect()->to(route('initiator.personalize', ['project' => $project], false).'?etape=premieres');
    }

    /** La première question choisie parmi les trois proposées passe devant. */
    public function first(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['question_id' => ['required', 'uuid']]);

        $offered = $this->offered($project)->pluck('id')->all();

        if (! in_array($data['question_id'], $offered, true)) {
            throw ValidationException::withMessages(['question_id' => __('initiator.personalize.errors.first')]);
        }

        // Devant tout ce qui a été avancé à la main, s'il y en a.
        $front = (int) ($project->questionSettings()->whereNotNull('custom_order')->min('custom_order') ?? 1);

        ProjectQuestionSetting::query()->updateOrCreate(
            ['project_id' => $project->id, 'question_id' => $data['question_id']],
            ['custom_order' => $front - 1, 'excluded' => false],
        );

        return redirect()->to(route('initiator.personalize', ['project' => $project], false).'?etape=fin');
    }

    public function skip(Project $project): RedirectResponse
    {
        $profile = ProjectProfile::query()->firstOrNew(['project_id' => $project->id]);
        $profile->skipped_at = now();
        $profile->save();

        AuditLog::record('skipped Project personalization', $project);

        return redirect()->to(route('initiator.personalize', ['project' => $project], false).'?etape=fin');
    }

    /**
     * Les faits qu'on demande dans le tunnel. « A connu ses parents » n'y est
     * pas : la question est trop intrusive pour deux minutes de tunnel, et le
     * cas trop rare pour la poser à tous.
     *
     * @return list<QuestionCondition>
     */
    private static function askedFacts(): array
    {
        return array_values(array_filter(
            QuestionCondition::lifeFacts(),
            static fn (QuestionCondition $fact): bool => $fact !== QuestionCondition::KnewParents,
        ));
    }

    private function onlyLifeFacts(): Closure
    {
        $known = array_map(static fn (QuestionCondition $fact): string => $fact->value, QuestionCondition::lifeFacts());

        return static function (string $attribute, mixed $value, Closure $fail) use ($known): void {
            if (is_array($value) && array_diff(array_map('strval', array_keys($value)), $known) !== []) {
                $fail(__('initiator.personalize.errors.facts'));
            }
        };
    }

    /**
     * @return list<array{text: string, theme: string, themeLabel: string}>
     */
    private function deck(Project $project): array
    {
        $gender = $project->primaryNarrator?->grammatical_gender;

        return array_values(Question::query()->whereIn('slug', self::DECK)->get()
            ->sortBy(fn (Question $question): int|false => array_search($question->slug, self::DECK, true))
            ->map(fn (Question $question): array => [
                // La première phrase seulement : une carte, pas un paragraphe.
                'text' => self::firstSentence(QuestionWording::for($question, $project->address_form, $gender)),
                'theme' => $question->theme->value,
                'themeLabel' => Options::label($question->theme),
            ])
            ->all());
    }

    /**
     * L'exemple de question sur l'acheteur, pour chaque lien, chaque genre de
     * la narratrice et chaque genre de l'acheteur. Le prénom reste en
     * marqueur : c'est le navigateur qui l'écrit, lettre après lettre.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    private function previews(Project $project): array
    {
        $questions = Question::query()->whereIn('slug', array_values(self::PREVIEWS))->get()->keyBy('slug');
        $genders = ['feminine' => GrammaticalGender::Feminine, 'masculine' => GrammaticalGender::Masculine, 'unknown' => null];
        $previews = [];

        foreach (self::PREVIEWS as $kind => $slug) {
            $question = $questions->get($slug);

            if (! $question instanceof Question) {
                continue;
            }

            foreach (['feminine' => GrammaticalGender::Feminine, 'masculine' => GrammaticalGender::Masculine] as $narratorKey => $narratorGender) {
                foreach ($genders as $buyerKey => $buyerGender) {
                    $previews[$kind][$narratorKey][$buyerKey] = QuestionWording::for($question, $project->address_form, $narratorGender, null, $buyerGender);
                }
            }
        }

        return $previews;
    }

    /**
     * @return list<array{id: string, text: string, theme: string, themeLabel: string}>
     */
    private function firstQuestions(Project $project): array
    {
        $gender = $project->primaryNarrator?->grammatical_gender;

        return array_values($this->offered($project)
            ->map(fn (Question $question): array => [
                'id' => $question->id,
                'text' => QuestionWording::forProject($question, $project, $gender),
                'theme' => $question->theme->value,
                'themeLabel' => Options::label($question->theme),
            ])
            ->all());
    }

    /**
     * Les trois questions parmi lesquelles choisir la première : les trois
     * premières qui partiront, sans les questions sur l'acheteur. Celles-là
     * ont leur rythme — la sixième semaine, puis toutes les huit — et une
     * question sur soi n'ouvre pas le livre de quelqu'un d'autre.
     *
     * @return Collection<int, Question>
     */
    private function offered(Project $project): Collection
    {
        $profile = QuestionProfile::of($project);

        return $this->picker->queue($project)
            ->reject(fn (Question $question): bool => $profile->isAboutBuyer($question))
            ->take(3)
            ->values();
    }

    private static function firstSentence(string $text): string
    {
        return preg_match('/^.+?[?.!](?=\s|$)/u', $text, $match) === 1 ? $match[0] : $text;
    }
}
