<?php

declare(strict_types=1);

namespace App\Services\Payments;

use RuntimeException;
use Stripe\StripeClient;

/**
 * Le catalogue vu chez Stripe.
 *
 * Les objets sont retrouvés par `metadata[catalogue_key]` et non par leur nom :
 * un nom se retouche dans le tableau de bord, et une commande qui s'appuierait
 * dessus recréerait un doublon au premier renommage.
 */
final class StripeProductCatalogue implements ProductCatalogue
{
    private readonly StripeClient $client;

    public function __construct(private readonly string $key)
    {
        if ($this->key === '') {
            throw new RuntimeException('Aucune clé Stripe : renseignez `STRIPE_SECRET` ou passez --ask.');
        }

        $this->client = new StripeClient($this->key);
    }

    public function account(): string
    {
        $account = $this->client->accounts->retrieve();
        $name = $account->settings->dashboard->display_name ?? 'sans nom';

        return sprintf('%s (%s, %s)', $name, $account->id, $this->isLive() ? 'LIVE' : 'test');
    }

    public function isLive(): bool
    {
        return str_contains($this->key, '_live_');
    }

    public function prices(): array
    {
        $found = [];

        // `active` seulement : un prix archivé a été remplacé à dessein, et le
        // faire réapparaître ici ferait croire le catalogue conforme.
        foreach ($this->client->prices->all(['limit' => 100, 'active' => true])->autoPagingIterator() as $price) {
            $key = $price->metadata['catalogue_key'] ?? null;

            if (! is_string($key) || $key === '') {
                continue;
            }

            $found[$key] = ['price' => (string) $price->id, 'amount' => (int) $price->unit_amount];
        }

        return $found;
    }

    public function createPrice(string $key, string $name, string $description, int $amountCents): string
    {
        $product = $this->client->products->create([
            'name' => $name,
            'description' => $description,
            'metadata' => ['catalogue_key' => $key],
        ]);

        $price = $this->client->prices->create([
            'product' => $product->id,
            'unit_amount' => $amountCents,
            'currency' => 'eur',
            'metadata' => ['catalogue_key' => $key],
        ]);

        return (string) $price->id;
    }

    /** @return array{id: string, percent: int}|null */
    public function coupon(string $key): ?array
    {
        foreach ($this->client->coupons->all(['limit' => 100])->autoPagingIterator() as $coupon) {
            if (($coupon->metadata['catalogue_key'] ?? null) !== $key || $coupon->valid !== true) {
                continue;
            }

            return ['id' => (string) $coupon->id, 'percent' => (int) $coupon->percent_off];
        }

        return null;
    }

    public function createCoupon(string $key, string $name, int $percentOff): string
    {
        // `once` et non `forever` : le produit vend des achats uniques, et les
        // deux autres durées n'ont de sens que pour un abonnement.
        $coupon = $this->client->coupons->create([
            'percent_off' => $percentOff,
            'duration' => 'once',
            'name' => $name,
            'metadata' => ['catalogue_key' => $key],
        ]);

        return (string) $coupon->id;
    }
}
