<?php

declare(strict_types=1);

namespace App\Actions;

use App\Audit\AuditLog;
use App\Enums\BookStatus;
use App\Enums\SupportTicketStatus;
use App\Exceptions\Domain\ErasureBlocked;
use App\Jobs\EraseProject;
use App\Models\Book;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Confirmer un effacement, et l'exécuter.
 *
 * Le geste d'un humain, jamais d'un planificateur. Les trente jours du
 * dossier sont **notre** marge, pas un délai de réflexion imposé à la
 * personne : dès la confirmation, cela part.
 *
 * Un seul refus possible, et il n'est pas technique : **un livre à
 * l'impression**. Effacer les récits pendant qu'une machine les imprime
 * produirait un objet dont plus rien ne dit d'où il vient, et qu'on ne
 * pourrait ni honorer ni annuler. L'explication rendue dit quoi faire.
 */
final readonly class ConfirmErasure
{
    public function handle(SupportTicket $ticket, User $confirmedBy): SupportTicket
    {
        $project = $ticket->project;

        // Un ticket sans projet n'existe pas — la colonne est obligatoire —
        // mais le type le permet, et confirmer un effacement dans le vide
        // serait le pire endroit pour découvrir une hypothèse fausse.
        if (! $project instanceof Project) {
            throw ErasureBlocked::noProject();
        }

        $enImpression = Book::query()
            ->where('project_id', $project->getKey())
            ->whereIn('status', [BookStatus::Ordered->value, BookStatus::Printed->value, BookStatus::Reprint->value])
            ->exists();

        if ($enImpression) {
            throw ErasureBlocked::printInProgress();
        }

        EraseProject::dispatch($project->getKey());

        $ticket->forceFill([
            'status' => SupportTicketStatus::Closed,
            'closed_at' => now(),
            'closed_by_user_id' => $confirmedBy->getKey(),
            'payload' => [...$ticket->payload ?? [], 'confirmed_at' => now()->toIso8601String()],
        ])->save();

        AuditLog::record('confirmed Erasure', $project, [
            'ticket_id' => $ticket->getKey(),
            'scope' => $ticket->payload['scope'] ?? null,
        ], $project);

        Log::warning('rgpd.erasure_confirmed', [
            'project_id' => $project->getKey(),
            'confirmed_by' => $confirmedBy->getKey(),
        ]);

        return $ticket;
    }
}
