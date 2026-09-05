<?php

declare(strict_types=1);

namespace App\Services\Payments;

/**
 * Le catalogue en mémoire, pour éprouver `stripe:catalogue` sans appeler
 * Stripe. On ne teste pas une commande qui crée des produits chez un tiers en
 * créant des produits chez ce tiers.
 */
final class FakeProductCatalogue implements ProductCatalogue
{
    /**
     * @param  array<string, array{price: string, amount: int}>  $prices
     * @param  array{id: string, percent: int}|null  $coupon
     */
    public function __construct(
        private array $prices = [],
        private ?array $coupon = null,
        private readonly bool $live = false,
    ) {}

    /** @var list<array{key: string, amount: int}> */
    public array $created = [];

    /** @var list<array{key: string, percent: int}> */
    public array $createdCoupons = [];

    public function account(): string
    {
        return 'Décor (acct_test, '.($this->live ? 'live' : 'test').')';
    }

    public function isLive(): bool
    {
        return $this->live;
    }

    public function prices(): array
    {
        return $this->prices;
    }

    public function createPrice(string $key, string $name, string $description, int $amountCents): string
    {
        $id = 'price_'.$key;

        $this->created[] = ['key' => $key, 'amount' => $amountCents];
        $this->prices[$key] = ['price' => $id, 'amount' => $amountCents];

        return $id;
    }

    /** @return array{id: string, percent: int}|null */
    public function coupon(string $key): ?array
    {
        return $this->coupon;
    }

    public function createCoupon(string $key, string $name, int $percentOff): string
    {
        $this->createdCoupons[] = ['key' => $key, 'percent' => $percentOff];
        $this->coupon = ['id' => 'coupon_'.$key, 'percent' => $percentOff];

        return 'coupon_'.$key;
    }
}
