<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Les réglages du pilote, éditables sans déploiement.
 *
 * Le `mode` gouverne ce que la page d'accueil annonce et ce que le tunnel
 * vend. Il vaut `pilot` par défaut, et ce défaut est une décision : les
 * préventes ne s'activent qu'à la main, et ne créent jamais de projet actif
 * (règle §9 du bloc 10). Se tromper dans ce sens-là coûte une vente ; se
 * tromper dans l'autre coûte une promesse qu'on ne peut pas tenir.
 *
 * Les prix sont en **centimes** et en entiers : un prix en flottant finit
 * par produire 48,99 € au lieu de 49 €, et on ne s'en aperçoit qu'à la
 * première facture.
 */
final class PilotSettings extends Settings
{
    /** `pilot`, `prevente` ou `core`. */
    public string $mode;

    /**
     * Plafond de l'option téléphone : une promesse humaine faite à plus de
     * familles qu'on ne peut en rappeler vaut moins qu'une promesse jamais
     * faite (R-2).
     */
    public int $phone_option_cap;

    public int $pilot_price_cents;

    /** @var array<int, int> Les deux prix de prévente comparés (R-3). */
    public array $prevente_prices_cents;

    public int $extra_copy_price_cents;

    public int $phone_option_price_cents;

    /** Heure d'envoi des cadeaux, fuseau du projet (décision T-28). */
    public int $gift_send_hour;

    public ?string $cohort_id;

    /**
     * Tant qu'elle est nulle, les pages légales portent leur bandeau
     * « à valider par conseil », et `golive:check` refuse de passer.
     */
    public ?string $legal_validated_at;

    public static function group(): string
    {
        return 'pilot';
    }

    public function isPilot(): bool
    {
        return $this->mode === 'pilot';
    }

    public function isPrevente(): bool
    {
        return $this->mode === 'prevente';
    }

    public function legalValidated(): bool
    {
        return $this->legal_validated_at !== null;
    }
}
