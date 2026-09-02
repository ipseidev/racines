<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Story;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use App\Support\Links;
use App\Support\SmsLength;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * La question de la semaine, par SMS et/ou par courriel.
 *
 * Trois exigences d'anti-hameçonnage du doc 04 §9 tiennent dans ce message :
 * la marque est nommée, le lien part du domaine annoncé dès l'invitation, et
 * **jamais** d'un raccourcisseur. Le courriel rappelle en clair qu'aucune page
 * ne demandera de mot de passe ni de paiement.
 *
 * Le lien est en dernier dans le SMS : c'est la seule position où un client
 * SMS ne le tronque pas, et la seule où le narrateur a lu la phrase avant de
 * toucher l'écran.
 */
final class PromptNotification extends Notification implements TracksDelivery
{
    public function __construct(
        private readonly Story $story,
        private readonly string $plainToken,
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

    /**
     * Un SMS qui tient dans un seul segment : au-delà, l'opérateur le découpe
     * et certains téléphones anciens n'en affichent que le premier morceau.
     */
    public function toSms(mixed $notifiable): string
    {
        $link = $this->link();
        $brand = Brand::shortName();
        $firstName = (string) ($notifiable->first_name ?? '');

        $render = fn (string $name): string => __('notifications.prompt.sms', [
            'name' => $name,
            'brand' => $brand,
            'link' => $link,
        ]);

        $body = $render($firstName);

        // Si le prénom fait déborder, on le raccourcit avant de sacrifier le
        // lien : un SMS sans lien ne sert à rien.
        if (SmsLength::exceedsSingleSegment($body)) {
            $body = $render(SmsLength::shorten($firstName));
        }

        return $body;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.prompt.subject'))
            ->markdown('mail.prompt', [
                'firstName' => $notifiable->first_name ?? '',
                'question' => $this->story->questionText(),
                'link' => $this->link(),
                'brand' => Brand::name(),
                'supportEmail' => Brand::supportEmail(),
            ]);
    }

    public function dedupeKey(Channel $channel): string
    {
        return "prompt:{$this->story->id}:{$channel->value}";
    }

    public function template(): string
    {
        return 'prompt';
    }

    public function projectId(): string
    {
        return $this->story->project_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function deliveryPayload(): array
    {
        return [
            'story_id' => $this->story->id,
            'sequence' => $this->story->sequence,
            'question_slug' => $this->story->question?->slug,
        ];
    }

    public function link(): string
    {
        return Links::record($this->plainToken);
    }
}
