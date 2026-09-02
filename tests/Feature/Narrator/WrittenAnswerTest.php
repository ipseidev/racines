<?php

declare(strict_types=1);

use App\Enums\AnswerType;
use App\Enums\TokenType;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Proposed;
use App\States\Story\Recorded;

it('enregistre une réponse écrite et fait avancer l’histoire', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->post("/r/{$token}/written-answer", [
        'written_answer' => 'Ma mère faisait des confitures de coings chaque automne.',
    ])->assertRedirect("/r/{$token}");

    $story->refresh();

    expect($story->state)->toBeInstanceOf(Recorded::class)
        ->and($story->answer_type)->toBe(AnswerType::Text)
        ->and($story->written_answer)->toBe('Ma mère faisait des confitures de coings chaque automne.')
        ->and($story->recorded_at)->not->toBeNull();
});

it('refuse une réponse vide', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->post("/r/{$token}/written-answer", ['written_answer' => '   '])
        ->assertSessionHasErrors('written_answer');

    expect($story->refresh()->state)->toBeInstanceOf(Proposed::class);
});

it('refuse une réponse de plus de vingt mille caractères', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->post("/r/{$token}/written-answer", ['written_answer' => str_repeat('a', 20_001)])
        ->assertSessionHasErrors('written_answer');

    expect($story->refresh()->state)->toBeInstanceOf(Proposed::class);
});

it('accepte exactement vingt mille caractères', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->post("/r/{$token}/written-answer", ['written_answer' => str_repeat('a', 20_000)])
        ->assertSessionHasNoErrors();

    expect($story->refresh()->state)->toBeInstanceOf(Recorded::class);
});
