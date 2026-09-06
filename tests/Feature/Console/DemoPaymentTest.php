<?php

declare(strict_types=1);

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le paiement de test du point 5 du bloc 11.
 *
 * Ce qui est vérifié ici, ce sont les **garde-fous**, pas l'appel : la
 * création du paiement chez Stripe est justement la partie qu'un double
 * rendrait muette, et le checkpoint l'exerce pour de vrai. Le motif a déjà
 * coûté trois défauts cette semaine (T-154, T-178, T-181) : un test qui
 * simule la partie difficile prouve qu'on appelle quelque chose, jamais que
 * quelque chose se produit.
 *
 * Le garde-fou qui compte est celui de la clé. Créer puis rembourser un
 * paiement réel pour équiper un décor serait un mouvement d'argent véritable,
 * et il suffit d'une clé live oubliée dans un `.env` pour que cela arrive.
 */
beforeEach(function (): void {
    config()->set('cashier.secret', 'sk_test_decor');
});

it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:paiement')->assertFailed();
});

it('refuse une clé live', function (): void {
    config()->set('cashier.secret', 'sk_live_pas_ici');
    Order::factory()->paid()->create();

    $this->artisan('demo:paiement')
        ->expectsOutputToContain('n’est pas une clé de test')
        ->assertFailed();
});

it('refuse quand aucune commande n’est payée', function (): void {
    Order::factory()->create();

    $this->artisan('demo:paiement')
        ->expectsOutputToContain('Aucune commande payée')
        ->assertFailed();
});
