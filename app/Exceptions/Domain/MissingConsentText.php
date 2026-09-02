<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Le texte de ce consentement n'existe pas dans la langue demandée.
 *
 * On refuse plutôt que de recueillir un accord sans pouvoir dire, plus tard,
 * ce qui avait été lu (doc 04 §2).
 */
final class MissingConsentText extends DomainException
{
    public static function forKind(string $kind, string $locale): self
    {
        return new self("No consent text in force for [{$kind}] in [{$locale}].");
    }
}
