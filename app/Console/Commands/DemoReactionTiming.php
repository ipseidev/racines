<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Features\ReactionNotificationTiming;
use App\Models\FamilyMember;
use App\Models\Reaction;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

/**
 * Les deux gestes du point 4 du bloc 08, qui ne se font pas à la main.
 *
 * Le drapeau des réactions est un **état invisible** : un projet passé à
 * « lendemain matin » n'envoie plus rien tout de suite, et rien à l'écran ne
 * le dit. Il a déjà coûté une vérification — la consigne du point 4 était
 * jouable avant le point 3, et l'a été (écart T-131). Une commande qui le
 * bascule et l'affiche vaut mieux qu'une ligne de `tinker` de deux cents
 * caractères recopiée dans un terminal, où la moindre coquille est muette.
 *
 * L'antidatage répond à l'autre moitié : le résumé lit les réactions de la
 * **veille**, donc il n'y a rien à résumer le jour où l'on réagit. Forcer
 * l'horloge du conteneur depuis un terminal n'est pas raisonnable ; antidater
 * la réaction l'est, et c'est le même effet observable.
 *
 * L'envoi lui-même n'est pas enveloppé : `reactions:send-digests` est la vraie
 * commande, celle qui tournera à 9 h en production, et c'est elle que la
 * vérification doit exercer.
 */
final class DemoReactionTiming extends Command
{
    protected $signature = 'demo:reaction-timing
        {timing? : immediate ou next-morning ; sans argument, affiche l’état}
        {--veille : Antidater la dernière réaction d’un jour, pour que le résumé la voie}';

    protected $description = 'Bascule le drapeau des réactions du projet d’essai, et antidate une réaction';

    /** Le scénario du décor sur lequel le point 3 du bloc 08 se joue. */
    private const SCENARIO = 'listen-react';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne touche qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        // Le semis rend un `Model` : le scénario d'écoute porte un proche, et
        // c'est lui qui nomme le projet. On le resserre plutôt que de faire
        // confiance à un nom de propriété.
        $subject = E2ELinksSeeder::subjectOf(self::SCENARIO);

        if (! $subject instanceof FamilyMember) {
            $this->components->error('Décor absent : sail artisan migrate:fresh --seed');

            return self::FAILURE;
        }

        $project = $subject->project;

        if ($this->option('veille')) {
            return $this->backdate();
        }

        $timing = $this->argument('timing');

        if (! is_string($timing) || $timing === '') {
            $this->components->twoColumnDetail(
                'Drapeau des réactions',
                (string) Feature::for($project)->value(ReactionNotificationTiming::class),
            );

            return self::SUCCESS;
        }

        $allowed = [ReactionNotificationTiming::IMMEDIATE, ReactionNotificationTiming::NEXT_MORNING];

        if (! in_array($timing, $allowed, true)) {
            $this->components->error('Valeurs possibles : '.implode(', ', $allowed).'.');

            return self::FAILURE;
        }

        Feature::for($project)->activate(ReactionNotificationTiming::class, $timing);

        $this->components->info("Drapeau des réactions posé à « {$timing} ».");

        return self::SUCCESS;
    }

    private function backdate(): int
    {
        $reaction = Reaction::query()->latest('created_at')->first();

        if ($reaction === null) {
            $this->components->warn('Il n’y a aucune réaction à antidater : réagissez d’abord depuis un lien d’écoute.');

            return self::SUCCESS;
        }

        // Écrit par le constructeur de requêtes et non par le modèle : un
        // `save()` remettrait `updated_at` à maintenant, ce qui est
        // exactement ce qu'on veut éviter.
        DB::table('reactions')
            ->where('id', $reaction->id)
            ->update(['updated_at' => now()->subDay()->setTime(20, 0)]);

        $this->components->info('Réaction antidatée à hier 20 h. Lancez maintenant : sail artisan reactions:send-digests');

        return self::SUCCESS;
    }
}
