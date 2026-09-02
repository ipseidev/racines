<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Enums\OutboundMessageStatus;
use App\Models\OutboundMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Livraison des SMS, rapportée par Twilio.
 *
 * C'est ce rappel qui donne du sens à la distinction entre « accepté par
 * l'opérateur » et « reçu ». Sans lui, le moteur de complétion relancerait un
 * narrateur qui n'a jamais rien reçu.
 */
final class TwilioStatusController
{
    /** Correspondance des statuts Twilio vers les nôtres. */
    private const STATUSES = [
        'queued' => OutboundMessageStatus::Queued,
        'accepted' => OutboundMessageStatus::Queued,
        'sending' => OutboundMessageStatus::Sent,
        'sent' => OutboundMessageStatus::Sent,
        'delivered' => OutboundMessageStatus::Delivered,
        'undelivered' => OutboundMessageStatus::Undelivered,
        'failed' => OutboundMessageStatus::Failed,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $sid = (string) $request->input('MessageSid', $request->input('SmsSid', ''));
        $status = mb_strtolower((string) $request->input('MessageStatus', $request->input('SmsStatus', '')));

        $message = $sid === ''
            ? null
            : OutboundMessage::query()->where('provider_message_id', $sid)->first();

        // Un message inconnu n'est pas une erreur : Twilio réessaie sur un 5xx,
        // et un rappel en retard sur un message purgé ne doit pas boucler.
        if (! $message instanceof OutboundMessage) {
            Log::info('webhook.twilio.unknown_message', ['status' => $status]);

            return response()->json(status: 202);
        }

        $mapped = self::STATUSES[$status] ?? null;

        if ($mapped === null) {
            Log::info('webhook.twilio.unknown_status', [
                'message_id' => $message->id,
                'status' => $status,
            ]);

            return response()->json(status: 202);
        }

        $message->applyProviderStatus($mapped, $request->input('ErrorMessage') === null
            ? null
            : (string) $request->input('ErrorMessage'));

        return response()->json(status: 200);
    }
}
