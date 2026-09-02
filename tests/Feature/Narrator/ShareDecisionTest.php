<?php

declare(strict_types=1);

use App\Enums\ShareDecision;
use App\Enums\TokenType;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Recorded;

function recordedLink(?Story $story = null): array
{
    $story ??= Story::factory()->recorded()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story, ['record', 'decide_share']);

    return [$issued->plain, $story];
}

it('enregistre les trois décisions possibles depuis le lien d’enregistrement', function (string $decision): void {
    [$token, $story] = recordedLink();

    $this->post("/r/{$token}/share-decision", ['decision' => $decision])
        ->assertRedirect();

    expect($story->refresh()->share_decision)->toBe(ShareDecision::from($decision))
        ->and($story->share_decided_at)->not->toBeNull();
})->with(['share', 'keep_private', 'decide_later']);

it('ne valide rien tout de suite : l’histoire reste enregistrée', function (): void {
    [$token, $story] = recordedLink();

    $this->post("/r/{$token}/share-decision", ['decision' => 'share'])->assertRedirect();

    // Le narrateur a dit ce qu'il voulait ; il n'a pas encore de texte à
    // valider. La décision s'applique après la transcription.
    expect($story->refresh()->state)->toBeInstanceOf(Recorded::class)
        ->and($story->validated_at)->toBeNull()
        ->and($story->validated_via)->toBeNull()
        ->and($story->shared_at)->toBeNull();
});

it('refuse une décision venue d’un lien d’écoute', function (): void {
    $story = Story::factory()->recorded()->create();
    $listen = app(TokenService::class)->issue(TokenType::ListenStory, $story, ['listen']);

    $this->post("/r/{$listen->plain}/share-decision", ['decision' => 'share'])
        ->assertNotFound();

    expect($story->refresh()->share_decision)->toBeNull();
});

it('refuse une décision inventée', function (): void {
    [$token, $story] = recordedLink();

    $this->post("/r/{$token}/share-decision", ['decision' => 'peut-être'])
        ->assertSessionHasErrors('decision');

    expect($story->refresh()->share_decision)->toBeNull();
});

it('refuse une décision sans décision', function (): void {
    [$token] = recordedLink();

    $this->post("/r/{$token}/share-decision", [])->assertSessionHasErrors('decision');
});

it('laisse le narrateur changer d’avis avant la transcription', function (): void {
    [$token, $story] = recordedLink();

    $this->post("/r/{$token}/share-decision", ['decision' => 'share'])->assertRedirect();
    $first = $story->refresh()->share_decided_at;

    $this->post("/r/{$token}/share-decision", ['decision' => 'keep_private'])->assertRedirect();

    // Rien n'est irréversible avant que la décision soit appliquée : c'est
    // ce qui distingue un choix d'un piège.
    expect($story->refresh()->share_decision)->toBe(ShareDecision::KeepPrivate)
        ->and($story->share_decided_at)->not->toBeNull()
        ->and($first)->not->toBeNull();
});

it('refuse une décision sur une histoire seulement proposée', function (): void {
    $story = Story::factory()->proposed()->create();
    [$token] = recordedLink($story);

    // Décider du sort d'une histoire qui n'existe pas encore n'a pas de sens,
    // et laisserait une décision orpheline s'appliquer plus tard.
    $this->post("/r/{$token}/share-decision", ['decision' => 'share'])->assertNotFound();

    expect($story->refresh()->share_decision)->toBeNull();
});

it('ne rend jamais visible par la seule décision', function (): void {
    [$token, $story] = recordedLink();

    $this->post("/r/{$token}/share-decision", ['decision' => 'share'])->assertRedirect();

    expect($story->refresh()->isVisibleToFamily())->toBeFalse();
});
