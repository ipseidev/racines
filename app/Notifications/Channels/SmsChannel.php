<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\Channel;
use App\Models\OutboundMessage;
use App\Notifications\TracksDelivery;
use App\Services\Sms\SmsSender;
use Illuminate\Notifications\Notification;

/**
 * Canal SMS, adossé à `SmsSender`, avec suivi de livraison.
 *
 * Chaque envoi laisse une ligne dans `outbound_messages`, portant une clé
 * d'idempotence : deux exécutions de `prompts:dispatch-due` dans la même
 * minute ne doivent pas produire deux SMS chez une personne de 82 ans.
 */
final readonly class SmsChannel
{
    public function __construct(private SmsSender $sender) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('sms', $notification);

        if (! is_string($to) || $to === '') {
            return;
        }

        $body = (string) $notification->toSms($notifiable);
        $tracked = $notification instanceof TracksDelivery ? $notification : null;
        $dedupeKey = $tracked?->dedupeKey(Channel::Sms) ?? 'sms:'.hash('sha256', $to.$body);

        $existing = OutboundMessage::query()->where('dedupe_key', $dedupeKey)->first();

        // Déjà parti : on ne recommence pas.
        if ($existing instanceof OutboundMessage) {
            return;
        }

        $message = new OutboundMessage([
            'project_id' => $tracked?->projectId(),
            'channel' => Channel::Sms,
            'template' => $tracked?->template() ?? 'sms',
            'payload' => $tracked?->deliveryPayload(),
            'dedupe_key' => $dedupeKey,
        ]);

        $message->to_hash = OutboundMessage::hashRecipient($to);
        $message->to_masked = OutboundMessage::mask($to);
        $message->save();

        $result = $this->sender->send($to, $body, $dedupeKey);

        if ($result->accepted) {
            $message->markSent($result->providerMessageId, config('services.sms.provider'));

            return;
        }

        $message->markFailed($result->error ?? 'refus du fournisseur');
    }
}
