<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Ce qu'il faut savoir pour transformer une page en PDF.
 *
 * Un objet plutôt que quatre paramètres : le format du livre est arrêté
 * (16 × 24 cm, T-179) mais il se lit dans la configuration, et une signature
 * à rallonge finirait par être appelée avec la largeur et la hauteur
 * inversées — une erreur qu'on ne voit qu'en ouvrant le PDF.
 *
 * Les marges sont **en CSS**, pas ici : Paged.js les utilise pour composer
 * les pages, et des marges déclarées côté Chromium se cumuleraient aux
 * siennes.
 */
final readonly class PdfOptions
{
    public function __construct(
        public float $widthMm,
        public float $heightMm,
        /** Le rendu attend ce drapeau : Paged.js pagine après le chargement. */
        public string $waitForFunction = 'window.PAGEDJS_DONE === true',
        public int $timeoutSeconds = 300,
    ) {}

    /** Le format du livre, tel que la configuration produit le décide. */
    public static function book(): self
    {
        /** @var array{0: int|float, 1: int|float} $trim */
        $trim = config('product.book.trim_size_mm');

        return new self((float) $trim[0], (float) $trim[1]);
    }
}
