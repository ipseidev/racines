<?php

declare(strict_types=1);

namespace App\Audit;

use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * La chaîne du journal d'audit tient-elle ?
 *
 * Extrait de `audit:verify` pour qu'il n'y ait **qu'une** définition de
 * « intacte ». L'exercice de restauration doit poser la même question à la
 * base restaurée, et une seconde vérification écrite en SQL s'était déjà
 * révélée dix fois plus faible : elle comparait les maillons sans recalculer
 * les empreintes, donc elle aurait laissé passer une ligne réécrite — soit
 * exactement ce que le journal existe pour rendre impossible.
 *
 * La connexion est un paramètre : le journal vérifié est tantôt celui de
 * l'application, tantôt celui d'une base d'exercice jetable.
 */
final readonly class ChainVerifier
{
    /**
     * @return list<string> les ruptures, vide si la chaîne tient
     */
    public function breaks(?string $connection = null, ?string $from = null, ?string $to = null): array
    {
        $rows = $this->rows($connection, $from, $to);
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
                if (($from === null || $from === '') && $previous !== AuditLog::GENESIS) {
                    $breaks[] = sprintf('Ligne %d : ne commence pas à la racine.', (int) $row->id);
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

    public function count(?string $connection = null, ?string $from = null, ?string $to = null): int
    {
        return count($this->rows($connection, $from, $to));
    }

    /**
     * @return list<stdClass>
     */
    private function rows(?string $connection, ?string $from, ?string $to): array
    {
        $query = DB::connection($connection)->table('audit_logs')->orderBy('id');

        if (is_string($from) && $from !== '') {
            $query->whereDate('occurred_at', '>=', $from);
        }

        if (is_string($to) && $to !== '') {
            $query->whereDate('occurred_at', '<=', $to);
        }

        return array_values($query->get()->all());
    }
}
