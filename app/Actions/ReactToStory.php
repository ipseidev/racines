<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AnalyticsEvent;
use App\Enums\ReactionType;
use App\Jobs\NotifyNarratorOfReactions;
use App\Models\FamilyMember;
use App\Models\Reaction;
use App\Models\Story;
use App\Services\Analytics\Analytics;
use Illuminate\Support\Facades\Log;

/**
 * Un cœur, un merci, et un mot si on veut.
 *
 * Idempotent par construction : un cœur donné deux fois reste un cœur. Le
 * narrateur n'a pas à distinguer un enthousiasme d'un double-clic, et une
 * notification par tap serait du harcèlement.
 *
 * Le mot, lui, **remplace** le précédent : quelqu'un qui se relit et corrige
 * son message ne doit pas en laisser deux.
 */
final readonly class ReactToStory
{
    public function __construct(private Analytics $analytics) {}

    public function handle(
        Story $story,
        FamilyMember $member,
        ReactionType $type,
        ?string $comment = null,
    ): Reaction {
        // `firstOrNew` puis association explicite : les clés étrangères
        // restent hors de l'assignation de masse, comme partout ailleurs.
        $reaction = Reaction::query()
            ->where('story_id', $story->id)
            ->where('family_member_id', $member->id)
            ->where('type', $type->value)
            ->first() ?? new Reaction(['type' => $type]);

        $reaction->story()->associate($story);
        $reaction->familyMember()->associate($member);
        $reaction->comment = $comment;
        $reaction->save();

        $this->analytics->capture(AnalyticsEvent::ReactionSent, [
            'story_id' => $story->id,
            'project_id' => $story->project_id,
            'type' => $type->value,
            'has_comment' => $comment !== null,
        ], $member->id);

        Log::info('family.reaction', [
            'story_id' => $story->id,
            'family_member_id' => $member->id,
            'type' => $type->value,
        ]);

        // Différé d'une minute : le temps d'agréger un cœur et un merci
        // envoyés d'affilée en une seule notification.
        NotifyNarratorOfReactions::dispatch($story->id)
            ->delay(now()->addSeconds(60));

        return $reaction;
    }
}
