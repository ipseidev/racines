<?php

declare(strict_types=1);

use App\Books\BookLexiconCheck;
use App\Enums\TranscriptKind;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\LexiconEntry;
use App\Models\Story;
use App\Models\Transcript;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le contrôle des noms propres avant l'impression.
 *
 * La transcription se trompe sur les noms : « Kerhostin » devient
 * « Ker Rostin », un grand-oncle « Ambroise » devient « Ambroisie ». C'est la
 * coquille que la famille remarquera en premier, et la seule qu'elle ne
 * pardonnera pas — parce qu'elle porte sur un lieu ou sur quelqu'un.
 */
function chapitreAvecNoms(Book $book, array $noms): BookChapter
{
    $story = Story::factory()->validated()->create(['project_id' => $book->project_id]);

    Transcript::factory()->for($story)->create([
        'kind' => TranscriptKind::Fluide,
        'is_current' => true,
        'metadata' => ['proper_nouns' => $noms],
    ]);

    $chapter = new BookChapter(['position' => 10, 'included' => true]);
    $chapter->story()->associate($story);
    $chapter->book()->associate($book);
    $chapter->save();

    return $chapter;
}

it('liste les noms propres que le lexique ne connaît pas', function (): void {
    $book = Book::factory()->create();
    chapitreAvecNoms($book, ['Kerhostin', 'Ambroise']);

    // `project_id` n'est pas remplissable en masse : le lexique appartient
    // à un projet et ne change pas de famille.
    $entree = new LexiconEntry(['term' => 'Ambroise']);
    $entree->project()->associate($book->project);
    $entree->save();

    expect(app(BookLexiconCheck::class)->handle($book->refresh()))->toBe(['Kerhostin']);
});

it('ignore la casse et les accents', function (): void {
    $book = Book::factory()->create();
    chapitreAvecNoms($book, ['Élise', 'kerhostin']);

    foreach (['elise', 'Kerhostin'] as $term) {
        $entree = new LexiconEntry(['term' => $term]);
        $entree->project()->associate($book->project);
        $entree->save();
    }

    // « Élise » et « elise » sont le même mot pour qui l'a déjà vérifié.
    expect(app(BookLexiconCheck::class)->handle($book->refresh()))->toBe([]);
});

it('ne compte pas deux fois le même nom', function (): void {
    $book = Book::factory()->create();
    chapitreAvecNoms($book, ['Kerhostin']);
    chapitreAvecNoms($book, ['Kerhostin', 'Quiberon']);

    expect(app(BookLexiconCheck::class)->handle($book->refresh()))->toBe(['Kerhostin', 'Quiberon']);
});

it('ignore les chapitres exclus', function (): void {
    $book = Book::factory()->create();
    $exclu = chapitreAvecNoms($book, ['Kerhostin']);
    $exclu->forceFill(['included' => false])->save();

    expect(app(BookLexiconCheck::class)->handle($book->refresh()))->toBe([]);
});
