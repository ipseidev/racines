<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Lead;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use App\Support\Percent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Le code de réduction de bienvenue (T-141).
 *
 * Il dit le code, sa valeur, sa date de fin et comment s'en servir, puis
 * mène au tunnel. Rien d'autre : un courriel qu'on a demandé pour un code
 * doit donner le code, pas un argumentaire.
 */
final class WelcomeOfferNotification extends Notification implements TracksDelivery
{
    public function __construct(private readonly Lead $lead) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return [TrackedMailChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $amount = Percent::format($this->lead->discount_percent);

        $message = (new MailMessage)
            ->subject(__('notifications.welcome_offer.subject', ['amount' => $amount]))
            ->greeting(__('notifications.welcome_offer.greeting'))
            ->line(__('notifications.welcome_offer.code_line', ['code' => $this->lead->discount_code]))
            ->line(__('notifications.welcome_offer.value_line', [
                'amount' => $amount,
                'date' => $this->lead->code_expires_at->translatedFormat('j F Y'),
            ]))
            ->line(__('notifications.welcome_offer.how_line'))
            ->action(__('notifications.welcome_offer.button'), route('checkout.show'));

        if ($this->lead->news_opted_in_at !== null) {
            $message->line(__('notifications.welcome_offer.news_line'));
        }

        return $message->salutation(__('notifications.prompt.signature', ['brand' => Brand::nameSafe()]));
    }

    /**
     * Une par minute et par personne : deux clics rapprochés ne font qu'un
     * courriel, et un envoi tombé en panne se retente la minute suivante.
     */
    public function dedupeKey(Channel $channel): string
    {
        return "welcome-offer:{$this->lead->id}:{$channel->value}:".now()->format('YmdHi');
    }

    public function template(): string
    {
        return 'welcome_offer';
    }

    public function projectId(): ?string
    {
        return null;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return ['lead_id' => $this->lead->id, 'discount_percent' => $this->lead->discount_percent];
    }
}
