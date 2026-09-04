<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Le nom définitif du produit, arrêté par le fondateur le 4 septembre 2026
 * (T-143). « Racines » n'était qu'un nom de code ; le dépôt le garde comme nom
 * technique.
 *
 * Une migration et non un simple changement d'environnement : la base est la
 * source de vérité (bloc 01), et chaque environnement déjà migré garderait
 * sinon le nom de code. Les nouvelles valeurs viennent de config/brand.php,
 * comme à la création des réglages : le test de garde interdit d'écrire le
 * nom de marque dans database/, et c'est lui qui protège cette règle. Chaque
 * valeur n'est remplacée que si elle porte encore la valeur de code ou le
 * gabarit d'origine : un nom saisi à la main dans l'administration n'est pas
 * écrasé. Le domaine des liens n'est pas touché, il dépend de l'environnement
 * (tunnel en local, domaine réel en production).
 */
return new class extends SettingsMigration
{
    /** @var array<string, array{0: string, 1: string}> réglage => [valeur de code, clé de configuration] */
    private const RENAMES = [
        'brand.product_name' => ['Racines', 'brand.product_name'],
        'brand.short_name' => ['Racines', 'brand.short_name'],
        'brand.sms_sender_id' => ['RACINES', 'brand.sms_sender_id'],
        'brand.support_email' => ['support@example.test', 'brand.support_email'],
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $property => [$codeName, $configKey]) {
            $after = (string) config($configKey);

            $this->migrator->update($property, fn (string $current): string => $current === $codeName ? $after : $current);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $property => [$codeName, $configKey]) {
            $after = (string) config($configKey);

            $this->migrator->update($property, fn (string $current): string => $current === $after ? $codeName : $current);
        }
    }
};
