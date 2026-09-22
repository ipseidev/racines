<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Actions\AttachPhoto;
use App\Actions\ProposeStory;
use App\Exceptions\Domain\InfectedUpload;
use App\Exceptions\Domain\UnsupportedImage;
use App\Models\FamilyMember;
use App\Models\Story;
use App\Support\FamilyPresenter;
use App\Support\QuestionQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Une question posée par un proche (R-1, dossier v3.1).
 *
 * C'est le maillon H2 que l'écoute seule ne produit pas. Écouter et mettre un
 * cœur est passif ; demander à son aïeule ce qu'on a toujours voulu savoir ne
 * l'est pas — et c'est du contenu qu'un corpus éditorial ne peut pas
 * fabriquer, faute de connaître la famille.
 *
 * **Le droit est explicite, et donné personne par personne.** Un jeton
 * d'écoute valide ne suffit pas : `can_ask` s'accorde proche par proche par
 * l'Initiateur·rice, et on peut inviter un cousin en lecture seule à côté
 * d'une fille qui pose des questions.
 *
 * **La question rejoint la file derrière ce qui attend déjà.** Elle ne double
 * personne : celle que l'Initiateur·rice vient d'écrire part avant. Cette
 * dernière garde la main — elle réordonne et retire depuis sa page des
 * questions (T-63) — et le veto du narrateur prévaut sur tout le monde : il
 * peut ne pas répondre, et personne ne le lui demandera deux fois.
 *
 * Le chemin est **celui de l'Initiateur·rice**, `ProposeStory` puis
 * `AttachPhoto` : une question de proche n'est pas une autre sorte d'objet,
 * c'est la même histoire proposée, avec un auteur.
 */
final readonly class QuestionController
{
    /** Quatre photos au plus : au-delà, on ne pose plus une question. */
    public const MAX_PHOTOS = 4;

    public function __construct(
        private ProposeStory $stories,
        private AttachPhoto $photos,
        private QuestionQueue $queue,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $member = FamilyPresenter::memberFor($request);

        abort_unless($member->can_ask === true && $member->removed_at === null, 403);

        $validated = $request->validate([
            'text' => ['required', 'string', 'min:10', 'max:300'],
            /*
             * Le **type** ne se vérifie pas ici : `AttachPhoto` annonce en
             * tête de sa classe « scanner d'abord », et une règle `image`
             * renverrait un fichier hostile avec « le fichier doit être une
             * image », sans l'avoir scanné et sans laisser de trace (T-187).
             */
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['file', 'max:'.AttachPhoto::MAX_KILOBYTES],
        ]);

        $project = $member->project;

        /*
         * Le narrateur avant tout le reste : `ProposeStory` refuse un projet
         * qui n'en a pas, et la confirmation le nomme. Le lire ici, une fois,
         * évite de le redemander à la base et de le supposer non nul.
         */
        $narrator = $project->primaryNarrator;

        abort_if($narrator === null, 404);

        $story = $this->stories->handle($project, null, (string) $validated['text']);

        /*
         * Derrière ce qui attend déjà, jamais devant.
         *
         * L'Initiateur·rice place les siennes au rang 0 — c'est son projet, et
         * T-63 dit que son ordre délibéré gagne. Un proche se met à la suite :
         * proposer une question n'est pas doubler la file.
         */
        $story->forceFill([
            'proposed_by_family_member_id' => $member->id,
            'queue_order' => $this->nextRank($member),
        ])->save();

        /** @var list<UploadedFile> $photos */
        $photos = array_values((array) $request->file('photos', []));

        foreach ($photos as $photo) {
            try {
                // L'histoire vient de naître en PROPOSÉE : `AttachPhoto` y
                // pose donc `is_prompt`, et le narrateur verra l'image
                // **avec** la question. C'est tout l'intérêt.
                $this->photos->handle($story, $photo, $member, null);
            } catch (InfectedUpload|UnsupportedImage) {
                /*
                 * La question est posée et vaut sans l'image : on ne la défait
                 * pas pour une photo refusée.
                 *
                 * Un message, et **pas** `withErrors` : une réponse qui porte
                 * des erreurs de validation est un échec pour Inertia, donc
                 * le formulaire reste ouvert, le statut n'est jamais montré,
                 * et la question part quand même — la personne voit un
                 * formulaire qui n'a pas l'air d'avoir marché alors que sa
                 * question est dans la file. Ici le geste a réussi ; ce qui a
                 * échoué se raconte, il ne se signale pas comme une faute de
                 * saisie.
                 */
                return back()->with('status', __('family.ask.added_without_photo'));
            }
        }

        return back()->with('status', __('family.ask.added', [
            // Le prénom de la narratrice, sinon le message affichait « :name »
            // à l'écran — un marqueur de gabarit servi à une vraie personne.
            'name' => $narrator->first_name,
        ]));
    }

    /**
     * Le rang qui met la question en queue de file.
     *
     * Un de plus que le dernier qui attend, et jamais moins de 1 : le rang 0
     * appartient à l'Initiateur·rice, qui y place ce qu'elle veut voir partir
     * en premier.
     */
    private function nextRank(FamilyMember $member): int
    {
        $dernier = $this->queue->pendingStories($member->project)
            ->max(fn (Story $story): int => (int) ($story->queue_order ?? 0));

        return max(1, (int) $dernier + 1);
    }
}
