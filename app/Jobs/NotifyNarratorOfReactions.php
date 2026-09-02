<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AnalyticsEvent;
use App\Features\ReactionNotificationTiming;
use App\Models\OutboundMessage;
use App\Models\Reaction;
use App\Models\Story;
use App\Notifications\ReactionReceivedNotification;
use App\Services\Analytics\Analytics;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Prévient le narrateur qu'on l'a écouté — au bon moment, et pas trop.
 *
 * Le moment est l'objet d'une micro-expérience (drapeau
 * `reaction-notification-timing`) : tout de suite, ou le lendemain matin.
 * Le dossier refuse de trancher sans mesure, parce qu'une notification
 * immédiate peut aussi bien réjouir que déranger — un SMS à 23 h chez une
 * personne de 85 ans n'est pas une bonne nouvelle.
 *
 * Trois garde-fous, quel que soit le moment :
 *  - **une** notification par histoire et par jour, agrégée ;
 *  - jamais pendant une pause demandée (`paused_until`) ;
 *  - jamais si l'histoire a cessé d'être visible entre-temps.
 */
final class NotifyNarratorOfReactions implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $storyId)
    {
        $this->onQueue('notifications');
    }

    public function handle(Analytics $analytics): void
    {
        $story = Story::query()->with(['narrator', 'project'])->find($this->storyId);

        if ($story === null || ! $story->isVisibleToFamily()) {
            // Le narrateur a masqué son récit entre la réaction et l'envoi :
            // le prévenir à propos de ce qu'il vient de cacher serait le pire
            // moment pour lui écrire.
            return;
        }

        if (! ReactionNotificationTiming::isImmediateFor($story->project)) {
            // Variante « lendemain matin » : le digest s'en charge.
            return;
        }

        if ($story->project->isPaused()) {
            Log::info('family.reaction_notification_skipped_paused', ['story_id' => $story->id]);

            return;
        }

        $reactions = self::recentReactions($story);

        if ($reactions->isEmpty()) {
            return;
        }

        if (self::alreadyNotifiedToday($story)) {
            Log::info('family.reaction_notification_capped', ['story_id' => $story->id]);

            return;
        }

        $story->narrator->notify(new ReactionReceivedNotification($story, array_values($reactions->all())));

        $analytics->capture(AnalyticsEvent::NarratorNotified, [
            'story_id' => $story->id,
            'project_id' => $story->project_id,
            'timing' => 'immediate',
            'reactions' => $reactions->count(),
        ], $story->narrator_id);
    }

    /**
     * @return Collection<int, Reaction>
     */
    private static function recentReactions(Story $story): Collection
    {
        return $story->reactions()
            ->with('familyMember')
            ->where('updated_at', '>=', now()->subMinutes(5))
            ->orderBy('updated_at')
            ->get();
    }

    /**
     * Le plafond : une notification par histoire et par jour.
     *
     * Trois proches qui réagissent le même soir ne font pas trois SMS. La
     * trace des envois (`outbound_messages`, bloc 05) est la source de
     * vérité : elle sait ce qui est **parti**, pas ce qu'on a voulu envoyer.
     */
    private static function alreadyNotifiedToday(Story $story): bool
    {
        return OutboundMessage::query()
            ->where('template', 'reaction_received')
            ->whereJsonContains('payload->story_id', $story->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();
    }
}
