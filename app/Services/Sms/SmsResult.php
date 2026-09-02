<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Résultat d'un envoi de SMS.
 *
 * `providerMessageId` sert à raccorder les webhooks de livraison du bloc 05 :
 * un SMS accepté par l'opérateur n'est pas un SMS reçu.
 */
final readonly class SmsResult
{
    public function __construct(
        public bool $accepted,
        public ?string $providerMessageId = null,
        public ?string $error = null,
    ) {}

    public static function accepted(?string $providerMessageId = null): self
    {
        return new self(true, $providerMessageId);
    }

    public static function refused(string $error): self
    {
        return new self(false, null, $error);
    }
}
