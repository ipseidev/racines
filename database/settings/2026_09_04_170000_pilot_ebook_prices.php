<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Le livre numérique, en option à 25 € (T-137).
 *
 * Deux réglages : le prix, et le prix barré qu'on affiche à côté, comme le
 * leader. Le second est un choix commercial du fondateur, pas un ancien prix.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pilot.ebook_price_cents', (int) config('product.pilot.ebook_price_cents'));
        $this->migrator->add('pilot.ebook_regular_price_cents', (int) config('product.pilot.ebook_regular_price_cents'));
    }

    public function down(): void
    {
        $this->migrator->delete('pilot.ebook_price_cents');
        $this->migrator->delete('pilot.ebook_regular_price_cents');
    }
};
