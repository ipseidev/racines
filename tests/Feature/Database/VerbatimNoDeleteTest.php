<?php

declare(strict_types=1);

use App\Enums\TranscriptKind;
use App\Models\Story;
use App\Models\Transcript;
use App\States\Story\Deleted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('refuse de supprimer le verbatim d’une histoire vivante, même en SQL direct', function (): void {
    $transcript = Transcript::factory()->create();

    // Le garde-fou vit en base : aucun chemin de code ne le contourne.
    //
    // Le `DELETE` refusé avorte la transaction Postgres courante, ce qui est
    // le comportement normal : on l'isole dans un point de reprise pour que
    // le reste du test puisse encore interroger la base.
    expect(function () use ($transcript): void {
        DB::transaction(fn () => DB::table('transcripts')->where('id', $transcript->id)->delete());
    })->toThrow(QueryException::class);

    expect(Transcript::query()->whereKey($transcript->id)->exists())->toBeTrue();
});

it('refuse aussi par le modèle, avec un message lisible', function (): void {
    $transcript = Transcript::factory()->create();

    // Mieux vaut une exception qui explique qu'une erreur Postgres remontée
    // jusqu'à un écran.
    expect(fn () => $transcript->delete())
        ->toThrow(RuntimeException::class, "ne se supprime pas tant qu'elle vit");

    expect(Transcript::query()->whereKey($transcript->id)->exists())->toBeTrue();
});

it('laisse supprimer le verbatim d’une histoire supprimée', function (): void {
    $story = Story::factory()->trashed()->create();
    $transcript = Transcript::factory()->create(['story_id' => $story->id]);

    $story->state->transitionTo(Deleted::class);

    // L'histoire est supprimée : c'est le seul cas où le verbatim s'effacé,
    // et c'est `PurgeDeletedStory` (bloc 07) qui le fera.
    $transcript->refresh()->delete();

    expect(Transcript::query()->whereKey($transcript->id)->exists())->toBeFalse();
});

it('laisse supprimer un rendu Fluide et une correction', function (): void {
    $story = Story::factory()->transcribed()->create();

    $fluide = Transcript::factory()->fluide()->create(['story_id' => $story->id]);
    $edited = Transcript::factory()->edited()->create(['story_id' => $story->id]);

    // Le rendu IA est réversible par construction : on peut le refaire.
    $fluide->delete();
    $edited->delete();

    expect(Transcript::query()->where('story_id', $story->id)->count())->toBe(0);
});

it('n’a jamais deux textes courants de même nature pour une histoire', function (): void {
    $story = Story::factory()->transcribed()->create();

    Transcript::factory()->create(['story_id' => $story->id]);

    expect(function () use ($story): void {
        DB::transaction(fn () => Transcript::factory()->create(['story_id' => $story->id]));
    })->toThrow(QueryException::class);
});

it('donne à lire la correction, puis le Fluide, puis le verbatim', function (): void {
    $story = Story::factory()->transcribed()->create();

    $verbatim = Transcript::factory()->create(['story_id' => $story->id]);

    expect(Transcript::readableFor($story->refresh())?->id)->toBe($verbatim->id);

    $fluide = Transcript::factory()->fluide()->create(['story_id' => $story->id]);

    expect(Transcript::readableFor($story->refresh())?->id)->toBe($fluide->id);

    $edited = Transcript::factory()->edited()->create(['story_id' => $story->id]);

    expect(Transcript::readableFor($story->refresh())?->id)->toBe($edited->id)
        ->and(Transcript::readableFor($story)?->kind)->toBe(TranscriptKind::Edited);
});
