<?php

declare(strict_types=1);

namespace App\Services\Payments;

/**
 * Le catalogue vendable, vu du côté du prestataire de paiement.
 *
 * Ce port existe pour une raison précise : les montants ont **une seule
 * source**, `PilotSettings`, et un prix créé chez Stripe qui en diverge ne se
 * voit qu'au moment de payer — c'est-à-dire devant un client. La commande
 * `stripe:catalogue` compare les deux et refuse de deviner.
 *
 * Il prend sa clé en paramètre plutôt que dans la configuration : le catalogue
 * live se crée avec une clé live, qui n'a rien à faire dans le `.env` d'une
 * machine de développement où un clic sur « Payer » prendrait une vraie carte.
 */
interface ProductCatalogue
{
    /** Ce que la clé ouvre, en clair : « {marque} (acct_…, live) ». */
    public function account(): string;

    /** Vrai si la clé agit sur de l'argent réel. */
    public function isLive(): bool;

    /**
     * Les prix existants, par clé d'article.
     *
     * @return array<string, array{price: string, amount: int}>
     */
    public function prices(): array;

    /** Crée le produit et son prix unique, et rend l'identifiant du prix. */
    public function createPrice(string $key, string $name, string $description, int $amountCents): string;

    /**
     * Le coupon portant cette clé, ou `null` s'il n'existe pas.
     *
     * @return array{id: string, percent: int}|null
     */
    public function coupon(string $key): ?array;

    /** Crée le coupon et rend son identifiant. */
    public function createCoupon(string $key, string $name, int $percentOff): string;
}
