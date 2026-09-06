<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Models\Book;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Les trois messages du livre, adressés à l'Initiateur·rice.
 *
 * Une seule classe pour trois motifs plutôt que trois classes : les textes
 * diffèrent, la mécanique est identique, et trois fichiers de quatre-vingts
 * lignes qui ne se distinguent que par une clé de traduction finissent par
 * diverger sur ce qui compte — le canal choisi, la déduplication.
 *
 * **Aucune promesse de délai d'impression.** Le devis n'est pas fait, et une
 * date annoncée qu'on ne tient pas coûte plus cher que l'attente (doc 03
 * P0-14). De même, « il y a de quoi faire un livre » n'est pas « faites votre
 * livre » : le rythme appartient à la famille.
 */
final class BookNotification extends Notification implements TracksDelivery
{
    /**
     * @param  'ready'|'format_proposal'|'proof_ready'  $reason
     */
    public function __construct(
        private readonly Book $book,
        public readonly string $reason,
    ) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        $preference = $notifiable->preferred_channel ?? Channel::Email;

        return array_values(array_filter(array_map(
            fn (Channel $channel): ?string => match ($channel) {
                Channel::Sms => ($notifiable->phone_e164 ?? null) === null ? null : SmsChannel::class,
                Channel::Email => ($notifiable->email ?? null) === null ? null : TrackedMailChannel::class,
                default => null,
            },
            $preference->resolve(),
        )));
    }

    public function bookUrl(): string
    {
        return url('/espace/livre');
    }

    public function toSms(mixed $notifiable): string
    {
        return __('notifications.book.'.$this->reason.'.sms', [
            'brand' => Brand::shortName(),
            'first_name' => $this->firstName(),
            'link' => $this->bookUrl(),
        ]);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.book.'.$this->reason.'.subject'))
            ->greeting(__('notifications.book.greeting', ['name' => $notifiable->name ?? '']))
            ->line(__('notifications.book.'.$this->reason.'.line', [
                'first_name' => $this->firstName(),
                'format' => $this->formatLabel(),
            ]))
            ->action(__('notifications.book.'.$this->reason.'.button'), $this->bookUrl())
            ->line(__('notifications.prompt.no_password'))
            ->salutation(__('notifications.book.signature', ['brand' => Brand::nameSafe()]));
    }

    /**
     * La clé de déduplication porte la **version du BAT**.
     *
     * Sans elle, un second bon à tirer n'annoncerait rien : la famille aurait
     * corrigé le lexique, relancé la génération, et attendrait un courriel
     * qui ne viendrait jamais. C'est la leçon T-154 — une clé qui ignore ce
     * qui distingue deux envois les fait passer pour un seul.
     */
    public function dedupeKey(Channel $channel): string
    {
        return "book-{$this->reason}:{$this->book->id}:{$this->book->proof_version}:{$channel->value}";
    }

    public function template(): string
    {
        return 'book_'.$this->reason;
    }

    public function projectId(): string
    {
        return $this->book->project_id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return [
            'book_id' => $this->book->id,
            'reason' => $this->reason,
            'proof_version' => $this->book->proof_version,
        ];
    }

    private function firstName(): string
    {
        return $this->book->project->primaryNarrator?->first_name ?: '';
    }

    private function formatLabel(): string
    {
        $format = $this->book->proposed_format ?? $this->book->format;

        return __('enums.book_format.'.$format->value);
    }
}
