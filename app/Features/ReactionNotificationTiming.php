<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\Project;
use Laravel\Pennant\Feature;

/**
 * Quand prévenir le narrateur qu'on l'a écouté.
 *
 * Micro-expérience H2. `immediate` : dans la minute, tant que l'élan est là.
 * `next-morning` : un digest à 9 h, pour ne pas faire vibrer un téléphone à
 * 23 h chez une personne de 85 ans.
 *
 * Le dossier refuse de trancher sans mesure, et le drapeau est par projet et
 * mémorisé : une famille ne doit pas changer de régime en cours de route,
 * sinon la comparaison ne veut rien dire.
 */
final class ReactionNotificationTiming
{
    public string $name = 'reaction-notification-timing';

    public const IMMEDIATE = 'immediate';

    public const NEXT_MORNING = 'next-morning';

    public function resolve(Project $project): string
    {
        return self::IMMEDIATE;
    }

    public static function isImmediateFor(Project $project): bool
    {
        return Feature::for($project)->value(self::class) === self::IMMEDIATE;
    }
}
