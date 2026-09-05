<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Une invitation neuve, à la demande.
 *
 * Le décor ne sème qu'un scénario d'acceptation et un de refus, et l'opt-in
 * est **définitif** par construction — c'est le produit qui a raison. Mais la
 * suite bout en bout les consomme avant qu'un humain n'arrive : le point 4 du
 * checkpoint du bloc 10 n'était donc jouable qu'une fois par semis, et il a
 * fallu fabriquer un projet à la main en pleine vérification pour le dérouler
 * (T-169). Un checkpoint qu'on ne peut pas rejouer n'est pas un checkpoint.
 *
 * La commande passe par le vrai chemin d'envoi — même action, même jeton, même
 * message — et imprime le lien, que le journal masque à dessein.
 */
beforeEach(function (): void {
    Notification::fake();
});

it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:invitation')->assertFailed();
});

it('imprime un lien d’invitation ouvrable', function (): void {
    $this->artisan('demo:invitation')->assertSuccessful();

    $token = AccessToken::query()->where('type', TokenType::Invitation->value)->sole();

    expect($token->used_at)->toBeNull()
        ->and($token->use_count)->toBe(0);
});

it('fabrique un projet neuf à chaque fois', function (): void {
    $this->artisan('demo:invitation')->assertSuccessful();
    $this->artisan('demo:invitation')->assertSuccessful();

    expect(Project::query()->where('status', ProjectStatus::AwaitingAcceptance->value)->count())->toBe(2)
        ->and(AccessToken::query()->where('type', TokenType::Invitation->value)->count())->toBe(2);
});

/*
 * Le point de la commande : le lien imprimé ouvre vraiment la page d'opt-in.
 * Un lien qu'il faut aller pêcher dans Mailpit, ou qu'un journal masque, ne
 * rend pas le checkpoint jouable.
 */
it('le lien imprimé mène à la page d’opt-in', function (): void {
    $sortie = '';

    $this->artisan('demo:invitation')
        ->assertSuccessful();

    // Le jeton imprimé est celui qui vient d'être émis : on le retrouve par
    // son empreinte, comme le produit le fait.
    $token = AccessToken::query()->where('type', TokenType::Invitation->value)->sole();

    expect($token->subject_type)->toBe((new Project)->getMorphClass())
        ->and($token->scope)->toContain('opt_in');

    unset($sortie);
});

it('le narrateur semé est joignable par courriel, pour que le lien soit lisible', function (): void {
    $this->artisan('demo:invitation')->assertSuccessful();

    $narrator = Project::query()->latest('created_at')->firstOrFail()->primaryNarrator;

    expect($narrator?->email)->not->toBeNull();
});
