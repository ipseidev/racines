<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Models\Project;
use App\Models\Story;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Log;

/**
 * Le produit lève la main à la place d'une famille.
 *
 * Une personne de 82 ans qui n'arrive pas à autoriser son micro n'écrit pas au
 * support : elle abandonne, et personne ne sait pourquoi. C'est donc au
 * produit de le signaler.
 *
 * Idempotent tant qu'un ticket du même genre est ouvert pour le même sujet :
 * un support noyé sous les doublons ne traite plus rien, et le deuxième ticket
 * n'apporte aucune information que le premier n'ait déjà.
 */
final class OpenSupportTicket
{
    /**
     * @param  array<string, mixed>  $payload  Des identifiants et des
     *                                         compteurs, jamais une donnée
     *                                         personnelle en clair.
     */
    public function handle(
        Project $project,
        SupportTicketKind $kind,
        ?Story $story = null,
        array $payload = [],
    ): SupportTicket {
        $existing = SupportTicket::query()
            ->where('project_id', $project->id)
            ->where('kind', $kind->value)
            ->where('status', SupportTicketStatus::Open->value)
            ->when($story !== null, fn ($query) => $query->where('story_id', $story?->id))
            ->first();

        if ($existing instanceof SupportTicket) {
            return $existing;
        }

        $ticket = new SupportTicket([
            'kind' => $kind,
            'payload' => $payload,
            'opened_at' => now(),
        ]);

        $ticket->project()->associate($project);

        if ($story !== null) {
            $ticket->story()->associate($story);
        }

        $ticket->save();

        Log::warning('support.ticket_opened', [
            'ticket_id' => $ticket->id,
            'kind' => $kind->value,
            'project_id' => $project->id,
        ]);

        return $ticket;
    }
}
