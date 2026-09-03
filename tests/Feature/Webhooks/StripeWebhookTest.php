<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PhoneOptionEntry;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\Sku;
use App\Jobs\SendGiftInvitation;
use App\Models\CheckoutDraft;
use App\Models\FamilyMember;
use App\Models\Order;
use App\Models\PhoneOption;
use App\Models\Project;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use App\Settings\PilotSettings;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

/**
 * Ce que Stripe nous dit, et ce qu'on en fait.
 *
 * Le test le plus important du bloc est l'**idempotence** : Stripe rejoue ses
 * webhooks, parfois plusieurs fois. Un projet créé en double serait un projet
 * de trop dans la vie d'une famille — deux invitations au même parent, deux
 * séries de questions, et un support qui découvre le problème par un appel.
 *
 * Le second : le projet naît `draft`. Rien ne part avant que le narrateur ait
 * accepté. C'est l'invariant H0, et il se vérifie ici plutôt qu'à l'écran.
 */
const WEBHOOK_SECRET = 'whsec_test_secret';

/**
 * Un événement signé comme Stripe le signe.
 *
 * @param  array<string, mixed>  $payload
 * @return array{0: string, 1: array<string, string>}
 */
function signedWebhook(array $payload): array
{
    $body = (string) json_encode($payload);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$body}", WEBHOOK_SECRET);

    return [$body, ['Stripe-Signature' => "t={$timestamp},v1={$signature}"]];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function completedSession(CheckoutDraft $draft, User $buyer, array $overrides = []): array
{
    return array_merge([
        'id' => 'cs_test_'.uniqid(),
        'payment_intent' => 'pi_test_'.uniqid(),
        'amount_subtotal' => 4_900,
        'amount_total' => 4_900,
        'metadata' => [
            'draft_id' => $draft->id,
            'user_id' => (string) $buyer->id,
        ],
    ], $overrides);
}

function payableDraft(array $overrides = []): CheckoutDraft
{
    $draft = new CheckoutDraft([
        'step' => 6,
        'payload' => array_merge([
            'for' => 'relative',
            'narrator_first_name' => 'Jeanne',
            'narrator_email' => 'jeanne@exemple.test',
            'preferred_channel' => 'email',
            'address_form' => 'vous',
            'gift_send_at' => now()->addDay()->toDateString(),
            'gift_message' => 'J’aimerais garder tes histoires.',
            'gift_variant' => 'ecard',
            'extra_copies' => 0,
            'accepts_terms' => true,
        ], $overrides),
        'expires_at' => now()->addDays(7),
    ]);

    $draft->save();

    return $draft;
}

function postWebhook(array $payload): TestResponse
{
    [$body, $headers] = signedWebhook($payload);

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $headers['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

beforeEach(function (): void {
    config()->set('cashier.webhook.secret', WEBHOOK_SECRET);
});

it('exécute la commande à la réception du paiement', function (): void {
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();
    $draft = payableDraft();

    $session = completedSession($draft, $buyer);

    postWebhook(['type' => 'checkout.session.completed', 'data' => ['object' => $session]])
        ->assertSuccessful();

    $order = Order::query()->where('stripe_checkout_session_id', $session['id'])->firstOrFail();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->total_cents)->toBe(4_900)
        // Quatorze jours à partir d'un fait daté : une règle qui change ne
        // doit pas rétroagir sur une commande déjà passée.
        ->and($order->withdrawal_deadline_at?->toDateString())
        ->toBe(now()->addDays(14)->toDateString());

    $project = $order->project;

    expect($project)->not->toBeNull()
        // `draft` et non `active` : rien ne part avant l'acceptation.
        ->and($project->status)->toBe(ProjectStatus::Draft)
        ->and($project->primaryNarrator?->first_name)->toBe('Jeanne')
        ->and($project->members()->where('role', ProjectMemberRole::Initiator)->count())->toBe(1)
        // L'Initiateur·rice écoute comme un proche : sans fiche, elle ne
        // pourrait pas réagir aux histoires qu'elle a offertes.
        ->and(FamilyMember::query()->where('project_id', $project->id)
            ->where('email', $buyer->email)->exists())->toBeTrue();

    Notification::assertSentTo($buyer, OrderConfirmationNotification::class);
    Queue::assertPushed(SendGiftInvitation::class);
});

it('reste idempotent quand Stripe rejoue l’événement', function (): void {
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();
    $draft = payableDraft();
    $session = completedSession($draft, $buyer);
    $event = ['type' => 'checkout.session.completed', 'data' => ['object' => $session]];

    postWebhook($event)->assertSuccessful();
    postWebhook($event)->assertSuccessful();
    postWebhook($event)->assertSuccessful();

    // Trois envois, une seule famille. C'est tout le point.
    expect(Order::query()->count())->toBe(1)
        ->and(Project::query()->count())->toBe(1);

    Queue::assertPushed(SendGiftInvitation::class, 1);
});

it('crée l’option téléphone quand elle est commandée', function (): void {
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();
    $draft = payableDraft(['phone_option' => true]);

    postWebhook([
        'type' => 'checkout.session.completed',
        'data' => ['object' => completedSession($draft, $buyer, ['amount_total' => 7_400])],
    ])->assertSuccessful();

    $order = Order::query()->firstOrFail();
    $option = PhoneOption::query()->firstOrFail();

    expect($order->includes(Sku::PhoneOption))->toBeTrue()
        ->and($option->entry)->toBe(PhoneOptionEntry::Checkout)
        ->and($option->project_id)->toBe($order->project?->id);
});

it('ne crée rien pour un paiement orphelin', function (): void {
    Queue::fake();
    Notification::fake();

    postWebhook([
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_test_orphelin',
            'metadata' => ['draft_id' => '00000000-0000-0000-0000-000000000000', 'user_id' => '999'],
        ]],
    ])->assertSuccessful();

    // On ne devine pas une famille : le support rattachera à la main.
    expect(Order::query()->count())->toBe(0)
        ->and(Project::query()->count())->toBe(0);

    Queue::assertNotPushed(SendGiftInvitation::class);
});

