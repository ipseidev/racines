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

        // Une vue plutôt que des lignes : le code est posé seul, en grand,
        // parce que c'est lui qu'on est venu chercher (T-237).
        return (new MailMessage)
            ->subject(__('notifications.welcome_offer.subject', ['amount' => $amount]))
            ->markdown('mail.welcome-offer', [
                'code' => $this->lead->discount_code,
                'amount' => $amount,
                'date' => $this->lead->code_expires_at->translatedFormat('j F Y'),
                'url' => route('checkout.show'),
                'news' => $this->lead->news_opted_in_at !== null,
                'brand' => Brand::nameSafe(),
            ]);
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
