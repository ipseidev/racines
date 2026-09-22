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

        $this->rememberFirstRun($story, (string) $request->validated('event'));

        return response()->json(status: 202);
    }

    /**
     * Le seul événement de cette table qui fasse autre chose que se compter.
     *
     * Le tour de chauffe (T-247) se décidait sur « cette personne n'a jamais
     * enregistré d'histoire ». Quelqu'un qui le joue puis referme sans
     * répondre n'a rien enregistré : il le retrouvait à l'ouverture suivante,
     * et à chaque fois tant qu'un récit n'était pas allé au bout. Ce qu'il
     * faut retenir n'est pas un enregistrement, c'est **qu'il a été proposé
     * et qu'on y a répondu** — joué ou passé, les deux valent réponse.
     *
     * La date s'écrit ici plutôt que par une route à elle : le signal part
     * déjà, au bon moment, en `keepalive`, et ajouter un aller-retour à une
     * page qui tient dans un budget de 150 Ko se paierait pour rien. Le prix
     * est cette exception, qui vaut d'être nommée : un envoi perdu fait
     * revoir le tour de chauffe une fois de plus, jamais deux mois de suite.
     */
    private function rememberFirstRun(Story $story, string $event): void
    {
        if (! in_array($event, ['first_run_done', 'first_run_skipped'], true)) {
            return;
        }

        $narrator = $story->narrator;

        if ($narrator->first_run_at !== null) {
            return;
        }

        $narrator->first_run_at = now();
        $narrator->save();
    }
}
