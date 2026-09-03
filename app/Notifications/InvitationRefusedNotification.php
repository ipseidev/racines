<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Enums\RefusalReason;
use App\Models\Project;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * « {Prénom} a préféré ne pas participer pour le moment. »
 *
 * Le message le plus difficile à écrire du produit. La personne qui le reçoit
 * vient d'offrir quelque chose de personnel, et on lui annonce un refus.
 *
 * Trois choix de rédaction, tous délibérés : on **respecte** le choix à voix
 * haute (« c'est son choix et nous le respectons »), on ne suggère **aucune**
 * relance, et on propose le remboursement sans qu'elle ait à le demander. Ce
 * n'est pas de la générosité commerciale : quelqu'un qui doit réclamer son
 * argent après ça ne reviendra jamais, et n'en parlera pas en bien.
 */
final class InvitationRefusedNotification extends Notification implements TracksDelivery
{
    public function __construct(
        private readonly Project $project,
        private readonly ?RefusalReason $reason = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        // Par courriel seulement : un SMS pour annoncer ça serait brutal.
        return [TrackedMailChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $narrator = $this->project->primaryNarrator->first_name ?? '';

        return (new MailMessage)
            ->subject(__('notifications.initiator.invitation_refused.subject'))
            ->greeting(__('notifications.initiator.invitation_refused.greeting', [
                'name' => $notifiable->name,
            ]))
            ->line(__('notifications.initiator.invitation_refused.line', ['narrator' => $narrator]))
            ->line(__('notifications.initiator.invitation_refused.respect'))
            ->action(
                __('notifications.initiator.invitation_refused.button'),
                route('initiator.orders'),
            )
            ->line(__('notifications.initiator.invitation_refused.refund'))
            ->salutation(__('notifications.prompt.signature', ['brand' => Brand::nameSafe()]));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "invitation-refused:{$this->project->id}:{$channel->value}";
    }

    public function template(): string
    {
        return 'invitation_refused';
    }

    public function projectId(): string
    {
        return $this->project->id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return ['project_id' => $this->project->id, 'reason' => $this->reason?->value];
    }
}
