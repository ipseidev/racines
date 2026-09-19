<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * L'adresse de contact publique passe de `support@` à `bonjour@`.
 *
 * Décision du fondateur, 16 septembre 2026. Une migration et non un simple
 * changement d'environnement : la base est la source de vérité depuis le bloc
 * 01, et un environnement déjà migré garderait sinon l'ancienne adresse.
 *
 * La valeur n'est remplacée que si elle porte encore l'ancienne : une adresse
 * saisie à la main dans l'administration n'est pas écrasée.
 *
 * Portée réelle du changement, qui dépasse le pied de page : c'est aussi
 * l'adresse des trois pages légales, du délégué à la protection des données,
 * de la FAQ, du pied de chaque courriel et de la carte de visite des liens.
 * Toutes lisent `BrandSettings::support_email`, il n'y en a qu'une.
 */
return new class extends SettingsMigration
{
    private const AVANT = 'support@narrae.fr';

    public function up(): void
    {
        $apres = (string) config('brand.support_email');

        $this->migrator->update(
            'brand.support_email',
            fn (string $actuelle): string => $actuelle === self::AVANT ? $apres : $actuelle,
        );
    }

    public function down(): void
    {
        $apres = (string) config('brand.support_email');

        $this->migrator->update(
            'brand.support_email',
            fn (string $actuelle): string => $actuelle === $apres ? self::AVANT : $actuelle,
        );
    }
};
