<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DeleteStoryAction;
use App\Audit\AuditLog;
use App\Enums\DeletionRequestedBy;
use App\Models\AccessToken;
use App\Models\Consent;
use App\Models\Export;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Services\Storage\MediaStorage;
use App\States\Story\Deleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Effacer un projet : les voix, les textes, les identités.
 *
 * Ce qui part et ce qui reste n'est pas un compromis technique — c'est
 * l'articulation de deux obligations qui tirent en sens contraire. Le droit à
 * l'effacement veut que tout disparaisse ; l'obligation de preuve veut qu'on
 * puisse démontrer, dans dix ans, qu'un consentement avait été donné et
 * qu'une commande avait été payée.
 *
 * **Ce qui part** : les objets du stockage (audio, photos, dérivés,
 * répliques), les transcriptions, les médias, le lexique, les réactions, et
 * les champs personnels des narrateurs et des proches. Les jetons sont
 * révoqués — un lien d'écoute qui survivrait ouvrirait une page vide, ou
 * pire, servirait un cache.
 *
 * **Ce qui reste** : les commandes, pour la comptabilité, dont ce n'est pas
 * nous qui fixons la durée. Les consentements, avec un **sujet haché** : la
 * preuve subsiste, elle ne désigne plus personne. Et le journal d'audit, dont
 * les lignes sont déjà masquées par `Redactor` — effacer la preuve de ce
 * qu'on a fait des données serait effacer le moyen de répondre à qui les
 * réclame.
 *
 * Le job est **rejouable** : un effacement interrompu à la moitié doit
 * pouvoir reprendre, et rien de ce qu'il fait n'a besoin d'un état initial.
 */
