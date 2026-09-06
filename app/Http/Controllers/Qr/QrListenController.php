<?php

declare(strict_types=1);

namespace App\Http\Controllers\Qr;

use App\Actions\RecordListenProgress;
use App\Enums\TokenType;
use App\Models\Story;
use App\Support\QrFamilyCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Les secondes écoutées depuis un QR imprimé.
 *
 * L'événement porte `token_type = qr` et **aucun proche** : on ne sait pas qui
 * tient le livre, et c'est le sens même d'une page lisible sans compte. Le
 * chiffre sert à savoir si les QR servent — la question que D-8 pose — pas à
 * savoir qui écoute.
 *
 * Le code famille est revérifié ici : sans cela, une requête directe
 * compterait des écoutes sur un livre verrouillé.
 */
final readonly class QrListenController
{
    public function __construct(private RecordListenProgress $progress) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        $story = $request->attributes->get('token_subject');

        if (! $story instanceof Story || ! $story->isVisibleToFamily()) {
            abort(404);
        }

        if (! QrFamilyCode::isUnlocked($request, $story->project)) {
            abort(403);
        }

        $validated = $request->validate([
            'seconds' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        $event = $this->progress->handle($story, null, (int) $validated['seconds'], TokenType::Qr);

        return response()->json([
            'seconds_listened' => $event->seconds_listened,
            'reached_30s' => $event->reached_30s,
        ]);
    }
}
