<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Enums\AnalyticsEvent;
use App\Services\Analytics\Analytics;
use App\Support\FamilyPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * « Les histoires de {Prénom} ».
 *
 * Un lien d'histoire n'a pas de liste à montrer : il mène directement à son
 * histoire. Afficher une liste vide, ou la liste complète, seraient deux
 * façons de trahir le périmètre du lien.
 */
final class HomePageController
{
    public function __invoke(Request $request, Analytics $analytics): Response|RedirectResponse
    {
        $member = FamilyPresenter::memberFor($request);
        $pinned = FamilyPresenter::pinnedStory($request);

        if ($pinned !== null) {
            return redirect()->route('family.stories.show', [
                'token' => $request->route('token'),
                'story' => $pinned->id,
            ]);
        }

        $analytics->capture(AnalyticsEvent::FamilyLinkOpened, [
            'project_id' => $member->project_id,
        ], $member->id);

        return inertia('family/Home', [
            'narratorFirstName' => $member->project->primaryNarrator()->first()?->first_name,
            'inviterName' => $member->invitedBy?->name,
            'stories' => FamilyPresenter::cards($member),
        ]);
    }
}
