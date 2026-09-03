<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\Invitation;
use App\Models\Project;
use App\Notifications\GiftInvitationNotification;
use App\Services\Tokens\OtpService;
use App\Services\Tokens\TokenService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * L'invitation part.
 *
 * Trois envois au maximum, jamais plus : l'invitation, puis deux relances
 * portées par la règle `invitation_not_accepted` du moteur (bloc 09). Au-delà,
 * ce n'est plus une invitation, c'est une insistance — et la limite vit aussi
 * en base, sur `invitations`.
 *
 * Le message annonce le domaine et rappelle qu'aucune page ne demandera de
 * mot de passe ni de paiement (doc 04 §9) : un cadeau inattendu d'un
 * expéditeur inconnu est exactement ce qu'un hameçonneur imiterait.
 */
final class SendGiftInvitation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $projectId,
        public readonly int $attempt = 1,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(TokenService $tokens): void
    {
        $project = Project::query()->with(['owner', 'primaryNarrator'])->find($this->projectId);

        if ($project === null) {
            return;
        }

        $narrator = $project->primaryNarrator;

        if ($narrator === null) {
            Log::error('gift.no_narrator', ['project_id' => $project->id]);

            return;
        }

        if ($project->accepted_at !== null || $project->refused_at !== null) {
            // Déjà répondu : on ne relance pas quelqu'un qui a tranché.
            return;
        }

        if (! Invitation::canSendTo($narrator)) {
            Log::info('gift.attempts_exhausted', ['project_id' => $project->id]);

            return;
        }

        $issued = $tokens->issue(
            TokenType::Invitation,
            $project,
            ['opt_in'],
            now()->addDays(30),
            $project->owner,
            issuedTo: $narrator,
        );

        $channel = OtpService::channelFor($narrator);

        $invitation = new Invitation([
            'channel' => $channel,
            'attempt' => $this->attempt,
            'sent_at' => now(),
        ]);

        $invitation->project()->associate($project);
        $invitation->narrator()->associate($narrator);
        $invitation->token()->associate($issued->token);
        $invitation->save();

        $narrator->notify(new GiftInvitationNotification($project, $issued->plain, $this->attempt));

        // Le statut ne passe à `awaiting_acceptance` qu'à l'envoi : avant, le
        // projet n'attend rien, personne ne sait qu'il existe.
        if ($project->status === ProjectStatus::Draft) {
            $project->status = ProjectStatus::AwaitingAcceptance;
        }

        $project->gift_sent_at = now();
        $project->save();

        Log::info('gift.invitation_sent', [
            'project_id' => $project->id,
            'attempt' => $this->attempt,
            'channel' => $channel->value,
        ]);
    }
}
