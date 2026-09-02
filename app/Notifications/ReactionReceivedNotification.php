<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Reaction;
use App\Models\Story;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * « Marie a écouté « {titre} » et vous dit merci. »
 *
 * Le message nomme la personne et cite son mot. C'est tout l'intérêt : « une
 * réaction » ne fait rien ressentir, « Marie vous dit merci » si. Et il ne
 * porte **aucun lien** : le narrateur n'a rien à consulter, on lui rapporte
 * une bonne nouvelle, on ne lui donne pas une tâche.
 *
 * @phpstan-type ReactionList list<Reaction>
 */
final class ReactionReceivedNotification extends Notification implements TracksDelivery
{
    /**
     * @param  list<Reaction>  $reactions
     */
    public function __construct(
        private readonly Story $story,
        private readonly array $reactions,
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

    public function toSms(mixed $notifiable): string
    {
        return __('notifications.reaction_received.sms', [
            'names' => $this->names(),
            'title' => $this->title(),
            'brand' => Brand::shortName(),
        ]);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notifications.reaction_received.subject', ['names' => $this->names()]))
            ->greeting(__('notifications.reaction_received.greeting', ['name' => $notifiable->first_name]))
            ->line(__('notifications.reaction_received.line', [
                'names' => $this->names(),
                'title' => $this->title(),
            ]));

        foreach ($this->comments() as $comment) {
            $message->line($comment);
        }

        return $message->salutation(__('notifications.prompt.signature', [
            'brand' => Brand::nameSafe(),
        ]));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "reaction-received:{$this->story->id}:{$channel->value}:".now()->toDateString();
    }

    public function template(): string
    {
        return 'reaction_received';
    }

    public function projectId(): string
    {
        return $this->story->project_id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return [
            'story_id' => $this->story->id,
            'reactions' => count($this->reactions),
        ];
    }

    private function title(): string
    {
        return $this->story->title
            ?? $this->story->questionText()
            ?? __('notifications.reaction_received.untitled');
    }

    /**
     * Les prénoms, dédupliqués et dans l'ordre d'arrivée.
     */
    private function names(): string
    {
        $names = [];

        foreach ($this->reactions as $reaction) {
            $name = $reaction->familyMember->display_name;

            if (! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return implode(', ', $names);
    }

    /**
     * @return list<string>
     */
    private function comments(): array
    {
        $lines = [];

        foreach ($this->reactions as $reaction) {
            if ($reaction->comment === null) {
                continue;
            }

            $lines[] = __('notifications.reaction_received.comment', [
                'name' => $reaction->familyMember->display_name,
                'comment' => $reaction->comment,
            ]);
        }

        return $lines;
    }
}
