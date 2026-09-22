<?php

declare(strict_types=1);

use App\Actions\FulfillOrder;
use App\Enums\Channel;
use App\Jobs\SendGiftInvitation;
use App\Models\CheckoutDraft;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/**
 * « Dès la commande » (T-260).
 *
 * Le tunnel n'offrait qu'une date : on achetait un cadeau pour sa mère un
 * dimanche soir et il fallait lui en donner une, alors qu'on voulait
 * l'appeler dans la foulée pour lui dire de regarder ses messages.
 *
 * Le calendrier reste le défaut — un cadeau de Noël envoyé le jour de l'achat
 * est une erreur qu'on ne rattrape pas — mais il n'est plus obligé.
 */
function draftWithWhen(string $when, array $extra = []): CheckoutDraft
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
            'gift_when' => $when,
            'gift_message' => 'J’aimerais garder tes histoires.',
            'extra_copies' => 0,
            'accepts_terms' => true,
        ], $extra),
        'expires_at' => now()->addDays(7),
    ]);
    $draft->save();

    return $draft;
}

function fulfilledFrom(CheckoutDraft $draft, string $reference): Project
{
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();

    app(FulfillOrder::class)->handle([
        'id' => "cs_{$reference}",
        'payment_intent' => "pi_{$reference}",
        'amount_total' => 8_900,
        'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
    ]);

    return Project::query()->where('owner_user_id', $buyer->id)->firstOrFail();
}

it('envoie l’annonce tout de suite quand on choisit « dès la commande »', function (): void {
    $this->freezeTime();

    $project = fulfilledFrom(draftWithWhen('now'), 'now');

    expect($project->gift_send_at?->toIso8601String())->toBe(now()->toIso8601String());

    /*
     * Et le travail part sans délai. C'est ce qui compte vraiment : une date
     * juste en base avec un envoi différé d'un jour ne serait « dès la
     * commande » que sur le papier.
     */
    Queue::assertPushed(
        SendGiftInvitation::class,
        fn (SendGiftInvitation $job): bool => $job->delay === null
            || $job->delay <= now(),
    );
});

it('respecte la date choisie quand on en choisit une', function (): void {
    $this->freezeTime();

    $project = fulfilledFrom(draftWithWhen('date', [
        'gift_send_at' => now()->addDays(10)->toDateString(),
        'gift_send_time' => '09:30',
    ]), 'date');

    expect($project->gift_send_at?->toDateString())
        ->toBe(now()->addDays(10)->toDateString())
        ->and($project->gift_send_at?->format('H:i'))->toBe('09:30');
});

it('n’exige ni date ni heure quand l’envoi est immédiat', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/acheter/etape/3', [
        'gift_when' => 'now',
        'gift_message' => 'J’aimerais garder tes histoires, maman.',
    ])->assertSessionHasNoErrors();
});

it('exige la date quand on dit en vouloir une', function (): void {
    $user = User::factory()->create();

    // Sans quoi le tunnel accepterait un « à une date » sans date, et l'envoi
    // retomberait sur un défaut que personne n'a choisi.
    $this->actingAs($user)->post('/acheter/etape/3', [
        'gift_when' => 'date',
        'gift_message' => 'J’aimerais garder tes histoires, maman.',
    ])->assertSessionHasErrors(['gift_send_at', 'gift_send_time']);
});
