<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\RecordShareDecision;
use App\Enums\ShareDecision;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Les trois choix de fin d'enregistrement (variante A).
 *
 * L'écran ne présélectionne rien et n'affiche aucun minuteur : l'absence de
 * réaction ne vaut jamais accord (doc 04 §1). Le troisième choix, « décider
 * plus tard », existe pour que le narrateur puisse ne pas choisir sans que
 * son silence soit interprété.
 */
final readonly class ShareDecisionController
{
    public function __construct(private RecordShareDecision $decisions) {}

    public function store(Request $request): RedirectResponse
    {
        $story = $request->attributes->get('token_subject');

        abort_unless($story instanceof Story, 404);
        abort_unless(RecordShareDecision::isAvailableFor($story), 404);

        $validated = $request->validate([
            'decision' => ['required', Rule::enum(ShareDecision::class)],
        ]);

        $decision = ShareDecision::from((string) $validated['decision']);

        $this->decisions->handle($story, $decision);

        return back()->with('status', __('narrator.share_decision.recorded.'.$decision->value));
    }
}
