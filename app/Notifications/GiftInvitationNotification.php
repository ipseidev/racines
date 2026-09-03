<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Project;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TrackedMailChannel;
use App\Services\Tokens\OtpService;
use App\Support\Brand;
use App\Support\Links;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * « {Initiateur} vous offre {Marque}. »
 *
 * Le message le plus délicat du produit : il arrive sans être attendu, d'un
 * expéditeur inconnu, et propose de raconter sa vie. Trois choses le rendent
 * crédible — le **nom de la personne** qui offre, son **message personnel**,
 * et la phrase qui dit qu'aucune page ne demandera de mot de passe ni de
 * paiement (doc 04 §9).
 *
 * Il ne demande **rien** : ni de s'enregistrer, ni de créer un compte. Juste
 * de découvrir de quoi il s'agit. Le narrateur décidera ensuite.
 */
final class GiftInvitationNotification extends Notification implements TracksDelivery
{
    public function __construct(
        private readonly Project $project,
        private readonly string $plainToken,
        private readonly int $attempt = 1,
    ) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        // Un seul canal : deux invitations pour un même cadeau ressemblent à
        // du démarchage.
        return match (OtpService::channelFor($notifiable)) {
            Channel::Email => $notifiable->email === null ? [] : [TrackedMailChannel::class],
            default => $notifiable->phone_e164 === null ? [] : [SmsChannel::class],
        };
    }

    public function optInUrl(): string
    {
        return Links::invitation($this->plainToken);
    }

    public function toSms(mixed $notifiable): string
    {
        return __('notifications.gift_invitation.sms', [
            'name' => $notifiable->first_name,
            'inviter' => $this->project->owner->name,
            'brand' => Brand::shortName(),
            'link' => $this->optInUrl(),
        ]);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notifications.gift_invitation.subject', [
                'inviter' => $this->project->owner->name,
            ]))
            ->greeting(__('notifications.gift_invitation.greeting', [
                'name' => $notifiable->first_name,
            ]))
            ->line(__('notifications.gift_invitation.line', [
                'inviter' => $this->project->owner->name,
                'brand' => Brand::nameSafe(),
            ]));

        $personal = $this->project->gift_message;

        if (is_string($personal) && trim($personal) !== '') {
            // Le message de la personne, cité tel quel : c'est lui qui fait
            // ouvrir le lien, pas notre argumentaire.
            $message->line('« '.trim($personal).' »');
        }

        return $message
            ->action(__('notifications.gift_invitation.button'), $this->optInUrl())
            ->line(__('notifications.gift_invitation.no_obligation'))
            ->line(__('notifications.prompt.no_password'))
            ->salutation(__('notifications.prompt.signature', ['brand' => Brand::nameSafe()]));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "gift-invitation:{$this->project->id}:{$this->attempt}:{$channel->value}";
    }

    public function template(): string
    {
        return 'gift_invitation';
    }

    public function projectId(): string
    {
        return $this->project->id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return ['project_id' => $this->project->id, 'attempt' => $this->attempt];
    }
}
