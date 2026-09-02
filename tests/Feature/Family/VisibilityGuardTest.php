<?php

declare(strict_types=1);

use App\Actions\HideStoryAction;
use App\Actions\SetStoryVisibility;
use App\Enums\StoryVisibility;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\StoryState;

/**
 * Le test le plus important du produit.
 *
 * « Rien n'est visible des proches avant l'état `validated` », et même après,
 * seulement si le narrateur a partagé. Chaque état de la machine est éprouvé
 * un par un : une régression ici n'est pas un bug d'affichage, c'est une
 * trahison de la promesse faite à la personne qui a raconté.
 *
 * @return array{string, FamilyMember, Story}
 */
function listenLink(Story $story): array
{
    $member = FamilyMember::factory()->create(['project_id' => $story->project_id]);
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen', 'react']);

    return [$issued->plain, $member, $story];
}

/**
 * Les onze états, chacun avec la fabrique qui l'atteint.
 *
 * @return array<string, callable(): Story>
 */
function storyInEveryState(): array
{
    return [
        'proposed' => fn (): Story => Story::factory()->proposed()->create(),
        'recorded' => fn (): Story => Story::factory()->recorded()->create(),
        'transcribed' => fn (): Story => Story::factory()->transcribed()->create(),
        'to_review' => fn (): Story => Story::factory()->toReview()->create(),
        'validated' => fn (): Story => Story::factory()->validated()->create(),
        'shared' => fn (): Story => Story::factory()->shared()->create(),
        'in_book' => fn (): Story => Story::factory()->inBook()->create(),
        'hidden' => fn (): Story => Story::factory()->hidden('shared')->create(),
        'archived' => fn (): Story => Story::factory()->archived('shared')->create(),
        'trashed' => fn (): Story => Story::factory()->trashed('shared')->create(),
        'deleted' => fn (): Story => Story::factory()->deleted()->create(),
    ];
}

it('éprouve exactement les états déclarés par la machine', function (): void {
    // Si un état est ajouté sans être éprouvé ici, ce test tombe : c'est la
    // seule façon de garantir que la couverture reste complète.
    $declared = array_map(
        fn (string $class): string => (new $class(new Story))->getValue(),
        array_values(StoryState::all()->all()),
    );

    sort($declared);
    $covered = array_keys(storyInEveryState());
    sort($covered);

    expect($covered)->toBe($declared);
});

it('n’ouvre une histoire aux proches que si elle est partagée', function (string $state): void {
    $story = storyInEveryState()[$state]();
    [$token] = listenLink($story);

    $response = $this->get("/l/{$token}/stories/{$story->id}");

    $visible = in_array($state, ['shared', 'in_book'], true);

    if ($visible) {
        $response->assertOk();

        return;
    }

    // 404 **amical** : une page en langage simple, jamais une erreur
    // technique, et aucun contenu de l'histoire dans la réponse.
    $response->assertNotFound();

    $content = (string) $response->getContent();

    // L'identifiant, lui, vient de l'URL que le visiteur a présentée : le
    // renvoyer ne lui apprend rien. Ce qui ne doit pas fuiter, c'est ce
    // qu'il ne connaît pas — la question, le narrateur, et le titre quand il
    // y en a un (voir le test suivant, qui en pose un exprès).
    foreach ([$story->questionText(), $story->narrator_id, $story->title] as $secret) {
        if (is_string($secret) && $secret !== '') {
            expect($content)->not->toContain($secret);
        }
    }
})->with(array_keys(storyInEveryState()));

it('ne laisse fuiter aucun contenu d’une histoire non partagée', function (): void {
    $story = Story::factory()->toReview()->create(['title' => 'Un titre confidentiel']);
    [$token] = listenLink($story);

    $content = (string) $this->get("/l/{$token}/stories/{$story->id}")->getContent();

    expect($content)->not->toContain('Un titre confidentiel')
        ->and($content)->not->toContain((string) $story->questionText())
        ->and($content)->not->toContain($story->narrator_id);
});

it('referme une histoire réservée au livre', function (): void {
    $story = Story::factory()->shared()->create();
    $story->forceFill(['visibility' => StoryVisibility::BookOnly])->save();
    [$token] = listenLink($story);

    // Le narrateur a choisi le papier, pas la diffusion.
    $this->get("/l/{$token}/stories/{$story->id}")->assertNotFound();
});

it('respecte la liste des proches autorisés', function (): void {
    $story = Story::factory()->shared()->create();
    $allowed = FamilyMember::factory()->create(['project_id' => $story->project_id]);
    $excluded = FamilyMember::factory()->create(['project_id' => $story->project_id]);

    app(SetStoryVisibility::class)
        ->handle($story, StoryVisibility::Restricted, [$allowed->id]);

    $tokens = app(TokenService::class);

    $this->get('/l/'.$tokens->issue(TokenType::ListenProject, $allowed, ['listen'])->plain."/stories/{$story->id}")
        ->assertOk();

    $this->get('/l/'.$tokens->issue(TokenType::ListenProject, $excluded, ['listen'])->plain."/stories/{$story->id}")
        ->assertNotFound();
});

it('masquer une histoire la retire immédiatement de l’écoute', function (): void {
    $story = Story::factory()->shared()->create();
    [$token] = listenLink($story);

    $this->get("/l/{$token}/stories/{$story->id}")->assertOk();

    app(HideStoryAction::class)->handle($story);

    // Immédiatement : pas au prochain rafraîchissement, pas dans une minute.
    $this->get("/l/{$token}/stories/{$story->id}")->assertNotFound();
});

it('refuse une histoire d’un autre projet', function (): void {
    $story = Story::factory()->shared()->create();
    $stranger = Story::factory()->shared()->create();
    [$token] = listenLink($story);

    $this->get("/l/{$token}/stories/{$stranger->id}")->assertNotFound();
});
