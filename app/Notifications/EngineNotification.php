<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Enums\EngineRuleId;
use App\Models\Project;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un message du moteur de complétion, quelle que soit la règle.
 *
 * Une seule classe pour les onze règles, et c'est un choix : le ton de ces
 * messages est la partie la plus délicate du produit — jamais « vous n'avez
 * pas », toujours « quand vous voudrez » — et onze classes signifieraient onze
 * endroits où ce ton peut déraper. Ici, tout le texte vit dans
 * `lang/fr/notifications.php` sous `engine.*`, où un test le relit.
 *
 * Les actions sont une liste : l'alerte à l'Initiateur·rice au bout de
 * vingt-et-un jours en propose quatre. La première devient le bouton du
 * courriel et le lien du SMS — un SMS à deux liens ne se lit pas.
 */
final class EngineNotification extends Notification implements TracksDelivery
{
    /**
     * @param  array<string, string>  $replacements
     * @param  list<array{label: string, url: string}>  $actions
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly EngineRuleId $rule,
        private readonly string $key,
        private readonly Project $project,
        private readonly array $replacements = [],
        private readonly array $actions = [],
        private readonly array $payload = [],
        /**
         * Force le canal, quand la règle veut précisément **l'autre** — un
         * lien resté sans réponse par SMS a plus de chances d'aboutir par
         * courriel, et inversement.
         */
        private readonly ?Channel $forceChannel = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        $preference = $this->forceChannel ?? $notifiable->preferred_channel ?? Channel::Email;

        return array_values(array_filter(array_map(
            fn (Channel $channel): ?string => match ($channel) {
                Channel::Sms => ($notifiable->phone_e164 ?? null) === null ? null : SmsChannel::class,
                Channel::Email => ($notifiable->email ?? null) === null ? null : TrackedMailChannel::class,
                default => null,
            },
            $preference->resolve(),
        )));
    }

    public function toSms(mixed $notifiable): string
    {
        return __("notifications.engine.{$this->key}.sms", $this->replacementsFor($notifiable));
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $replacements = $this->replacementsFor($notifiable);

        $message = (new MailMessage)
            ->subject(__("notifications.engine.{$this->key}.subject", $replacements))
            ->greeting(__('notifications.engine.greeting', $replacements))
            ->line(__("notifications.engine.{$this->key}.line", $replacements));

        $first = $this->actions[0] ?? null;

        if ($first !== null) {
            $message->action($first['label'], $first['url']);
        }

        // Les actions suivantes en lignes : `MailMessage` n'a qu'un bouton,
        // et un courriel à quatre boutons ne se lit pas mieux qu'un à quatre
        // liens.
        foreach (array_slice($this->actions, 1) as $action) {
            $message->line("[{$action['label']}]({$action['url']})");
        }

        return $message->salutation(__('notifications.prompt.signature', [
            'brand' => Brand::nameSafe(),
        ]));
    }

    /**
     * @return array<string, string>
     */
    private function replacementsFor(mixed $notifiable): array
    {
        return [
            'name' => (string) ($notifiable->first_name ?? $notifiable->display_name ?? $notifiable->name ?? ''),
            'brand' => Brand::shortName(),
            'link' => $this->actions[0]['url'] ?? '',
            ...$this->replacements,
        ];
    }

    public function dedupeKey(Channel $channel): string
    {
        $occurrence = (string) ($this->payload['occurrence_key'] ?? $this->project->id);

        return "engine:{$this->rule->value}:{$occurrence}:{$channel->value}";
    }

    public function template(): string
    {
        return 'engine_'.$this->rule->value;
    }

    public function projectId(): string
    {
        return $this->project->id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        // Ni lien ni texte du message : un message sortant est consultable au
        // support, et un jeton dans une table de traces est un jeton fuité.
        return ['rule_id' => $this->rule->value, ...$this->payload];
    }
}
