<?php

declare(strict_types=1);

namespace App\Metrics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * H0 : le parent accepte le cadeau, en quatorze jours.
 *
 * Seuil du dossier : **≥ 60 %** (R-5).
 *
 * Le dénominateur est le point délicat, et il est écrit noir sur blanc dans
 * R-5 : **les invitations délivrées**, pas les achats. Une invitation qui
 * n'arrive jamais — mauvais numéro, SMS bloqué par l'opérateur, courriel en
 * indésirable — n'a pas été refusée : elle n'a pas été posée. Les confondre
 * ferait passer un problème d'acheminement pour un refus du parent, et l'on
 * corrigerait la mauvaise chose — le discours du message, quand c'est le
 * canal qui est en cause.
 *
 * Quatorze jours, comptés depuis la **délivrance** et non depuis l'achat :
 * l'acheteur peut programmer le cadeau pour un anniversaire, et compter le
 * délai depuis son paiement mesurerait sa patience, pas la décision du parent.
 */
final readonly class H0Acceptance14d implements Metric
{
    private const JOURS = 14;

    /** Le gabarit du message d'invitation, tel que la notification le nomme. */
    private const TEMPLATE = 'gift_invitation';

    public function name(): string
    {
        return 'h0_acceptance_14d';
    }

    public function definition(): string
    {
        return 'Part des invitations **délivrées** acceptées en ≤ 14 jours. Seuil R-5 : ≥ 60 %. '
            .'Dénominateur : invitations délivrées — une invitation non arrivée n’a pas été refusée.';
    }

    public function compute(CarbonImmutable $date, ?string $cohort): MetricValue
    {
        $fin = $date->endOfDay();

        // Seules les invitations assez anciennes pour avoir eu leurs quatorze
        // jours : compter celles d'hier ferait baisser le taux chaque fois
        // qu'on vend, ce qui n'a aucun sens.
        $limite = $fin->subDays(self::JOURS);

        /*
         * La **délivrance** vit dans `outbound_messages`, pas dans
         * `invitations` — qui ne connaît que l'envoi. Le prestataire nous dit
         * si le SMS est arrivé ; l'invitation, elle, ne sait que qu'on a
         * essayé. Prendre `sent_at` pour dénominateur substituerait
         * silencieusement « envoyée » à « délivrée », soit exactement la
         * confusion contre laquelle R-5 met en garde (T-205).
         */
        $delivrees = DB::table('invitations')
            ->join('projects', 'projects.id', '=', 'invitations.project_id')
            ->whereExists(function ($query) use ($limite): void {
                $query->select(DB::raw(1))
                    ->from('outbound_messages')
                    ->whereColumn('outbound_messages.project_id', 'invitations.project_id')
                    ->where('outbound_messages.template', self::TEMPLATE)
                    ->whereNotNull('outbound_messages.delivered_at')
                    ->where('outbound_messages.delivered_at', '<=', $limite);
            })
            ->when($cohort !== null, fn ($q) => $q->where('projects.cohort_id', $cohort));

        $acceptees = (clone $delivrees)
            ->whereNotNull('invitations.accepted_at')
            ->whereRaw("invitations.accepted_at <= invitations.sent_at + interval '14 days'");

        return MetricValue::rate($acceptees->count(), $delivrees->count());
    }
}
