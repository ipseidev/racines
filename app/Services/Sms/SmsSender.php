<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Envoi de SMS, derrière une interface.
 *
 * Le dossier exige une stratégie de sortie documentée pour chaque
 * fournisseur : aucune classe métier ne connaît Twilio, qui arrive au bloc 05
 * comme une simple implémentation de plus.
 */
interface SmsSender
{
    public function send(string $toE164, string $body, ?string $dedupeKey = null): SmsResult;
}
