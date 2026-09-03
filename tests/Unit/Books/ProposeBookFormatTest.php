<?php

declare(strict_types=1);

use App\Books\BookReadiness;
use App\Books\ProposeBookFormat;
use App\Enums\BookFormat;

/**
 * Le format proposé selon la matière recueillie.
 *
 * C'est la « sortie honorable » du PRD §10, contractualisée dès la vente :
 * une part des projets n'atteindra pas R-6, et la promesse porte sur **un
 * résultat adaptable** plutôt que sur un livre ou rien.
 *
 * Trois formats, et le troisième est celui qui compte. « Chapitre fondateur »
 * n'est pas un lot de consolation : c'est un objet relié, court, qui existe.
 * Une famille qui a raconté trois histoires a raconté trois histoires de plus
 * que si on ne lui avait rien demandé, et lui rendre un export ZIP serait
 * transformer une réussite modeste en échec.
 */
function readiness(int $words, float $minutes = 0.0, int $pages = 0, int $themes = 0): BookReadiness
{
    return new BookReadiness(
        words: $words,
        audioMinutes: $minutes,
        estimatedPages: $pages,
        themes: $themes,
        chapters: 1,
        sensitiveUndecided: 0,
    );
}

it('propose le livre quand la matière est là', function (): void {
    $ready = readiness(words: 16_000, minutes: 95, pages: 62, themes: 6);

    expect($ready->isReady())->toBeTrue()
        ->and(ProposeBookFormat::for($ready))->toBe(BookFormat::Book);
});

it('propose le livret pour une matière intermédiaire', function (): void {
    // Vingt-quatre à soixante pages, même qualité de fabrication : ce n'est
    // pas un livre au rabais, c'est un livre plus court.
    expect(ProposeBookFormat::for(readiness(words: 5_000, pages: 30, themes: 3)))
        ->toBe(BookFormat::Booklet);
});

it('propose le livret sur la durée d’audio seule', function (): void {
    // Quelqu'un qui parle beaucoup et dont la transcription est courte a de
    // la matière : le « ou » du volume vaut aussi pour le format.
    expect(ProposeBookFormat::for(readiness(words: 900, minutes: 30, pages: 8)))
        ->toBe(BookFormat::Booklet);
});

it('propose le chapitre fondateur quand la matière est faible', function (): void {
    // Trois histoires racontées valent mieux que rien, et méritent un objet.
    expect(ProposeBookFormat::for(readiness(words: 1_200, minutes: 8, pages: 6)))
        ->toBe(BookFormat::FoundingChapter);
});

it('ne propose jamais « rien »', function (): void {
    // Même sur un projet vide : le format proposé existe toujours, et c'est
    // délibéré. Un `null` ici deviendrait un écran qui ne dit rien à une
    // famille qui a essayé.
    expect(ProposeBookFormat::for(readiness(words: 0)))
        ->toBe(BookFormat::FoundingChapter);
});

it('borne le livret par ses deux seuils de configuration', function (): void {
    expect((int) config('product.book.booklet_min_words'))->toBe(3_000)
        ->and((int) config('product.book.booklet_min_audio_minutes'))->toBe(25);
});
