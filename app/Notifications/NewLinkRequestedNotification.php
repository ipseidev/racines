<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Story;
use App\Notifications\Channels\TrackedMailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un narrateur a demandé un nouveau lien : on le dit à l'Initiateur·rice et
 * au support.
 *
 * Le message ne contient **pas** le lien : il n'a rien à faire dans la boîte
 * d'un tiers, même bien intentionné. Le narrateur a reçu le sien sur son
 * propre canal.
 */
final class NewLinkRequestedNotification extends Notification implements TracksDelivery
{
    public function __construct(
        private readonly Story $story,
        private readonly bool $forSupport,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return [TrackedMailChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $prefix = $this->forSupport ? 'support' : 'initiator';

        return (new MailMessage)
            ->subject(__("notifications.new_link_requested.{$prefix}_subject"))
            ->line(__('notifications.new_link_requested.line', [
                'story' => $this->story->questionText() ?? $this->story->id,
            ]))
            ->line(__("notifications.new_link_requested.{$prefix}_action"));
    }

    public function dedupeKey(Channel $channel): string
    {
        $audience = $this->forSupport ? 'support' : 'initiator';

        // Une demande par heure au plus (limiteur du bloc 03) : la clé porte
        // l'heure pour qu'une nouvelle demande demain soit bien signalée.
        return "new-link:{$audience}:{$this->story->id}:".now()->format('Y-m-d-H');
    }

    public function template(): string
    {
        return $this->forSupport ? 'new_link_requested_support' : 'new_link_requested_initiator';
    }

    public function projectId(): string
    {
        return $this->story->project_id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return ['story_id' => $this->story->id, 'audience' => $this->forSupport ? 'support' : 'initiator'];
    }
}
