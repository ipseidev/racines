<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Features\PhoneOptionOffer;
use App\Models\CheckoutDraft;
use App\Models\ClientEvent;
use App\Models\PhoneOption;
use App\Models\User;
use App\Services\Payments\CheckoutSessions;
use App\Services\Payments\FakeCheckoutSessions;
use App\Settings\PilotSettings;
use Inertia\Testing\AssertableInertia;
use Laravel\Pennant\Feature;

/**
 * Le tunnel d'achat, étape par étape.
 *
 * Trois invariants s'y éprouvent, et ils comptent plus que le reste :
 * le plafond de l'option téléphone tient **côté serveur** ; la case marketing
 * est décochée, séparée, et jamais requise pour payer ; le brouillon survit à
 * un aller-retour, sinon quelqu'un qui corrige un champ perd tout le reste.
 */
function completeDraft(array $overrides = []): CheckoutDraft
{
    $draft = new CheckoutDraft([
        'step' => 6,
        'payload' => array_merge([
            'for' => 'relative',
            'narrator_first_name' => 'Jeanne',
            'narrator_email' => 'jeanne@exemple.test',
            'preferred_channel' => Channel::Email->value,
            'address_form' => 'vous',
            'narrator_tech_comfort' => 'daily',
            'gift_send_at' => now()->addDay()->toDateString(),
            'gift_send_time' => '09:00',
            'gift_message' => 'J’aimerais garder tes histoires.',
            'gift_variant' => 'ecard',
            'extra_copies' => 0,
            'accepts_terms' => true,
        ], $overrides),
        'price_variant' => 9_900,
        'expires_at' => now()->addDays(7),
    ]);

    $draft->save();

    return $draft;
}

function fakeCheckout(): FakeCheckoutSessions
{
    $sessions = new FakeCheckoutSessions;
    app()->instance(CheckoutSessions::class, $sessions);

    return $sessions;
}

it('ouvre le tunnel sans compte', function (): void {
    // Le compte se crée à la quatrième étape : exiger une connexion avant
    // reviendrait à demander un mot de passe à quelqu'un qui ne sait pas
    // encore ce qu'il achète.
    $this->get('/acheter')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Checkout')
            ->where('step', 1)
            ->where('isAuthenticated', false),
        );
});

it('refuse une étape 1 sans réponse', function (): void {
    $this->post('/acheter/etape/1', [])->assertSessionHasErrors('for');
});

it('accepte de raconter sa propre histoire', function (): void {
    // Un vrai produit, pas un pilote (T-136) : raconter soi-même est un
    // chemin entier, pas un intérêt qu'on note pour plus tard.
    $this->post('/acheter/etape/1', ['for' => 'self'])->assertRedirect();

    expect(CheckoutDraft::query()->firstOrFail()->value('for'))->toBe('self')
        ->and(ClientEvent::query()->count())->toBe(0);
});

it('exige au moins une coordonnée pour le narrateur', function (): void {
    $this->post('/acheter/etape/2', [
        'narrator_first_name' => 'Jeanne',
        'preferred_channel' => Channel::Sms->value,
        'address_form' => 'vous',
    ])->assertSessionHasErrors(['narrator_email', 'narrator_phone']);
});

it('met un numéro tapé à la française au format international', function (): void {
    // « 06 12 34 56 78 » est ce que tout le monde tape (T-136). Le format
    // international est notre contrainte : on la porte, pas l'acheteur.
    $this->post('/acheter/etape/2', [
        'narrator_first_name' => 'Jeanne',
        'narrator_phone' => '06 12 34 56 78',
        'preferred_channel' => Channel::Sms->value,
        'address_form' => 'vous',
        'narrator_tech_comfort' => 'daily',
    ])->assertSessionHasNoErrors();

    expect(CheckoutDraft::query()->firstOrFail()->value('narrator_phone'))->toBe('+33612345678');
});

it('refuse un numéro qui ne ressemble à rien', function (): void {
    $this->post('/acheter/etape/2', [
        'narrator_first_name' => 'Jeanne',
        'narrator_phone' => '12',
        'preferred_channel' => Channel::Sms->value,
        'address_form' => 'vous',
        'narrator_tech_comfort' => 'daily',
    ])->assertSessionHasErrors('narrator_phone');
});

it('refuse une date d’envoi au-delà de quatre-vingt-dix jours', function (): void {
    $this->post('/acheter/etape/3', [
        'gift_send_at' => now()->addDays(120)->toDateString(),
        'gift_send_time' => '09:00',
        'gift_message' => 'Bonjour',
    ])->assertSessionHasErrors('gift_send_at');
});

