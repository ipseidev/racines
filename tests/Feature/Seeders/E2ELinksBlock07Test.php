<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Enums\ValidationVariant;
use App\Models\AccessToken;
use App\Services\Tokens\TokenService;
use App\States\Story\Proposed;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * Le décor du bloc 07 doit être vérifiable **des deux côtés**.
 *
 * La promesse du bloc n'est pas « elle peut masquer » : c'est « elle masque, et
 * la famille ne voit plus rien ». Or chaque scénario du bloc 07 vit dans son
 * propre projet — la variante de validation est un réglage de projet — et les
 * liens d'écoute du bloc 08 vivent chacun dans le leur. Sans lien famille sur
 * **ces** projets, la vérification qui compte le plus n'est jouable qu'en
 * fabriquant un jeton à la main dans tinker, ce qui veut dire : jamais.
 */
beforeEach(function (): void {
    $this->seed(E2ELinksSeeder::class);
});

/** Le jeton d'écoute apparié à un scénario du bloc 07. */
function pairedFamilyToken(string $scenario): AccessToken
{
    $token = AccessToken::query()
        ->where('token_hash', TokenService::hash(E2ELinksSeeder::token($scenario.'-famille')))
        ->first();

    expect($token)->not->toBeNull("Le lien famille apparié à « {$scenario} » manque au décor.");

    return $token;
}

it('apparie un lien d’écoute à chaque scénario de validation et de retrait', function (string $scenario): void {
    $token = pairedFamilyToken($scenario);

    expect($token->type)->toBe(TokenType::ListenProject);
})->with(['variant-a-later', 'variant-b', 'variant-b-share', 'withdraw']);

it('sert le lien apparié sur le projet du scénario, et pas sur un autre', function (): void {
    $story = AccessToken::query()
        ->where('token_hash', TokenService::hash(E2ELinksSeeder::token('withdraw')))
        ->firstOrFail()
        ->subject;

    $member = pairedFamilyToken('withdraw')->subject;

    expect($member->project_id)->toBe($story->project_id);
});

it('montre le récit partagé du scénario de retrait, pour qu’on puisse le voir disparaître', function (): void {
    $this->get('/l/'.E2ELinksSeeder::token('withdraw-famille'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('family/Home')
            ->has('stories', 1)
            ->where('stories.0.title', 'L’odeur du pain'));
});

it('ouvre un second lien en variante A, pour le chemin « décider plus tard »', function (): void {
    // Un lien par scénario, sur sa propre histoire : la variante A a **deux**
    // chemins — décider tout de suite, ou remettre à la relecture — et le
    // second n'était jouable qu'après avoir consommé le premier.
    $story = AccessToken::query()
        ->where('token_hash', TokenService::hash(E2ELinksSeeder::token('variant-a-later')))
        ->firstOrFail()
        ->subject;

    expect($story->project->validation_variant)->toBe(ValidationVariant::Immediate)
        ->and($story->state::class)->toBe(Proposed::class);
});

it('ne montre rien sur le scénario « garder pour moi », qui n’a pas encore décidé', function (): void {
    $this->get('/l/'.E2ELinksSeeder::token('variant-b-share-famille'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('family/Home')
            ->has('stories', 0));
});
