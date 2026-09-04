<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce qui peut être acheté.
 *
 * Cinq articles. Chacun correspond à un prix
 * créé dans Stripe (`STRIPE_PRICE_*`) : la correspondance vit dans
 * `config/services.php`, pas en dur dans le tunnel.
 */
enum Sku: string
{
    use HasTranslatedLabel;

    case Pilot = 'pilot';
    case CorePrevente = 'core_prevente';
    case ExtraCopy = 'extra_copy';
    case PhoneOption = 'phone_option';
    case Ebook = 'ebook';

    /**
     * L'identifiant de prix Stripe correspondant.
     */
    /**
     * @param  int|null  $variantCents  Pour la prévente, le prix vu par ce
     *                                  visiteur : les deux variantes ont
     *                                  chacune leur identifiant Stripe.
     */
    public function stripePriceId(?int $variantCents = null): ?string
    {
        $key = match ($this) {
            self::Pilot => 'pilot',
            self::CorePrevente => 'prevente_'.intdiv($variantCents ?? 9_900, 100),
            self::ExtraCopy => 'extra_copy',
            self::PhoneOption => 'phone_option',
            self::Ebook => 'ebook',
        };

        $value = config("services.stripe.prices.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
