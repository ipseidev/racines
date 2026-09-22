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
use App\Support\PhotoPresenter;
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
            /*
             * Les photos qui posent la question (et non celles de la
             * réponse). Une famille qui joint une image demande « raconte-nous
             * celle-ci » ; jusqu'ici l'image partait en base et personne ne la
             * voyait avant l'enregistrement — c'est-à-dire jamais au moment où
             * elle servait.
             */
            'questionPhotos' => PhotoPresenter::promptsForStory($story),
            'storyRef' => hash('sha256', $story->id),
            'state' => $story->state->getValue(),
            'limits' => [
                'softWarningSeconds' => (int) config('product.recording.soft_warning_seconds'),
                'hardStopSeconds' => (int) config('product.recording.hard_stop_seconds'),
                'maxBytes' => (int) config('product.recording.max_bytes'),
                'segmentMilliseconds' => (int) config('product.recording.segment_milliseconds'),
                'firstRunSeconds' => (int) config('product.recording.first_run_seconds'),
                'partSizeBytes' => (int) config('product.recording.upload_part_bytes'),
                'acceptedMimes' => array_values((array) config('product.recording.accepted_mimes')),
                // Les bornes de la vidéo (T-210). Le débit est imposé au
                // navigateur : laissé libre, il produit un fichier qu'une 4G
                // de campagne n'enverra jamais.
                'video' => [
                    'maxBytes' => (int) config('product.recording.video.max_bytes'),
                    'bitsPerSecond' => (int) config('product.recording.video.bits_per_second'),
                    'audioBitsPerSecond' => (int) config('product.recording.video.audio_bits_per_second'),
                    'height' => (int) config('product.recording.video.height'),
                    'acceptedMimes' => array_values((array) config('product.recording.video.accepted_mimes')),
                ],
            ],
            'writtenAnswerMaxChars' => 20_000,
            // La variante décide si les trois choix s'affichent ici, juste
            // après la confirmation, ou si la relecture viendra par message
            // (bloc 07 §6.2).
            'validationVariant' => Feature::for($project)->value(ValidationVariant::class),
            'shareDecisionAction' => route('narrator.share_decision.store', ['token' => $request->route('token')], false),
            'shareDecision' => $story->share_decision?->value,
            // Déclarée par l'acheteur (T-136) : la page dose son aide d'après
            // elle. Nulle pour les projets antérieurs.
            'techComfort' => $narrator->tech_comfort?->value,
            /*
             * La déclaration d'avance (D-10) : quand elle existe, l'écran de
             * fin **annonce** au lieu de demander. La question ne disparaît
             * pas pour autant — « garder celle-ci pour moi » reste à un
             * doigt, parce qu'un accord permanent n'est pas un engagement
             * histoire par histoire.
             */
            'declaredSharing' => $project->declared_sharing_at !== null,
            /*
             * Le tout premier lien de cette personne (T-247), qui décide du
             * tour de chauffe.
             *
             * Le signal est **ce qu'elle a déjà fait**, pas ce que son
             * navigateur se rappelle : un témoin local se perd en navigation
             * privée, sur un second téléphone, ou au premier nettoyage — et
             * proposer d'essayer à quelqu'un qui a déjà raconté dix histoires
             * serait la façon la plus sûre de passer pour une machine qui ne
             * reconnaît personne.
             *
             * Deux souvenirs, et non un seul. « Elle a déjà enregistré »
             * ne suffisait pas : quelqu'un qui joue les quinze secondes puis
             * referme sans répondre n'a rien enregistré, et retrouvait le
             * tour de chauffe à chaque ouverture. `first_run_at` retient
             * qu'il a été **proposé et répondu** — joué ou passé.
             */
            'firstTime' => $narrator->first_run_at === null
                && ! $narrator->stories()
                    ->whereNotNull('recorded_at')
                    ->exists(),
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
