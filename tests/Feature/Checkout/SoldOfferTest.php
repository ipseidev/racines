<?php

declare(strict_types=1);

use App\Actions\FulfillOrder;
use App\Enums\Channel;
use App\Enums\Offer;
use App\Models\CheckoutDraft;
use App\Models\Project;
use App\Models\User;
use App\Settings\PilotSettings;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/**
 * Le mode du tunnel décide de l'offre, et l'offre de la durée (R-2).
 *
 * Ce que ces tests protègent : qu'on ne puisse plus vendre douze mois de
 * collecte et n'en ouvrir que douze semaines. `PilotSettings::mode` proposait
 * « Offre courante » dans l'administration depuis le bloc 10 et personne ne le
 * lisait : `FulfillOrder` ne testait que la prévente, tout le reste retombait
 * sur le pilote. Le panneau changeait l'étiquette, la famille recevait le
 * calendrier du pilote, et l'espace affichait « semaine 9 sur 12 » à quelqu'un
 * qui a payé une année.
 */
function fulfilled(string $mode, string $reference): Project
{
    Queue::fake();
    Notification::fake();

    $settings = app(PilotSettings::class);
    $settings->mode = $mode;
    $settings->save();

    $buyer = User::factory()->create();

    $draft = new CheckoutDraft([
        'step' => 6,
        'payload' => [
            'for' => 'relative',
            'narrator_first_name' => 'Jeanne',
            'narrator_email' => 'jeanne@exemple.test',
            'preferred_channel' => Channel::Email->value,
            'address_form' => 'vous',
            'narrator_tech_comfort' => 'daily',
            'gift_when' => 'date',
            'gift_send_at' => now()->addDay()->toDateString(),
            'gift_send_time' => '09:00',
            'gift_message' => 'J’aimerais garder tes histoires.',
            'extra_copies' => 0,
            'accepts_terms' => true,
        ],
        'expires_at' => now()->addDays(7),
    ]);
    $draft->save();

    app(FulfillOrder::class)->handle([
        'id' => "cs_{$reference}",
        'payment_intent' => "pi_{$reference}",
        'amount_total' => 8_900,
        'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
    ]);

    return Project::query()->where('owner_user_id', $buyer->id)->firstOrFail();
}

it('vend le pilote et ses douze semaines quand le mode est « pilote »', function (): void {
    $project = fulfilled('pilot', 'pilot');

    expect($project->offer)->toBe(Offer::Pilot);

    $window = $project->collectionWindow(now());

    expect($window->collectionEndsAt->toDateString())
        ->toBe(now()->addWeeks((int) config('product.offer.pilot_weeks'))->toDateString());
});

it('vend l’offre courante et ses 52 questions quand le mode est « core »', function (): void {
    $project = fulfilled('core', 'core');

    expect($project->offer)->toBe(Offer::Core);

    $window = $project->collectionWindow(now());

    // 52 questions au rythme du projet, puis trois mois de finalisation :
    // c'est R-2, et c'est ce que l'acheteur a payé.
    expect($window->collectionEndsAt->toDateString())
        ->toBe(now()->addWeeks(Project::collectionWeeks($project->cadence))->toDateString())
        ->and($window->finalizationEndsAt->toDateString())
        ->toBe(now()
            ->addWeeks(Project::collectionWeeks($project->cadence))
            ->addMonths((int) config('product.offer.finalization_months'))
            ->toDateString());
});

it('vend la prévente quand le mode est « prevente »', function (): void {
    expect(fulfilled('prevente', 'prevente')->offer)->toBe(Offer::Prevente);
});
