<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\PhoneOption;
use App\Settings\PilotSettings;
use Laravel\Pennant\Feature;

/**
 * L'option « enregistrement par téléphone » est-elle proposable ?
 *
 * Deux conditions, et la seconde est **calculée**, jamais réglée à la main :
 * le drapeau doit être ouvert, **et** le plafond du pilote ne doit pas être
 * atteint. Dix familles, c'est ce qu'une personne peut rappeler chaque
 * semaine ; la onzième recevrait une promesse qu'on ne tiendrait pas.
 *
 * Le plafond compte les options `requested` **et** `active` : une demande en
 * attente occupe déjà un créneau humain.
 */
final class PhoneOptionOffer
{
    public string $name = 'phone-option-offer';

    /**
     * Drapeau global, sans portée : c'est une capacité de l'équipe, pas une
     * caractéristique d'un projet.
     */
    public function resolve(mixed $scope): bool
    {
        return false;
    }

    /**
     * Proposable : le drapeau est ouvert et il reste de la place.
     */
    public static function isOpen(): bool
    {
        return Feature::active(self::class) && ! self::isSaturated();
    }

    public static function isSaturated(): bool
    {
        return self::taken() >= self::cap();
    }

    public static function taken(): int
    {
        return PhoneOption::query()->countingTowardsCap()->count();
    }

    public static function cap(): int
    {
        return app(PilotSettings::class)->phone_option_cap;
    }

    public static function remaining(): int
    {
        return max(0, self::cap() - self::taken());
    }
}
