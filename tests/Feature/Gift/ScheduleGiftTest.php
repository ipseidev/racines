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
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Notifications\GiftInvitationNotification;
use App\Notifications\OrderConfirmationNotification;
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
    //
    // Le **report** est vérifié, pas seulement la poussée : c'est tout ce qui
    // sépare « programmé » d'« envoyé maintenant », et c'est précisément ce
    // que l'ancienne version de ce test ne regardait pas (T-239).
    Queue::assertPushed(
        SendGiftInvitation::class,
        fn (SendGiftInvitation $job): bool => $job->projectId === $project->id
            && $job->attempt === 1
            && $job->delay instanceof DateTimeInterface
            && $job->delay->format('Y-m-d H:i') === $project->gift_send_at?->format('Y-m-d H:i'),
    );
});

/*
 * La garde qui manquait.
 *
 * Un report n'est pas une promesse : sur une file `sync`, `deferred` ou
 * `background`, `->delay()` est ignoré et le travail s'exécute dans la requête
 * qui le pousse — donc dans le webhook Stripe, donc à la seconde du paiement.
 * C'est ce qui est arrivé en production le 10 septembre 2026 : cadeau
 * programmé pour dix heures, parti à neuf heures quarante.
 *
 * Aucun test ne pouvait le voir, et pour une raison qui vaut d'être écrite :
 * **la suite tourne elle-même sur `sync`** (`phpunit.xml`), où le report
 * n'existe pas. Un `Queue::fake()` ne le voit pas davantage. La seule défense
 * qui tienne est donc dans l'envoi lui-même, et elle se teste sans file.
 */
it('ne part jamais avant l’heure choisie, même si la file ignore le report', function (): void {
    Notification::fake();

    $project = Project::factory()->create([
        'status' => ProjectStatus::Draft,
        'gift_send_at' => now()->addHours(3),
    ]);
    $narrator = Narrator::factory()->byEmail()->create([
        'project_id' => $project->id,
        'is_primary' => true,
    ]);

    (new SendGiftInvitation($project->id))->handle(app(TokenService::class));

    Notification::assertNothingSentTo($narrator);

    // Rien n'a bougé : ni jeton émis, ni statut avancé. Le projet attend son
    // heure, et personne ne sait encore qu'il existe.
    expect(Invitation::query()->count())->toBe(0)
        ->and($project->refresh()->gift_sent_at)->toBeNull()
        ->and($project->status)->toBe(ProjectStatus::Draft);
});

it('part à l’heure venue, par le filet, sans rien attendre de la file', function (): void {
    Notification::fake();

    $project = Project::factory()->create([
        'status' => ProjectStatus::Draft,
        'gift_send_at' => now()->subMinute(),
    ]);
    $narrator = Narrator::factory()->byEmail()->create([
        'project_id' => $project->id,
        'is_primary' => true,
    ]);

    $this->artisan('gifts:dispatch-due')->assertSuccessful();

    Notification::assertSentTo($narrator, GiftInvitationNotification::class);
    expect($project->refresh()->gift_sent_at)->not->toBeNull()
        ->and($project->status)->toBe(ProjectStatus::AwaitingAcceptance);
});

it('ne renvoie pas, par le filet, une invitation déjà partie', function (): void {
    Notification::fake();

    $project = Project::factory()->create([
        'status' => ProjectStatus::AwaitingAcceptance,
        'gift_send_at' => now()->subDay(),
        'gift_sent_at' => now()->subDay(),
    ]);
    $narrator = Narrator::factory()->byEmail()->create([
        'project_id' => $project->id,
        'is_primary' => true,
    ]);

    $this->artisan('gifts:dispatch-due')->assertSuccessful();

    // Deux envois de la même invitation, c'est deux fois la même surprise, et
    // la table le refuserait de toute façon (unique par narrateur et
    // tentative) — au prix d'un travail en échec.
    Notification::assertNothingSentTo($narrator);
    expect(Invitation::query()->count())->toBe(0);
});

it('annonce dans la confirmation l’heure choisie, et pas neuf heures', function (): void {
    $buyer = User::factory()->create();

    $project = Project::factory()->create([
        'status' => ProjectStatus::Draft,
        'gift_send_at' => now()->addDays(3)->setTime(10, 0),
    ]);
    Narrator::factory()->byEmail()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'first_name' => 'Agate',
    ]);

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'project_id' => $project->id,
    ]);

    $html = (string) (new OrderConfirmationNotification($order->refresh()))->toMail($buyer)->render();

    // « à 10 h », comme le récapitulatif du tunnel l'a écrit : la phrase
    // portait « à neuf heures » en dur depuis le bloc 10, et annonçait donc
    // neuf heures à qui avait demandé dix (T-239).
    expect($html)->toContain('10 h')
        ->and($html)->not->toContain('neuf heures');
});

/*
 * L'invitation ne part qu'**après** le `commit`.
 *
 * Ce que le test précédent prouve : le job est poussé, avec le bon
 * identifiant. Ce qu'il ne prouvait pas : que le job puisse **trouver** son
 * projet. `FulfillOrder` construit tout dans une transaction, et le job ne
 * reçoit qu'un identifiant. Poussé avant la validation, un ouvrier le prend en
 * quelques millisecondes, ne trouve pas le projet, et `SendGiftInvitation`
 * sort par sa première garde en rendant `null` : pas d'exception, pas de job
 * en échec, rien dans `failed_jobs`. Le job a « réussi ».
 *
 * Ce que le client voit alors : sa confirmation d'achat arrive, le projet
 * reste en `draft`, et le parent n'est **jamais** invité. C'est une course,
 * donc intermittente — elle a tenu en local et perdu à la première commande
 * passée en production (T-223). Un `Queue::fake()` ne la joue pas : il n'y a
 * ni ouvrier ni transaction réelle. C'est donc l'intention qui est vérifiée
 * ici, et c'est la seule chose vérifiable sans ouvrier.
 */
it('ne pousse l’invitation qu’après la validation de la transaction', function (): void {
    Queue::fake();
    Notification::fake();

    $buyer = User::factory()->create();

    $draft = new CheckoutDraft([
        'step' => 6,
        'payload' => [
            'narrator_first_name' => 'Jeanne',
            'narrator_email' => 'jeanne@exemple.test',
            'preferred_channel' => Channel::Email->value,
            'address_form' => 'vous',
            'gift_send_at' => now()->toDateString(),
            'gift_message' => 'J’aimerais garder tes histoires.',
            'accepts_terms' => true,
        ],
        'expires_at' => now()->addDays(7),
    ]);
    $draft->save();

    app(FulfillOrder::class)->handle([
        'id' => 'cs_test_after_commit',
        'payment_intent' => 'pi_test_after_commit',
        'amount_total' => 4_900,
        'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
    ]);

    Queue::assertPushed(
        SendGiftInvitation::class,
        fn (SendGiftInvitation $job): bool => $job->afterCommit === true,
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
