<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Le prix du pilote et le slogan, arrêtés par le fondateur le 4 septembre 2026.
 *
 * Une migration et non un simple changement de configuration : la base est la
 * source de vérité (bloc 01), et chaque environnement déjà migré garderait
 * sinon 49 € et l'ancien slogan. L'analyse colorimétrique parlait déjà de 89 € ;
 * c'est le prix réel de l'offre pilote. Le slogan reprend la promesse de
 * Remento adaptée à notre univers : l'objet, puis ce qui nous distingue.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('pilot.pilot_price_cents', fn (): int => 8_900);
        $this->migrator->update('brand.tagline', fn (): string => 'Le livre de leurs souvenirs, avec leur voix à chaque page.');
    }

    public function down(): void
    {
        $this->migrator->update('pilot.pilot_price_cents', fn (): int => 4_900);
        $this->migrator->update('brand.tagline', fn (): string => 'Le livre de souvenirs de vos parents qui va réellement au bout.');
    }
};
