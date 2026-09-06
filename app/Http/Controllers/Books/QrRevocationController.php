<?php

declare(strict_types=1);

namespace App\Http\Controllers\Books;

use App\Books\IssueQrToken;
use App\Books\RevokeQrToken;
use App\Models\BookChapter;
use App\Models\Narrator;
use App\Models\Story;
use App\Support\InitiatorProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Désactiver, ou rallumer, le QR d'une histoire.
 *
 * Deux portes vers le même geste : l'espace du narrateur — c'est son récit —
 * et l'espace de l'Initiateur·rice, qui reçoit souvent la demande par
 * téléphone. Aucune ne décide à la place de l'autre : elles exécutent la même
 * action, et la trace dit qui a agi.
 *
 * La réactivation redonne le **même** code : les exemplaires déjà imprimés
 * refonctionnent. Un nouveau code les condamnerait.
 */
final readonly class QrRevocationController
{
    public function __construct(
        private RevokeQrToken $revoke,
        private IssueQrToken $issue,
    ) {}

    public function destroy(Request $request, string $token, Story $story): RedirectResponse
    {
        $this->authorizeNarrator($request, $story);
        $this->revoke->handle($story);

        return back()->with('status', __('narrator.space.qr.revoked'));
    }

    public function restore(Request $request, string $token, Story $story): RedirectResponse
    {
        $this->authorizeNarrator($request, $story);
        $this->reissue($story);

        return back()->with('status', __('narrator.space.qr.restored'));
    }

    /** La même paire, depuis l'espace de l'Initiateur·rice. */
    public function initiatorDestroy(Request $request, Story $story): RedirectResponse
    {
        $this->authorizeInitiator($request, $story);
        $this->revoke->handle($story);

        return back()->with('status', __('narrator.space.qr.revoked'));
    }

    public function initiatorRestore(Request $request, Story $story): RedirectResponse
    {
        $this->authorizeInitiator($request, $story);
        $this->reissue($story);

        return back()->with('status', __('narrator.space.qr.restored'));
    }

    private function reissue(Story $story): void
    {
        $chapter = BookChapter::query()->where('story_id', $story->getKey())->first();

        if ($chapter instanceof BookChapter) {
            $this->issue->handle($chapter);
        }
    }

    private function authorizeNarrator(Request $request, Story $story): void
    {
        $subject = $request->attributes->get('token_subject');

        abort_unless(
            $subject instanceof Narrator && $subject->project_id === $story->project_id,
            404,
        );
    }

    private function authorizeInitiator(Request $request, Story $story): void
    {
        $user = $request->user();
        abort_if($user === null, 403);

        abort_unless(InitiatorProject::forOrFail($user)->getKey() === $story->project_id, 404);
    }
}