final class EraseProject implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    /**
     * Ce qui remplace un nom effacé.
     *
     * Un marqueur explicite plutôt qu'une chaîne vide ou un tiret : dans un
     * dump de base, six mois plus tard, il faut pouvoir distinguer « cette
     * personne a demandé l'effacement » de « ce champ n'a jamais été rempli ».
     */
    public const MARQUEUR = '[effacé]';

    public function __construct(private readonly string $projectId)
    {
        $this->onQueue('exports');
    }

    public function handle(MediaStorage $storage): void
    {
        $project = Project::query()
            ->with(['stories.recordings', 'narrators', 'familyMembers'])
            ->find($this->projectId);

        if ($project === null) {
            return;
        }

        /*
         * Chaque histoire part par **la machine à états**, jamais par une
         * suppression directe.
         *
         * Un déclencheur Postgres refuse d'effacer un mot à mot tant que son
         * histoire vit (bloc 06) — et il a raison : c'est ce qui protège la
         * parole source d'une suppression accidentelle. Le chemin légitime
         * existe déjà, `DeleteStoryAction` puis `PurgeDeletedStory`, et le
         * réimplémenter ici aurait produit une seconde définition de « purger
         * une histoire » qui aurait divergé (même leçon que `ChainVerifier`).
         *
         * La purge est appelée **maintenant** et non mise en file : un
         * effacement à moitié fait, dont la suite dépendrait d'un worker,
         * laisserait des objets en place sans que rien ne le dise.
         */
        foreach ($project->stories as $story) {
            if (! $story->state instanceof Deleted) {
                app(DeleteStoryAction::class)->handle($story, DeletionRequestedBy::Narrator);
            }

            app()->call([new PurgeDeletedStory($story->getKey()), 'handle']);
        }

        $this->eraseObjects($project, $storage);

        DB::transaction(function () use ($project): void {
            foreach ($project->stories as $story) {
                $story->reactions()->delete();
                $story->media()->delete();

                // Le titre est du contenu : il part aussi.
                $story->forceFill(['title' => null])->save();
            }

            $project->lexiconEntries()->delete();

            foreach ($project->narrators as $narrator) {
                $this->anonymise($narrator);
            }

            foreach ($project->familyMembers as $member) {
                $this->anonymise($member);
            }

            $this->anonymiseConsents($project);

            AccessToken::query()
                ->whereNull('revoked_at')
                ->whereIn('subject_id', $project->stories->pluck('id')->all())
                ->update(['revoked_at' => now()]);

            $this->revokeProjectTokens($project);

            // Les archives d'export deviennent des lignes sans objet : leur
            // contenu, c'est précisément ce qu'on vient d'effacer.
            Export::query()->where('project_id', $project->getKey())
                ->update(['status' => 'expired', 'object_path' => null, 'manifest' => null]);

            $project->forceFill(['erased_at' => now()])->save();
        });

        AuditLog::record('erased Project', $project, [
            'stories' => $project->stories->count(),
            'narrators' => $project->narrators->count(),
        ], $project);

        Log::warning('rgpd.project_erased', ['project_id' => $project->getKey()]);
    }

    /**
     * Les objets du stockage, hors transaction.
     *
     * Une transaction de base ne protège rien de ce qui vit sur R2 : si elle
     * échouait après coup, les objets seraient partis quand même. On efface
     * donc **avant**, et l'ordre est le bon — mieux vaut une ligne qui pointe
     * un objet absent qu'un objet qui survit à sa ligne.
     */
    private function eraseObjects(Project $project, MediaStorage $storage): void
    {
        $cles = [];

        foreach ($project->stories as $story) {
            foreach ($story->recordings as $recording) {
                foreach ([$recording->original_path, $recording->derived_mp3_path, $recording->derived_mp4_path, $recording->replica_path] as $cle) {
                    if (is_string($cle) && $cle !== '') {
                        $cles[] = $cle;
                    }
                }

                foreach ($recording->segments ?? [] as $segment) {
                    $cle = $segment['key'] ?? null;

                    if (is_string($cle) && $cle !== '') {
                        $cles[] = $cle;
                    }
                }
            }

            foreach ($story->getMedia(Story::PHOTOS) as $photo) {
                $cles[] = $photo->id.'/'.$photo->file_name;
            }
        }

        foreach (Export::query()->where('project_id', $project->getKey())->pluck('object_path') as $cle) {
            if (is_string($cle) && $cle !== '') {
                $cles[] = $cle;
            }
        }

        foreach (array_unique($cles) as $cle) {
            try {
                $storage->delete($cle);
            } catch (Throwable $exception) {
                // Un objet déjà absent n'arrête pas un effacement : le laisser
                // échouer laisserait tout le reste en place.
                Log::info('rgpd.object_missing', ['key' => $cle, 'reason' => $exception->getMessage()]);
            }
        }
    }

    /**
     * Les champs personnels à `null`, la ligne conservée.
     *
     * Supprimer la ligne casserait les clés étrangères des histoires et des
     * commandes — et ferait disparaître la structure dont l'audit a besoin
     * pour rester lisible. Ce qui identifie part ; ce qui relie reste.
     */
    private function anonymise(Narrator|FamilyMember $personne): void
    {
        /*
         * `first_name` et `display_name` ne sont pas nulls en base, et c'est
         * volontaire : tout le produit les lit sans vérifier, un narrateur
         * sans prénom ferait tomber vingt écrans. Ils reçoivent donc un
         * **marqueur** — qui dit ce qui s'est passé, plutôt qu'un tiret qui
         * ferait croire à une donnée manquante.
         *
         * Le reste part à `null` : ce sont les coordonnées, et elles ne
         * servent qu'à joindre quelqu'un qui a demandé qu'on ne le joigne
         * plus.
         */
        $champs = [
            'last_name' => null,
            'email' => null,
            'phone_e164' => null,
        ];

        /*
         * `contact_deleted_at` est la condition de la contrainte
         * `narrators_reachable_check` : un narrateur **vivant** doit rester
         * joignable, un narrateur dont on a effacé les coordonnées n'a plus
         * à l'être. La colonne existe depuis le bloc 05, posée pour ce cas
         * précis — la renseigner est la façon prévue de le dire à la base.
         */
        if ($personne->getConnection()->getSchemaBuilder()->hasColumn($personne->getTable(), 'contact_deleted_at')) {
            $champs['contact_deleted_at'] = now();
        }

        if ($personne instanceof Narrator) {
            $champs['first_name'] = self::MARQUEUR;
        }

        if ($personne->getConnection()->getSchemaBuilder()->hasColumn($personne->getTable(), 'display_name')) {
            $champs['display_name'] = self::MARQUEUR;
        }

        $personne->forceFill($champs)->save();
    }

    /**
     * Les consentements survivent, leur sujet est haché.
     *
     * La preuve d'un consentement est une obligation ; désigner la personne
     * ne l'est pas. Le hachage garde la possibilité de vérifier « cette
     * personne avait-elle consenti » si elle se présente avec son
     * identifiant, sans permettre de remonter d'un dump vers un nom.
     */
    private function anonymiseConsents(Project $project): void
    {
        foreach (Consent::query()->where('project_id', $project->getKey())->get() as $consent) {
            $consent->forceFill([
                'subject_id' => hash('sha256', $consent->subject_type.':'.$consent->subject_id),
            ])->save();
        }
    }

    private function revokeProjectTokens(Project $project): void
    {
        $sujets = [
            ...$project->narrators->pluck('id')->all(),
            ...$project->familyMembers->pluck('id')->all(),
            $project->getKey(),
        ];

        AccessToken::query()
            ->whereNull('revoked_at')
            ->whereIn('subject_id', array_map('strval', $sujets))
            ->update(['revoked_at' => now()]);
    }
}
