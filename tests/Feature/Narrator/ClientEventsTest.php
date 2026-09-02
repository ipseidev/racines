<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\ClientEvent;
use App\Models\Story;
use App\Services\Tokens\TokenService;

it('enregistre les événements que le navigateur rapporte', function (string $event): void {
    $story = Story::factory()->proposed()->create();
    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->postJson("/r/{$token}/events", ['event' => $event, 'payload' => ['platform' => 'ios']])
        ->assertStatus(202);

    $stored = ClientEvent::query()->sole();

    expect($stored->event)->toBe($event)
        ->and($stored->story_id)->toBe($story->id)
        ->and($stored->payload)->toBe(['platform' => 'ios']);
})->with([
    'mic_denied',
    'recording_started',
    'page_hidden',
    'resumed_from_draft',
    'soft_warning_reached',
]);

it('refuse un événement qui n’est pas dans la liste fermée', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->postJson("/r/{$token}/events", ['event' => 'inventé'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('event');
});

it('refuse un payload de plus de deux kilo-octets', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->postJson("/r/{$token}/events", [
        'event' => 'page_hidden',
        'payload' => ['bruit' => str_repeat('x', 3000)],
    ])->assertStatus(422)->assertJsonValidationErrors('payload');
});

it('limite les événements à cent vingt par minute et par lien', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    for ($i = 0; $i < 120; $i++) {
        $this->postJson("/r/{$token}/events", ['event' => 'page_hidden'])->assertStatus(202);
    }

    $this->postJson("/r/{$token}/events", ['event' => 'page_hidden'])->assertStatus(429);
});
