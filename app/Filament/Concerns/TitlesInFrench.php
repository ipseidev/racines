<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Resources\Pages\ListRecords;

/**
 * Le titre d'une page de liste, tel qu'il est écrit.
 *
 * Filament dérive le titre du libellé de la ressource en le passant par une
 * mise en capitales à l'anglaise : « Les histoires » devient « Les
 * Histoires ». En français, seul le premier mot prend la majuscule — et le
 * produit soigne son français jusque dans le back-office, parce que les mots
 * qu'une équipe lit toute la journée finissent par décider comment elle écrit
 * aux familles.
 *
 * @phpstan-require-extends ListRecords
 */
trait TitlesInFrench
{
    public function getTitle(): string
    {
        return static::getResource()::getPluralModelLabel();
    }
}
