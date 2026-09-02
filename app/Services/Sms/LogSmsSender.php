<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Expéditeur local : le SMS part dans le journal.
 *
 * Utile pour dérouler un parcours narrateur sur sa machine sans dépenser de
 * crédits ni prévenir un opérateur. Le numéro est masqué, le corps ne l'est
 * pas : c'est le seul moyen de lire le code envoyé pendant un développement.
 */
final class LogSmsSender implements SmsSender
{
    public function send(string $toE164, string $body, ?string $dedupeKey = null): SmsResult
    {
        Log::info('sms.sent', [
            'to_masked' => self::mask($toE164),
            'body' => $body,
            'dedupe_key' => $dedupeKey,
        ]);

        return SmsResult::accepted('log-'.Str::uuid7()->toString());
    }

    public static function mask(string $toE164): string
    {
        return Str::mask($toE164, '•', 4, max(strlen($toE164) - 6, 0));
    }
}
