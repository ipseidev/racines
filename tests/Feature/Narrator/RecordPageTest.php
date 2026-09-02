<?php

declare(strict_types=1);

use App\Enums\AddressForm;
use App\Enums\TokenType;
use App\Models\Recording;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Validated;

function pageLink(Story $story): string
{
    return app(TokenService::class)->issue(TokenType::Record, $story)->plain;
}

it('rend la page d’enregistrement avec la question, l’état et les limites', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = pageLink($story);

    $this->get("/r/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('narrator/Record')
            ->where('question', $story->questionText())
            ->where('firstName', $story->narrator->first_name)
            ->where('addressForm', 'vous')
            ->where('state', 'proposed')
            ->where('limits.softWarningSeconds', 600)
            ->where('limits.hardStopSeconds', 1200)
            ->where('limits.maxBytes', 209_715_200)
            ->where('limits.segmentMilliseconds', 5000));
});

it('ne fait descendre jusqu’au navigateur ni identifiant d’histoire ni coordonnée', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = pageLink($story);

    $response = $this->get("/r/{$token}");
    $content = (string) $response->getContent();

    expect($content)->not->toContain($story->id)
        ->and($content)->not->toContain((string) $story->narrator->phone_e164)
        ->and($content)->toContain(hash('sha256', $story->id));
});

it('suit le tutoiement réglé sur le projet', function (): void {
    $story = Story::factory()->proposed()->create();
    $story->project->update(['address_form' => AddressForm::Tu]);

    $this->get('/r/'.pageLink($story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('addressForm', 'tu'));
});

it('dit à un narrateur qu’il a déjà répondu, avec la date', function (): void {
    $story = Story::factory()->recorded()->create();
    $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    $this->get('/r/'.pageLink($story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('narrator/AlreadyRecorded')
            ->where('recordedAt', $recording->confirmed_at?->toIso8601String())
            ->where('answerType', 'audio'));
});

it('sert la page amicale quand l’histoire est validée : le lien est révoqué', function (): void {
    $story = Story::factory()->toReview()->create();
    $token = pageLink($story);

    $story->state->transitionTo(Validated::class);

    $this->get("/r/{$token}")
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page
            ->component('narrator/LinkUnavailable')
            ->where('reason', 'revoked'));
});
