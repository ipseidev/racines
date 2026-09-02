<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Enums\AnalyticsEvent;
use App\Exceptions\Domain\StoryUnavailable;
use App\Queries\VisibleStoriesForFamilyMember;
use App\Services\Analytics\Analytics;
use App\Services\Storage\MediaStorage;
use App\Support\FamilyPresenter;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Une histoire, écoutée par un proche.
 *
 * Tout passe par `VisibleStoriesForFamilyMember`. Une histoire non visible
 * n'est pas « chargée puis masquée » : elle n'est **pas trouvée**, et la
 * réponse ne contient donc rien d'elle — ni son titre, ni sa question, ni son
 * identifiant. La différence compte : un objet chargé finit par fuir dans des
 * props.
 */
final class StoryPageController
{
    public function __invoke(
        Request $request,
        string $token,
        string $story,
        MediaStorage $storage,
        Analytics $analytics,
    ): Response {
        $member = FamilyPresenter::memberFor($request);
        $pinned = FamilyPresenter::pinnedStory($request);

        // Un lien d'histoire n'ouvre que la sienne : le périmètre du jeton
        // prime sur ce que l'URL demande.
        if ($pinned !== null && $pinned->id !== $story) {
            throw StoryUnavailable::make();
        }

        $found = (new VisibleStoriesForFamilyMember($member))->find($story);

        if ($found === null) {
            throw StoryUnavailable::make();
        }

        $analytics->capture(AnalyticsEvent::StoryPageOpened, [
            'story_id' => $found->id,
            'project_id' => $found->project_id,
        ], $member->id);

        return inertia('family/Story', [
            'narratorFirstName' => $found->narrator->first_name,
            ...FamilyPresenter::storyProps($found, $member, $storage),
            'siblings' => FamilyPresenter::siblings($member, $found),
        ]);
    }
}
