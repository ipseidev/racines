<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Project;
use App\Notifications\Channels\TrackedMailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Le corpus est épuisé pour ce projet.
 *
 * Envoyée une seule fois — la clé de déduplication porte l'identifiant du
 * projet. Sans elle, l'Initiateur·rice recevrait le même message chaque
 * semaine, ce qui est le meilleur moyen de le faire ignorer.
 */
final class CorpusExhaustedNotification extends Notification implements TracksDelivery
{
    public function __construct(private readonly Project $project) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return [TrackedMailChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.corpus_exhausted.subject'))
            ->line(__('notifications.corpus_exhausted.line'))
            ->line(__('notifications.corpus_exhausted.action'));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "corpus-exhausted:{$this->project->id}:{$channel->value}";
    }

    public function template(): string
    {
        return 'corpus_exhausted';
    }

    public function projectId(): string
    {
        return $this->project->id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return ['project_id' => $this->project->id];
    }
}
