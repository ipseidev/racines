<?php

declare(strict_types=1);

use App\Actions\AttachPhoto;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\StoryState;
use Illuminate\Support\Facades\Storage;

/**
 * Une photo suit la visibilité de son histoire.
 *
 * Critère de sortie du bloc : **aucune photo n'est servie sans passer par
 * `VisibleStoriesForFamilyMember`**. C'est le même invariant que pour le
 * texte et la voix, et il serait facile de le perdre ici : une photo est un
 * fichier, et un fichier se sert par une URL qu'on peut oublier de protéger.
 *
 * Le test attaque par la route, pas par le présentateur : c'est la route
 * qu'un navigateur appelle.
 */
beforeEach(function (): void {
    Storage::fake('r2');
});

it('ne sert aucune photo d’une histoire non partagée', function (string $state): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    app(AttachPhoto::class)->handle($story, photoFile(), $narrator, 'Un souvenir');

    // L'histoire n'est pas partagée : elle n'existe pas pour les proches.
    $story->forceFill(['state' => $state])->save();

    $member = FamilyMember::factory()->create(['project_id' => $story->project_id]);
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen', 'react']);

    test()->get("/l/{$issued->plain}/stories/{$story->id}")->assertNotFound();
})->with(fn (): array => StoryState::all()
    // `all()` rend une **collection** dont les clés sont les noms d'états :
    // ce sont les clés qui nous intéressent, pas les noms de classes.
    ->keys()
    ->reject(fn (string $state): bool => in_array($state, ['shared', 'in_book'], true))
    ->values()
    ->all());

it('sert les photos d’une histoire partagée, avec des URL temporaires', function (): void {
    $story = Story::factory()->shared()->create();
    $narrator = narratorOf($story);

    app(AttachPhoto::class)->handle($story, photoFile(), $narrator, 'Le mariage');

    $member = FamilyMember::factory()->create(['project_id' => $story->project_id]);
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen', 'react']);

    $this->get("/l/{$issued->plain}/stories/{$story->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('photos', 1)
            ->where('photos.0.caption', 'Le mariage')
            ->has('photos.0.url')
            ->has('photos.0.thumbUrl'));
});

it('nomme le déposant dans le texte alternatif', function (): void {
    $story = Story::factory()->shared()->create();

    $claire = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'display_name' => 'Claire',
        'can_contribute' => true,
    ]);

    app(AttachPhoto::class)->handle($story, photoFile(), $claire, null);

    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $claire, ['listen', 'react']);

    // « Photo jointe par Claire » plutôt que « Photo » : un lecteur d'écran
    // qui annonce dix fois « Photo » ne dit rien.
    $this->get("/l/{$issued->plain}/stories/{$story->id}")
        ->assertInertia(fn ($page) => $page
            ->where('photos.0.alt', 'Photo jointe par Claire'));
});

it('n’offre le bouton d’ajout qu’à qui peut contribuer', function (): void {
    $story = Story::factory()->shared()->create();

    $spectateur = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'can_contribute' => false,
    ]);
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $spectateur, ['listen', 'react']);

    // Un bouton grisé invite à demander pourquoi ; un bouton absent non.
    $this->get("/l/{$issued->plain}/stories/{$story->id}")
        ->assertInertia(fn ($page) => $page->where('canContribute', false));
});
