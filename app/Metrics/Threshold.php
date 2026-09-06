<?php

declare(strict_types=1);

namespace App\Metrics;

/**
 * Le seuil du dossier pour une métrique, et l'échantillon minimal.
 *
 * Le second champ est celui qui compte, et il ne figure nulle part dans la
 * feuille de route : **un seuil ne se déclare pas atteint sur trois
 * familles**. H0 ≥ 60 % sur cinq invitations, c'est trois acceptations — un
 * résultat que le hasard produit une fois sur trois. L'afficher en vert
 * ferait prendre une décision de gate sur du bruit.
 *
 * Le tableau de bord montre donc trois états et non deux : atteint, pas
 * encore, et **échantillon trop petit pour se prononcer**. Le troisième est
 * le plus utile pendant un pilote, où il sera longtemps le seul vrai.
 */
final readonly class Threshold
{
    public function __construct(
        public string $metric,
        public float $target,
        public string $label,
        /** En dessous, on ne se prononce pas. */
        public int $minimumSample = 10,
        /** Vrai quand un chiffre **bas** est le bon (contre-métriques). */
        public bool $lowerIsBetter = false,
    ) {}

    /** @return list<self> */
    public static function all(): array
    {
        return [
            new self('h0_acceptance_14d', 0.60, '≥ 60 % (R-5)'),
            new self('h1_itt_8_stories_j70', 0.50, '≥ 50 % en ITT (R-5)'),
            new self('h1_activated_8_stories_j70', 0.65, '≥ 65 % des activés (R-5)'),
            new self('first_recording_unassisted', 0.85, '≥ 85 % (R-5)'),
            // Quatre sollicitations par mois : la promesse faite à
            // l'Initiateur·rice, et la première chose qu'une famille
            // abandonnera si elle n'est pas tenue.
            new self('initiator_requests_per_month', 4.0, '≤ 4 par mois', minimumSample: 5, lowerIsBetter: true),
        ];
    }

    public static function for(string $metric): ?self
    {
        foreach (self::all() as $seuil) {
            if ($seuil->metric === $metric) {
                return $seuil;
            }
        }

        return null;
    }

    /**
     * Atteint, manqué, ou indécidable.
     *
     * @return 'met'|'missed'|'too_small'
     */
    public function verdict(float $value, ?int $denominator): string
    {
        if ($denominator !== null && $denominator < $this->minimumSample) {
            return 'too_small';
        }

        $atteint = $this->lowerIsBetter ? $value <= $this->target : $value >= $this->target;

        return $atteint ? 'met' : 'missed';
    }
}
