<?php

declare(strict_types=1);

use App\Actions\FulfillOrder;
use App\Enums\Channel;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Jobs\SendGiftInvitation;
use App\Models\CheckoutDraft;
use App\Models\Invitation;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\User;
use App\Notifications\GiftInvitationNotification;
use App\Services\Tokens\TokenService;
use App\Settings\PilotSettings;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/**
 * Quand le cadeau part, et comment.
 *
 * À la date choisie, à neuf heures, sur le canal du narrateur. Trois envois au
 * maximum : l'invitation puis deux relances. Au-delà, ce n'est plus une
 * invitation, c'est une insistance — et la limite vit en base, pas seulement
 * dans le moteur qui la déclenche.
 */
it('programme l’invitation à la date choisie, à neuf heures', function (): void {
    Queue::fake();
    Notification::fake();

    $settings = app(PilotSettings::class);
    $settings->fill(['gift_send_hour' => 9])->save();

    $buyer = User::factory()->create();

    $draft = new CheckoutDraft([
        'step' => 6,
        'payload' => [
            'narrator_first_name' => 'Jeanne',
            'narrator_email' => 'jeanne@exemple.test',
            'preferred_channel' => Channel::Email->value,
            'address_form' => 'vous',
            'gift_send_at' => now()->addDays(5)->toDateString(),
            'gift_message' => 'J’aimerais garder tes histoires.',
            'gift_variant' => 'ecard',
            'accepts_terms' => true,
        ],
        'expires_at' => now()->addDays(7),
    ]);
    $draft->save();

    app(FulfillOrder::class)->handle([
        'id' => 'cs_test_gift',
        'payment_intent' => 'pi_test_gift',
        'amount_total' => 4_900,
        'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
    ]);

    $project = Project::query()->firstOrFail();

    expect($project->gift_send_at?->format('H:i'))->toBe('09:00')
        ->and($project->gift_send_at?->toDateString())->toBe(now()->addDays(5)->toDateString());

    // Programmée, jamais envoyée tout de suite : un cadeau qui arrive avant
    // l'heure n'est plus une surprise.
    Queue::assertPushed(
        SendGiftInvitation::class,
        fn (SendGiftInvitation $job): bool => $job->projectId === $project->id && $job->attempt === 1,
    );
});

it('envoie l’invitation sur le canal du narrateur', function (): void {
    Notification::fake();

    $project = Project::factory()->create(['status' => ProjectStatus::Draft]);
    $narrator = Narrator::factory()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'email' => 'jeanne@exemple.test',
        'phone_e164' => null,
        'preferred_channel' => Channel::Email,
    ]);

    (new SendGiftInvitation($project->id))->handle(app(TokenService::class));

    Notification::assertSentTo($narrator, GiftInvitationNotification::class);

    $project->refresh();

    // Le statut ne bascule qu'à l'envoi : avant, personne ne sait que le
    // projet existe.
    expect($project->status)->toBe(ProjectStatus::AwaitingAcceptance)
        ->and($project->gift_sent_at)->not->toBeNull();
});

it('émet un jeton d’invitation valable trente jours et rien de plus', function (): void {
    Notification::fake();

    $project = Project::factory()->create(['status' => ProjectStatus::Draft]);
    Narrator::factory()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'preferred_channel' => Channel::Email,
    ]);

    (new SendGiftInvitation($project->id))->handle(app(TokenService::class));

    $invitation = Invitation::query()->firstOrFail();
    $token = $invitation->token;

    expect($token)->not->toBeNull()
        ->and($token->type)->toBe(TokenType::Invitation)
        // Périmètre strict : ce lien ouvre l'opt-in, pas l'enregistrement.
        ->and($token->scope)->toBe(['opt_in'])
        ->and($token->expires_at?->toDateString())->toBe(now()->addDays(30)->toDateString());
});

it('s’arrête à trois envois', function (): void {
    Notification::fake();

    $project = Project::factory()->create(['status' => ProjectStatus::Draft]);
    $narrator = Narrator::factory()->byEmail()->create([
        'project_id' => $project->id,
        'is_primary' => true,
    ]);

    foreach ([1, 2, 3, 4, 5] as $attempt) {
        (new SendGiftInvitation($project->id, $attempt))
            ->handle(app(TokenService::class));
    }

    // Deux invitations et une relance : la limite du doc 04 §2.
    expect(Invitation::attemptsFor($narrator))->toBe(Invitation::MAX_ATTEMPTS);

    Notification::assertSentToTimes($narrator, GiftInvitationNotification::class, 3);
});

it('ne relance jamais quelqu’un qui a tranché', function (): void {
    Notification::fake();

    $project = Project::factory()->create([
        'status' => ProjectStatus::AwaitingAcceptance,
        'refused_at' => now(),
    ]);
    $narrator = Narrator::factory()->byEmail()->create([
        'project_id' => $project->id,
        'is_primary' => true,
    ]);

    (new SendGiftInvitation($project->id, 2))->handle(app(TokenService::class));

    Notification::assertNothingSentTo($narrator);
    expect(Invitation::query()->count())->toBe(0);
});

it('porte le message personnel et la phrase anti-hameçonnage', function (): void {
    $notifications = require base_path('lang/fr/notifications.php');

    // Un cadeau inattendu d'un expéditeur inconnu est exactement ce qu'un
    // hameçonneur imiterait (doc 04 §9).
    $sms = $notifications['gift_invitation']['sms'];

    expect($sms)->toContain(':inviter')
        ->and($sms)->toContain(':link')
        ->and(mb_stripos($sms, 'mot de passe'))->not->toBeFalse()
        ->and(mb_stripos($sms, 'paiement'))->not->toBeFalse();
});

it('ne fait rien si le projet n’a pas de narrateur', function (): void {
    Notification::fake();

    $project = Project::factory()->create(['status' => ProjectStatus::Draft]);

    (new SendGiftInvitation($project->id))->handle(app(TokenService::class));

    expect(Invitation::query()->count())->toBe(0)
        ->and($project->refresh()->status)->toBe(ProjectStatus::Draft);
});
