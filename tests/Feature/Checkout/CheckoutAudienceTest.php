<?php

declare(strict_types=1);

use App\Actions\FulfillOrder;
use App\Actions\SaveCheckoutStep;
use App\Enums\Channel;
use App\Enums\TechComfort;
use App\Models\CheckoutDraft;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

/**
 * Le tunnel s'adapte à qui racontera (T-136).
 *
 * Offrir à un proche demande à quel point cette personne est à l'aise avec un
 * téléphone, parce que la réponse change ce qu'on propose. Raconter soi-même
 * ne le demande pas, et n'exige pas de mot d'invitation. L'heure d'envoi est
 * choisie à l'achat, plus imposée par un réglage.
 */
function draftFor(string $for): CheckoutDraft
{
    $draft = new CheckoutDraft([
        'step' => 2,
        'payload' => ['for' => $for],
        'expires_at' => now()->addDays(7),
    ]);
    $draft->save();

    return $draft;
}

it('demande, pour un proche, à quel point la personne est à l’aise avec un téléphone', function (): void {
    $draft = draftFor('relative');

    $this->withCookie('checkout_draft', $draft->id)
        ->post('/acheter/etape/2', [
            'narrator_first_name' => 'Jeanne',
            'narrator_email' => 'jeanne@exemple.test',
            'preferred_channel' => Channel::Email->value,
            'address_form' => 'vous',
        ])
        ->assertSessionHasErrors('narrator_tech_comfort');
});

it('ne le demande pas quand on raconte soi-même', function (): void {
    $draft = draftFor('self');

    $this->withCookie('checkout_draft', $draft->id)
        ->post('/acheter/etape/2', [
            'narrator_first_name' => 'Camille',
            'narrator_email' => 'camille@exemple.test',
            'preferred_channel' => Channel::Email->value,
            'address_form' => 'tu',
        ])
        ->assertSessionHasNoErrors();
});

it('exige une heure d’envoi, et pas de mot quand on s’écrit à soi-même', function (): void {
    $self = draftFor('self');

    $this->withCookie('checkout_draft', $self->id)
        ->post('/acheter/etape/3', [
            'gift_send_at' => now()->addDays(3)->toDateString(),
        ])
        ->assertSessionHasErrors('gift_send_time')
        ->assertSessionDoesntHaveErrors('gift_message');

    $this->withCookie('checkout_draft', $self->id)
        ->post('/acheter/etape/3', [
            'gift_send_at' => now()->addDays(3)->toDateString(),
            'gift_send_time' => '18:30',
        ])
        ->assertSessionHasNoErrors();

    // La forme du cadeau n'est plus demandée : le drapeau la pose.
    expect($self->refresh()->value('gift_variant'))->toBe('ecard')
        ->and($self->value('gift_send_time'))->toBe('18:30');

    $relative = draftFor('relative');

    $this->withCookie('checkout_draft', $relative->id)
        ->post('/acheter/etape/3', [
            'gift_send_at' => now()->addDays(3)->toDateString(),
            'gift_send_time' => '09:00',
        ])
        ->assertSessionHasErrors('gift_message');
});

it('sait ce qui manque selon le chemin choisi', function (): void {
    $self = draftFor('self');
    $self->merge([
        'narrator_first_name' => 'Camille',
        'narrator_email' => 'camille@exemple.test',
        'preferred_channel' => Channel::Email->value,
        'address_form' => 'tu',
        'gift_send_at' => now()->addDays(3)->toDateString(),
        'gift_send_time' => '09:00',
        'accepts_terms' => true,
        'extra_copies' => 0,
    ]);

    expect(SaveCheckoutStep::missingSteps($self))->toBe([]);

    $relative = draftFor('relative');
    $relative->merge(Arr::except($self->payload, ['for']));

    // Même brouillon, autre chemin : il manque l'aisance et le mot.
    expect(SaveCheckoutStep::missingSteps($relative))->toBe([2, 3]);
});

it('programme l’invitation à l’heure choisie et retient l’aisance de la narratrice', function (): void {
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();

    $draft = new CheckoutDraft([
        'step' => 6,
        'payload' => [
            'for' => 'relative',
            'narrator_first_name' => 'Jeanne',
            'narrator_email' => 'jeanne@exemple.test',
            'preferred_channel' => Channel::Email->value,
            'address_form' => 'vous',
            'narrator_tech_comfort' => TechComfort::Rarely->value,
            'gift_send_at' => now()->addDays(5)->toDateString(),
            'gift_send_time' => '18:30',
            'gift_message' => 'J’aimerais garder tes histoires.',
            'accepts_terms' => true,
        ],
        'expires_at' => now()->addDays(7),
    ]);
    $draft->save();

    app(FulfillOrder::class)->handle([
        'id' => 'cs_test_time',
        'payment_intent' => 'pi_test_time',
        'amount_total' => 8_900,
        'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
    ]);

    $project = Project::query()->firstOrFail();
    $narrator = Narrator::query()->firstOrFail();

    expect($project->gift_send_at?->format('H:i'))->toBe('18:30')
        ->and($narrator->tech_comfort)->toBe(TechComfort::Rarely)
        ->and($narrator->tech_comfort?->suggestsPhoneOption())->toBeTrue();
});

it('donne au tunnel les choix d’aisance et l’heure par défaut', function (): void {
    $this->get('/acheter?step=2')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Checkout')
            ->has('techComforts', 4)
            ->where('techComforts.0.value', 'daily')
            ->has('giftSendHour'),
        );
});
