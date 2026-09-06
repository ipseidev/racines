<?php

declare(strict_types=1);

namespace App\Metrics;

use Illuminate\Database\Query\Builder;

/**
 * Le catalogue des métriques du pilote.
 *
 * Une liste **explicite** et non une découverte automatique du dossier : une
 * métrique qui apparaît parce qu'un fichier a été déposé apparaît aussi quand
 * on ne le voulait pas, et disparaît quand on renomme. Ici, ajouter une
 * mesure est un geste conscient, visible en revue.
 *
 * L'ordre est celui du tableau de bord, qui est aussi celui du raisonnement :
 * la North Star d'abord, puis les hypothèses dans l'ordre du parcours, puis
 * les contre-métriques — qui viennent en dernier parce qu'elles se lisent
 * **contre** ce qui précède.
 */
final class Registry
{
    /**
     * @return list<Metric>
     */
    public static function all(): array
    {
        return [
            new LivingProjects,
            new H0Acceptance14d,
            new FirstRecordingUnassisted,
            new H1Itt8StoriesJ70,
            new H1Activated,
            new InitiatorLoad,

            ...self::counters(),
        ];
    }

    /**
     * Les contre-métriques (PRD §7).
     *
     * Elles empêchent un chiffre flatteur. Un taux de validation excellent
     * accompagné de retraits massifs dirait qu'on a poussé des gens à
     * partager ce qu'ils ne voulaient pas — et sans ces comptages, le
     * tableau de bord ne le montrerait jamais.
     *
     * @return list<Metric>
     */
    private static function counters(): array
    {
        return [
            new CounterMetrics(
                'stories_hidden_30d',
                'Histoires masquées sur 30 jours. Un retrait n’est pas un échec technique : '
                .'c’est le signe qu’un partage a eu lieu trop tôt, ou qu’il a été mal compris.',
                'stories',
                'hidden_at',
            ),
            new CounterMetrics(
                'stories_deleted_30d',
                'Histoires supprimées sur 30 jours. Définitif, là où un masquage se défait.',
                'stories',
                'deleted_at',
            ),
            /*
             * Depuis le **journal d'audit** et non depuis `orders` : la table
             * ne porte qu'un montant remboursé, sans date. Le journal, lui,
             * est inaltérable et horodaté — et pour un chiffre qui décide de
             * H3, une trace qu'on ne peut pas réécrire vaut mieux qu'une
             * colonne qu'un `updated_at` déplacerait au prochain changement.
             */
            new CounterMetrics(
                'refunds_30d',
                'Remboursements sur 30 jours, comptés dans le journal d’audit. Seuil H3 : ≤ 8 %.',
                'audit_logs',
                'occurred_at',
                filter: function (Builder $query): void {
                    $query->where('audit_logs.action', 'refunded Order');
                },
            ),
            new CounterMetrics(
                'invitations_refused_30d',
                'Refus **explicites** sur 30 jours, à ne pas confondre avec les silences : '
                .'le premier dit que le cadeau est mal reçu, le second qu’il n’est pas arrivé.',
                'invitations',
                'refused_at',
            ),
            new CounterMetrics(
                'print_defects_30d',
                'Défauts d’impression signalés sur 30 jours. La réimpression est gratuite : '
                .'le chiffre mesure l’imprimeur, pas la tolérance des familles.',
                'support_tickets',
                'opened_at',
                filter: function (Builder $query): void {
                    $query->where('support_tickets.kind', 'print_defect');
                },
            ),
            new CounterMetrics(
                'erasures_requested_30d',
                'Demandes d’effacement sur 30 jours. La contre-métrique ultime : quelqu’un '
                .'a voulu que tout disparaisse.',
                'support_tickets',
                'opened_at',
                filter: function (Builder $query): void {
                    $query->where('support_tickets.kind', 'erasure_requested');
                },
            ),
        ];
    }
}
