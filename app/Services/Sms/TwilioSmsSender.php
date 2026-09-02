<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\Brand;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Rest\Client;

/**
 * Envoi de SMS par Twilio.
 *
 * L'expéditeur est le nom de la marque quand l'opérateur du destinataire
 * accepte un expéditeur alphanumérique — c'est une exigence d'anti-hameçonnage
 * du doc 04 §9 : un narrateur doit reconnaître d'où vient le message avant de
 * l'ouvrir. Là où l'alphanumérique est interdit, on retombe sur un numéro
 * **constant**, qui joue le même rôle.
 */
final readonly class TwilioSmsSender implements SmsSender
{
    public function __construct(
        private Client $client,
        private ?string $statusCallback = null,
    ) {}

    public function send(string $toE164, string $body, ?string $dedupeKey = null): SmsResult
    {
        $options = ['from' => $this->senderFor($toE164), 'body' => $body];

        if ($this->statusCallback !== null) {
            // Sans rappel de statut, « envoyé » et « reçu » se confondent.
            $options['statusCallback'] = $this->statusCallback;
        }

        try {
            $message = $this->client->messages->create($toE164, $options);
        } catch (Throwable $exception) {
            Log::warning('sms.refused', [
                'to_masked' => LogSmsSender::mask($toE164),
                'reason' => $exception->getMessage(),
            ]);

            return SmsResult::refused($exception->getMessage());
        }

        return SmsResult::accepted($message->sid);
    }

    /**
     * Nom de marque là où c'est permis, numéro constant ailleurs.
     */
    public function senderFor(string $toE164): string
    {
        $fallback = (string) config('services.twilio.from');

        if (! $this->allowsAlphanumeric($toE164)) {
            return $fallback;
        }

        $senderId = Brand::smsSenderId();

        return $senderId === '' ? $fallback : $senderId;
    }

    private function allowsAlphanumeric(string $toE164): bool
    {
        $countries = config('product.sms.alphanumeric_countries');

        if (! is_array($countries)) {
            return false;
        }

        foreach ($countries as $country) {
            if (! is_string($country)) {
                continue;
            }

            foreach (self::PREFIXES[$country] ?? [] as $prefix) {
                if (str_starts_with($toE164, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Indicatifs des pays de `config('product.sms.alphanumeric_countries')`.
     *
     * @var array<string, list<string>>
     */
    private const PREFIXES = [
        'FR' => ['+33', '+590', '+594', '+596', '+262', '+269'],
        'BE' => ['+32'],
        'CH' => ['+41'],
        'LU' => ['+352'],
    ];
}
