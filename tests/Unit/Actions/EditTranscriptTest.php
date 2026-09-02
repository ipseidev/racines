<?php

declare(strict_types=1);

use App\Actions\EditTranscript;
use App\Enums\TranscriptKind;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\User;

it('crée une version corrigée sans rien modifier', function (): void {
    $story = Story::factory()->transcribed()->create();
    $verbatim = Transcript::factory()->create(['story_id' => $story->id]);
    $fluide = Transcript::factory()->fluide()->create(['story_id' => $story->id]);
    $editor = $story->project->owner;

    $edited = app(EditTranscript::class)->handle($fluide, 'Je me souviens de Kerhostin, chez ma grand-mère.', $editor);

    expect($edited->kind)->toBe(TranscriptKind::Edited)
        ->and($edited->version)->toBe($fluide->version + 1)
        ->and($edited->source_transcript_id)->toBe($fluide->id)
        ->and($edited->edited_by_type)->toBe('user')
        ->and($edited->edited_by_id)->toBe((string) $editor->id)
        ->and($edited->is_current)->toBeTrue();

    // Ni le verbatim ni le Fluide n'ont bougé : une correction ajoute.
    expect($verbatim->refresh()->text)->toContain('euh')
        ->and($fluide->refresh()->text)->toBe('Je me souviens de la maison de Kerhostin.');
});

it('laisse le verbatim courant parmi les verbatims', function (): void {
    $story = Story::factory()->transcribed()->create();
    $verbatim = Transcript::factory()->create(['story_id' => $story->id]);

    app(EditTranscript::class)->handle($verbatim, 'Un texte corrigé.', User::factory()->create());

    // La parole de la personne reste la référence de son espèce.
    expect($verbatim->refresh()->is_current)->toBeTrue()
        ->and($story->transcripts()->ofKind(TranscriptKind::Verbatim)->current()->count())->toBe(1);
});

it('garde tout l’historique des corrections', function (): void {
    $story = Story::factory()->transcribed()->create();
    $verbatim = Transcript::factory()->create(['story_id' => $story->id]);
    $editor = User::factory()->create();

    $first = app(EditTranscript::class)->handle($verbatim, 'Première correction.', $editor);
    $second = app(EditTranscript::class)->handle($verbatim, 'Deuxième correction.', $editor);

    expect($first->refresh()->is_current)->toBeFalse()
        ->and($second->is_current)->toBeTrue()
        ->and($second->version)->toBe($first->version + 1)
        ->and($story->transcripts()->ofKind(TranscriptKind::Edited)->count())->toBe(2);
});

it('donne à lire la dernière correction', function (): void {
    $story = Story::factory()->transcribed()->create();
    $verbatim = Transcript::factory()->create(['story_id' => $story->id]);

    app(EditTranscript::class)->handle($verbatim, 'Correction finale.', User::factory()->create());

    expect(Transcript::readableFor($story->refresh())?->text)->toBe('Correction finale.');
});

it('nettoie les espaces autour du texte corrigé', function (): void {
    $story = Story::factory()->transcribed()->create();
    $verbatim = Transcript::factory()->create(['story_id' => $story->id]);

    $edited = app(EditTranscript::class)->handle($verbatim, "  Un texte.\n", User::factory()->create());

    expect($edited->text)->toBe('Un texte.');
});
