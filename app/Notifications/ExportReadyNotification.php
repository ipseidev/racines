<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;
use App\Enums\ExportKind;
use App\Models\Export;
use App\Notifications\Channels\TrackedMailChannel;
use App\Support\Brand;
use App\Support\Links;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * « Vos données sont prêtes. »
 *
 * Le message dit trois choses, et la troisième est celle qu'on oublie : le
 * lien **expire dans sept jours**, et un nouveau se demande gratuitement. Une
 * échéance annoncée sans porte de sortie transforme un service en piège, et
 * quelqu'un qui part en vacances doit pouvoir revenir sans avoir rien perdu.
 *
 * Le lien ne part **jamais par SMS** : c'est une archive de récits de
 * famille, un SMS se transfère d'un doigt, et le public de ce produit est
 * justement celui que le hameçonnage vise (doc 04 §9).
 */
final class ExportReadyNotification extends Notification implements TracksDelivery
{
    public function __construct(
        private readonly Export $export,
        private readonly string $plainToken,
    ) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        // Courriel seulement. Voir le commentaire de classe : un lien de
        // téléchargement dans un SMS est un lien qu'on transfère sans y penser.
        return ($notifiable->email ?? null) === null ? [] : [TrackedMailChannel::class];
    }

    public function url(): string
    {
        return Links::export($this->plainToken);
    }

    public function toSms(mixed $notifiable): string
    {
        // Jamais appelée : `via()` n'ouvre pas le canal SMS. Présente parce
        // que le contrat de notification du dépôt la déclare.
        return __('notifications.export.sms', ['brand' => Brand::shortName()]);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $cle = $this->export->kind === ExportKind::GdprAccess ? 'access' : 'ready';

        return (new MailMessage)
            ->subject(__('notifications.export.'.$cle.'.subject'))
            ->greeting(__('notifications.export.greeting', ['name' => $notifiable->name ?? $notifiable->first_name ?? '']))
            ->line(__('notifications.export.'.$cle.'.line'))
            ->action(__('notifications.export.button'), $this->url())
            ->line(__('notifications.export.expiry', ['days' => Export::DAYS]))
            ->line(__('notifications.export.keep'))
            ->salutation(__('notifications.export.signature', ['brand' => Brand::nameSafe()]));
    }

    public function dedupeKey(Channel $channel): string
    {
        // L'identifiant de l'export : deux exports du même projet sont deux
        // envois légitimes, et une clé au projet n'en laisserait passer qu'un.
        return "export-ready:{$this->export->id}:{$channel->value}";
    }

    public function template(): string
    {
        return 'export_'.$this->export->kind->value;
    }

    public function projectId(): string
    {
        return $this->export->project_id;
    }

    /** @return array<string, mixed> */
    public function deliveryPayload(): array
    {
        return [
            'export_id' => $this->export->id,
            'kind' => $this->export->kind->value,
            'bytes' => $this->export->bytes,
        ];
    }
}
