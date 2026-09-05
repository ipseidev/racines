<?php

declare(strict_types=1);

use App\Enums\ShareDecision;
use App\Enums\TokenType;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Recorded;
use Inertia\Testing\AssertableInertia;

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

/*
 * Ce que le geste renvoie à l'écran.
 *
 * Sur un vrai téléphone, « Partager avec mes proches » a paru ne rien faire :
 * la décision était enregistrée — deux fois, parce que le narrateur a cliqué
 * deux fois — mais l'écran ne bougeait pas. Le serveur répond `back()`, qu'une
 * requête Inertia suit d'un GET ; encore faut-il que ce GET rende la page
 * d'enregistrement **en connaissant la décision**, sinon l'écran redessine
 * exactement ce qu'il montrait déjà (T-176).
 */
it('rend la page d’enregistrement en portant la décision prise', function (): void {
    [$token, $story] = recordedLink();

    $reponse = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => '',
        'Referer' => "/r/{$token}",
    ])->post("/r/{$token}/share-decision", ['decision' => 'share']);

    $reponse->assertRedirect();

    // `withHeaders` persiste d'une requête à l'autre : sans ce nettoyage, le
    // GET repart en requête Inertia avec une version vide, et reçoit un 409.
    $this->flushHeaders();

    // La page qui suit est `AlreadyRecorded` — l'histoire **est** enregistrée
    // — et c'est elle qui doit porter l'accusé de réception. Sans lui, la
    // personne atterrit sur « vous avez déjà répondu » sans savoir que c'est
    // elle qui vient de répondre.
    $this->get($reponse->headers->get('Location'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('narrator/AlreadyRecorded')
            ->where('shareDecision', 'share')
            ->where('flash.status', __('narrator.share_decision.recorded.share')));
});
