<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Une histoire que ce proche ne peut pas écouter.
 *
 * Rendue en **404 amical** : une page en langage simple, avec un retour vers
 * la liste. Jamais l'erreur du framework — un `abort(404)` nu affiche une
 * page technique en anglais, ce que la convention §16 interdit sur les pages
 * à jeton, et qui plus est sans nonce sur ses styles.
 *
 * Le message ne dit pas **pourquoi** : ni « pas encore validée », ni
 * « masquée », ni « réservée au livre ». Un proche qui apprendrait qu'une
 * histoire existe mais lui est refusée en saurait déjà trop.
 */
final class StoryUnavailable extends HttpException
{
    public static function make(): self
    {
        return new self(404, 'This story is not available to this family member.');
    }
}
