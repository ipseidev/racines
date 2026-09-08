<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Ads\MetaConversions;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * L'achat envoyé à Meta, en file (T-226).
 *
 * En file, et pas dans le webhook, pour une raison qui a déjà coûté : un
 * webhook qui répond 500 est un webhook que Stripe **désactive** (T-169). Une
 * requête sortante vers un tiers dans le chemin du webhook, c'est un 500 en
 * attente — un délai réseau, une panne chez Meta, et la commande suivante
 * n'est plus encaissée.
 *
 * Trois essais, espacés : l'API de conversions accepte un événement jusqu'à
 * sept jours après les faits, il n'y a donc aucune urgence à réussir du
 * premier coup.
 */
final class SendMetaPurchase implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 300];

    /**
     * @param  array{fbp?: string, fbc?: string, ua?: string, url?: string}  $click
     * @param  array{id?: int, name?: string}  $buyer
     */
    public function __construct(
        private readonly string $orderId,
        private readonly int $totalCents,
        private readonly string $currency,
        private readonly string $email,
        private readonly array $click = [],
        private readonly array $buyer = [],
    ) {}

    public function handle(MetaConversions $meta): void
    {
        $meta->purchase(
            orderId: $this->orderId,
            totalCents: $this->totalCents,
            currency: $this->currency,
            email: $this->email,
            click: $this->click,
            buyer: $this->buyer,
        );
    }
}
