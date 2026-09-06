<?php

declare(strict_types=1);

namespace App\Health;

use App\Models\Recording;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Les enregistrements confirmés sont-ils tous répliqués ?
 *
 * Le dossier promet **zéro perte après « votre histoire est enregistrée »**
 * (doc 04 §11), et cette promesse repose entièrement sur la copie vers un
 * second bucket. Un job de réplication qui échoue en silence laisse la
 * promesse debout dans l'interface et fausse dans les faits — pendant des
 * jours, jusqu'à ce qu'un incident sur le premier bucket la démente.
 *
 * Une heure de retard vaut alerte. C'est large pour une copie d'objet à
 * objet, et c'est voulu : le seuil doit sonner pour une file bloquée, pas
 * pour un pic de trafic un dimanche soir.
 */
final class ReplicationLagCheck extends Check
{
    private const HEURES = 1;

    public function run(): Result
    {
        $enRetard = Recording::query()
            ->whereNotNull('confirmed_at')
            ->whereNull('replicated_at')
            ->where('confirmed_at', '<', now()->subHours(self::HEURES))
            ->count();

        if ($enRetard === 0) {
            return Result::make()->ok()->shortSummary('à jour');
        }

        // P1 au sens du runbook d'incident : la promesse de non-perte n'est
        // plus tenue, même si rien n'est encore perdu.
        return Result::make()
            ->failed(sprintf(
                '%d enregistrement(s) confirmé(s) depuis plus d’une heure et non répliqué(s) : la promesse de non-perte n’est plus tenue.',
                $enRetard,
            ))
            ->shortSummary($enRetard.' en retard');
    }
}
