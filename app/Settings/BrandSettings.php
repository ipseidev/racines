<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Identité de marque, éditable dans l'administration sans déploiement.
 *
 * Le nom, le domaine des liens et les couleurs ne sont décidés qu'après la
 * Phase 0 : aucun d'eux n'apparaît en dur dans le code (voir le test
 * BrandAgnosticTest). Les valeurs initiales viennent de config/brand.php.
 */
final class BrandSettings extends Settings
{
    public string $product_name;

    public string $short_name;

    public string $tagline;

    public string $links_domain;

    public string $support_email;

    public ?string $support_phone;

    public string $sms_sender_id;

    public string $color_primary;

    public string $color_primary_foreground;

    public string $color_accent;

    public string $color_accent_foreground;

    public string $color_background;

    public string $color_surface;

    public string $color_text;

    public string $color_muted;

    public string $font_display;

    public string $font_body;

    public ?string $logo_path;

    public ?string $favicon_path;

    public string $legal_entity;

    public string $legal_address;

    public static function group(): string
    {
        return 'brand';
    }

    /**
     * Couples dont le contraste conditionne la lisibilité (WCAG 2.2 AA).
     *
     * @return array<int, array{string, string, string}> avant-plan, fond, libellé
     */
    public static function contrastPairs(): array
    {
        return [
            ['color_text', 'color_background', 'texte sur fond'],
            ['color_text', 'color_surface', 'texte sur surface'],
            ['color_primary_foreground', 'color_primary', 'avant-plan sur couleur principale'],
            ['color_accent_foreground', 'color_accent', 'avant-plan sur couleur d’accent'],
        ];
    }
}
