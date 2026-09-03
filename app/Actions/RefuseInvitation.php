<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProjectStatus;
use App\Enums\RefusalReason;
use App\Enums\SupportTicketKind;
use App\Models\Invitation;
use App\Models\Project;
use App\Notifications\InvitationRefusedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Le narrateur décline.
 *
 * Le chemin le plus important du bloc après l'acceptation, et celui qu'on
 * écrit le plus mal d'habitude. Trois principes :
 *
 *  1. **Aucune friction.** Un bouton de la même taille que « j'accepte », pas
 *     de confirmation, pas de « êtes-vous sûr ». Rendre le refus difficile ne
 *     produit pas des oui, ça produit des gens qui ne répondent pas.
 *  2. **Le motif est facultatif.** On le recueille pour comprendre H0, pas
 *     pour répondre à l'objection.
 *  3. **L'Initiateur·rice est prévenue avec tact**, et le remboursement est
 *     **proposé sans qu'elle le demande**. Elle vient d'offrir quelque chose
 *     qui a été refusé : c'est le pire moment pour lui faire remplir un
 *     formulaire.
 *
 * Le contact du narrateur part dans trente jours : on ne garde pas le
 * téléphone de quelqu'un qui a dit non.
 */
final readonly class RefuseInvitation
{
    public function __construct(private OpenSupportTicket $tickets) {}

    public function handle(Project $project, ?RefusalReason $reason = null): Project
    {
        $narrator = $project->primaryNarrator;

        return DB::transaction(function () use ($project, $narrator, $reason): Project {
            $project->status = ProjectStatus::AwaitingAcceptance;
            $project->refused_at = now();
            $project->refusal_reason = $reason?->value;
            $project->next_prompt_at = null;
            $project->save();

            if ($narrator !== null) {
                $narrator->opted_out_at = now();
                // On ne garde pas le téléphone de quelqu'un qui a dit non.
                $narrator->contact_deletion_due_at = now()->addDays(30);
                $narrator->save();

                Invitation::query()
                    ->where('narrator_id', $narrator->id)
                    ->whereNull('accepted_at')
                    ->whereNull('refused_at')
                    ->latest('sent_at')
                    ->first()
                    ?->forceFill(['refused_at' => now(), 'opened_at' => now()])
                    ->save();
            }

            $project->owner->notify(new InvitationRefusedNotification($project, $reason));

            // Le remboursement se propose, il ne s'attend pas : elle vient
            // d'offrir quelque chose qui a été refusé.
            $this->tickets->handle($project, SupportTicketKind::RefundOffer, null, [
                'reason' => $reason?->value,
            ]);

            Log::info('invitation.refused', [
                'project_id' => $project->id,
                'reason' => $reason?->value,
            ]);

            return $project->refresh();
        });
    }
}
