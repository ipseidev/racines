<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Engine\BaseRule;
use App\Engine\Occurrence;
use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Enums\TokenType;
use App\Models\EngineEvent;
use App\Models\ListenEvent;
use App\Models\Story;
use App\Queries\VisibleStoriesForFamilyMember;
use App\Services\Tokens\TokenService;
use App\States\Story\Shared;
use App\Support\Links;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Une histoire partagée que personne n'a écoutée.
 *
 * C'est le maillon le plus important de la chaîne H2 : le narrateur a
 * raconté, il a accepté de partager, et rien ne revient. Un nudge par
 * histoire, à chaque proche, avec **son** lien direct — pas la liste : on
 * demande deux minutes d'écoute, pas une visite.
 *
 * Un seul nudge par histoire. Si personne n'écoute après ça, insister ne
 * changerait rien, et ce n'est pas au narrateur de payer le silence de sa
 * famille par des rappels supplémentaires.
 */
final class ValidatedNotListened extends BaseRule
{
    public function __construct(private readonly TokenService $tokens) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::ValidatedNotListened;
    }

    public function audience(Occurrence $occurrence): EngineAudience
    {
        return EngineAudience::Family;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        $days = (int) config('product.engine.validated_not_listened_days');

        return Story::query()
            ->with(['project.familyMembers', 'narrator'])
            ->where('state', Shared::$name)
            ->where('shared_at', '<=', $now->subDays($days))
            ->whereDoesntHave('listenEvents', fn ($query) => $query->where('reached_30s', true))
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
        $story = $occurrence->story;

        if ($story === null) {
            return ['skipped' => 'no_story'];
        }

        $nudged = 0;

        foreach ($occurrence->project->familyMembers as $member) {
            // Chaque proche ne reçoit un lien que s'il a bien le droit
            // d'écouter cette histoire : la visibilité restreinte s'applique
            // ici comme partout ailleurs.
            if ((new VisibleStoriesForFamilyMember($member))->find($story->id) === null) {
                continue;
            }

            $issued = $this->tokens->issue(
                TokenType::ListenStory,
                $story,
                ['listen', 'react'],
                now()->addDays(30),
                issuedTo: $member,
            );

            $this->tell(
                $member,
                $occurrence,
                'new_story_nudge',
                [
                    'narrator' => $occurrence->narrator === null ? '' : $occurrence->narrator->first_name,
                    'title' => $story->title ?? $story->questionText() ?? '',
                ],
                [[
                    'label' => __('notifications.engine.new_story_nudge.button'),
                    'url' => Links::listenStory($issued->plain),
                ]],
            );

            $nudged++;
        }

        return ['nudged' => $nudged];
    }

    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $listened = ListenEvent::query()
            ->where('story_id', $event->story_id)
            ->where('reached_30s', true)
            ->exists();

        if ($listened) {
            return true;
        }

        return $event->fired_at->lte($now->subDays(7)) ? false : null;
    }
}
