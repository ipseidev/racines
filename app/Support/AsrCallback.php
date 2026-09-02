<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Signature des rappels de transcription.
 *
 * Les fournisseurs ASR ne signent pas tous leurs rappels, et aucun ne signe de
 * la même façon. On signe donc **nous-mêmes** l'URL qu'on leur donne : sans
 * cela, n'importe qui pourrait injecter une fausse transcription dans
 * l'histoire de quelqu'un — et le texte est ce qui va dans le livre.
 */
final class AsrCallback
{
    public static function signatureFor(string $recordingId): string
    {
        return hash_hmac('sha256', $recordingId, self::secret());
    }

    public static function urlFor(string $provider, string $recordingId): string
    {
        return URL::to("/webhooks/asr/{$provider}/{$recordingId}")
            .'?sig='.self::signatureFor($recordingId);
    }

    /**
     * @throws HttpException
     */
    public static function assertSignature(string $recordingId, string $signature): void
    {
        if (! hash_equals(self::signatureFor($recordingId), $signature)) {
            throw new HttpException(403, 'Signature de rappel ASR invalide.');
        }
    }

    private static function secret(): string
    {
        $secret = (string) config('services.asr.callback_secret');

        if ($secret === '') {
            // Un secret vide validerait n'importe quoi : mieux vaut refuser
            // tous les rappels que d'en accepter un faux.
            throw new HttpException(500, 'ASR_CALLBACK_SECRET n’est pas configuré.');
        }

        return $secret;
    }
}
