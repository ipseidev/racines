<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\Project;
use Laravel\Pennant\Feature;

/**
 * Un proche peut-il valider à la place du narrateur, sur mandat écrit.
 *
 * Fermé par défaut, et il faut que ça se voie : déléguer la validation est
 * une exception au principe « le narrateur est souverain », consentie par
 * lui, révocable par lui. Un projet ne l'ouvre que sur demande, et
 * `GrantMandate` exige en plus un consentement journalisé.
 */
final class MandateDelegation
{
    public string $name = 'mandate-delegation';

    public function resolve(Project $project): bool
    {
        return false;
    }

    public static function isOpenFor(Project $project): bool
    {
        return Feature::for($project)->active(self::class);
    }
}
