<?php

declare(strict_types=1);

use App\Actions\AcceptInvitation;
use App\Enums\Channel;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Project;
use App\Models\Story;
use App\States\Story\Proposed;
use Illuminate\Support\Facades\Notification;

/**
 * Le parcours de celle à qui on offre le cadeau, de bout en bout.
 *
 * Les deux moitiés existaient déjà — `demo:invitation` pour l'annonce, le
 * planificateur pour la question — mais rien ne les reliait, et c'est
 * précisément la couture qui est intéressante : entre l'acceptation et la
 * première question il y a **une nuit**, posée à dessein par
 * `AcceptInvitation` (« une question dans la minute donne l'impression d'une
 * machine qui attendait »). Un humain qui veut dérouler le parcours n'a pas
 * une nuit devant lui, et l'avancer à la main demande de connaître
 * `next_prompt_at`, `prompts:dispatch-due`, puis d'aller pêcher un lien que
 * `RedactTokens` masque partout.
 *
 * La commande avance l'horloge du projet et **appelle la vraie commande** :
 * elle n'enveloppe pas le planificateur, elle le déclenche.
 */
beforeEach(function (): void {
    Notification::fake();
});

it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:cadeau')->assertFailed();
});

it('étape 1 : l’annonce part par le vrai chemin, avec un mot de l’acheteuse', function (): void {
    $this->artisan('demo:cadeau --prenom=Suzanne')
        ->expectsOutputToContain('/i/')
        ->assertSuccessful();

    $project = Project::query()->latest('created_at')->firstOrFail();

    expect($project->status)->toBe(ProjectStatus::AwaitingAcceptance)
        // Sans mot personnel, la page d'annonce ne montre pas ce qu'un vrai
        // destinataire reçoit — et c'est justement ce qu'on vient éprouver.
        ->and($project->gift_message)->not->toBeNull()
        ->and($project->primaryNarrator?->first_name)->toBe('Suzanne')
        // Par courriel : en local les SMS partent dans le journal, où le
        // jeton est masqué à dessein.
        ->and($project->primaryNarrator?->email)->not->toBeNull();

    $token = AccessToken::query()->where('type', TokenType::Invitation->value)->sole();

    expect($token->scope)->toContain('opt_in')
        ->and($token->used_at)->toBeNull();
});

it('étape 2 refuse tant que l’annonce n’a pas été acceptée', function (): void {
    $this->artisan('demo:cadeau')->assertSuccessful();

    // Rien ne part avant l'acceptation : c'est l'invariant du bloc 10, et la
    // commande le dit plutôt que de le contourner.
    $this->artisan('demo:cadeau --question')->assertFailed();

    expect(Story::query()->count())->toBe(0);
});

it('étape 2 : la première question n’attend pas le lendemain', function (): void {
    $this->artisan('demo:cadeau')->assertSuccessful();

    $project = Project::query()->latest('created_at')->firstOrFail();
    app(AcceptInvitation::class)->handle($project, ['preferred_channel' => Channel::Email->value]);

    expect($project->refresh()->next_prompt_at?->isFuture())->toBeTrue();

    $this->artisan('demo:cadeau --question')
        ->expectsOutputToContain('/r/')
        ->assertSuccessful();

    $story = Story::query()->where('project_id', $project->id)->sole();

    expect($story->state->getValue())->toBe(Proposed::$name)
        ->and($story->question)->not->toBeNull();

    $token = AccessToken::query()
        ->where('type', TokenType::Record->value)
        ->where('subject_id', $story->id)
        ->latest('created_at')
        ->firstOrFail();

    expect($token->scope)->toBe(['record', 'decide_share']);
});

it('étape 2 rejouée rouvre le même lien, sans poser une deuxième question', function (): void {
    $this->artisan('demo:cadeau')->assertSuccessful();

    $project = Project::query()->latest('created_at')->firstOrFail();
    app(AcceptInvitation::class)->handle($project, ['preferred_channel' => Channel::Email->value]);

    $this->artisan('demo:cadeau --question')->assertSuccessful();
    $this->artisan('demo:cadeau --question')->assertSuccessful();

    // Un checkpoint qu'on ne peut pas rejouer n'est pas un checkpoint (T-155) :
    // relancer l'étape doit redonner le lien, pas empiler les questions.
    expect(Story::query()->where('project_id', $project->id)->count())->toBe(1);
});
