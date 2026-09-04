<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * La palette validée le 3 septembre 2026 remplace celle du kit.
 *
 * Une migration et non un simple changement de `config/brand.php` : la base
 * est la source de vérité depuis le premier enregistrement (bloc 01), et la
 * configuration n'est qu'un repli pour un semis neuf. Sans cette migration,
 * chaque environnement déjà migré garderait le vert acide de Remento.
 *
 * Le sens des champs ne change pas, leur valeur si : `primary` reste la couleur
 * de marque, `accent` reste la couleur d'action — mais l'action passe d'un
 * vert acide qui se fondait dans une marque verte à une terracotta qui est la
 * seule couleur chaude saturée de la page (docs/design/README.md).
 */
return new class extends SettingsMigration
{
    /** @var array<string, string> */
    private const NEW = [
        'color_primary' => '#2F4A3F',
        'color_primary_foreground' => '#FFFFFF',
        'color_accent' => '#B0432A',
        'color_accent_foreground' => '#FFFFFF',
        'color_background' => '#FBF6EE',
        'color_surface' => '#FFFFFF',
        'color_text' => '#26211C',
        'color_muted' => '#5A5049',
    ];

    /** @var array<string, string> */
    private const OLD = [
        'color_primary' => '#1F3D2B',
        'color_primary_foreground' => '#FFFFFF',
        'color_accent' => '#D9E76C',
        'color_accent_foreground' => '#1F3D2B',
        'color_background' => '#F7F5EF',
        'color_surface' => '#FFFFFF',
        'color_text' => '#1B1B1B',
        'color_muted' => '#6B6B6B',
    ];

    public function up(): void
    {
        foreach (self::NEW as $property => $value) {
            $this->migrator->update("brand.{$property}", fn (): string => $value);
        }
    }

    public function down(): void
    {
        foreach (self::OLD as $property => $value) {
            $this->migrator->update("brand.{$property}", fn (): string => $value);
        }
    }
};
