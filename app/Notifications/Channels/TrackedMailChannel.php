<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\Channel;
use App\Models\OutboundMessage;
use App\Notifications\TracksDelivery;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Le canal courriel de Laravel, avec suivi de livraison.
 *
 * L'identifiant du fournisseur n'est connu qu'après l'envoi : on écoute
 * l'événement `MessageSent` le temps de l'appel pour le récupérer, plutôt que
 * d'installer un écouteur global qui ne saurait pas à quelle ligne rattacher
 * le message.
 */
final class TrackedMailChannel extends MailChannel
{
    public function send($notifiable, Notification $notification)
    {
        $to = $notifiable->routeNotificationFor('mail', $notification);
        $recipient = is_array($to) ? (string) (array_key_first($to) ?? '') : (string) $to;

        if ($recipient === '') {
            return null;
        }

        $tracked = $notification instanceof TracksDelivery ? $notification : null;
        $dedupeKey = $tracked?->dedupeKey(Channel::Email) ?? 'mail:'.hash('sha256', $recipient.$notification::class);

        if (OutboundMessage::query()->where('dedupe_key', $dedupeKey)->exists()) {
            return null;
        }

        $message = new OutboundMessage([
            'project_id' => $tracked?->projectId(),
            'channel' => Channel::Email,
            'template' => $tracked?->template() ?? 'mail',
            'payload' => $tracked?->deliveryPayload(),
            'dedupe_key' => $dedupeKey,
            'provider' => (string) config('mail.default'),
        ]);

        $message->to_hash = OutboundMessage::hashRecipient($recipient);
        $message->to_masked = OutboundMessage::mask($recipient);
        $message->save();

        $providerId = null;

        Event::listen(function (MessageSent $event) use (&$providerId): void {
            $providerId ??= $event->sent->getMessageId();
        });

        try {
            parent::send($notifiable, $notification);
            $message->markSent($providerId);
        } catch (Throwable $exception) {
            $message->markFailed($exception->getMessage());

            throw $exception;
        }

        return null;
    }
}
