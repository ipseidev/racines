<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Actions\IssueRecordToken;
use App\Engine\BaseRule;
use App\Engine\Occurrence;
use App\Enums\EngineRuleId;
use App\Enums\ShareDecision;
use App\Enums\TokenIssuedReason;
use App\Models\EngineEvent;
use App\Models\Story;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use App\States\Story\Validated;
use App\Support\Links;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Le texte est prêt, la décision ne vient pas.
 *
 * Deux rappels, puis **le silence** — et le silence est ici une décision de
 * conception, pas un abandon. Passé deux relances, l'histoire est marquée
 * `awaiting_quietly` : elle attend, indéfiniment, sans que personne ne
 * redemande. Le narrateur a le droit de ne pas trancher, et une troisième
 * relance transformerait une hésitation en dette.
 *
 * L'état, lui, ne bouge pas : rien n'est validé par lassitude.
 */
final class RecordedNotValidated extends BaseRule
{
    public function __construct(private readonly IssueRecordToken $tokens) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::RecordedNotValidated;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        $days = (int) config('product.engine.recorded_not_validated_days');
        $cutoff = $now->subDays($days);

        return Story::query()
            ->with(['project', 'narrator'])
            ->where(function ($query) use ($cutoff): void {
                $query
                    // Variante B : la relecture attend.
                    ->where(fn ($deferred) => $deferred
                        ->where('state', ToReview::$name)
                        ->where('transcribed_at', '<=', $cutoff))
                    // Variante A : le narrateur a demandé qu'on lui reredemande.
                    ->orWhere(fn ($later) => $later
                        ->where('state', Transcribed::$name)
                        ->where('share_decision', ShareDecision::DecideLater->value)
                        ->where('transcribed_at', '<=', $cutoff));
            })
            ->get()
            ->map(function (Story $story): Occurrence {
                // Les rappels **partis**, pas les lignes : un événement
                // supprimé n'a relancé personne.
                $already = $this->firedForStory($story->id);

                return new Occurrence(
                    project: $story->project,
                    story: $story,
                    narrator: $story->narrator,
                    attempt: $already + 1,
                );
            })
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        $max = (int) config('product.engine.recorded_not_validated_max_reminders');

        return $occurrence->attempt > $max;
    }

    public function fire(Occurrence $occurrence): array
    {
        $narrator = $occurrence->narrator;
        $story = $occurrence->story;

        if ($narrator === null || $story === null) {
            return ['skipped' => 'no_recipient'];
        }

        $issued = $this->tokens->handle($story, TokenIssuedReason::Rotation);
        $max = (int) config('product.engine.recorded_not_validated_max_reminders');

        $payload = $this->tell(
            $narrator,
            $occurrence,
            'validation_reminder',
            [],
            [[
                'label' => __('notifications.engine.validation_reminder.button'),
                'url' => Links::record($issued->plain).'/review',
            ]],
        );

        if ($occurrence->attempt >= $max) {
            // Dernier rappel envoyé : à partir de maintenant, on attend en
            // silence. L'histoire reste exactement là où elle est.
            $payload['awaiting_quietly'] = true;
        }

        return $payload;
    }

    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $story = $event->story;

        if ($story === null) {
            return false;
        }

        if ($story->state instanceof Validated || $story->validated_at !== null) {
            return true;
        }

        // Garder pour soi est une décision, et donc une reprise : ce qu'on
        // mesurait, c'est l'absence de décision.
        if ($story->share_decision === ShareDecision::KeepPrivate) {
            return true;
        }

        return $event->fired_at->lte($now->subDays(14)) ? false : null;
    }
}
