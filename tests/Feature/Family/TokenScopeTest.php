<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\Project;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\Route;

it('refuse un lien d’enregistrement sur l’espace famille', function (): void {
    $story = Story::factory()->shared()->create();
    $record = app(TokenService::class)->issue(TokenType::Record, $story, ['record']);

    // Le périmètre est déclaré sur la route : un jeton du narrateur n'ouvre
    // pas l'espace des proches, et le message d'erreur ne révèle pas
    // l'existence d'un jeton d'un autre périmètre.
    $this->get("/l/{$record->plain}")->assertNotFound();
});

it('refuse un proche d’un autre projet', function (): void {
    $story = Story::factory()->shared()->create();
    $stranger = FamilyMember::factory()->create(['project_id' => Project::factory()->create()->id]);
    $token = app(TokenService::class)->issue(TokenType::ListenProject, $stranger, ['listen']);

    $this->get("/l/{$token->plain}/stories/{$story->id}")->assertNotFound();

    // Sa propre page d'accueil existe, et elle est vide.
    $this->get("/l/{$token->plain}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('stories', 0));
});

it('un lien d’histoire n’ouvre que la sienne', function (): void {
    $story = Story::factory()->shared()->create();
    $other = Story::factory()->shared()->create(['project_id' => $story->project_id, 'narrator_id' => $story->narrator_id]);
    $member = FamilyMember::factory()->create(['project_id' => $story->project_id]);

    $token = app(TokenService::class)->issue(
        TokenType::ListenStory,
        $story,
        ['listen'],
        issuedTo: $member,
    );

    $this->get("/l/{$token->plain}/stories/{$story->id}")->assertOk();

    // L'autre histoire est pourtant partagée : c'est le périmètre du lien qui
    // la referme, pas son état.
    $this->get("/l/{$token->plain}/stories/{$other->id}")->assertNotFound();
});

it('un lien d’histoire mène droit à son histoire', function (): void {
    $story = Story::factory()->shared()->create();
    $member = FamilyMember::factory()->create(['project_id' => $story->project_id]);

    $token = app(TokenService::class)->issue(
        TokenType::ListenStory,
        $story,
        ['listen'],
        issuedTo: $member,
    );

    // Ni liste vide, ni liste complète : les deux trahiraient le périmètre.
    $this->get("/l/{$token->plain}")
        ->assertRedirect("/l/{$token->plain}/stories/{$story->id}");
});

it('refuse un lien d’histoire sans porteur identifié', function (): void {
    $story = Story::factory()->shared()->create();
    $token = app(TokenService::class)->issue(TokenType::ListenStory, $story, ['listen']);

    // Un lien personnel sans porteur ne se révoque pas pour une seule
    // personne, et la visibilité restreinte devient inapplicable.
    $this->get("/l/{$token->plain}")->assertNotFound();
});

it('refuse un lien révoqué avec une page amicale', function (): void {
    $story = Story::factory()->shared()->create();
    $member = FamilyMember::factory()->create(['project_id' => $story->project_id]);
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen']);

    app(TokenService::class)->revoke($issued->token, 'test');

    $this->get("/l/{$issued->plain}")
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page->component('family/LinkUnavailable'));
});

it('porte les trois protections sur toutes les routes famille', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->uri(), 'l/'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $middleware = collect($route->gatherMiddleware());
        $uri = (string) $route->uri();

        expect($middleware->contains(
            fn (mixed $name): bool => is_string($name) && str_starts_with($name, 'resolve.token:'),
        ))->toBeTrue("{$uri} ne résout pas son jeton")
            ->and($middleware->contains('no-store'))->toBeTrue("{$uri} sans no-store")
            ->and($middleware->contains(
                fn (mixed $name): bool => is_string($name) && str_starts_with($name, 'throttle:'),
            ))->toBeTrue("{$uri} sans limiteur");
    }
});
