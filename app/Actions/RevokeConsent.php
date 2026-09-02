<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use App\Exceptions\Domain\ConsentNotGranted;
use App\Exceptions\Domain\ConsentOperatorRequired;
use App\Models\Consent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Retire un consentement.
 *
 * La ligne d'origine n'est jamais modifiée : la révocation ajoute une ligne,
 * de sorte que l'historique dise à la fois ce qui a été accordé et quand cela
 * a cessé de l'être (doc 04 §2). La date d'accord est reprise de la ligne
 * révoquée, pour que la période couverte reste lisible.
 */
final class RevokeConsent
{
    /**
     * @param  array{ip?: string|null, user_agent?: string|null}  $context
     */
    public function handle(
        Model $subject,
        Project $project,
        ConsentKind $kind,
        ConsentChannel $channel,
        ?User $recordedBy = null,
        array $context = [],
    ): Consent {
        if ($channel === ConsentChannel::Phone && $recordedBy === null) {
            throw ConsentOperatorRequired::make();
        }

        $granted = Consent::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('kind', $kind->value)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($granted === null || $granted->status !== ConsentStatus::Granted) {
            throw ConsentNotGranted::forKind($kind->value);
        }

        $consent = new Consent([
            'kind' => $kind,
            'status' => ConsentStatus::Revoked,
            'channel' => $channel,
            'text_version' => $granted->text_version,
            'ip_hash' => RecordConsent::hashIp($context['ip'] ?? null),
            'user_agent' => $context['user_agent'] ?? null,
            'granted_at' => $granted->granted_at,
            'revoked_at' => now(),
            'recorded_by_user_id' => $recordedBy?->id,
        ]);

        $consent->project()->associate($project);
        $consent->subject()->associate($subject);
        $consent->save();

        return $consent;
    }
}
