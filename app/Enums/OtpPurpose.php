<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Pourquoi un code à usage unique est demandé (doc 04 §12).
 *
 * `narrator_space` ouvre l'espace personnel du narrateur ; `sensitive_act`
 * autorise un acte irréversible ou durable pendant quinze minutes.
 */
enum OtpPurpose: string
{
    use HasTranslatedLabel;

    case NarratorSpace = 'narrator_space';
    case SensitiveAct = 'sensitive_act';

    public function grants(): TokenType
    {
        return match ($this) {
            self::NarratorSpace => TokenType::NarratorSpace,
            self::SensitiveAct => TokenType::SensitiveGrant,
        };
    }
}
