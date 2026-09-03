<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Models\Narrator;
use App\Models\Story;
use App\States\Story\Proposed;
use App\States\Story\Trashed;
use App\Support\PhotoPresenter;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * « Vos histoires » : la liste, en langage simple.
 *
 * Aucun nom d'état technique n'apparaît. Un narrateur ne lit pas
 * « transcribed » : il lit « en attente de votre choix ». La traduction vit
 * dans les fichiers de langue, et la carte porte aussi la date jusqu'à
 * laquelle une histoire en corbeille reste récupérable — une promesse qu'on
 * n'annonce pas se juge comme une promesse rompue.
 *
 * Les histoires simplement proposées ne sont pas listées : il n'y a rien à en
 * faire, et leur présence donnerait l'impression d'un travail en retard.
 */
final class SpaceController
{
    public function __invoke(Request $request): Response
    {
        $narrator = $request->attributes->get('token_subject');

        abort_unless($narrator instanceof Narrator, 404);

        $retention = (int) config('product.stories.trash_retention_days');

        $stories = $narrator->stories()
            ->with('question')
            ->whereNot('state', Proposed::$name)
            ->orderByDesc('recorded_at')
            ->orderByDesc('sequence')
            ->get()
            ->map(fn (Story $story): array => [
                'id' => $story->id,
                'title' => $story->title,
                'question' => $story->questionText(),
                'state' => $story->state->getValue(),
                'label' => __('narrator.space.states.'.$story->state->getValue()),
                'recordedAt' => $story->recorded_at?->toIso8601String(),
                'visibility' => $story->visibility->value,
                'printedInBook' => (bool) $story->printed_in_book,
                'restorableUntil' => $story->state instanceof Trashed
                    ? $story->trashed_at?->addDays($retention)->toIso8601String()
                    : null,
                // Les photos de l'histoire, avec leurs URL temporaires : le
                // narrateur voit ce qui est joint à son récit, et peut le
                // retirer — c'est le sien, y compris ce qu'un proche y a mis.
                'photos' => PhotoPresenter::forStory($story),
            ])
            ->all();

        return inertia('narrator/Space', [
            'firstName' => $narrator->first_name,
            'addressForm' => $narrator->project->address_form->value,
            'stories' => $stories,
            'pausedUntil' => $narrator->project->paused_until?->toIso8601String(),
            'printedCopiesWarning' => __('narrator.withdrawals.printed_copies_warning'),
        ]);
    }
}
