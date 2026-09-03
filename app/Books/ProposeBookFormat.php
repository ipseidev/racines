<?php

declare(strict_types=1);

namespace App\Books;

use App\Enums\BookFormat;

/**
 * Le format proposé pour la matière recueillie.
 *
 * La « sortie honorable » du PRD §10, contractualisée dès la vente : une part
 * des projets n'atteindra pas R-6, et la promesse porte sur **un résultat
 * adaptable** plutôt que sur un livre ou rien.
 *
 * Il n'existe pas de « aucun format ». Un `null` ici deviendrait un écran qui
 * ne dit rien à une famille qui a essayé — alors qu'un chapitre fondateur se
 * fabrique avec une seule histoire.
 */
final class ProposeBookFormat
{
    public static function for(BookReadiness $readiness): BookFormat
    {
        if ($readiness->isReady()) {
            return BookFormat::Book;
        }

        // Le « ou » du volume vaut aussi ici : quelqu'un qui parle beaucoup
        // et dont la transcription est courte a de la matière.
        $intermediate = $readiness->words >= (int) config('product.book.booklet_min_words')
            || $readiness->audioMinutes >= (float) config('product.book.booklet_min_audio_minutes');

        return $intermediate ? BookFormat::Booklet : BookFormat::FoundingChapter;
    }
}
