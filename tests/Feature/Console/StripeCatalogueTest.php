<?php

declare(strict_types=1);

use App\Services\Payments\FakeProductCatalogue;
use App\Services\Payments\ProductCatalogue;
use App\Settings\PilotSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le catalogue vendable, créé et vérifié depuis les réglages.
 *
 * Le compte de test a été rempli à la main le 2026-09-05, et les montants
 * confrontés aux réglages **avant** création — parce qu'un prix Stripe qui
 * diverge de celui qu'affiche la page ne se voit qu'au moment de payer. Cette
 * commande fait de cette précaution une propriété du produit plutôt qu'une
 * vigilance : elle lit `PilotSettings`, compare, et ne devine jamais.
 *
 * Elle sert deux fois : en test, pour vérifier que le catalogue n'a pas
 * dérivé ; en live, pour le créer au moment du déploiement, avec une clé qui
 * n'a rien à faire dans le `.env` d'une machine de développement.
 */
function fakeCatalogue(array $prices = [], ?array $coupon = null, bool $live = false): FakeProductCatalogue
{
    $catalogue = new FakeProductCatalogue($prices, $coupon, $live);
    app()->instance(ProductCatalogue::class, $catalogue);

    return $catalogue;
}

it('ne crée rien sans qu’on le demande', function (): void {
    $catalogue = fakeCatalogue();

    $this->artisan('stripe:catalogue')
        ->expectsOutputToContain('Aucune écriture')
        ->assertSuccessful();

    expect($catalogue->created)->toBeEmpty()
        ->and($catalogue->createdCoupons)->toBeEmpty();
});

it('crée les six prix et le coupon aux montants des réglages', function (): void {
    $catalogue = fakeCatalogue();
    $settings = app(PilotSettings::class);

    $this->artisan('stripe:catalogue', ['--write' => true])->assertSuccessful();

    $montants = collect($catalogue->created)->pluck('amount', 'key')->all();

    expect($montants)->toEqual([
        'pilot' => $settings->pilot_price_cents,
        'prevente_99' => $settings->prevente_prices_cents[0],
        'prevente_129' => $settings->prevente_prices_cents[1],
        'extra_copy' => $settings->extra_copy_price_cents,
        'ebook' => $settings->ebook_price_cents,
        'phone_option' => $settings->phone_option_price_cents,
    ]);

    expect($catalogue->createdCoupons)->toHaveCount(1)
        ->and($catalogue->createdCoupons[0]['percent'])->toBe($settings->welcome_offer_discount_percent);
});

it('ne recrée pas ce qui existe déjà au bon montant', function (): void {
    $settings = app(PilotSettings::class);

    $catalogue = fakeCatalogue(
        ['pilot' => ['price' => 'price_existant', 'amount' => $settings->pilot_price_cents]],
        ['id' => 'coupon_existant', 'percent' => $settings->welcome_offer_discount_percent],
    );

    $this->artisan('stripe:catalogue', ['--write' => true])->assertSuccessful();

    expect(collect($catalogue->created)->pluck('key'))->not->toContain('pilot')
        ->and($catalogue->createdCoupons)->toBeEmpty();
});

/*
 * Le cas qui compte le plus. Un prix Stripe **ne se modifie pas** : il se
 * remplace. Une divergence ne peut donc pas se corriger toute seule, et la
 * corriger en créant un second prix laisserait deux prix vivants pour le même
 * article — celui que la page affiche et celui que la caisse encaisse.
 */
it('signale une divergence et refuse de la corriger seule', function (): void {
    $settings = app(PilotSettings::class);

    $catalogue = fakeCatalogue([
        'pilot' => ['price' => 'price_perime', 'amount' => $settings->pilot_price_cents - 1_000],
    ]);

    $this->artisan('stripe:catalogue', ['--write' => true])
        ->expectsOutputToContain('diverge')
        ->assertFailed();

    expect(collect($catalogue->created)->pluck('key'))->not->toContain('pilot');
});

it('demande confirmation avant d’écrire sur un compte live', function (): void {
    $catalogue = fakeCatalogue(live: true);

    $this->artisan('stripe:catalogue', ['--write' => true])
        ->expectsConfirmation('Écrire pour de vrai sur ce compte ?', 'no')
        ->assertFailed();

    expect($catalogue->created)->toBeEmpty();
});

it('écrit sur un compte live quand on confirme', function (): void {
    $catalogue = fakeCatalogue(live: true);

    $this->artisan('stripe:catalogue', ['--write' => true])
        ->expectsConfirmation('Écrire pour de vrai sur ce compte ?', 'yes')
        ->assertSuccessful();

    expect($catalogue->created)->toHaveCount(6);
});

it('imprime les lignes de configuration à recopier', function (): void {
    fakeCatalogue();

    $this->artisan('stripe:catalogue', ['--write' => true])
        ->expectsOutputToContain('STRIPE_PRICE_PILOT=price_pilot')
        ->expectsOutputToContain('STRIPE_COUPON_WELCOME=coupon_welcome')
        ->assertSuccessful();
});
