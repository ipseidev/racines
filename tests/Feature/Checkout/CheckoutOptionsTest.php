<?php

declare(strict_types=1);

use App\Actions\FulfillOrder;
use App\Actions\StartStripeCheckout;
use App\Enums\Channel;
use App\Enums\Sku;
use App\Models\CheckoutDraft;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

/**
 * Le livre numérique, en option à 25 € (T-137).
 *
 * Un article de plus, et le même chemin que les autres : une ligne chez le
 * prestataire de paiement, un article sur la commande au prix des réglages.
 */
function draftWithOptions(array $overrides = []): CheckoutDraft
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
            'extra_copies' => 0,
            'accepts_terms' => true,
        ], $overrides),
        'expires_at' => now()->addDays(7),
    ]);
    $draft->save();

    return $draft;
}

it('ajoute le livre numérique aux lignes de paiement quand il est choisi', function (): void {
    config()->set('services.stripe.prices', [
        'pilot' => 'price_pilot',
        'extra_copy' => 'price_extra',
        'phone_option' => 'price_phone',
        'ebook' => 'price_ebook',
    ]);

    expect(StartStripeCheckout::lineItemsFor(draftWithOptions(['ebook' => true])))->toBe([
        ['price' => 'price_pilot', 'quantity' => 1],
        ['price' => 'price_ebook', 'quantity' => 1],
    ]);

    expect(StartStripeCheckout::lineItemsFor(draftWithOptions()))->toBe([
        ['price' => 'price_pilot', 'quantity' => 1],
    ]);
});

it('porte le livre numérique sur la commande, au prix des réglages', function (): void {
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();
    $draft = draftWithOptions(['ebook' => true]);

    app(FulfillOrder::class)->handle([
        'id' => 'cs_test_ebook',
        'payment_intent' => 'pi_test_ebook',
        'amount_total' => 11_400,
        'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
    ]);

    $order = Order::query()->firstOrFail();
    $ebook = $order->items()->where('sku', Sku::Ebook->value)->first();

    expect($ebook)->not->toBeNull()
        ->and($ebook?->unit_cents)->toBe(2_500)
        ->and($ebook?->quantity)->toBe(1);
});

it('donne au tunnel le prix du livre numérique et le prix barré', function (): void {
    $this->get('/acheter?step=5')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Checkout')
            ->where('prices.ebook', 2_500)
            ->where('prices.ebookRegular', 4_500)
            ->where('phoneOption.open', true),
        );
});
