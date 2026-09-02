<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Un SMS tel qu'il a été remis à l'expéditeur.
 */
final readonly class SmsMessage
{
    public function __construct(
        public string $to,
        public string $body,
        public ?string $dedupeKey = null,
    ) {}
}
