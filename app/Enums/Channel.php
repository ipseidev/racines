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
    case PhoneOperator = 'phone_operator';

    /** @return list<string> */
    public static function outboundValues(): array
    {
        return [self::Sms->value, self::Email->value];
    }
}