it('refuse un événement mal signé', function (): void {
    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 't=1,v1=faux',
        'CONTENT_TYPE' => 'application/json',
    ], (string) json_encode(['type' => 'checkout.session.completed']))
        ->assertForbidden();

    expect(Order::query()->count())->toBe(0);
});

it('enregistre un remboursement total et annule le projet non accepté', function (): void {
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();
    $draft = payableDraft();
    $session = completedSession($draft, $buyer);

    postWebhook(['type' => 'checkout.session.completed', 'data' => ['object' => $session]]);

    postWebhook(['type' => 'charge.refunded', 'data' => ['object' => [
        'payment_intent' => $session['payment_intent'],
        'amount_refunded' => 4_900,
    ]]])->assertSuccessful();

    $order = Order::query()->firstOrFail();

    expect($order->status)->toBe(OrderStatus::Refunded)
        ->and($order->refunded_cents)->toBe(4_900)
        // Inutile de laisser un cadeau en attente que plus personne ne paie.
        ->and($order->project?->refresh()->status)->toBe(ProjectStatus::Cancelled);
});

it('distingue un remboursement partiel d’un remboursement total', function (): void {
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();
    $draft = payableDraft(['phone_option' => true]);
    $session = completedSession($draft, $buyer, ['amount_total' => 7_400]);

    postWebhook(['type' => 'checkout.session.completed', 'data' => ['object' => $session]]);

    // Le cas réel le plus fréquent : on rembourse l'option téléphone qu'on
    // n'a pas assurée, pas la commande entière.
    postWebhook(['type' => 'charge.refunded', 'data' => ['object' => [
        'payment_intent' => $session['payment_intent'],
        'amount_refunded' => 2_500,
    ]]])->assertSuccessful();

    $order = Order::query()->firstOrFail();

    expect($order->status)->toBe(OrderStatus::PartiallyRefunded)
        ->and($order->project?->refresh()->status)->toBe(ProjectStatus::Draft);
});

it('ignore sans broncher les événements dont il n’a que faire', function (): void {
    // Stripe envoie des dizaines de types. Une erreur sur un type inconnu
    // ferait retenter le webhook indéfiniment.
    //
    // Les types choisis sont ceux que **ni** notre écouteur **ni** Cashier ne
    // traitent : `customer.updated` en est exclu parce que Cashier s'en occupe,
    // et son comportement n'est pas ce qu'on éprouve ici.
    foreach (['invoice.paid', 'payment_intent.created', 'charge.succeeded'] as $type) {
        postWebhook([
            'type' => $type,
            'data' => ['object' => ['id' => 'obj_test_'.uniqid()]],
        ])->assertSuccessful();
    }

    expect(Order::query()->count())->toBe(0);
});

it('naît en prévente quand le mode l’est', function (): void {
    Queue::fake();
    Notification::fake();

    app(PilotSettings::class)->fill(['mode' => 'prevente'])->save();

    $buyer = User::factory()->create();
    $draft = payableDraft();

    postWebhook([
        'type' => 'checkout.session.completed',
        'data' => ['object' => completedSession($draft, $buyer)],
    ]);

    expect(Order::query()->firstOrFail()->includes(Sku::CorePrevente))->toBeTrue();
});
