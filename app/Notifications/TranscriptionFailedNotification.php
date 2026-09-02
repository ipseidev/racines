<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Recording;
use App\Notifications\Channels\TrackedMailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * La transcription d'un enregistrement a échoué définitivement.
 *
 * Le narrateur a parlé, l'audio est en sécurité, et le texte n'arrive pas :
 * pour la famille, cela ressemble à un silence inexpliqué. Le support doit
 * pouvoir relancer à la main avant que quiconque s'en aperçoive.
 */
final class TranscriptionFailedNotification extends Notification implements TracksDelivery
{
    public function __construct(private readonly Recording $recording) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return [TrackedMailChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.transcription_failed.subject'))
            ->line(__('notifications.transcription_failed.line', ['recording' => $this->recording->id]))
            ->line(__('notifications.transcription_failed.action'));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "transcription-failed:{$this->recording->id}:{$channel->value}";
    }

    public function template(): string
    {
        return 'transcription_failed';
    }

    public function projectId(): string
    {
        return $this->recording->story->project_id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return [
            'recording_id' => $this->recording->id,
            'story_id' => $this->recording->story_id,
        ];
    }
}
