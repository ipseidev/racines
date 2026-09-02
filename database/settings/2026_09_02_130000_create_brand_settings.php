<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach ($this->values() as $property => $value) {
            $this->migrator->add("brand.{$property}", $value);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->values()) as $property) {
            $this->migrator->delete("brand.{$property}");
        }
    }

    /**
     * Valeurs initiales, reprises de config/brand.php : la configuration est le
     * repli, la base devient la source de vérité dès le premier enregistrement.
     *
     * @return array<string, string|null>
     */
    private function values(): array
    {
        return [
            'product_name' => (string) config('brand.product_name'),
            'short_name' => (string) config('brand.short_name'),
            'tagline' => (string) config('brand.tagline'),
            'links_domain' => (string) config('brand.links_domain'),
            'support_email' => (string) config('brand.support_email'),
            'support_phone' => config('brand.support_phone'),
            'sms_sender_id' => (string) config('brand.sms_sender_id'),
            'color_primary' => (string) config('brand.colors.primary'),
            'color_primary_foreground' => (string) config('brand.colors.primary_foreground'),
            'color_accent' => (string) config('brand.colors.accent'),
            'color_accent_foreground' => (string) config('brand.colors.accent_foreground'),
            'color_background' => (string) config('brand.colors.background'),
            'color_surface' => (string) config('brand.colors.surface'),
            'color_text' => (string) config('brand.colors.text'),
            'color_muted' => (string) config('brand.colors.muted'),
            'font_display' => (string) config('brand.fonts.display'),
            'font_body' => (string) config('brand.fonts.body'),
            'logo_path' => null,
            'favicon_path' => null,
            'legal_entity' => (string) config('brand.legal_entity'),
            'legal_address' => (string) config('brand.legal_address'),
        ];
    }
};
