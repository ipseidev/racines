<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\BrandSettings;
use Throwable;

/**
 * Point d'accès unique à la marque.
 *
 * Tout ce qui est visible d'un client, d'un narrateur ou d'un proche passe
 * par ici : nom, domaine des liens, couleurs, polices. Rien n'est écrit en dur
 * ailleurs, pour que le jour où le nom et la charte sont arrêtés, un seul
 * formulaire suffise.
 */
final class Brand
{
    public static function settings(): BrandSettings
    {
        return app(BrandSettings::class);
    }

    public static function name(): string
    {
        return self::settings()->product_name;
    }

    public static function shortName(): string
    {
        return self::settings()->short_name;
    }

    public static function linksDomain(): string
    {
        return self::settings()->links_domain;
    }

    public static function supportEmail(): string
    {
        return self::settings()->support_email;
    }

    public static function smsSenderId(): string
    {
        return self::settings()->sms_sender_id;
    }

    public static function mailFromName(): string
    {
        return self::name();
    }

    public static function logoUrl(): ?string
    {
        $path = self::settings()->logo_path;

        return $path === null ? null : asset('storage/'.$path);
    }

    public static function faviconUrl(): ?string
    {
        $path = self::settings()->favicon_path;

        return $path === null ? null : asset('storage/'.$path);
    }

    /**
     * Variantes tolérantes, utilisées là où le code s'exécute avant que la
     * base soit migrée : enregistrement du panneau, commandes d'installation.
     */
    public static function nameSafe(): string
    {
        return self::safely(fn (): string => self::name(), (string) config('brand.product_name'));
    }

    public static function primaryColorSafe(): string
    {
        return self::safely(
            fn (): string => self::settings()->color_primary,
            (string) config('brand.colors.primary'),
        );
    }

    /**
     * @template T
     *
     * @param  callable(): T  $resolver
     * @param  T  $fallback
     * @return T
     */
    private static function safely(callable $resolver, mixed $fallback): mixed
    {
        try {
            return $resolver();
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * Variables CSS injectées dans la vue racine et consommées par Tailwind.
     *
     * @return array<string, string>
     */
    public static function cssVariables(): array
    {
        $brand = self::settings();

        return [
            '--brand-primary' => $brand->color_primary,
            '--brand-primary-foreground' => $brand->color_primary_foreground,
            '--brand-accent' => $brand->color_accent,
            '--brand-accent-foreground' => $brand->color_accent_foreground,
            '--brand-background' => $brand->color_background,
            '--brand-surface' => $brand->color_surface,
            '--brand-text' => $brand->color_text,
            '--brand-muted' => $brand->color_muted,
            '--brand-font-display' => $brand->font_display,
            '--brand-font-body' => $brand->font_body,
        ];
    }

    /**
     * Propriétés partagées avec le front par Inertia.
     *
     * @return array<string, string|null>
     */
    public static function toInertia(): array
    {
        $brand = self::settings();

        return [
            'name' => $brand->product_name,
            'short_name' => $brand->short_name,
            'tagline' => $brand->tagline,
            'links_domain' => $brand->links_domain,
            'support_email' => $brand->support_email,
            'support_phone' => $brand->support_phone,
            'logo_url' => self::logoUrl(),
        ];
    }
}
