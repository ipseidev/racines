<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Order;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * La confirmation d'achat.
 *
 * Elle dit trois choses, et la troisième est celle qu'on oublie : ce qui a
 * été acheté, **quand le cadeau partira**, et que le narrateur reste libre de
 * refuser — avec remboursement intégral. Le dire ici plutôt qu'au moment du
 * refus évite que la déception se double d'une surprise.
 *
 * Le délai de rétractation est annoncé, avec sa date : une mention légale
 * qu'on ne peut pas lire ne vaut pas information.
 */
final class OrderConfirmationNotification extends Notification implements TracksDelivery
{
    public function __construct(private readonly Order $order) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return [TrackedMailChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $project = $this->order->project;

        $message = (new MailMessage)
            ->subject(__('notifications.checkout.confirmation.subject'))
            ->greeting(__('notifications.checkout.confirmation.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.checkout.confirmation.line', [
                'narrator' => $project->primaryNarrator->first_name ?? '',
            ]));

        if ($project?->gift_send_at !== null) {
            $message->line(__('notifications.checkout.confirmation.gift_date', [
                'date' => $project->gift_send_at->translatedFormat('j F Y'),
            ]));
        }

        return $message
            // Dit maintenant plutôt qu'au moment du refus : la déception ne
            // doit pas se doubler d'une surprise.
            ->line(__('notifications.checkout.confirmation.free_to_refuse'))
            ->line(__('notifications.checkout.confirmation.withdrawal', [
                'date' => $this->order->withdrawal_deadline_at?->translatedFormat('j F Y') ?? '',
            ]))
            ->salutation(__('notifications.prompt.signature', ['brand' => Brand::nameSafe()]));
    }

    public function dedupeKey(Channel $channel): string
    {
        return "order-confirmation:{$this->order->id}:{$channel->value}";
    }

    public function template(): string
    {
        return 'checkout_confirmation';
    }

    public function projectId(): ?string
    {
        return $this->order->project_id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return ['order_id' => $this->order->id, 'total_cents' => $this->order->total_cents];
    }
}
