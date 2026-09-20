<?php

declare(strict_types=1);

use App\Enums\Cadence;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;

/**
 * La cadence accepte deux et trois questions par semaine.
 *
 * La contrainte `check` de `projects.cadence` a été émise avec les deux seuls
 * cas d'alors — `weekly` et `biweekly` (migration du 2 septembre). Comme
 * toujours avec `EnumCheck::of()`, la liste est figée au moment où la
 * migration tourne : sans cette réémission, Postgres refuserait les deux
 * nouveaux rythmes, et l'acceptation du cadeau tomberait en erreur 500 au
 * moment précis où quelqu'un dit oui (même mécanisme que T-222).
 */
return new class extends Migration
{
    /** Ce que cette migration ajoute. */
    private const AJOUTS = ['twice_weekly', 'thrice_weekly'];

    public function up(): void
    {
        EnumCheck::drop('projects', 'cadence');
        EnumCheck::add('projects', 'cadence', EnumCheck::of(Cadence::class));
    }

    public function down(): void
    {
        EnumCheck::drop('projects', 'cadence');
        EnumCheck::add('projects', 'cadence', array_values(array_diff(
            EnumCheck::of(Cadence::class),
            self::AJOUTS,
        )));
    }
};
