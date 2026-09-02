<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Actions\ReactToStory;
use App\Enums\ReactionType;
use App\Exceptions\Domain\StoryUnavailable;
use App\Queries\VisibleStoriesForFamilyMember;
use App\Support\FamilyPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Réagir à une histoire : un tap, et un mot si on veut.
 *
 * La visibilité est revérifiée ici, et pas seulement à l'affichage : entre le
 * chargement de la page et le tap, le narrateur a pu masquer son récit. Sans
 * cette seconde vérification, une réaction arriverait sur une histoire
 * retirée — et le narrateur serait notifié à propos de ce qu'il vient de
 * cacher.
 */
final readonly class ReactionController
{
    public function __construct(private ReactToStory $reactions) {}

    public function store(Request $request, string $token, string $story): RedirectResponse
    {
        $member = FamilyPresenter::memberFor($request);
        $found = (new VisibleStoriesForFamilyMember($member))->find($story);

        if ($found === null) {
            throw StoryUnavailable::make();
        }

        $validated = $request->validate([
            'type' => ['required', Rule::enum(ReactionType::class)],
            'comment' => ['nullable', 'string', 'max:280'],
        ]);

        $comment = $validated['comment'] ?? null;

        $this->reactions->handle(
            $found,
            $member,
            ReactionType::from((string) $validated['type']),
            is_string($comment) && trim($comment) !== '' ? trim($comment) : null,
        );

        // Retour **explicite** sur la page de l'histoire, et non `back()` :
        // le proche y est arrivé par une navigation Inertia, et l'en-tête
        // `Referer` du navigateur pointe encore la liste. `back()` le
        // renvoyait donc à la liste, en lui faisant perdre sa place et la
        // confirmation de son geste.
        return redirect()
            ->route('family.stories.show', ['token' => $token, 'story' => $found->id])
            ->with('status', __('family.reaction.sent', [
                'first_name' => $found->narrator->first_name,
            ]));
    }
}
