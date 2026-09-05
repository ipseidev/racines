<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\IssueRecordToken;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\States\Story\Proposed;
use App\States\Story\Shared;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Le préalable du point 1 du bloc 09, qui ne se fait pas à la main.
 *
 * La feuille des vérifications disait « forcer les horodatages » et s'arrêtait
 * là. Trois signaux sont à armer, sur trois tables différentes, et deux d'entre
 * eux se lisent **à l'envers** : le silence de vingt et un jours se prouve par
 * l'absence de toute histoire enregistrée récemment, et le lien non ouvert par
 * un `use_count` resté à zéro sur un jeton assez vieux. Rien de tout cela ne
 * se devine devant un `tinker`, et un tick qui ne déclenche rien ne dit pas
 * pourquoi — il dit « 0 occurrence », ce qui ressemble à un moteur cassé.
 *
 * La commande arme, énumère ce qu'elle a armé, et se rejoue : le checkpoint
 * doit pouvoir être recommencé après un `engine:tick` malheureux sans repasser
 * par un `migrate:fresh` qui coûte tout le reste du décor.
 *
 * Ce qu'elle ne fait pas : lancer le tick. `engine:tick` est la vraie commande,
 * celle qui tournera toutes les heures en production, et c'est elle que la
 * vérification doit exercer.
 */
final class DemoEngine extends Command
{
    protected $signature = 'demo:moteur';

    protected $description = 'Arme les trois signaux du checkpoint du moteur sur le projet de démonstration';

    /** Le silence se compte à 21 jours : on antidate plus loin, pour la marge. */
    private const SILENCE_DAYS = 25;

    /** Le lien non ouvert se compte à 3 jours. */
    private const LINK_DAYS = 4;

    /** L'histoire partagée non écoutée se compte à 5 jours. */
    private const SHARED_DAYS = 6;

    public function handle(IssueRecordToken $issueToken): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne touche qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        $project = Project::query()
            ->whereHas('owner', fn ($query) => $query->where('email', 'demo@example.test'))
            ->first();

        if (! $project instanceof Project) {
            $this->components->error('Décor absent : sail artisan migrate:fresh --seed');

            return self::FAILURE;
        }

        $this->clearTrace($project);
        $this->acceptance($project);
        $this->secondChannel($project);
        $this->linkNotOpened($project, $issueToken);
        $this->validatedNotListened($project);
        $this->silence($project);

        $this->newLine();
        $this->components->info('Signaux armés. Au tour du moteur : sail artisan engine:tick');
        $this->line('  Trois envois attendus, et cinq lignes dans <fg=gray>engine_events</> :');
        $this->line('  <fg=green>link_not_opened</>       → au narrateur, sur son autre canal');
        $this->line('  <fg=green>validated_not_listened</> → un rappel par proche');
        $this->line('  <fg=green>narrator_silence_21d</>   → à l’Initiateur·rice, quatre liens en un tap');
        $this->line('  <fg=yellow>recorded_not_validated</> et <fg=yellow>narrator_silence_10d</> : consignées');
        $this->line('  <fg=yellow>supprimées</>. Elles parlaient au même narrateur le même jour, et une');
        $this->line('  seule règle par jour a le droit de le déranger. C’est la garde anti-harcèlement,');
        $this->line('  pas une panne — c’est même le point le plus important du checkpoint.');
        $this->newLine();
        $this->components->warn('Enchaînez tout de suite : le planificateur passe à :07 de chaque heure et consommerait les occurrences avant vous.');

