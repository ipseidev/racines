<?php

declare(strict_types=1);

namespace App\Health;

use App\Audit\ChainVerifier;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Le journal d'audit est-il toujours cohérent ?
 *
 * Une rupture de chaîne n'est jamais un incident technique : c'est soit une
 * altération, soit une restauration ratée. Dans les deux cas, la seule preuve
 * de ce qui s'est passé dans le back-office vient d'être perdue, et le
 * dossier en fait une obligation (doc 04 §12).
 *
 * Le contrôle porte sur **le jour même**. Vérifier le journal entier à chaque
 * minute coûterait de plus en plus cher à mesure qu'il grossit, jusqu'à ce
 * qu'on désactive le contrôle — ce qui est la vraie panne. La vérification
 * complète reste celle de `audit:verify`, tournée par le planificateur.
 */
final class AuditChainCheck extends Check
{
    public function run(): Result
    {
        $today = now()->toDateString();
        $breaks = app(ChainVerifier::class)->breaks(null, $today, $today);

        if ($breaks === []) {
            return Result::make()->ok()->shortSummary('intacte');
        }

        return Result::make()
            ->failed('Chaîne d’audit rompue aujourd’hui : '.implode(' ; ', array_slice($breaks, 0, 3)))
            ->shortSummary(count($breaks).' rupture(s)');
    }
}
