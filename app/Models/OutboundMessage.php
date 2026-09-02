<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\Channel;
use App\Enums\OutboundMessageStatus;
use App\Services\Tokens\OtpService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un message parti du produit, et ce qu'il est devenu.
 *
 * Le destinataire n'y figure jamais en clair : une empreinte pour
 * dédupliquer, une forme masquée pour que le support puisse dire « envoyé au
 * 06 •• •• •• 12 » sans conserver le numéro une seconde fois.
 *
 * @property string $id
 * @property string|null $project_id
 * @property Channel $channel
 * @property string $to_hash
 * @property string $to_masked
 * @property string $template
 * @property array<string, mixed>|null $payload
 * @property string|null $provider
 * @property string|null $provider_message_id
 * @property OutboundMessageStatus $status
 * @property string|null $status_detail
 * @property string $dedupe_key
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $failed_at
 * @property CarbonImmutable|null $created_at
 */
final class OutboundMessage extends Model
{
    use HasUuids, StoresDatesWithOffset;

    public const UPDATED_AT = null;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => OutboundMessageStatus::Queued->value];

    /** @var list<string> */
    protected $fillable = [
        'project_id', 'channel', 'template', 'payload', 'provider',
        'provider_message_id', 'status', 'status_detail', 'dedupe_key',
        'sent_at', 'delivered_at', 'failed_at',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Empreinte du destinataire : sert à dédupliquer et à retrouver les envois
     * d'une même personne sans stocker sa coordonnée.
     */
    public static function hashRecipient(string $recipient): string
    {
        return hash('sha256', mb_strtolower(trim($recipient)));
    }

    /**
     * Forme lisible et incomplète : « +336•••••12 » ou « c•••e@example.test ».
     */
    public static function mask(string $recipient): string
    {
        return OtpService::mask($recipient);
    }

    public function markSent(?string $providerMessageId, ?string $provider = null): void
    {
        $this->provider_message_id = $providerMessageId;
        $this->provider = $provider ?? $this->provider;
        $this->status = OutboundMessageStatus::Sent;
        $this->sent_at = now();
        $this->save();
    }

    public function markFailed(string $detail): void
    {
        $this->status = OutboundMessageStatus::Failed;
        $this->status_detail = mb_substr($detail, 0, 255);
        $this->failed_at = now();
        $this->save();
    }

    /**
     * Rapporte ce que dit un webhook de livraison.
     *
     * Un statut plus avancé ne redescend jamais : un opérateur qui envoie
     * `sent` après `delivered` ne doit pas faire croire que le message n'est
     * plus arrivé.
     */
    public function applyProviderStatus(OutboundMessageStatus $status, ?string $detail = null): void
    {
        if ($this->status->reached() && ! $status->isFailure()) {
            return;
        }

        $this->status = $status;
        $this->status_detail = $detail === null ? $this->status_detail : mb_substr($detail, 0, 255);

        if ($status->reached()) {
            $this->delivered_at = now();
        }

        if ($status->isFailure()) {
            $this->failed_at = now();
        }

        $this->save();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'channel' => Channel::class,
            'status' => OutboundMessageStatus::class,
            'payload' => 'array',
            'sent_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }
}
