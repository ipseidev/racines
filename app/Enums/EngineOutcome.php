<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Ce qu'une relance a produit.
 *
 * C'est la colonne qui fait du moteur un actif défendable plutôt qu'une
 * collection de messages : sans elle, on saurait combien on a relancé, pas si
 * ça a servi.
 */
enum EngineOutcome: string
{
    case Resumed = 'resumed';
    case NoEffect = 'no_effect';
}
