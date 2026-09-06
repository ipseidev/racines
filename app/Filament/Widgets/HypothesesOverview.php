<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Metrics\Threshold;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * Les hypothèses du dossier, telles que la donnée les dit.
 *
 * Ce sont les chiffres sur lesquels se décident les gates. Trois partis pris
 * gouvernent l'affichage, et chacun corrige une manière classique de se
 * mentir avec un tableau de bord.
 *
 * **Le dénominateur est visible.** Toujours, à côté du taux. « 62 % » ne veut
 * rien dire sans « sur 40 » — et le dossier fixe des seuils qu'il serait
 * absurde de déclarer atteints sur trois familles.
 *
 * **Trois états, pas deux.** Atteint, manqué, et **échantillon trop petit**.
 * Le troisième sera longtemps le seul vrai pendant un pilote ; le masquer
 * forcerait une décision sur du bruit.
 *
 * **Rien n'est rouge.** Un seuil non atteint au milieu d'un pilote n'est pas
 * une panne : c'est l'objet même de l'expérience. Le rouge appelle un geste
 * réflexe, et il n'y en a pas à faire — il y a une hypothèse à réviser.
 */
final class HypothesesOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = -2;

    /** @var array<string, array{value: float, numerator: int|null, denominator: int|null}>|null */
    private ?array $cache = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.read');
    }

    protected function getHeading(): string
    {
        return __('admin.metrics.heading');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $lignes = $this->latest();
        $stats = [];

        // La North Star en tête : c'est le seul chiffre qui dit si le produit
        // fait ce pour quoi il existe.
        $stats[] = $this->stat('living_projects', __('admin.metrics.living'));

        foreach (Threshold::all() as $seuil) {
            $stats[] = $this->stat($seuil->metric, __('admin.metrics.'.$seuil->metric));
        }

        return $stats;
    }

    private function stat(string $metric, string $label): Stat
    {
        $ligne = $this->latest()[$metric] ?? null;

        if ($ligne === null) {
            return Stat::make($label, '—')
                ->description(__('admin.metrics.not_computed'))
                ->color('gray');
        }

        $valeur = $ligne['value'];
        $seuil = Threshold::for($metric);
        $verdict = $seuil?->verdict($valeur, $ligne['denominator']);

        return Stat::make($label, $this->format($metric, $valeur))
            ->description($this->description($ligne, $seuil, $verdict))
            ->color(match ($verdict) {
                'met' => 'success',
                // Ni rouge ni orange : un seuil non atteint pendant un pilote
                // est l'objet de l'expérience, pas une alarme.
                'missed' => 'gray',
                default => 'gray',
            });
    }

    private function format(string $metric, float $valeur): string
    {
        // La charge est un nombre de sollicitations, pas un taux.
        return $metric === 'initiator_requests_per_month'
            ? number_format($valeur, 1, ',', ' ')
            : number_format($valeur * 100, 0).' %';
    }

    /**
     * @param  array{value: float, numerator: int|null, denominator: int|null}  $ligne
     */
    private function description(array $ligne, ?Threshold $seuil, ?string $verdict): string
    {
        $parts = [];

        if ($ligne['denominator'] !== null) {
            // Le dénominateur, toujours : « 62 % » ne veut rien dire sans
            // « sur 40 ».
            $parts[] = __('admin.metrics.over', [
                'numerator' => $ligne['numerator'] ?? 0,
                'denominator' => $ligne['denominator'],
            ]);
        }

        if ($seuil !== null) {
            $parts[] = $seuil->label;
        }

        if ($verdict === 'too_small') {
            $parts[] = __('admin.metrics.too_small');
        }

        return implode(' · ', $parts);
    }

    /**
     * La dernière mesure de chaque métrique, toutes cohortes confondues.
     *
     * @return array<string, array{value: float, numerator: int|null, denominator: int|null}>
     */
    private function latest(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $date = DB::table('daily_metrics')->max('date');

        if ($date === null) {
            return $this->cache = [];
        }

        $lignes = [];

        foreach (DB::table('daily_metrics')->where('date', $date)->whereNull('cohort_id')->get() as $ligne) {
            $lignes[(string) $ligne->metric] = [
                'value' => (float) $ligne->value,
                'numerator' => $ligne->numerator === null ? null : (int) $ligne->numerator,
                'denominator' => $ligne->denominator === null ? null : (int) $ligne->denominator,
            ];
        }

        return $this->cache = $lignes;
    }
}
