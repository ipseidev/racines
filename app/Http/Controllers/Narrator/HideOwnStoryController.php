<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\HideStoryAction;
use App\Models\AccessToken;
use App\Models\Story;
use App\Support\SensitiveActs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Masquer l'histoire que ce lien porte, sans code.
 *
 * La route ne prend **aucun** identifiant d'histoire : elle agit sur le sujet
 * du jeton, et sur rien d'autre. Accepter un paramètre ouvrirait exactement
 * ce que `SensitiveActs` refuse — agir sur le récit d'un autre depuis un lien
 * qui n'en porte qu'un.
 */
final readonly class HideOwnStoryController
{
    public function __construct(private HideStoryAction $hide) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $token = $request->attributes->get('access_token');
        $story = $request->attributes->get('token_subject');

        abort_unless($token instanceof AccessToken, 404);
        abort_unless($story instanceof Story, 404);

        // Ceinture et bretelles : la route est déjà limitée au sujet du
        // jeton, et l'invariant est vérifié là où il est écrit.
        abort_if(SensitiveActs::requiresGrant($story, $token), 403);

        $this->hide->handle($story);

        return back()->with('status', __('narrator.withdrawals.hidden'));
    }
}
