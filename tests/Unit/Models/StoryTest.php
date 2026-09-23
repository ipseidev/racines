<?php

declare(strict_types=1);

use App\Enums\AddressForm;
use App\Enums\GrammaticalGender;
use App\Enums\StoryVisibility;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\States\Story\Recorded;
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

/*
 * L'intitulé d'une question du corpus, tel que la narratrice l'a lu.
 *
 * Tant que l'histoire attend, il se calcule : tutoiement du projet, genre de
 * la narratrice, texte courant du corpus. Dès qu'elle est enregistrée, il est
 * photographié — une reformulation du corpus ne doit jamais changer, dans le
 * livre ou l'export, la question à laquelle elle a répondu.
 */
it('tutoie et accorde la question quand le projet tutoie', function (): void {
    $project = Project::factory()->create(['address_form' => AddressForm::Tu]);
    Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'grammatical_gender' => GrammaticalGender::Feminine,
    ]);
    $question = Question::factory()->create([
        'text' => 'Où êtes-vous né{|e} ?',
        'text_tu' => 'Où es-tu né{|e} ?',
    ]);

    $story = Story::factory()->forProject($project->refresh())->proposed()->create(['question_id' => $question->id]);

    expect($story->questionText())->toBe('Où es-tu née ?');
});

it('photographie l’intitulé à l’enregistrement, et n’en change plus', function (): void {
    $project = Project::factory()->create(['address_form' => AddressForm::Tu]);
    Narrator::factory()->primary()->create(['project_id' => $project->id, 'grammatical_gender' => null]);
    $question = Question::factory()->create([
        'text' => 'Où êtes-vous né{|e} ?',
        'text_tu' => 'Où es-tu né{|e} ?',
    ]);
    $story = Story::factory()->forProject($project->refresh())->proposed()->create(['question_id' => $question->id]);

    $story->state->transitionTo(Recorded::class);

    $question->update(['text_tu' => 'Raconte le jour de ta naissance.']);

    expect($story->refresh()->question_text)->toBe('Où es-tu né·e ?')
        ->and($story->questionText())->toBe('Où es-tu né·e ?');
});

it('ne photographie pas une question personnalisée', function (): void {
    $story = Story::factory()->proposed()->create([
        'question_id' => null,
        'custom_question_text' => 'Raconte-nous la maison de Marseille.',
    ]);

    $story->state->transitionTo(Recorded::class);

    expect($story->refresh()->question_text)->toBeNull()
        ->and($story->questionText())->toBe('Raconte-nous la maison de Marseille.');
});