it('exige l’acceptation des conditions et jamais celle du marketing', function (): void {
    // Deux assertions dans un même test, et c'est le point : les deux cases
    // sont **liées** par le fait qu'elles ne le sont pas.
    $this->post('/acheter/etape/5', ['extra_copies' => 0])
        ->assertSessionHasErrors('accepts_terms');

    $this->post('/acheter/etape/5', ['extra_copies' => 0, 'accepts_terms' => true])
        ->assertSessionHasNoErrors();
});

it('garde le consentement marketing décoché par défaut', function (): void {
    $this->post('/acheter/etape/5', ['extra_copies' => 0, 'accepts_terms' => true]);

    $draft = CheckoutDraft::query()->latest()->firstOrFail();

    expect($draft->value('marketing_email'))->not->toBe(true)
        ->and($draft->value('accepts_terms'))->toBe(true);
});

it('applique le plafond de l’option téléphone côté serveur', function (): void {
    $settings = app(PilotSettings::class);
    $settings->fill(['phone_option_cap' => 1])->save();

    Feature::define('phone-option-offer', true);

    // Le plafond est atteint par une autre famille.
    PhoneOption::factory()->create();

    expect(PhoneOptionOffer::isOpen())->toBeFalse();

    $this->post('/acheter/etape/5', [
        'extra_copies' => 0,
        'accepts_terms' => true,
        'phone_option' => true,
    ])->assertSessionHasNoErrors();

    $draft = CheckoutDraft::query()->latest()->firstOrFail();

    // Masquer la case ne suffit pas : un formulaire se rejoue (critère §8).
    expect($draft->value('phone_option'))->toBeFalse();
});

it('renvoie à l’étape manquante plutôt que d’ouvrir le paiement', function (): void {
    $buyer = User::factory()->create();
    $draft = completeDraft();
    $draft->user()->associate($buyer)->save();
    $draft->merge(['gift_message' => null], 6);

    $this->actingAs($buyer)
        ->withCookie('checkout_draft', $draft->id)
        ->post('/acheter/payer')
        ->assertRedirect('/acheter?step=3');
});

it('ouvre la session de paiement avec les bons prix et quantités', function (): void {
    config()->set('services.stripe.prices', [
        'pilot' => 'price_pilot',
        'extra_copy' => 'price_extra',
        'phone_option' => 'price_phone',
    ]);

    $sessions = fakeCheckout();
    $buyer = User::factory()->create();
    $draft = completeDraft(['extra_copies' => 2]);
    $draft->user()->associate($buyer)->save();

    $this->actingAs($buyer)
        ->withCookie('checkout_draft', $draft->id)
        ->post('/acheter/payer')
        ->assertRedirectContains('checkout.stripe.test');

    $created = $sessions->last();

    expect($created)->not->toBeNull()
        ->and($created['customer_email'])->toBe($buyer->email)
        ->and($created['line_items'])->toBe([
            ['price' => 'price_pilot', 'quantity' => 1],
            ['price' => 'price_extra', 'quantity' => 2],
        ])
        // Sans `draft_id`, une session payée serait un paiement orphelin.
        ->and($created['metadata']['draft_id'])->toBe($draft->id)
        ->and($created['metadata']['user_id'])->toBe((string) $buyer->id);
});

it('vend l’offre de prévente au prix vu par ce visiteur', function (): void {
    app(PilotSettings::class)->fill([
        'mode' => 'prevente',
        'prevente_prices_cents' => [9_900, 12_900],
    ])->save();

    config()->set('services.stripe.prices', [
        'prevente_99' => 'price_99',
        'prevente_129' => 'price_129',
    ]);

    $sessions = fakeCheckout();
    $buyer = User::factory()->create();
    $draft = completeDraft();
    $draft->forceFill(['price_variant' => 12_900])->save();
    $draft->user()->associate($buyer)->save();

    $this->actingAs($buyer)
        ->withCookie('checkout_draft', $draft->id)
        ->post('/acheter/payer');

    expect($sessions->last()['line_items'])->toBe([
        ['price' => 'price_129', 'quantity' => 1],
    ]);
});

it('exige une connexion pour payer, jamais avant', function (): void {
    $this->post('/acheter/payer')->assertRedirect('/login');
});

it('reprend le brouillon d’un aller-retour', function (): void {
    $this->post('/acheter/etape/2', [
        'narrator_first_name' => 'Jeanne',
        'narrator_email' => 'jeanne@exemple.test',
        'preferred_channel' => Channel::Email->value,
        'address_form' => 'vous',
        'narrator_tech_comfort' => 'daily',
    ]);

    $draft = CheckoutDraft::query()->latest()->firstOrFail();

    $this->withCookie('checkout_draft', $draft->id)
        ->get('/acheter?step=2')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('draft.narrator_first_name', 'Jeanne')
            ->where('draft.narrator_email', 'jeanne@exemple.test'),
        );
});
