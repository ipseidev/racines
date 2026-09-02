<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use App\Exceptions\Domain\FeatureClosed;
use App\Features\MandateDelegation;
use App\Models\Consent;
use App\Models\Mandate;
use App\Models\Narrator;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Confie à un proche le droit de valider à la place du narrateur.
 *
 * Trois verrous, et chacun répond à une façon dont ce mandat pourrait
 * dériver :
 *
 *  1. **Le drapeau du projet.** Fermé, le mandat n'existe pas — 404, pas 403.
 *  2. **Un consentement en vigueur du narrateur**, du bon motif. Sans lui,
 *     un proche pressé validerait le récit de quelqu'un qui ne l'a pas relu.
 *  3. **Un canal qui laisse une trace** : le web ou le téléphone, jamais
 *     l'administration. Un « accord » saisi par le support n'est pas
 *     l'accord du narrateur.
 *
 * Un seul mandat vivant par mandataire : accorder à nouveau révoque le
 * précédent, pour qu'il n'y ait jamais deux périmètres à comparer.
 */
final class GrantMandate
{
    /** @var list<string> */
    public const DEFAULT_SCOPE = ['validate'];

    /** @var list<ConsentChannel> */
    private const TRUSTED_CHANNELS = [ConsentChannel::Web, ConsentChannel::Phone];

    /**
     * @param  list<string>  $scope
     */
    public function handle(
        Project $project,
        Narrator $narrator,
        Model $holder,
        Consent $consent,
        array $scope = self::DEFAULT_SCOPE,
    ): Mandate {
        if (! MandateDelegation::isOpenFor($project)) {
            throw FeatureClosed::make('mandate-delegation');
        }

        self::guardConsent($narrator, $consent);

        return DB::transaction(function () use ($project, $narrator, $holder, $consent, $scope): Mandate {
            Mandate::query()
                ->where('narrator_id', $narrator->id)
                ->where('holder_type', $holder->getMorphClass())
                ->where('holder_id', (string) $holder->getKey())
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $mandate = new Mandate(['scope' => $scope, 'granted_at' => now()]);
            $mandate->project()->associate($project);
            $mandate->narrator()->associate($narrator);
            $mandate->consent()->associate($consent);
            $mandate->holder()->associate($holder);
            $mandate->save();

            Log::warning('mandate.granted', [
                'mandate_id' => $mandate->id,
                'narrator_id' => $narrator->id,
                'holder_type' => $holder->getMorphClass(),
                'scope' => $mandate->scope,
                'consent_channel' => $consent->channel->value,
            ]);

            return $mandate;
        });
    }

    private static function guardConsent(Narrator $narrator, Consent $consent): void
    {
        if ($consent->subject_id !== $narrator->id) {
            throw new InvalidArgumentException('Le consentement doit être celui du narrateur mandant.');
        }

        if ($consent->kind !== ConsentKind::MandateDelegation) {
            throw new InvalidArgumentException('Le consentement ne porte pas sur la délégation de validation.');
        }

        if ($consent->status !== ConsentStatus::Granted) {
            throw new InvalidArgumentException('Le consentement à la délégation n’est pas en vigueur.');
        }

        if (! in_array($consent->channel, self::TRUSTED_CHANNELS, true)) {
            throw new InvalidArgumentException(
                'Un mandat se consent par le web ou par téléphone, jamais par l’administration.',
            );
        }
    }
}
