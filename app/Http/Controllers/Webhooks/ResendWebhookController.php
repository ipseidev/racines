<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Enums\OutboundMessageStatus;
use App\Models\OutboundMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Livraison des courriels, rapportée par Resend.
 */
final class ResendWebhookController
{
    /** Correspondance des événements Resend vers nos statuts. */
    private const EVENTS = [
        'email.sent' => OutboundMessageStatus::Sent,
        'email.delivered' => OutboundMessageStatus::Delivered,
        'email.delivery_delayed' => OutboundMessageStatus::Sent,
        'email.bounced' => OutboundMessageStatus::Bounced,
        'email.complained' => OutboundMessageStatus::Bounced,
        'email.failed' => OutboundMessageStatus::Failed,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $type = (string) $request->input('type', '');
        $providerId = (string) $request->input('data.email_id', '');

        $message = $providerId === ''
            ? null
            : OutboundMessage::query()->where('provider_message_id', $providerId)->first();

        if (! $message instanceof OutboundMessage) {
            Log::info('webhook.resend.unknown_message', ['type' => $type]);

            return response()->json(status: 202);
        }

        $mapped = self::EVENTS[$type] ?? null;

        if ($mapped === null) {
            Log::info('webhook.resend.unknown_event', [
                'message_id' => $message->id,
                'type' => $type,
            ]);

            return response()->json(status: 202);
        }

        $message->applyProviderStatus($mapped, $type);

        return response()->json(status: 200);
    }
}
