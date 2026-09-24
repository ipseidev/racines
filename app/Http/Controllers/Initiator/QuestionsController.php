<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Actions\AttachPhoto;
use App\Actions\PickNextQuestion;
use App\Actions\ProposeStory;
use App\Audit\AuditLog;
use App\Enums\BuyerRelation;
use App\Exceptions\Domain\InfectedUpload;
use App\Exceptions\Domain\UnsupportedImage;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\Support\InitiatorProject;
use App\Support\Options;
use App\Support\QuestionQueue;
use App\Support\QuestionWording;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
        private QuestionQueue $queue,
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

        $gender = $project->primaryNarrator()->first()?->grammatical_gender;

        $present = fn (Question $question): array => [
            'id' => $question->id,
            'text' => QuestionWording::forProject($question, $project, $gender),
            'theme' => $question->theme->value,
            'themeLabel' => Options::label($question->theme),
        ];

        /*
         * Les questions écrites par la famille et pas encore parties.
         *
         * Elles ne sont pas dans le corpus : une question personnalisée
         * devient tout de suite une histoire PROPOSÉE. Sans cette liste, la
         * page promettait un ordre où elles n'apparaissaient nulle part — on
         * écrivait sa question et elle disparaissait de l'écran où on venait
         * de l'écrire. Elles passent devant le corpus à l'envoi, donc elles
         * s'affichent devant lui ici.
         */

        /*
         * **Une seule file**, où les deux natures se mêlent (T-255).
         *
         * Les questions de la famille formaient un bloc en tête, et rien ne
         * pouvait passer devant : deux listes, deux échelles, aucun ordre
         * commun. Elles partagent maintenant le rang — `queue_order` pour
         * une histoire proposée, `custom_order` pour une question du corpus —
         * et l'écran n'en montre plus qu'une, dans l'ordre où ça partira.
         */
        $file = $this->queue->forProject($project);

        return inertia('initiator/Questions', [
            // Refaire le tunnel de personnalisation : sauf pour qui raconte
            // sa propre histoire, qui n'a personne à décrire.
            'personalizeUrl' => $project->profile?->buyer_relation === BuyerRelation::Myself
                ? null
                : route('initiator.personalize', ['project' => $project], false),
            'queue' => $this->picker->queue($project)->map($present)->values()->all(),
            'excluded' => Question::query()->active()->whereIn('id', $excludedIds)
                ->orderBy('order_hint')->orderBy('slug')->get()->map($present)->values()->all(),
            'asked' => Question::query()->whereIn('id', $askedIds)
                ->orderBy('order_hint')->orderBy('slug')->get()->map($present)->values()->all(),
            'next' => $file,
            'narratorFirstName' => $project->primaryNarrator?->first_name,
        ]);
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
     * L'ordre de la file, les deux natures mêlées (T-255).
     *
     * On reçoit la file entière — `{kind, id}` dans l'ordre voulu — et on
     * écrit le rang de chacun dans **sa** table : `custom_order` pour une
     * question du corpus, `queue_order` pour une histoire déjà proposée.
     * Les deux vivent dans la même échelle, et c'est ce qui permet à l'une de
     * passer devant l'autre.
     *
     * Les rangs commencent à 0 : une question de la famille placée en tête y
     * retrouve le rang que lui donne `store`, et rien ne dépend d'un décalage
     * implicite.
     *
     * Ce qui n'attend pas ne bouge pas : une histoire dont le lien est déjà
     * chez la narratrice est ignorée, quoi qu'en dise la requête.
     */
    public function reorder(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*.kind' => ['required', 'string', 'in:story,question'],
            'order.*.id' => ['required', 'string'],
        ]);

        $pending = $this->queue->pendingStories($project)->keyBy('id');

        /** @var list<array{kind: string, id: string}> $entrees */
        $entrees = array_values((array) $validated['order']);

        DB::transaction(function () use ($entrees, $pending, $project): void {
            foreach ($entrees as $position => $entree) {
                if ($entree['kind'] === 'question') {
                    $project->questionSettings()->updateOrCreate(
                        ['question_id' => $entree['id']],
                        ['custom_order' => $position],
                    );

                    continue;
                }

                $story = $pending->get($entree['id']);

                if ($story instanceof Story) {
                    $story->forceFill(['queue_order' => $position])->save();
                }
            }
        });

        return back()->with('status', __('initiator.questions.reordered'));
    }

    /**
     * Retirer une question qu'on a écrite soi-même.
     *
     * Les questions du corpus s'écartent ; les siennes n'avaient rien — et
     * une question qu'on ne peut pas reprendre est un piège, surtout avec des
     * photos jointes. On la **supprime** plutôt que de l'écarter : elle
     * n'existe que pour ce projet, il n'y a rien à réintégrer nulle part, et
     * la laisser en base sous un drapeau ferait un cimetière d'histoires
     * jamais racontées.
     *
     * Jamais une histoire dont le lien est parti : celle-là appartient déjà à
     * la narratrice, qui seule décide de ce qu'elle en fait (bloc 07).
     */
    public function destroyPending(Request $request, Project $project, string $story): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $found = $this->queue->pendingStories($project)->firstWhere('id', $story);

        abort_unless($found instanceof Story, 404);

        AuditLog::record('deleted Story', $found, [
            'reason' => 'initiator_withdrew_custom_question',
        ], $project);

        $found->delete();

        return back()->with('status', __('initiator.questions.removed'));
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

        /*
         * Rang 0 : devant tout le corpus, qui commence à 1.
         *
         * C'est le défaut demandé — « quand j'ajoute une question, je veux
         * qu'elle soit première » — et ce n'est qu'un **défaut** : elle vit
         * dans la même échelle que les questions du corpus, donc l'une comme
         * l'autre peut ensuite passer devant.
         */
        $story->forceFill(['queue_order' => 0])->save();

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
