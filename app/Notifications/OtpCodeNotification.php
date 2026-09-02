<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Notifications\Channels\SmsChannel;
use App\Support\Brand;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Le code à usage unique, par SMS ou par courriel.
 *
 * Le message nomme la marque, annonce la durée de validité et dit de ne pas
 * communiquer le code : trois éléments d'anti-hameçonnage du doc 04 §9. Il ne
 * contient aucun lien — un code qui arrive avec un lien à cliquer entraîne
 * exactement le réflexe qu'on veut désapprendre.
 */
final class OtpCodeNotification extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly Channel $channel,
        private readonly int $minutes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return $this->channel === Channel::Email ? ['mail'] : [SmsChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.otp.subject', ['code' => $this->code]))
            ->greeting(__('notifications.otp.greeting'))
            ->line(__('notifications.otp.code_line', ['code' => $this->code]))
            ->line(__('notifications.otp.expiry_line', ['minutes' => $this->minutes]))
            ->line(__('notifications.otp.warning_line'));
    }

    public function toSms(mixed $notifiable): string
    {
        return __('notifications.otp.sms', [
            'brand' => Brand::shortName(),
            'code' => $this->code,
            'minutes' => $this->minutes,
        ]);
    }
}
