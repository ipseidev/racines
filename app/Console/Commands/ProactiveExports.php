<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RequestExport;
use App\Enums\ExportScope;
use App\Models\Export;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Les exports que personne ne demande.
 *
 * Une famille ne pense pas à télécharger ses données. Elle y pense le jour où
 * le service ferme — c'est-à-dire trop tard. R-10.2 nous oblige donc à
 * prendre les devants, et le choix du moment est tout l'enjeu :
 * **soixante jours** avant la fin d'hébergement, assez tôt pour qu'une
 * personne de quatre-vingts ans ait le temps de demander de l'aide à
 * quelqu'un, et pas si tôt que le message se perde.
 *
 * La commande passe tous les jours. Deux gardes l'empêchent d'être une
 * nuisance : un export récent suffit, et un projet effacé n'en reçoit pas —
 * écrire à quelqu'un qui a demandé l'effacement serait le contraire de ce
 * qu'il a demandé.
 */
final class ProactiveExports extends Command
{
    protected $signature = 'exports:proactive';

    protected $description = 'Envoie un export aux familles dont l’hébergement se termine bientôt';

    /** Soixante jours : le temps de demander de l'aide à quelqu'un. */
    private const JOURS_AVANT = 60;

    /** Un export des trois derniers mois vaut celui-ci. */
    private const RECENT_JOURS = 90;

    public function handle(RequestExport $exports): int
    {
        $projets = Project::query()
            ->whereNull('erased_at')
            ->whereNotNull('hosting_ends_at')
            ->where('hosting_ends_at', '<=', now()->addDays(self::JOURS_AVANT))
            ->where('hosting_ends_at', '>', now())
            ->get();

        $envoyes = 0;

        foreach ($projets as $project) {
            $recent = Export::query()
                ->where('project_id', $project->getKey())
                ->whereNotNull('built_at')
                ->where('built_at', '>=', now()->subDays(self::RECENT_JOURS))
                ->exists();

            $enCours = Export::query()
                ->where('project_id', $project->getKey())
                ->whereIn('status', ['queued', 'building'])
                ->exists();

            if ($recent || $enCours) {
                continue;
            }

            $exports->handle($project, ExportScope::Initiator);
            $envoyes++;

            Log::info('export.proactive', [
                'project_id' => $project->getKey(),
                'hosting_ends_at' => $project->hosting_ends_at?->toIso8601String(),
            ]);
        }

        $this->components->info(sprintf('%d export(s) envoyé(s) avant échéance.', $envoyes));

        return self::SUCCESS;
    }
}
