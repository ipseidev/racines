<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\Narrator;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Deleted;
use App\States\Story\Hidden;
use App\States\Story\Shared;
use App\States\Story\Trashed;
use App\Support\SensitiveActs;
use App\Support\SensitiveGrant;

/**
 * Un lien d'enregistrement sur une histoire partagée.
 *
 * @return array{string, Story}
 */
function withdrawableLink(): array
{
    $story = Story::factory()->shared()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story, ['record', 'decide_share']);

    return [$issued->plain, $story];
}

/**
 * @return array{string, Narrator, string}
 */
function spaceWithGrant(): array
{
    $narrator = Narrator::factory()->primary()->create();
    $space = app(TokenService::class)->issue(TokenType::NarratorSpace, $narrator, ['read', 'withdraw']);
    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    return [$space->plain, $narrator, $grant->plain];
}

describe('la frontière de l’acte sensible', function (): void {
    it('n’exige rien pour l’histoire que le lien porte', function (): void {
        $story = Story::factory()->shared()->create();
        $issued = app(TokenService::class)->issue(TokenType::Record, $story, ['record']);

        expect(SensitiveActs::requiresGrant($story, $issued->token))->toBeFalse();
    });

    it('exige un code pour une autre histoire', function (): void {
        $story = Story::factory()->shared()->create();
        $issued = app(TokenService::class)->issue(TokenType::Record, $story, ['record']);
        $other = Story::factory()->shared()->create([
            'narrator_id' => $story->narrator_id,
            'project_id' => $story->project_id,
        ]);

        expect(SensitiveActs::requiresGrant($other, $issued->token))->toBeTrue();
    });

    it('exige un code depuis un jeton d’espace, même pour sa propre histoire', function (): void {
        [, $story] = withdrawableLink();
        $space = app(TokenService::class)->issue(TokenType::NarratorSpace, $story->narrator);

        // Le jeton d'espace ouvre *toutes* les histoires et a pu être obtenu
        // il y a longtemps : le code reste exigé.
        expect(SensitiveActs::requiresGrant($story, $space->token))->toBeTrue();
    });
});

it('masque l’histoire du lien sans code', function (): void {
    [$token, $story] = withdrawableLink();

    $this->post("/r/{$token}/hide")->assertRedirect();

    // Quelqu'un qui regrette ce qu'il vient de raconter doit pouvoir le
    // retirer tout de suite, sans attendre un SMS.
    expect($story->refresh()->state)->toBeInstanceOf(Hidden::class)
        ->and($story->previous_state)->toBe('shared')
        ->and($story->isVisibleToFamily())->toBeFalse();
});

it('masque en une seule requête après l’écran de confirmation', function (): void {
    [$token, $story] = withdrawableLink();

    $this->post("/r/{$token}/hide")->assertRedirect();

    expect($story->refresh()->hidden_at)->not->toBeNull();
});

it('exige un code pour masquer une autre histoire depuis l’espace', function (): void {
    [$space, $narrator] = spaceWithGrant();
    $story = Story::factory()->shared()->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);

    $this->post("/n/{$space}/stories/{$story->id}/hide")
        ->assertRedirect(route('narrator.space.otp.show', ['token' => $space]));

    expect($story->refresh()->state)->toBeInstanceOf(Shared::class);
});

it('remet une histoire masquée là d’où elle venait', function (): void {
    [$space, $narrator, $grant] = spaceWithGrant();
    $story = Story::factory()->hidden('shared')->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);

    $this->withCookie(SensitiveGrant::COOKIE, $grant)
        ->post("/n/{$space}/stories/{$story->id}/unhide")
        ->assertRedirect();

    expect($story->refresh()->state)->toBeInstanceOf(Shared::class)
        ->and($story->previous_state)->toBeNull();
});

it('met à la corbeille puis restaure dans les trente jours', function (): void {
    [$space, $narrator, $grant] = spaceWithGrant();
    $story = Story::factory()->shared()->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);

    $this->withCookie(SensitiveGrant::COOKIE, $grant)
        ->post("/n/{$space}/stories/{$story->id}/trash")
        ->assertRedirect();

    expect($story->refresh()->state)->toBeInstanceOf(Trashed::class);

    $this->travel(29)->days();
    $fresh = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    $this->withCookie(SensitiveGrant::COOKIE, $fresh->plain)
        ->post("/n/{$space}/stories/{$story->id}/restore")
        ->assertRedirect();

    expect($story->refresh()->state)->toBeInstanceOf(Shared::class)
        ->and($story->trashed_at)->toBeNull();
});

it('refuse la restauration passé trente jours', function (): void {
    [$space, $narrator] = spaceWithGrant();
    $story = Story::factory()->trashed('shared')->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);
    $story->forceFill(['trashed_at' => now()->subDays(31)])->save();

    $this->travel(1)->minute();
    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    // La corbeille n'est pas un archivage déguisé : un produit qui promet
    // trente jours tient trente jours, pas trente-et-un.
    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->post("/n/{$space}/stories/{$story->id}/restore")
        ->assertSessionHasErrors('restore');

    expect($story->refresh()->state)->toBeInstanceOf(Trashed::class);
});

it('ne supprime qu’avec un code et le mot SUPPRIMER', function (): void {
    [$space, $narrator, $grant] = spaceWithGrant();
    $story = Story::factory()->trashed('shared')->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);

    $this->withCookie(SensitiveGrant::COOKIE, $grant)
        ->post("/n/{$space}/stories/{$story->id}/delete", ['confirmation' => 'supprimer'])
        ->assertSessionHasErrors('confirmation');

    expect($story->refresh()->state)->toBeInstanceOf(Trashed::class);

    $fresh = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    $this->withCookie(SensitiveGrant::COOKIE, $fresh->plain)
        ->post("/n/{$space}/stories/{$story->id}/delete", ['confirmation' => 'SUPPRIMER'])
        ->assertRedirect();

    expect($story->refresh()->state)->toBeInstanceOf(Deleted::class)
        ->and($story->deletion_requested_by?->value)->toBe('narrator');
});

it('avertit des exemplaires déjà imprimés', function (): void {
    [$space, $narrator] = spaceWithGrant();
    $story = Story::factory()->inBook()->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);
    $story->forceFill(['printed_in_book' => true])->save();

    $this->get("/n/{$space}")
        ->assertInertia(fn ($page) => $page
            ->where('stories.0.printedInBook', true)
            // Ce qui est imprimé est imprimé : le taire serait promettre
            // l'impossible.
            ->where('printedCopiesWarning', __('narrator.withdrawals.printed_copies_warning')),
        );
});

it('refuse de masquer l’histoire d’un autre depuis un lien d’enregistrement', function (): void {
    [$token] = withdrawableLink();
    $stranger = Story::factory()->shared()->create();

    $this->post("/r/{$token}/hide", ['story' => $stranger->id])->assertRedirect();

    // La route ne prend pas d'histoire en paramètre : elle agit sur celle du
    // jeton, et rien d'autre.
    expect($stranger->refresh()->state)->toBeInstanceOf(Shared::class);
});
