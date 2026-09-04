<?php

declare(strict_types=1);

use App\Actions\ApplyDiscountCode;
use App\Actions\FulfillOrder;
use App\Exceptions\Domain\DiscountCodeUnavailable;
use App\Http\Controllers\Public\WelcomeOfferController;
use App\Models\CheckoutDraft;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\CheckoutSessions;
use App\Services\Payments\FakeCheckoutSessions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

/**
 * Le code de réduction au récapitulatif (T-141).
 *
 * Deux règles tiennent tout. La réduction vient du coupon Stripe, jamais d'un
 * calcul ici : on envoie un identifiant, pas un montant. Et le code n'est brûlé
 * qu'à l'encaissement : poser un code n'est pas acheter, et un code posé
 * puis abandonné doit pouvoir servir plus tard, sur un autre appareil.
 */
function discountDraft(array $overrides = []): CheckoutDraft
{
    $draft = new CheckoutDraft([
        'step' => 6,
        'payload' => array_merge([
            'for' => 'relative',
            'narrator_first_name' => 'Jeanne',
            'narrator_email' => 'jeanne@exemple.test',
            'preferred_channel' => 'email',
            'address_form' => 'vous',
            'narrator_tech_comfort' => 'daily',
            'gift_send_at' => now()->addDay()->toDateString(),
            'gift_send_time' => '09:00',
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

function fakeSessions(): FakeCheckoutSessions
{
    $sessions = new FakeCheckoutSessions;
    app()->instance(CheckoutSessions::class, $sessions);

    return $sessions;
}

beforeEach(function (): void {
    config()->set('services.stripe.prices.pilot', 'price_pilot');
    config()->set('services.stripe.coupons.welcome', 'coupon_welcome');
});

it('pose un code valable sur le brouillon et l’affiche au récapitulatif', function (): void {
    $lead = Lead::factory()->create(['discount_percent' => 10]);
    $draft = discountDraft();

    $this->withCookie('checkout_draft', $draft->id)
        ->post('/acheter/code', ['code' => $lead->discount_code])
        ->assertRedirect('/acheter?step=6')
        ->assertSessionHasNoErrors();

    $draft->refresh();

    expect($draft->value('discount_code'))->toBe($lead->discount_code)
        // Copié au moment où le code est posé, comme un prix de commande.
        ->and($draft->value('discount_percent'))->toBe(10);

    $this->withCookie('checkout_draft', $draft->id)
        ->get('/acheter?step=6')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('discount.code', $lead->discount_code)
            ->where('discount.percent', 10),
        );
});

it('accepte un code tapé en minuscules, avec des espaces, sans tiret', function (): void {
    $lead = Lead::factory()->create();
    $draft = discountDraft();
    $typed = ' '.mb_strtolower(str_replace('-', ' ', $lead->discount_code)).' ';

    $this->withCookie('checkout_draft', $draft->id)
        ->post('/acheter/code', ['code' => $typed])
        ->assertSessionHasNoErrors();

    expect($draft->refresh()->value('discount_code'))->toBe($lead->discount_code);
});

it('dit pourquoi un code est refusé : inconnu, utilisé, ou plus valable', function (): void {
    $draft = discountDraft();
    $post = fn (string $code) => $this->withCookie('checkout_draft', $draft->id)->post('/acheter/code', ['code' => $code]);

    $post('ZZZZ-ZZZZ')->assertSessionHasErrors(['code' => __('public.checkout.discount.errors.unknown')]);
    $post(Lead::factory()->used()->create()->discount_code)->assertSessionHasErrors(['code' => __('public.checkout.discount.errors.used')]);
    $post(Lead::factory()->expired()->create()->discount_code)->assertSessionHasErrors(['code' => __('public.checkout.discount.errors.expired')]);

    expect($draft->refresh()->value('discount_code'))->toBeNull();
});

it('se retire', function (): void {
    $lead = Lead::factory()->create();
    $draft = discountDraft(['discount_code' => $lead->discount_code, 'discount_percent' => 10]);

    $this->withCookie('checkout_draft', $draft->id)
        ->delete('/acheter/code')
        ->assertRedirect('/acheter?step=6');

    expect($draft->refresh()->value('discount_code'))->toBeNull()
        ->and($draft->value('discount_percent'))->toBeNull();
});

it('applique tout seul le code laissé en cookie par la page d’accueil', function (): void {
    $lead = Lead::factory()->create();
    $draft = discountDraft();

    $this->withCookie('checkout_draft', $draft->id)
        ->withCookie(WelcomeOfferController::COOKIE, $lead->discount_code)
        ->get('/acheter?step=6')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('discount.code', $lead->discount_code),
        );

    expect($draft->refresh()->value('discount_code'))->toBe($lead->discount_code);
});

it('ignore en silence un cookie dont le code a déjà servi', function (): void {
    // La personne n'a rien demandé ici : il n'y a pas d'erreur à lui montrer.
    $lead = Lead::factory()->used()->create();
    $draft = discountDraft();

    $this->withCookie('checkout_draft', $draft->id)
        ->withCookie(WelcomeOfferController::COOKIE, $lead->discount_code)
        ->get('/acheter?step=6')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('discount', null));
});

it('n’affiche plus un code qui a servi ailleurs entre-temps', function (): void {
    $lead = Lead::factory()->create();
    $draft = discountDraft(['discount_code' => $lead->discount_code, 'discount_percent' => 10]);

    $lead->forceFill(['code_used_at' => now()])->save();

    $this->withCookie('checkout_draft', $draft->id)
        ->get('/acheter?step=6')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('discount', null));
});

it('envoie le coupon Stripe, et jamais un pourcentage ni un montant', function (): void {
    $sessions = fakeSessions();
    $lead = Lead::factory()->create();
    $buyer = User::factory()->create();
    $draft = discountDraft(['discount_code' => $lead->discount_code, 'discount_percent' => 10]);
    $draft->user()->associate($buyer)->save();

    $this->actingAs($buyer)->post('/acheter/payer')->assertRedirect();

    $session = $sessions->last();

    expect($session['discounts'])->toBe([['coupon' => 'coupon_welcome']])
        ->and($session['metadata']['discount_code'])->toBe($lead->discount_code)
        ->and(json_encode($session))->not->toContain('"10"')
        ->and(json_encode($session))->not->toContain('890');
});

it('n’envoie aucun coupon sans code', function (): void {
    $sessions = fakeSessions();
    $buyer = User::factory()->create();
    $draft = discountDraft();
    $draft->user()->associate($buyer)->save();

    $this->actingAs($buyer)->post('/acheter/payer')->assertRedirect();

    expect($sessions->last()['discounts'])->toBe([])
        ->and($sessions->last()['metadata']['discount_code'])->toBe('');
});

it('retire au paiement un code qui a servi ailleurs, et encaisse sans lui', function (): void {
    $sessions = fakeSessions();
    $lead = Lead::factory()->used()->create();
    $buyer = User::factory()->create();
    $draft = discountDraft(['discount_code' => $lead->discount_code, 'discount_percent' => 10]);
    $draft->user()->associate($buyer)->save();

    $this->actingAs($buyer)->post('/acheter/payer')->assertRedirect();

    expect($sessions->last()['discounts'])->toBe([])
        ->and($draft->refresh()->value('discount_code'))->toBeNull();
});

it('refuse d’encaisser plein tarif à qui on a promis une réduction', function (): void {
    // Un code posé et pas de coupon configuré : on lève, plutôt que
    // d'inventer un montant ou de faire payer le prix entier.
    config()->set('services.stripe.coupons.welcome', null);
    fakeSessions();

    $lead = Lead::factory()->create();
    $buyer = User::factory()->create();
    $draft = discountDraft(['discount_code' => $lead->discount_code, 'discount_percent' => 10]);
    $draft->user()->associate($buyer)->save();

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($buyer)->post('/acheter/payer'))
        ->toThrow(RuntimeException::class, 'STRIPE_COUPON_WELCOME');
});

it('brûle le code à l’encaissement et le rattache à la commande', function (): void {
    Queue::fake();
    Notification::fake();

    $lead = Lead::factory()->create();
    $buyer = User::factory()->create();
    $draft = discountDraft(['discount_code' => $lead->discount_code, 'discount_percent' => 10]);

    $order = app(FulfillOrder::class)->handle([
        'id' => 'cs_test_'.uniqid(),
        'payment_intent' => 'pi_test',
        'amount_subtotal' => 8_900,
        'amount_total' => 8_010,
        'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
    ]);

    $lead->refresh();

    expect($order)->toBeInstanceOf(Order::class)
        ->and($lead->code_used_at)->not->toBeNull()
        ->and($lead->order_id)->toBe($order?->id)
        ->and($lead->codeUsable())->toBeFalse()
        // Stripe dit le total réellement payé : la commande le garde tel quel.
        ->and($order?->total_cents)->toBe(8_010)
        ->and($order?->subtotal_cents)->toBe(8_900);

    // Le même code ne se pose plus.
    expect(fn () => (new ApplyDiscountCode)->handle(discountDraft(), $lead->discount_code))
        ->toThrow(DiscountCodeUnavailable::class);
});
