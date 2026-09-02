<?php

declare(strict_types=1);

use App\Enums\StoryVisibility;
use App\Models\Question;
use App\Models\Story;
use App\States\Story\Shared;

it('is never visible to family unless shared or in_book', function (): void {
    foreach (['proposed', 'recorded', 'transcribed', 'to_review', 'validated', 'hidden', 'archived', 'trashed', 'deleted'] as $state) {
        expect(Story::factory()->create(['state' => $state])->isVisibleToFamily())->toBeFalse();
    }

    expect(Story::factory()->shared()->create()->isVisibleToFamily())->toBeTrue()
        ->and(Story::factory()->inBook()->create()->isVisibleToFamily())->toBeTrue();
});

it('cache aux proches une histoire réservée au livre, même partagée', function (): void {
    $story = Story::factory()->shared()->create(['visibility' => StoryVisibility::BookOnly]);

    expect($story->state)->toBeInstanceOf(Shared::class)
        ->and($story->isVisibleToFamily())->toBeFalse();
});

it('restreint la visibilité sans la refuser', function (): void {
    $story = Story::factory()->shared()->create(['visibility' => StoryVisibility::Restricted]);

    expect($story->isVisibleToFamily())->toBeTrue();
});

it('expose le texte de la question, du corpus ou personnalisée', function (): void {
    $question = Question::factory()->create(['text' => 'Quel métier rêviez-vous de faire ?']);

    expect(Story::factory()->create(['question_id' => $question->id])->questionText())
        ->toBe('Quel métier rêviez-vous de faire ?');

    expect(Story::factory()->create([
        'question_id' => null,
        'custom_question_text' => 'Raconte-nous la maison de Marseille.',
    ])->questionText())->toBe('Raconte-nous la maison de Marseille.');
});

it('numérote les histoires par projet', function (): void {
    $first = Story::factory()->create();
    $second = Story::factory()->forProject($first->project)->create();

    expect($first->sequence)->toBe(1)
        ->and($second->sequence)->toBe(2);
});
