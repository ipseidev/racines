<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Audit\ChainVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Recalcule la chaîne du journal d'audit et signale ce qui ne colle pas.
 *
 * Le trigger `audit_logs_append_only` protège de l'erreur et du script
 * maladroit. Il ne protège pas de qui détient les droits de le désactiver —
 * et c'est précisément la menace qu'on modélise, puisqu'elle est interne. La
 * chaîne d'empreintes est le second verrou : elle ne **prévient** rien, elle
 * rend l'altération **détectable**, ce qui est le mieux qu'on puisse faire
 * contre quelqu'un qui a les clés.
 *
 * Trois ruptures possibles, et il faut les distinguer : une empreinte qui ne
 * correspond plus à son contenu (ligne modifiée), un parent introuvable
 * (ligne supprimée au milieu), et un début qui n'est pas la racine (journal
 * tronqué par le haut). La troisième serait indétectable si la chaîne
 * commençait à `null` : un journal amputé de ses cent premières lignes
 * ressemblerait à un journal neuf.
 */
final class VerifyAuditChain extends Command
{
    protected $signature = 'audit:verify {--from= : Date de début (incluse)} {--to= : Date de fin (incluse)}';

    protected $description = 'Vérifie l’intégrité de la chaîne du journal d’audit';

    public function handle(ChainVerifier $verifier): int
    {
        $from = is_string($this->option('from')) ? $this->option('from') : null;
        $to = is_string($this->option('to')) ? $this->option('to') : null;

        $checked = $verifier->count(null, $from, $to);

        if ($checked === 0) {
            $this->components->info('Journal vide : rien à vérifier.');

            return self::SUCCESS;
        }

        $breaks = $verifier->breaks(null, $from, $to);

        if ($breaks === []) {
            $this->components->info(sprintf(
                'Chaîne intacte : %d ligne(s) vérifiée(s).',
                $checked,
            ));

            return self::SUCCESS;
        }

        /*
         * `line()` et non `components->error()` : ce dernier passe par
         * Termwind, qui met en forme et **replie** le texte à la largeur du
         * terminal. Une rupture dont le message est coupé au milieu est une
         * rupture qu'on ne retrouve pas dans un journal — ni dans un test.
         */
        foreach ($breaks as $break) {
            $this->line($break);
        }

        $this->components->error(sprintf(
            '%d rupture(s) sur %d ligne(s) vérifiée(s).',
            count($breaks),
            $checked,
        ));

        // Journalisé en plus d'être affiché : la commande tourne dans le
        // planificateur, où personne ne lit la sortie. L'alerte remonte par
        // les journaux (Flare au bloc 16).
        Log::critical('audit.chain_broken', [
            'breaks' => $breaks,
            'checked' => $checked,
        ]);

        return self::FAILURE;
    }
}
