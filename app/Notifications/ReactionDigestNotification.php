<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Narrator;
use App\Models\Reaction;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * « Hier, trois personnes ont écouté vos histoires. »
 *
 * Un message par narrateur et par jour, qui nomme les personnes histoire par
 * histoire. Le but n'est pas d'informer : c'est de faire ressentir qu'on a
 * été écouté, et un nom fait ça mieux qu'un compteur.
 */
final class ReactionDigestNotification extends Notification implements TracksDelivery
{
    /**
     * @param  list<Reaction>  $reactions
     */
    public function __construct(
        private readonly Narrator $narrator,
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
        return $this->headline();
    }

    /**
     * « Une personne a écouté » ou « trois personnes ont écouté ».
     *
     * Écrit avec `trans_choice` et non `__` : la forme précédente disait
     * « 1 personne(s) ont écouté vos histoires », deux fautes en huit mots
     * dans un message dont le seul rôle est de donner envie de raconter la
     * suite (écart T-130).
     */
    private function headline(): string
    {
        $count = count($this->listeners());

        return trans_choice('notifications.reaction_received.digest.line', $count, [
            'count' => (string) $count,
        ]);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notifications.reaction_received.digest.subject'))
            ->greeting(__('notifications.reaction_received.greeting', ['name' => $notifiable->first_name]))
            ->line($this->headline());

        foreach ($this->byStory() as $line) {
            $message->line($line);
        }

        return $message->salutation(__('notifications.prompt.signature', [
            'brand' => Brand::nameSafe(),
        ]));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "reaction-digest:{$this->narrator->id}:{$channel->value}:".now()->toDateString();
    }

    public function template(): string
    {
        return 'reaction_digest';
    }

    public function projectId(): string
    {
        return $this->narrator->project_id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return [
            'narrator_id' => $this->narrator->id,
            'reactions' => count($this->reactions),
            'stories' => count($this->byStory()),
        ];
    }

    /**
     * @return list<string>
     */
    private function listeners(): array
    {
        $names = [];

        foreach ($this->reactions as $reaction) {
            $name = $reaction->familyMember->display_name;

            if (! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Une ligne par histoire, avec les prénoms.
     *
     * @return list<string>
     */
    private function byStory(): array
    {
        $grouped = [];

        foreach ($this->reactions as $reaction) {
            $title = $reaction->story->title
                ?? $reaction->story->questionText()
                ?? __('notifications.reaction_received.untitled');

            $name = $reaction->familyMember->display_name;

            if (! isset($grouped[$title])) {
                $grouped[$title] = [];
            }

            if (! in_array($name, $grouped[$title], true)) {
                $grouped[$title][] = $name;
            }
        }

        $lines = [];

        foreach ($grouped as $title => $names) {
            $lines[] = __('notifications.reaction_received.digest.story', [
                'title' => (string) $title,
                'names' => implode(', ', $names),
            ]);
        }

        return $lines;
    }
}
