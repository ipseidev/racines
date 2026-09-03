<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Audit\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

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

    public function handle(): int
    {
        $rows = $this->rows();

        if ($rows === []) {
            $this->components->info('Journal vide : rien à vérifier.');

            return self::SUCCESS;
        }

        $breaks = $this->breaks($rows);

        if ($breaks === []) {
            $this->components->info(sprintf(
                'Chaîne intacte : %d ligne(s) vérifiée(s).',
                count($rows),
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
            count($rows),
        ));

        // Journalisé en plus d'être affiché : la commande tourne dans le
        // planificateur, où personne ne lit la sortie. L'alerte remonte par
        // les journaux (Flare au bloc 16).
        Log::critical('audit.chain_broken', [
            'breaks' => $breaks,
            'checked' => count($rows),
        ]);

        return self::FAILURE;
    }

    /**
     * @return list<stdClass>
     */
    private function rows(): array
    {
        $query = DB::table('audit_logs')->orderBy('id');

        $from = $this->option('from');
        $to = $this->option('to');

        if (is_string($from) && $from !== '') {
            $query->whereDate('occurred_at', '>=', $from);
        }

        if (is_string($to) && $to !== '') {
            $query->whereDate('occurred_at', '<=', $to);
        }

        return array_values($query->get()->all());
    }

    /**
     * @param  list<stdClass>  $rows
     * @return list<string>
     */
    private function breaks(array $rows): array
    {
        $breaks = [];
        $expected = null;

        foreach ($rows as $index => $row) {
            $previous = trim((string) $row->previous_hash);

            /*
             * Le premier maillon examiné doit s'accrocher à la racine — mais
             * seulement si l'on a demandé le journal entier. Une période
             * bornée commence légitimement au milieu de la chaîne, et exiger
             * la racine y produirait une fausse alerte chaque jour.
             */
            if ($index === 0) {
                $whole = ! is_string($this->option('from')) || $this->option('from') === '';

                if ($whole && $previous !== AuditLog::GENESIS) {
                    $breaks[] = sprintf(
                        'Ligne %d : ne commence pas à la racine.',
                        (int) $row->id,
                    );
                }
            } elseif ($previous !== $expected) {
                $breaks[] = sprintf('Ligne %d : chaînage rompu.', (int) $row->id);
            }

            $recomputed = AuditLog::hash([
                'previous_hash' => $previous,
                'occurred_at' => $row->occurred_at,
                'action' => $row->action,
                'subject_type' => $row->subject_type,
                'subject_id' => $row->subject_id,
                'payload' => $row->payload,
            ]);

            if ($recomputed !== trim((string) $row->hash)) {
                $breaks[] = sprintf('Ligne %d : empreinte incohérente.', (int) $row->id);
            }

            $expected = trim((string) $row->hash);
        }

        return $breaks;
    }
}
