<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Canal de contact (R-9, glossaire §6).
 *
 * Le téléphone n'est jamais un canal automatique : c'est l'option payante D-9,
 * opérée par un humain. `outbound()` liste les seuls canaux qu'un envoi
 * programmé peut emprunter.
 */
enum Channel: string
{
    use HasTranslatedLabel;

    case Sms = 'sms';
    case Email = 'email';
    case Both = 'both';
    case PhoneOperator = 'phone_operator';

    /** Canaux qu'un message sortant peut réellement emprunter. */
    /** @return list<string> */
    public static function outboundValues(): array
    {
        return [self::Sms->value, self::Email->value];
    }

    /**
     * Ce qu'un narrateur peut choisir : l'un, l'autre, ou les deux.
     *
     * @return list<string>
     */
    public static function narratorPreferences(): array
    {
        return [self::Sms->value, self::Email->value, self::Both->value];
    }

    /**
     * Canaux effectifs d'une préférence.
     *
     * @return list<self>
     */
    public function resolve(): array
    {
        return match ($this) {
            self::Sms => [self::Sms],
            self::Email => [self::Email],
            self::Both => [self::Sms, self::Email],
            self::PhoneOperator => [],
        };
    }
}
