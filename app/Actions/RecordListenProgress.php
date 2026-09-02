<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AnalyticsEvent;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\ListenEvent;
use App\Models\Story;
use App\Services\Analytics\Analytics;
use Illuminate\Support\Facades\DB;

/**
 * Cumule les secondes réellement écoutées.
 *
 * Une ligne par proche et par histoire, additionnée : c'est ce qui permet de
 * distinguer « a ouvert la page » de « a écouté », et le dossier fait de
 * cette distinction le cœur de la chaîne H2.
 *
 * `reached_30s` est posé **une seule fois**, et l'événement analytics part
 * une seule fois. Le franchissement du seuil est un fait daté ; le recompter
 * à chaque envoi gonflerait la mesure d'un facteur dix.
 */
final readonly class RecordListenProgress
{
    public function __construct(private Analytics $analytics) {}

    public function handle(
        Story $story,
        ?FamilyMember $member,
        int $seconds,
        TokenType $tokenType = TokenType::ListenProject,
    ): ListenEvent {
        [$event, $justCrossed] = DB::transaction(function () use ($story, $member, $seconds, $tokenType): array {
            $event = ListenEvent::query()
                ->where('story_id', $story->id)
                ->where('family_member_id', $member?->id)
                ->lockForUpdate()
                ->first();

            if ($event === null) {
                $event = new ListenEvent([
                    'token_type' => $tokenType,
                    'seconds_listened' => 0,
                    'started_at' => now(),
                ]);

                $event->story()->associate($story);

                if ($member !== null) {
                    $event->familyMember()->associate($member);
                }
            }

            $wasBelow = ! $event->reached_30s;
            $event->seconds_listened += max(0, $seconds);

            $crossed = $wasBelow && $event->seconds_listened >= ListenEvent::THRESHOLD_SECONDS;

            if ($crossed) {
                $event->reached_30s = true;
            }

            $event->save();

            return [$event, $crossed];
        });

        if ($justCrossed) {
            $this->analytics->capture(AnalyticsEvent::StoryListened30s, [
                'story_id' => $story->id,
                'project_id' => $story->project_id,
                'seconds_listened' => $event->seconds_listened,
            ], $member?->id);
        }

        return $event;
    }
}
