<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Actions\RequestErasure;
use App\Actions\RequestExport;
use App\Enums\ErasureScope;
use App\Enums\ExportKind;
use App\Enums\ExportScope;
use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Models\Export;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Support\InitiatorProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * « Mes données » : ce qu'on emporte, et ce qu'on efface.
 *
 * La page qui rend la non-captivité **visible**. Un export possible mais
 * caché dans un courriel de support ne vaut rien : ce que le dossier promet,
 * c'est que la famille puisse partir, et une porte qu'on ne voit pas n'est
 * pas une porte.
 *
 * L'effacement y figure aussi, avec ses conséquences écrites en clair. Le
 * dissimuler par prudence commerciale serait exactement le comportement que
 * la non-captivité est censée exclure.
 */
final readonly class DataController
{
    public function __construct(
        private RequestExport $exports,
        private RequestErasure $erasures,
    ) {}

    public function index(Request $request): Response
    {
        $project = $this->project($request);

        return inertia('initiator/Data', [
            'exports' => Export::query()
                ->where('project_id', $project->getKey())
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(fn (Export $export): array => [
                    'id' => $export->getKey(),
                    'kind' => $export->kind->value,
                    'status' => $export->status,
                    'builtAt' => $export->built_at?->toIso8601String(),
                    'expiresAt' => $export->expires_at?->toIso8601String(),
                    'bytes' => $export->bytes,
                    // Jamais l'URL : le jeton en clair n'existe qu'entre son
                    // émission et son envoi, et la page se recharge.
                    'usable' => $export->isReady(),
                ])
                ->all(),
            'erasureRequested' => $this->pendingErasure($project) !== null,
            'printInProgress' => $project->books()->whereIn('status', ['ordered', 'printed', 'reprint'])->exists(),
        ]);
    }

    /** Demander l'export de ses données. */
    public function export(Request $request): RedirectResponse
    {
        $project = $this->project($request);

        $validated = $request->validate([
            'kind' => ['nullable', 'string', 'in:full,offline_pack,gdpr_access'],
        ]);

        $this->exports->handle(
            $project,
            ExportScope::Initiator,
            ExportKind::from($validated['kind'] ?? 'full'),
            $request->user(),
        );

        return back()->with('status', __('initiator.data.export_queued'));
    }

    /**
     * Demander l'effacement du projet.
     *
     * La demande **n'efface rien** : elle ouvre un ticket, et un humain
     * confirme. Ce n'est pas une friction commerciale — c'est que personne ne
     * récupère ce qui part par erreur, et que le dossier accorde trente jours
     * pour le faire proprement.
     */
    public function erase(Request $request): RedirectResponse
    {
        $project = $this->project($request);

        $request->validate([
            // Taper le mot en entier : le même geste que la suppression d'une
            // histoire dans l'espace narrateur. Une case à cocher se clique
            // sans lire.
            'confirmation' => ['required', 'string', 'in:EFFACER'],
        ]);

        $this->erasures->handle($project, ErasureScope::Project, $request->user());

        return back()->with('status', __('initiator.data.erasure_requested'));
    }

    private function pendingErasure(Project $project): ?SupportTicket
    {
        return SupportTicket::query()
            ->where('project_id', $project->getKey())
            ->where('kind', SupportTicketKind::ErasureRequested->value)
            ->where('status', SupportTicketStatus::Open->value)
            ->first();
    }

    private function project(Request $request): Project
    {
        $user = $request->user();
        abort_if($user === null, 403);

        return InitiatorProject::forOrFail($user);
    }
}
