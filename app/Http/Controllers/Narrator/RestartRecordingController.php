<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\RestartRecording;
use App\Models\AccessToken;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Recommencer l'histoire que ce lien porte.
 *
 * Comme pour le masquage, la route ne prend **aucun** identifiant d'histoire :
 * elle agit sur le sujet du jeton et sur rien d'autre. Et sans code : c'est le
 * même geste de regret que le masquage, à ceci près qu'elle veut redire les
 * choses plutôt que les retirer, et lui demander un SMS la ferait renoncer.
 */
final readonly class RestartRecordingController
{
    public function __construct(private RestartRecording $restart) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $token = $request->attributes->get('access_token');
        $story = $request->attributes->get('token_subject');

        abort_unless($token instanceof AccessToken, 404);
        abort_unless($story instanceof Story, 404);
        abort_unless($this->restart->mayRestart($story), 403);

        $this->restart->handle($story);

        return redirect("/r/{$request->route('token')}");
    }
}
