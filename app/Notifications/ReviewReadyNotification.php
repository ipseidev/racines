<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Story;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use App\Support\Links;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * « Votre histoire est prête à relire. »
 *
 * Deux motifs, deux textes. `ready` : la variante B, ou une transcription
 * arrivée sans qu'aucune décision ait été prise. `decide_later` : le narrateur
 * a lui-même demandé qu'on le relance — le message le lui rappelle, pour
 * qu'il ne se sente pas poursuivi.
 *
 * Aucun compte à rebours, aucune date butoir, aucune formule qui suggère que
 * l'inaction vaudrait accord (doc 04 §1, R-11).
 */
final class ReviewReadyNotification extends Notification implements TracksDelivery
{
    /**
     * @param  'ready'|'decide_later'  $reason
     */
    public function __construct(
        private readonly Story $story,
        private readonly string $plainToken,
        public readonly string $reason = 'ready',
    ) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        $preference = $notifiable->preferred_channel ?? Channel::Sms;

        return array_values(array_filter(array_map(
            fn (Channel $channel): ?string => match ($channel) {
                Channel::Sms => $notifiable->phone_e164 === null ? null : SmsChannel::class,
                Channel::Email => $notifiable->email === null ? null : TrackedMailChannel::class,
                default => null,
            },
            $preference->resolve(),
        )));
    }

    public function reviewUrl(): string
    {
        return Links::record($this->plainToken).'/review';
    }

    public function toSms(mixed $notifiable): string
    {
        return __('notifications.review.'.$this->reason.'.sms', [
            'name' => $notifiable->first_name,
            'brand' => Brand::shortName(),
            'link' => $this->reviewUrl(),
        ]);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.review.'.$this->reason.'.subject'))
            ->greeting(__('notifications.review.greeting', ['name' => $notifiable->first_name]))
            ->line(__('notifications.review.'.$this->reason.'.line'))
            ->action(__('notifications.review.button'), $this->reviewUrl())
            ->line(__('notifications.review.no_deadline'))
            ->line(__('notifications.prompt.no_password'))
            ->salutation(__('notifications.prompt.signature', ['brand' => Brand::nameSafe()]));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "review-{$this->reason}:{$this->story->id}:{$channel->value}";
    }

    public function template(): string
    {
        return 'review_'.$this->reason;
    }

    public function projectId(): string
    {
        return $this->story->project_id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return ['story_id' => $this->story->id, 'reason' => $this->reason];
    }
}
