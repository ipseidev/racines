<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Actions\IssueRecordToken;
use App\Engine\BaseRule;
use App\Engine\Occurrence;
use App\Enums\ClientEventName;
use App\Enums\EngineRuleId;
use App\Enums\TokenIssuedReason;
use App\Models\EngineEvent;
use App\Models\Story;
use App\States\Story\Proposed;
use App\Support\Links;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Quelqu'un a commencé à raconter, et n'a jamais envoyé.
 *
 * Le brouillon est resté sur son téléphone — c'est précisément ce que le
 * bloc 04 garantit. Le message le lui rappelle sans lui reprocher quoi que ce
 * soit : « votre histoire vous attend », pas « vous n'avez pas terminé ». La
 * personne a peut-être été interrompue par un appel, ou s'est arrêtée parce
 * que le souvenir était difficile.
 *
 * Un seul rappel. Si le brouillon reste, c'est un choix, et il se respecte.
 */
final class RecordingAbandoned extends BaseRule
{
    public function __construct(private readonly IssueRecordToken $tokens) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::RecordingAbandoned;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        $days = (int) config('product.engine.recording_abandoned_days');

        return Story::query()
            ->with(['project', 'narrator'])
            ->where('state', Proposed::$name)
            ->whereHas(
                'clientEvents',
                fn ($query) => $query
                    ->where('event', ClientEventName::RecordingStarted->value)
                    ->where('created_at', '<=', $now->subDays($days)),
            )
            // Rien de confirmé : un enregistrement envoyé n'est pas abandonné.
            ->whereDoesntHave('recordings', fn ($query) => $query->whereNotNull('confirmed_at'))
            ->get()
            ->map(fn (Story $story): Occurrence => new Occurrence(
                project: $story->project,
                story: $story,
                narrator: $story->narrator,
            ))
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        return $this->firedForStory($occurrence->story?->id) >= 1;
    }

    public function fire(Occurrence $occurrence): array
    {
        $narrator = $occurrence->narrator;
        $story = $occurrence->story;

        if ($narrator === null || $story === null) {
            return ['skipped' => 'no_recipient'];
        }

        // Le même lien qu'avant aurait suffi, mais il a pu expirer : le
        // brouillon, lui, est rangé par histoire, pas par jeton.
        $issued = $this->tokens->handle($story, TokenIssuedReason::Rotation);

        return $this->tell(
            $narrator,
            $occurrence,
            'draft_waiting',
            [],
            [['label' => __('notifications.engine.draft_waiting.button'), 'url' => Links::record($issued->plain)]],
        );
    }

    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $confirmed = Story::query()
            ->whereKey($event->story_id)
            ->whereHas('recordings', fn ($query) => $query->whereNotNull('confirmed_at'))
            ->exists();

        if ($confirmed) {
            return true;
        }

        return $event->fired_at->lte($now->subDays(7)) ? false : null;
    }
}
