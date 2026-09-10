<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;
use App\Models\Project;
use App\Models\Story;
use Illuminate\Database\Eloquent\Model;

/**
 * La locale de la requête en cours.
 *
 * Laravel ne connaît que la langue (`app()->getLocale()` vaut `it`) ; la
 * locale entière (`it-CH`) vit ici, liée au conteneur pour la durée de la
 * requête — et remise à zéro avec lui d'un test à l'autre. `SetLocale` la
 * pose, `ResolveAccessToken` peut la préciser d'après le projet, et tout le
 * reste la lit.
 */
final class Locales
{
    public static function current(): Locale
    {
        return app()->bound(Locale::class) ? app(Locale::class) : Locale::default();
    }

    public static function set(Locale $locale): void
    {
        app()->instance(Locale::class, $locale);
        app()->setLocale($locale->language());
    }

    /** @return list<Locale> */
    public static function supported(): array
    {
        return Locale::cases();
    }

    /**
     * Le projet derrière le sujet d'un jeton : l'histoire à enregistrer, le
     * projet à écouter, ou rien.
     */
    public static function projectOf(mixed $subject): ?Project
    {
        if ($subject instanceof Project) {
            return $subject;
        }

        if ($subject instanceof Story) {
            return $subject->project;
        }

        if ($subject instanceof Model && $subject->isRelation('project')) {
            $project = $subject->getRelationValue('project');

            return $project instanceof Project ? $project : null;
        }

        return null;
    }
}
