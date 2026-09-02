<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AnalyticsEvent;
use App\Features\ReactionNotificationTiming;
use App\Models\Narrator;
use App\Models\Reaction;
use App\Notifications\ReactionDigestNotification;
use App\Services\Analytics\Analytics;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Le digest du matin : « on vous a écouté·e hier ».
 *
 * L'autre moitié de la micro-expérience H2. Un SMS à 23 h chez une personne
 * de 85 ans n'est pas une bonne nouvelle ; un message à 9 h, oui. Reste à
 * savoir si l'élan survit à la nuit — c'est ce que la mesure dira.
 *
 * Un message par narrateur, jamais par réaction : trois proches qui réagissent
 * le même soir font un message qui les nomme tous les trois.
 */
final class SendReactionDigests extends Command
{
    protected $signature = 'reactions:send-digests';

    protected $description = 'Envoie aux narrateurs le résumé des réactions de la veille';

    public function handle(Analytics $analytics): int
    {
        $since = now()->subDay()->startOfDay();
        $until = now()->startOfDay();
        $sent = 0;

        Reaction::query()
            ->with(['story.narrator', 'story.project', 'familyMember'])
            ->whereBetween('updated_at', [$since, $until])
            ->get()
            ->groupBy(fn (Reaction $reaction): string => $reaction->story->narrator_id)
            ->each(function (Collection $reactions, string $narratorId) use ($analytics, &$sent): void {
                $narrator = Narrator::query()->with('project')->find($narratorId);

                if ($narrator === null) {
                    return;
                }

                if (ReactionNotificationTiming::isImmediateFor($narrator->project)) {
                    // Ce projet est en notification immédiate : le digest n'a
                    // rien à y faire, sinon on écrirait deux fois.
                    return;
                }

                if ($narrator->project->isPaused()) {
                    return;
                }

                // Une histoire masquée depuis hier soir ne se rappelle pas au
                // narrateur par un résumé.
                $visible = $reactions->filter(
                    fn (Reaction $reaction): bool => $reaction->story->isVisibleToFamily(),
                );

                if ($visible->isEmpty()) {
                    return;
                }

                $narrator->notify(new ReactionDigestNotification($narrator, array_values($visible->all())));
                $sent++;

                foreach ($visible->groupBy('story_id')->keys() as $storyId) {
                    $analytics->capture(AnalyticsEvent::NarratorNotified, [
                        'story_id' => (string) $storyId,
                        'project_id' => $narrator->project_id,
                        'timing' => ReactionNotificationTiming::NEXT_MORNING,
                    ], $narrator->id);
                }
            });

        $this->components->info(sprintf('%d résumé(s) envoyé(s).', $sent));

        return self::SUCCESS;
    }
}
