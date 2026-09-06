<?php

declare(strict_types=1);

namespace App\Actions;

use App\Audit\AuditLog;
use App\Enums\ErasureScope;
use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Models\Project;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Demander l'effacement.
 *
 * **La demande n'efface rien.** Elle ouvre un ticket, et un humain confirme —
 * parce que personne ne récupère ce qui a été effacé par erreur, et parce que
 * le dossier accorde trente jours (doc 04 §4), pas zéro seconde. Ces trente
 * jours ne sont pas un délai de réflexion imposé à la personne : c'est notre
 * marge à nous, et l'effacement a lieu dès la confirmation.
 *
 * **Le narrateur gagne** (doc 04 §3). Sa demande remplace celle de
 * l'Initiateur·rice, sans arbitrage éditorial : c'est sa voix, et une famille
 * qui voudrait conserver un récit qu'il retire n'a pas de recours. L'inverse
 * n'est pas vrai — une demande de l'Initiateur·rice ne chasse pas la sienne.
 */
final readonly class RequestErasure
{
    public function handle(Project $project, ErasureScope $scope, ?Model $requestedBy = null): SupportTicket
    {
        $ouverts = SupportTicket::query()
            ->where('project_id', $project->getKey())
            ->where('kind', SupportTicketKind::ErasureRequested->value)
            ->where('status', SupportTicketStatus::Open->value)
            ->get();

        foreach ($ouverts as $ticket) {
            $portee = $ticket->payload['scope'] ?? null;

            // Une demande du narrateur remplace celle du projet ; l'inverse
            // ne se produit pas, et deux demandes identiques n'en font qu'une.
            if ($scope === ErasureScope::Narrator && $portee !== ErasureScope::Narrator->value) {
                $ticket->forceFill([
                    'status' => SupportTicketStatus::Closed,
                    'closed_at' => now(),
                ])->save();

                continue;
            }

            return $ticket;
        }

        $ticket = new SupportTicket([
            'kind' => SupportTicketKind::ErasureRequested,
            'payload' => [
                'scope' => $scope->value,
                'requested_by_type' => $requestedBy?->getMorphClass(),
                'requested_by_id' => $requestedBy === null ? null : (string) $requestedBy->getKey(),
                // La date limite, pour que le support la voie sans calculer.
                'due_at' => now()->addDays(30)->toIso8601String(),
            ],
            'opened_at' => now(),
        ]);

        $ticket->project()->associate($project);
        $ticket->save();

        AuditLog::record('requested Erasure', $project, [
            'scope' => $scope->value,
            'ticket_id' => $ticket->getKey(),
        ], $project);

        Log::warning('rgpd.erasure_requested', [
            'project_id' => $project->getKey(),
            'scope' => $scope->value,
        ]);

        return $ticket;
    }
}
