<?php

declare(strict_types=1);

use App\Actions\AcceptInvitation;
use App\Enums\Channel;
use App\Enums\ProjectStatus;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\States\Story\Proposed;
use Illuminate\Support\Facades\Notification;

/**
 * Le parcours de celle qui **offre**, de bout en bout.
 *
 * `demo:cadeau` montre l'autre moitié. Celle-ci part du paiement, et ce
 * qu'elle protège tient en deux points.
 *
 * Le premier : le paiement passe par `FulfillOrder`, l'action que le webhook
 * appelle, avec la même forme de session. Fabriquer la commande à la main
 * reviendrait à éprouver une fabrication qui n'existe nulle part ailleurs —
 * et c'est justement ce chemin-là qui a caché deux défauts au bloc 10, la
 * commande honorée avant le paiement (T-167) et le webhook qui répondait 500
 * (T-169).
 *
 * Le second : **la commande n'accepte jamais à la place de la narratrice**.
 * L'acceptation lui appartient, elle est définitive, et un banc d'essai qui
 * la simulerait ne montrerait plus le produit.
 */
beforeEach(function (): void {
    Notification::fake();
});

it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:offrir')->assertFailed();
});

it('étape 1 : le paiement crée la commande et le projet, en attente', function (): void {
    $this->artisan('demo:offrir --prenom=Suzanne')
        ->expectsOutputToContain('offrir@example.test')
        ->assertSuccessful();

    $buyer = User::query()->where('email', 'offrir@example.test')->firstOrFail();

    // Vérifié, sinon tout l'espace est derrière `verified` et la personne
    // tombe sur la page de vérification sans comprendre pourquoi.
    expect($buyer->hasVerifiedEmail())->toBeTrue();

    $order = Order::query()->latest()->firstOrFail();
    expect($order->total_cents)->toBe(8900);

    $project = $order->project;
    expect($project)->not->toBeNull();

    // Rien ne part avant l'acceptation : c'est l'invariant du bloc 10.
    expect($project->status)->toBe(ProjectStatus::Draft)
        ->and($project->gift_sent_at)->toBeNull()
        ->and($project->stories()->count())->toBe(0);

    expect($project->primaryNarrator?->first_name)->toBe('Suzanne');
});

it('étape 2 : l’annonce part, et le lien s’imprime parce qu’il ne se relit pas', function (): void {
    $this->artisan('demo:offrir --prenom=Suzanne')->assertSuccessful();

    $this->artisan('demo:offrir --annonce')
        ->expectsOutputToContain('/i/')
        ->assertSuccessful();

    $project = Project::query()->latest('created_at')->firstOrFail();

    // `draft` avant l'annonce, `awaiting_acceptance` après : c'est ce que
    // l'espace de l'acheteuse doit refléter entre les deux étapes.
    expect($project->gift_sent_at)->not->toBeNull()
        ->and($project->status)->toBe(ProjectStatus::AwaitingAcceptance);
});

it('étape 3 : aucune question ne part tant que la personne n’a pas accepté', function (): void {
    $this->artisan('demo:offrir --prenom=Suzanne')->assertSuccessful();
    $this->artisan('demo:offrir --annonce')->assertSuccessful();

    $this->artisan('demo:offrir --question')
        ->expectsOutputToContain('n’a pas encore été acceptée')
        ->assertFailed();

    expect(Project::query()->latest('created_at')->firstOrFail()->stories()->count())
        ->toBe(0);
});

it('étape 3 : une fois acceptée, la question part sans attendre le lendemain', function (): void {
    $this->artisan('demo:offrir --prenom=Suzanne')->assertSuccessful();
    $this->artisan('demo:offrir --annonce')->assertSuccessful();

    $project = Project::query()->latest('created_at')->firstOrFail();

    app(AcceptInvitation::class)->handle($project, [
        'preferred_channel' => Channel::Email->value,
        'prompt_day' => 1,
        'prompt_window' => 'morning',
    ]);

    $this->artisan('demo:offrir --question')->assertSuccessful();

    expect($project->refresh()->stories()->where('state', Proposed::$name)->count())
        ->toBe(1);
});
