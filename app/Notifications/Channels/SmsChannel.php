<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Services\Sms\SmsSender;
use Illuminate\Notifications\Notification;

/**
 * Canal de notification SMS, adossé à `SmsSender`.
 *
 * Passer par un canal Laravel plutôt que par un appel direct permet à
 * `Notification::fake()` de fonctionner dans les tests des blocs suivants,
 * et au bloc 05 de brancher les files et les webhooks de livraison sans
 * toucher aux notifications elles-mêmes.
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

        $this->sender->send($to, (string) $notification->toSms($notifiable));
    }
}