        return self::SUCCESS;
    }

    /**
     * Efface ce que le moteur a déjà dit à ce projet.
     *
     * Le défaut trouvé en jouant le checkpoint pour de vrai : le planificateur
     * tourne à :07 de chaque heure, dans son propre conteneur, sur un décor
     * pas encore armé. Il avait déjà fait parler `recorded_not_validated` au
     * narrateur le matin même, si bien que `link_not_opened` sortait
     * **supprimée** — une seule règle par jour a le droit de déranger — et que
     * `validated_not_listened` avait consommé son idempotence. Le tick
     * annonçait « 1 déclenchement, 2 supprimés, 2 ignorés » là où le
     * checkpoint en attend trois, et rien à l'écran ne disait pourquoi.
     *
     * Un checkpoint qu'on ne peut pas rejouer n'est pas un checkpoint. On
     * efface donc la trace du moteur sur ce projet — ses événements et les
     * messages qu'ils ont produits — pour que le tour suivant reparte de zéro.
     * Les messages des autres blocs (invitations, bienvenue) ne sont pas
     * touchés : ils ne gênent pas, et les effacer effacerait du décor utile.
     */
    private function clearTrace(Project $project): void
    {
        $events = DB::table('engine_events')->where('project_id', $project->id)->delete();

        $messages = DB::table('outbound_messages')
            ->where('project_id', $project->id)
            ->where('template', 'like', 'engine\_%')
            ->delete();

        $this->components->twoColumnDetail(
            'Trace du moteur effacée',
            $events.' événement(s), '.$messages.' message(s)',
        );
    }

    /**
     * Le projet du décor est `active` mais n'a jamais de `accepted_at` : le
     * seeder pose le statut à la main sans passer par l'acceptation.
     *
     * Ce n'est pas un détail cosmétique. Les deux règles de silence exigent
     * `accepted_at` — un narrateur qui n'a jamais accepté relève de
     * `invitation_not_accepted`, pas d'une relance. Sans cette date, les deux
     * règles se taisent **sans rien dire**, et le checkpoint conclut à un
     * moteur cassé. C'est exactement le défaut qu'aucune assertion ne voit.
     */
    private function acceptance(Project $project): void
    {
        if ($project->accepted_at !== null) {
            $this->components->twoColumnDetail('Invitation acceptée', 'déjà datée');

            return;
        }

        DB::table('projects')->where('id', $project->id)->update([
            'accepted_at' => now()->subDays(60),
        ]);

        $project->refresh();

        $this->components->twoColumnDetail('Invitation acceptée', 'datée à J-60 (le décor l’oubliait)');
    }

    /**
     * Sans second canal, « renvoyer sur l'autre canal » renvoie sur le même, et
     * le point 2 du checkpoint ne montre pas ce qu'il annonce. Le décor donne
     * un téléphone à Marie et rien d'autre ; on lui ajoute une adresse, en
     * laissant le SMS comme canal préféré pour que l'autre soit le courriel.
     */
    private function secondChannel(Project $project): void
    {
        $narrator = $project->primaryNarrator()->first();

        if (! $narrator instanceof Narrator) {
            $this->components->warn('Ce projet n’a pas de narrateur principal : le renvoi n’aura pas de destinataire.');

            return;
        }

        if ($narrator->email !== null) {
            $this->components->twoColumnDetail('Second canal', 'déjà posé ('.$narrator->email.')');

            return;
        }

        $narrator->email = 'marie@example.test';
        $narrator->save();

        $this->components->twoColumnDetail('Second canal', 'courriel ajouté ('.$narrator->email.')');
    }

    /**
     * Un jeton d'enregistrement jamais ouvert, assez vieux pour compter.
     *
     * Le jeton est émis par l'action du produit et non forgé : c'est elle qui
     * pose le périmètre et la trace d'émission, et un jeton forgé à la main
     * aurait déclenché la règle sans rien prouver.
     */
    private function linkNotOpened(Project $project, IssueRecordToken $issueToken): void
    {
        $story = $project->stories()->where('state', Proposed::$name)->first();

        if (! $story instanceof Story) {
            $this->components->warn('Aucune histoire « proposée » : le lien non ouvert ne sera pas armé.');

            return;
        }

        $token = AccessToken::query()
            ->where('subject_type', $story->getMorphClass())
            ->where('subject_id', $story->id)
            ->where('type', TokenType::Record->value)
            ->first();

        if (! $token instanceof AccessToken) {
            $issueToken->handle($story);

            $token = AccessToken::query()
                ->where('subject_type', $story->getMorphClass())
                ->where('subject_id', $story->id)
                ->where('type', TokenType::Record->value)
                ->firstOrFail();
        }

        // Par le constructeur de requêtes : un `save()` remettrait `updated_at`
        // à maintenant, et le décor mentirait sur son propre âge.
        DB::table('access_tokens')->where('id', $token->id)->update([
            'created_at' => now()->subDays(self::LINK_DAYS),
            'use_count' => 0,
            'revoked_at' => null,
        ]);

        $this->components->twoColumnDetail('Lien non ouvert', 'envoyé il y a '.self::LINK_DAYS.' jours, jamais ouvert');
    }

    /**
     * Une histoire partagée que personne n'a écoutée trente secondes.
     *
     * On efface les écoutes abouties plutôt que de compter dessus : le décor
     * n'en sème pas aujourd'hui, mais un checkpoint rejoué après une écoute
     * réelle en trouverait, et la règle se tairait sans dire pourquoi.
     */
    private function validatedNotListened(Project $project): void
    {
        $story = $project->stories()->where('state', Shared::$name)->first();

        if (! $story instanceof Story) {
            $this->components->warn('Aucune histoire partagée : le rappel aux proches ne sera pas armé.');

            return;
        }

        DB::table('stories')->where('id', $story->id)->update([
            'shared_at' => now()->subDays(self::SHARED_DAYS),
        ]);

        DB::table('listen_events')->where('story_id', $story->id)->update(['reached_30s' => false]);

        $count = $project->familyMembers()->count();

        $this->components->twoColumnDetail(
            'Partagée non écoutée',
            'depuis '.self::SHARED_DAYS.' jours, '.$count.' proche(s) à relancer',
        );
    }

    /**
     * Le silence : aucune histoire enregistrée depuis vingt et un jours.
     *
     * Il se prouve par une absence, donc il s'arme sur **toutes** les histoires
     * du projet à la fois. En oublier une suffit à ce que la règle se taise.
     */
    private function silence(Project $project): void
    {
        $touched = DB::table('stories')
            ->where('project_id', $project->id)
            ->whereNotNull('recorded_at')
            ->update(['recorded_at' => now()->subDays(self::SILENCE_DAYS)]);

        $this->components->twoColumnDetail(
            'Silence du narrateur',
            self::SILENCE_DAYS.' jours ('.$touched.' histoire(s) antidatée(s))',
        );
    }
}
