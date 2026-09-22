<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Project;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use App\Support\Links;
use App\Support\Names;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * « {Initiateur} vous invite à écouter les histoires de {Prénom}. »
 *
 * Trois exigences d'anti-hameçonnage du doc 04 §9 : la marque est nommée, le
 * lien part du domaine annoncé, et le message dit qu'aucune page ne demandera
 * de mot de passe ni de paiement. Une invitation à écouter la voix d'un aïeul
 * est exactement ce qu'un hameçonneur imiterait.
 *
 * Le message dit aussi que le lien est **personnel**. C'est la seule
 * protection contre sa circulation dans un groupe de messagerie, et elle vaut
 * mieux qu'un avertissement en petits caractères.
 */
final class FamilyInvitationNotification extends Notification implements TracksDelivery
{
    public function __construct(
        private readonly Project $project,
        private readonly string $plainToken,
        private readonly User $invitedBy,
    ) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return array_values(array_filter([
            $notifiable->email === null ? null : TrackedMailChannel::class,
            $notifiable->phone_e164 === null ? null : SmsChannel::class,
        ]));
    }

    public function listenUrl(): string
    {
        return Links::listen($this->plainToken);
    }

    public function toSms(mixed $notifiable): string
    {
        return __('notifications.family_invitation.sms', [
            'inviter' => $this->invitedBy->name,
            'narrator_of' => Names::of($this->narratorName()),
            'brand' => Brand::shortName(),
            'link' => $this->listenUrl(),
        ]);
    }

    /**
     * L'invitation, dans l'ordre où l'on se pose les questions.
     *
     * D'où ça vient, ce que ça me coûte, ce que je verrai, ce que je peux
     * rendre — puis le bouton. Le message tenait en une ligne et arrivait chez
     * quelqu'un qui n'a jamais entendu parler de nous : il donnait un lien
     * sans donner une raison de cliquer.
     *
     * La ligne des photos ne s'ajoute que pour qui en a le droit. Le dire à
     * tout le monde ferait une promesse que la page dément trois secondes plus
     * tard — et le droit de contribuer s'accorde personne par personne
     * (bloc 12).
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $narrator = $this->narratorName();

        $message = (new MailMessage)
            ->subject(__('notifications.family_invitation.subject', [
                'inviter' => $this->invitedBy->name,
                'narrator_of' => Names::of($narrator),
            ]))
            ->greeting(__('notifications.family_invitation.greeting', [
                'name' => $notifiable->display_name,
            ]))
            ->line(__('notifications.family_invitation.gift', [
                'inviter' => $this->invitedBy->name,
                'narrator' => $narrator,
            ]))
            ->line(__('notifications.family_invitation.free'))
            ->line(__('notifications.family_invitation.sovereign', [
                'narrator' => $narrator,
            ]))
            ->line(__('notifications.family_invitation.react'));

        if ($notifiable->can_ask === true) {
            $message->line(__('notifications.family_invitation.contribute'));
        }

        return $message
            ->action(__('notifications.family_invitation.button'), $this->listenUrl())
            ->line(__('notifications.family_invitation.personal'))
            ->line(__('notifications.prompt.no_password'))
            ->salutation(__('notifications.prompt.signature', ['brand' => Brand::nameSafe()]));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "family-invitation:{$this->project->id}:{$channel->value}:".hash('sha256', $this->plainToken);
    }

    public function template(): string
    {
        return 'family_invitation';
    }

    public function projectId(): string
    {
        return $this->project->id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        // Aucun lien ici : un message sortant est consultable au support
        // (bloc 11), et un jeton dans une table de traces est un jeton fuité.
        return ['project_id' => $this->project->id];
    }

    private function narratorName(): string
    {
        $narrator = $this->project->primaryNarrator()->first();

        return $narrator === null
            ? __('notifications.family_invitation.your_relative')
            : $narrator->first_name;
    }
}
