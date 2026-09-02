<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Actions\RecordListenProgress;
use App\Enums\TokenType;
use App\Exceptions\Domain\StoryUnavailable;
use App\Models\AccessToken;
use App\Queries\VisibleStoriesForFamilyMember;
use App\Support\FamilyPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce que le lecteur audio rapporte : des secondes écoutées.
 *
 * Le client envoie un cumul toutes les dix secondes et à la pause. On ne lui
 * fait pas confiance sur le total — `RecordListenProgress` additionne des
 * incréments bornés côté serveur — mais on lui fait confiance sur le fait
 * qu'il a joué du son, ce qu'aucun serveur ne peut vérifier.
 */
final readonly class ListenProgressController
{
    public function __construct(private RecordListenProgress $progress) {}

    public function store(Request $request, string $token, string $story): JsonResponse
    {
        $member = FamilyPresenter::memberFor($request);
        $found = (new VisibleStoriesForFamilyMember($member))->find($story);

        if ($found === null) {
            throw StoryUnavailable::make();
        }

        $validated = $request->validate([
            'seconds' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        $accessToken = $request->attributes->get('access_token');

        $event = $this->progress->handle(
            $found,
            $member,
            (int) $validated['seconds'],
            $accessToken instanceof AccessToken ? $accessToken->type : TokenType::ListenProject,
        );

        return response()->json([
            'seconds_listened' => $event->seconds_listened,
            'reached_30s' => $event->reached_30s,
        ]);
    }
}
