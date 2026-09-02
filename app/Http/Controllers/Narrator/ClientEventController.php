<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Http\Requests\Narrator\ClientEventRequest;
use App\Models\ClientEvent;
use App\Models\Story;
use Illuminate\Http\JsonResponse;

/**
 * Ce que le navigateur du narrateur rapporte de sa séance.
 *
 * Sans ces événements, on ne saurait pas *pourquoi* un narrateur n'a pas
 * enregistré : micro refusé, page purgée, envoi échoué. C'est la matière du
 * taux d'échec de capture du doc 04 §11 et des règles du moteur du bloc 09.
 */
final class ClientEventController
{
    public function store(ClientEventRequest $request): JsonResponse
    {
        $story = $request->attributes->get('token_subject');

        abort_unless($story instanceof Story, 404);

        /** @var array<string, mixed>|null $payload */
        $payload = $request->validated('payload');

        $event = new ClientEvent([
            'event' => (string) $request->validated('event'),
            'payload' => $payload,
        ]);

        $event->story()->associate($story);
        $event->save();

        return response()->json(status: 202);
    }
}
