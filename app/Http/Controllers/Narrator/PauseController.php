<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Models\Narrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * « Demander une pause » : les questions s'arrêtent, le projet continue.
 *
 * Une pause a une **fin**, toujours. Un arrêt sans terme ferait disparaître le
 * projet en silence, et personne ne saurait s'il faut relancer ou pas. Le
 * moteur de complétion (bloc 09) reprend le fil tout seul à l'échéance.
 */
final class PauseController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $narrator = $request->attributes->get('token_subject');

        abort_unless($narrator instanceof Narrator, 404);

        $validated = $request->validate([
            'weeks' => ['required', 'integer', 'min:1', 'max:26'],
        ]);

        $weeks = (int) $validated['weeks'];

        $project = $narrator->project;
        $project->paused_until = now()->addWeeks($weeks);
        $project->save();

        return back()->with('status', __('narrator.space.paused', ['weeks' => $weeks]));
    }
}
