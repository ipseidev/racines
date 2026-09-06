<?php

declare(strict_types=1);

namespace App\Metrics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * La réussite du **premier** enregistrement, sans aide.
 *
 * Seuil du dossier : **≥ 85 %** (R-5).
 *
 * C'est la mesure la plus proche du terrain de tout le bloc : elle dit si une
 * personne de quatre-vingts ans, seule devant son téléphone, arrive à parler
 * dans le produit. Tout le reste en dépend — sans première histoire, il n'y a
 * ni deuxième, ni écoute, ni livre.
 *
 * Elle se calcule sur les **événements du navigateur** et non sur la base :
 * un narrateur dont le micro est refusé ne laisse aucune histoire derrière
 * lui, donc aucune ligne. Compter à partir des histoires enregistrées
 * mesurerait la réussite des gens qui ont réussi — un chiffre toujours à
 * 100 %.
 *
 * Le dénominateur est donc « a atteint l'écran du micro », et le numérateur
 * « a confirmé un enregistrement ».
 */
final readonly class FirstRecordingUnassisted implements Metric
{
    public function name(): string
    {
        return 'first_recording_unassisted';
    }

    public function definition(): string
    {
        return 'Part des histoires ayant atteint la demande de micro qui aboutissent à un '
            .'enregistrement confirmé. Seuil R-5 : ≥ 85 %. Calculée sur les événements du '
            .'navigateur : un micro refusé ne laisse aucune ligne en base.';
    }

    public function compute(CarbonImmutable $date, ?string $cohort): MetricValue
    {
        $fin = $date->endOfDay();

        $tentatives = DB::table('client_events')
            ->join('stories', 'stories.id', '=', 'client_events.story_id')
            ->join('projects', 'projects.id', '=', 'stories.project_id')
            ->whereIn('client_events.event', ['mic_granted', 'mic_denied'])
            ->where('client_events.created_at', '<=', $fin)
            ->when($cohort !== null, fn ($q) => $q->where('projects.cohort_id', $cohort))
            ->distinct('stories.id');

        $reussies = (clone $tentatives)
            ->whereExists(function ($query) use ($fin): void {
                $query->select(DB::raw(1))
                    ->from('recordings')
                    ->whereColumn('recordings.story_id', 'stories.id')
                    ->whereNotNull('recordings.confirmed_at')
                    ->where('recordings.confirmed_at', '<=', $fin);
            });

        return MetricValue::rate(
            $reussies->count('stories.id'),
            $tentatives->count('stories.id'),
        );
    }
}
