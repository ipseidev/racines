<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AddressForm;
use App\Enums\Cadence;
use App\Enums\Channel;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ProjectStatus;
use App\Enums\PromptSlot;
use App\Models\Invitation;
use App\Models\Narrator;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Le narrateur accepte.
 *
 * C'est le moment H0 du dossier, et le seul endroit où un projet devient
 * `active`. Avant lui, **rien** ne part : ni question, ni relance. Le cadeau
 * se propose, il ne s'impose pas.
 *
 * Cinq consentements, un par case cochée, chacun sa ligne datée avec la
 * version du texte lu. Pas un « j'accepte tout » : le dossier 04 §2 les veut
 * distincts et révocables, et une case unique rendrait la révocation d'un
 * seul impossible.
 *
 * Le premier prompt est planifié au **lendemain**, pas dans l'heure : quelqu'un
 * qui vient d'accepter a besoin de digérer, et une question qui arrive dans la
 * minute donne l'impression d'une machine qui attendait.
 */
final readonly class AcceptInvitation
{
    /**
     * Les cinq consentements de l'opt-in. `sensitive_categories` en fait
     * partie : c'est l'accord « je comprends que je peux parler de sujets
     * personnels », et il vaut mieux qu'il soit dit avant qu'après.
     *
     * @var list<ConsentKind>
     */
    public const CONSENTS = [
        ConsentKind::VoiceRecording,
        ConsentKind::Transcription,
        ConsentKind::AiRendering,
        ConsentKind::FamilySharing,
        ConsentKind::SensitiveCategories,
    ];

    public function __construct(
        private RecordConsent $consents,
        private ScheduleNextPrompt $schedule,
    ) {}

    /**
     * @param  array<string, mixed>  $preferences
     */
    public function handle(Project $project, array $preferences): Project
    {
        $narrator = $project->primaryNarrator;

        if ($narrator === null) {
            return $project;
        }

        return DB::transaction(function () use ($project, $narrator, $preferences): Project {
            $this->applyPreferences($narrator, $project, $preferences);

            foreach (self::CONSENTS as $kind) {
                $this->consents->handle($narrator, $project, $kind, ConsentChannel::Web);
            }

            $project->status = ProjectStatus::Active;
            $project->accepted_at = now();
            $project->collection_started_at = now();
            // Douze semaines de collecte au pilote, puis la finalisation : les
            // fenêtres sont posées maintenant pour que les écrans puissent
            // dire où l'on en est, et le moteur savoir quand se taire.
            $project->collection_ends_at = now()->addWeeks(12);
            $project->finalization_ends_at = now()->addWeeks(16);

            // Le lendemain, au créneau choisi : une question dans la minute
            // donne l'impression d'une machine qui attendait.
            $project->next_prompt_at = $this->schedule->handle($project, now()->addDay());
            $project->save();

            Invitation::query()
                ->where('narrator_id', $narrator->id)
                ->whereNull('accepted_at')
                ->whereNull('refused_at')
                ->latest('sent_at')
                ->first()
                ?->forceFill(['accepted_at' => now(), 'opened_at' => now()])
                ->save();

            Log::info('invitation.accepted', [
                'project_id' => $project->id,
                'attempts' => Invitation::attemptsFor($narrator),
            ]);

            return $project->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $preferences
     */
    private function applyPreferences(Narrator $narrator, Project $project, array $preferences): void
    {
        if (isset($preferences['preferred_channel'])) {
            $narrator->preferred_channel = Channel::from((string) $preferences['preferred_channel']);
        }

        if (isset($preferences['narrator_phone'])) {
            $narrator->phone_e164 = (string) $preferences['narrator_phone'];
        }

        $narrator->opted_in_at = now();
        // Le contact n'est plus en sursis : la personne a dit oui.
        $narrator->contact_deletion_due_at = null;
        $narrator->save();

        if (isset($preferences['cadence'])) {
            $project->cadence = Cadence::from((string) $preferences['cadence']);
        }

        if (isset($preferences['prompt_day'])) {
            $project->prompt_day = (int) $preferences['prompt_day'];
        }

        if (isset($preferences['prompt_slot'])) {
            $project->prompt_slot = PromptSlot::from((string) $preferences['prompt_slot']);
        }

        if (isset($preferences['address_form'])) {
            $project->address_form = AddressForm::from((string) $preferences['address_form']);
        }
    }
}
