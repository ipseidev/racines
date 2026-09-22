<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Actions\AttachPhoto;
use App\Actions\PickNextQuestion;
use App\Actions\ProposeStory;
use App\Exceptions\Domain\InfectedUpload;
use App\Exceptions\Domain\UnsupportedImage;
use App\Models\Project;
use App\Models\Question;
use App\Support\InitiatorProject;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Response;

/**
 * Le corpus, vu par l'Initiateur·rice.
 *
 * Elle réordonne, exclut, ajoute. La règle 3 de l'annexe A gagne contre la
 * règle 5 (décision T-63) : une question avancée par elle passe devant, y
 * compris intime. La règle 5 protège du séquencement **automatique**, qui ne
 * connaît pas la famille ; elle n'a pas à contredire un choix délibéré. Le
 * narrateur, lui, garde le droit de ne pas répondre — c'est là que vit sa
 * souveraineté, pas dans le corpus.
 */
final readonly class QuestionsController
{
    /** Quatre photos au plus sur une question : au-delà, c'est un diaporama. */
    public const MAX_PHOTOS = 4;

    public function __construct(
        private ProposeStory $stories,
        private PickNextQuestion $picker,
        private AttachPhoto $photos,
    ) {}

    /**
     * Trois listes, pas une : ce qui va partir, dans l'ordre où ça partira ;
     * ce qu'elle a écarté ; ce qui a déjà été posé. La première est celle du
     * moteur (`PickNextQuestion::queue`), pour que l'écran ne promette jamais
     * un ordre que l'envoi ne tiendrait pas.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $askedIds = $project->stories()->whereNotNull('question_id')->pluck('question_id')->all();
        $excludedIds = $project->questionSettings()->where('excluded', true)->pluck('question_id')->all();

        $present = fn (Question $question): array => [
            'id' => $question->id,
            'text' => $question->text,
            'theme' => $question->theme->value,
            'themeLabel' => Options::label($question->theme),
        ];

        return inertia('initiator/Questions', [
            'queue' => $this->picker->queue($project)->map($present)->values()->all(),
            'excluded' => Question::query()->active()->whereIn('id', $excludedIds)
                ->orderBy('order_hint')->orderBy('slug')->get()->map($present)->values()->all(),
            'asked' => Question::query()->whereIn('id', $askedIds)
                ->orderBy('order_hint')->orderBy('slug')->get()->map($present)->values()->all(),
            'narratorFirstName' => $project->primaryNarrator?->first_name,
        ]);
    }

    public function reorder(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['string'],
        ]);

        foreach (array_values((array) $validated['order']) as $position => $questionId) {
            $project->questionSettings()->updateOrCreate(
                ['question_id' => (string) $questionId],
                ['custom_order' => $position + 1],
            );
        }

        return back()->with('status', __('initiator.questions.reordered'));
    }

    /*
     * `Project $project` en tête, et il n'est pas lu ici.
     *
     * Depuis que les adresses portent le projet, Laravel passe les
     * paramètres de route **par position** : sans cette déclaration,
     * `$member` recevait le projet sérialisé et la requête tombait sur un
     * « invalid input syntax for type uuid ». Le projet lui-même continue
     * d'être résolu par `InitiatorProject`, qui lit la route.
     */
    public function exclude(Request $request, Project $project, string $question): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $validated = $request->validate(['excluded' => ['required', 'boolean']]);

        $project->questionSettings()->updateOrCreate(
            ['question_id' => $question],
            ['excluded' => (bool) $validated['excluded']],
        );

        return back()->with('status', __('initiator.questions.updated'));
    }

    /**
     * Une question écrite par la famille.
     *
     * Elle ne rejoint pas le corpus : elle devient une histoire proposée avec
     * son texte propre (`stories.custom_question_text`). Le corpus est un
     * bien commun, relu ; une question de famille est une question de famille.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $validated = $request->validate([
            'text' => ['required', 'string', 'min:10', 'max:300'],
            /*
             * Les photos de la question (T-251, T-253).
             *
             * Le **type** ne se vérifie pas ici : `AttachPhoto` annonce en
             * tête de sa classe « scanner d'abord », et une règle `image`
             * renverrait un fichier hostile avec « le fichier doit être une
             * image », sans l'avoir scanné et sans laisser de trace (T-187).
             * `Sanitizer` refuse ensuite ce qui n'est pas une image lisible.
             *
             * Quatre au plus. Ce n'est pas une borne technique : au-delà, on
             * ne pose plus une question, on propose un diaporama — et la
             * carte de la narratrice n'en montre que des vignettes.
             */
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['file', 'max:'.AttachPhoto::MAX_KILOBYTES],
        ]);

        $story = $this->stories->handle($project, null, (string) $validated['text']);

        /** @var list<UploadedFile> $photos */
        $photos = array_values((array) $request->file('photos', []));

        foreach ($photos as $photo) {
            try {
                /*
                 * L'histoire vient de naître en PROPOSÉE : `AttachPhoto` y
                 * pose donc `is_prompt`, et la narratrice verra l'image
                 * **avec** la question. C'est le même chemin que le dépôt
                 * depuis le tableau de bord — on ne fabrique rien à côté.
                 */
                $this->photos->handle($story, $photo, $user, null);
            } catch (InfectedUpload|UnsupportedImage $exception) {
                /*
                 * La question est déjà posée, et elle vaut sans l'image : on
                 * ne la défait pas pour une photo refusée. Le message dit
                 * laquelle, et la personne peut la joindre depuis le tableau
                 * de bord, où le dépôt vit aussi.
                 */
                return back()
                    ->with('status', __('initiator.questions.added_without_photo'))
                    ->withErrors(['photos' => $exception->getMessage()]);
            }
        }

        return back()->with('status', __('initiator.questions.added'));
    }
}
