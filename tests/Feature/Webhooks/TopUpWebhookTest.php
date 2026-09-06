<?php

declare(strict_types=1);

use App\Enums\PhoneOptionEntry;
use App\Enums\Sku;
use App\Models\Order;
use App\Models\PhoneOption;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * Ce que le paiement d'un complément produit.
 *
 * L'argent est déjà pris quand ce code s'exécute : un complément payé qui
 * n'arrive pas est un litige, pas un bogue. D'où l'idempotence — Stripe rejoue
 * ses webhooks — et le refus d'exécuter une session impayée, comme pour la
 * commande initiale (T-167, T-184).
 */
const TOP_UP_SECRET = 'whsec_test_secret';

function signedTopUp(array $payload): array
{
    $body = (string) json_encode($payload);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$body}", TOP_UP_SECRET);

    return [$body, ['Stripe-Signature' => "t={$timestamp},v1={$signature}"]];
}

function postTopUp(array $payload): TestResponse
{
    [$body, $headers] = signedTopUp($payload);

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $headers['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

function ordrePayePourComplement(): Order
{
    $buyer = User::factory()->create();
    $project = Project::factory()->create(['owner_user_id' => $buyer->id]);

    return Order::factory()->paid()->create([
        'user_id' => $buyer->id,
        'project_id' => $project->id,
    ]);
}

function topUpSession(Order $order, Sku $sku, array $overrides = []): array
{
    return array_merge([
        'id' => 'cs_test_'.uniqid(),
        'payment_intent' => 'pi_test_'.uniqid(),
        'amount_total' => 2_500,
        'payment_status' => 'paid',
        'metadata' => ['order_id' => $order->id, 'sku' => $sku->value],
    ], $overrides);
}

beforeEach(function (): void {
    config()->set('cashier.webhook.secret', TOP_UP_SECRET);
});

it('ajoute l’option téléphone et la compte comme un complément', function (): void {
    $order = ordrePayePourComplement();

    postTopUp(['type' => 'checkout.session.completed', 'data' => ['object' => topUpSession($order, Sku::PhoneOption)]])
        ->assertSuccessful();

    $option = PhoneOption::query()->sole();

    // `complement` et non `checkout` : mélanger les deux gonflerait le taux
    // d'attache au tunnel, qui décide de l'automatisation en Phase 2 (D-9).
    expect($option->entry)->toBe(PhoneOptionEntry::Complement)
        ->and($order->refresh()->items()->where('sku', Sku::PhoneOption->value)->count())->toBe(1);
});

it('ajoute le livre numérique sans créer d’option téléphone', function (): void {
    $order = ordrePayePourComplement();

    postTopUp(['type' => 'checkout.session.completed', 'data' => ['object' => topUpSession($order, Sku::Ebook)]])
        ->assertSuccessful();

    expect($order->refresh()->items()->where('sku', Sku::Ebook->value)->count())->toBe(1)
        ->and(PhoneOption::query()->count())->toBe(0);
});

it('augmente le total de la commande du montant payé', function (): void {
    $order = ordrePayePourComplement();
    $avant = $order->total_cents;

    postTopUp(['type' => 'checkout.session.completed', 'data' => ['object' => topUpSession($order, Sku::Ebook)]])
        ->assertSuccessful();

    expect($order->refresh()->total_cents)->toBe($avant + 2_500);
});

it('reste idempotent quand Stripe rejoue l’événement', function (): void {
    $order = ordrePayePourComplement();
    $session = topUpSession($order, Sku::PhoneOption);

    postTopUp(['type' => 'checkout.session.completed', 'data' => ['object' => $session]])->assertSuccessful();
    postTopUp(['type' => 'checkout.session.completed', 'data' => ['object' => $session]])->assertSuccessful();

    expect(PhoneOption::query()->count())->toBe(1)
        ->and($order->refresh()->items()->where('sku', Sku::PhoneOption->value)->count())->toBe(1);
});

it('n’ajoute rien tant que la session n’est pas payée', function (): void {
    $order = ordrePayePourComplement();

    postTopUp(['type' => 'checkout.session.completed', 'data' => ['object' => topUpSession($order, Sku::PhoneOption, ['payment_status' => 'unpaid'])]])
        ->assertSuccessful();

    expect(PhoneOption::query()->count())->toBe(0);
});

it('ne bronche pas sur une commande introuvable', function (): void {
    $order = ordrePayePourComplement();
    $session = topUpSession($order, Sku::Ebook, [
        'metadata' => ['order_id' => '01a00000-0000-7000-8000-000000000000', 'sku' => Sku::Ebook->value],
    ]);

    postTopUp(['type' => 'checkout.session.completed', 'data' => ['object' => $session]])->assertSuccessful();

    expect($order->refresh()->items()->count())->toBe(0);
});
