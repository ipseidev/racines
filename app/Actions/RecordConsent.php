<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use App\Exceptions\Domain\ConsentOperatorRequired;
use App\Exceptions\Domain\MissingConsentText;
use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Enregistre un consentement accordé.
 *
 * Trois exigences du doc 04 §2 sont tenues ici : le consentement porte la
 * version exacte du texte lu ; l'adresse IP n'est gardée que sous forme
 * d'empreinte ; un accord recueilli par téléphone nomme son opérateur.
 */
final class RecordConsent
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

        $locale = (string) config('app.locale');
        $text = ConsentText::current($kind, $locale);

        if ($text === null) {
            throw MissingConsentText::forKind($kind->value, $locale);
        }

        $consent = new Consent([
            'kind' => $kind,
            'status' => ConsentStatus::Granted,
            'channel' => $channel,
            'text_version' => $text->version,
            'ip_hash' => self::hashIp($context['ip'] ?? null),
            'user_agent' => $context['user_agent'] ?? null,
            'granted_at' => now(),
            'recorded_by_user_id' => $recordedBy?->id,
        ]);

        $consent->project()->associate($project);
        $consent->subject()->associate($subject);
        $consent->save();

        return $consent;
    }

    public static function hashIp(?string $ip): ?string
    {
        return $ip === null ? null : hash('sha256', $ip);
    }
}
