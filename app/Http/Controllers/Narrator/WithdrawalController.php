<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\DeleteStoryAction;
use App\Actions\HideStoryAction;
use App\Actions\RestoreStoryAction;
use App\Actions\SetStoryVisibility;
use App\Actions\TrashStoryAction;
use App\Actions\UnhideStoryAction;
use App\Enums\DeletionRequestedBy;
use App\Enums\StoryVisibility;
use App\Models\Narrator;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Les cinq retraits, depuis l'espace narrateur.
 *
 * Chaque route commence par vérifier que l'histoire appartient bien au
 * narrateur du jeton. Ce n'est pas une redondance avec le middleware : le
 * jeton d'espace porte une **personne**, pas une histoire, et un identifiant
 * d'histoire arrive dans l'URL — sans cette vérification, il suffirait de
 * changer un UUID pour agir sur le récit de quelqu'un d'autre.
 *
 * La suppression demande le mot SUPPRIMER en clair. Ce n'est pas une
 * cérémonie : c'est le seul geste irréversible du produit, et il doit demander
 * un acte que le doigt ne fait pas par accident.
 */
final readonly class WithdrawalController
{
    public function __construct(
        private HideStoryAction $hide,
        private UnhideStoryAction $unhide,
        private TrashStoryAction $trash,
        private RestoreStoryAction $restore,
        private DeleteStoryAction $delete,
        private SetStoryVisibility $visibilities,
    ) {}

    public function hide(Request $request, string $token, string $story): RedirectResponse
    {
        $this->hide->handle(self::ownStory($request, $story));

        return back()->with('status', __('narrator.withdrawals.hidden'));
    }

    public function unhide(Request $request, string $token, string $story): RedirectResponse
    {
        $this->unhide->handle(self::ownStory($request, $story));

        return back()->with('status', __('narrator.withdrawals.unhidden'));
    }

    public function trash(Request $request, string $token, string $story): RedirectResponse
    {
        $this->trash->handle(self::ownStory($request, $story));

        return back()->with('status', __('narrator.withdrawals.trashed'));
    }

    public function restore(Request $request, string $token, string $story): RedirectResponse
    {
        $target = self::ownStory($request, $story);

        if (! RestoreStoryAction::isAvailableFor($target)) {
            // La fenêtre est fermée. Le dire en une phrase vaut mieux qu'une
            // erreur technique : la personne cherchait un souvenir, pas un
            // code d'état.
            throw ValidationException::withMessages([
                'restore' => __('narrator.withdrawals.restore_window_closed', [
                    'days' => (int) config('product.stories.trash_retention_days'),
                ]),
            ]);
        }

        $this->restore->handle($target);

        return back()->with('status', __('narrator.withdrawals.restored'));
    }

    public function destroy(Request $request, string $token, string $story): RedirectResponse
    {
        $target = self::ownStory($request, $story);

        $confirmation = (string) $request->input('confirmation', '');

        if ($confirmation !== __('narrator.withdrawals.delete_word')) {
            throw ValidationException::withMessages([
                'confirmation' => __('narrator.withdrawals.delete_word_missing', [
                    'word' => __('narrator.withdrawals.delete_word'),
                ]),
            ]);
        }

        $this->delete->handle($target, DeletionRequestedBy::Narrator);

        return back()->with('status', __('narrator.withdrawals.deleted'));
    }

    public function visibility(Request $request, string $token, string $story): RedirectResponse
    {
        $target = self::ownStory($request, $story);

        $validated = $request->validate([
            'visibility' => ['required', Rule::enum(StoryVisibility::class)],
            'family_member_ids' => ['sometimes', 'array'],
            'family_member_ids.*' => ['string'],
        ]);

        $this->visibilities->handle(
            $target,
            StoryVisibility::from((string) $validated['visibility']),
            array_values(array_map('strval', (array) ($validated['family_member_ids'] ?? []))),
        );

        return back()->with('status', __('narrator.withdrawals.visibility_changed'));
    }

    /**
     * L'histoire du narrateur de ce jeton, et aucune autre.
     */
    private static function ownStory(Request $request, string $storyId): Story
    {
        $narrator = $request->attributes->get('token_subject');

        abort_unless($narrator instanceof Narrator, 404);

        $story = $narrator->stories()->whereKey($storyId)->first();

        abort_unless($story instanceof Story, 404);

        return $story;
    }
}
