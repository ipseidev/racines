<?php

declare(strict_types=1);

namespace App\Support\Database;

use BackedEnum;
use Illuminate\Support\Facades\DB;

/**
 * Contraintes `check` sur les colonnes qui stockent une énumération en texte.
 *
 * Convention §13 : la contrainte vit en base et pas seulement dans le code.
 * Une valeur d'état inventée par un script, une console psql ou une future
 * migration est refusée par Postgres, pas seulement par Eloquent.
 */
final class EnumCheck
{
    /**
     * @param  class-string<BackedEnum>  $enum
     * @return list<string>
     */
    public static function of(string $enum): array
    {
        return array_map(
            static fn (BackedEnum $case): string => (string) $case->value,
            $enum::cases(),
        );
    }

    /**
     * @param  list<string>  $values
     */
    public static function add(string $table, string $column, array $values, bool $nullable = false): void
    {
        $list = implode("','", $values);
        $expression = "{$column} in ('{$list}')";

        if ($nullable) {
            $expression = "{$column} is null or {$expression}";
        }

        DB::statement("alter table {$table} add constraint {$table}_{$column}_check check ({$expression})");
    }

    public static function drop(string $table, string $column): void
    {
        DB::statement("alter table {$table} drop constraint if exists {$table}_{$column}_check");
    }
}
