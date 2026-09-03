<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\RestartRecording;
use App\Features\ValidationVariant;
use App\Models\AccessToken;
use App\Models\Story;
use App\States\Story\Hidden;
use App\States\Story\Proposed;
use App\States\Story\Recorded;
use Illuminate\Http\Request;
use Inertia\Response;
use Laravel\Pennant\Feature;

/**
 * La page d'enregistrement du narrateur.
 *
 * Elle ne montre que ce dont la personne a besoin : son prénom, sa question,
 * les limites de durée. Aucune donnée d'un tiers, aucun identifiant technique
 * lisible — un lien porteur ne doit pas devenir une fiche de renseignement.
 *
 * Une histoire déjà enregistrée n'est pas un cul-de-sac : la page le dit et
 * propose de recommencer. C'est le narrateur qui décide, pas l'état.
 */
final class RecordPageController
{
    public function __construct(private readonly RestartRecording $restart) {}

    public function __invoke(Request $request): Response
    {
        $token = $request->attributes->get('access_token');
        $story = $request->attributes->get('token_subject');

        abort_unless($token instanceof AccessToken, 404);
        abort_unless($story instanceof Story, 404);

        $project = $story->project;
        $narrator = $story->narrator;

        $props = [
            'firstName' => $narrator->first_name,
            'addressForm' => $project->address_form->value,
            'question' => $story->questionText(),
            'storyRef' => hash('sha256', $story->id),
            'state' => $story->state->getValue(),
            'limits' => [
                'softWarningSeconds' => (int) config('product.recording.soft_warning_seconds'),
                'hardStopSeconds' => (int) config('product.recording.hard_stop_seconds'),
                'maxBytes' => (int) config('product.recording.max_bytes'),
                'segmentMilliseconds' => (int) config('product.recording.segment_milliseconds'),
                'partSizeBytes' => (int) config('product.recording.upload_part_bytes'),
                'acceptedMimes' => array_values((array) config('product.recording.accepted_mimes')),
            ],
            'writtenAnswerMaxChars' => 20_000,
            // La variante décide si les trois choix s'affichent ici, juste
            // après la confirmation, ou si la relecture viendra par message
            // (bloc 07 §6.2).
            'validationVariant' => Feature::for($project)->value(ValidationVariant::class),
            'shareDecisionAction' => route('narrator.share_decision.store', ['token' => $request->route('token')], false),
            'shareDecision' => $story->share_decision?->value,
        ];

        if ($story->state instanceof Recorded || ! $story->state instanceof Proposed) {
            $recording = $story->currentRecording()->first();

            return inertia('narrator/AlreadyRecorded', [
                ...$props,
                'recordedAt' => $recording?->confirmed_at?->toIso8601String()
                    ?? $story->recorded_at?->toIso8601String(),
                'answerType' => $story->answer_type?->value,
                // Masquer se propose depuis ce lien, sans code : il porte
                // précisément cette histoire (bloc 07 §6.5).
                'canHide' => ! $story->state instanceof Hidden,
                // Recommencer aussi, tant qu'elle n'a rien validé (bloc 04).
                'canRestart' => $this->restart->mayRestart($story),
                'restartAction' => route('narrator.record.restart', ['token' => $request->route('token')], false),
            ]);
        }

        return inertia('narrator/Record', $props);
    }
}
