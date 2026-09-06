<?php

declare(strict_types=1);

namespace App\Actions;

use App\Audit\AuditLog;
use App\Enums\ExportKind;
use App\Enums\ExportScope;
use App\Jobs\BuildExport;
use App\Models\Export;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

/**
 * Demander un export.
 *
 * Une action minuscule, et deux décisions dedans.
 *
 * **On ne refabrique pas ce qui existe.** Un export prêt et non expiré du
 * même genre et de la même portée est rendu tel quel : reconstruire cinq
 * gigaoctets parce que quelqu'un a cliqué deux fois coûte une demi-heure de
 * file pour un fichier identique. La famille peut toujours en forcer un neuf
 * — c'est ce que fait le bouton après expiration.
 *
 * **La portée vient du demandeur, jamais du formulaire.** Un narrateur reçoit
 * tout ce qui est à lui ; l'Initiateur·rice ne reçoit que ce qu'il a validé.
 * Laisser la portée arriver par la requête ouvrirait la porte de derrière du
 * bloc 07.
 */
final readonly class RequestExport
{
    public function handle(
        Project $project,
        ExportScope $scope,
        ExportKind $kind = ExportKind::Full,
        ?Model $requestedBy = null,
    ): Export {
        $existant = Export::query()
            ->where('project_id', $project->getKey())
            ->where('kind', $kind->value)
            ->where('scope', $scope->value)
            ->where('status', 'ready')
            ->where('expires_at', '>', now())
            ->latest('built_at')
            ->first();

        if ($existant instanceof Export) {
            return $existant;
        }

        $export = new Export(['kind' => $kind, 'scope' => $scope]);
        $export->project()->associate($project);

        if ($requestedBy instanceof Model) {
            $export->requestedBy()->associate($requestedBy);
        }

        $export->save();

        AuditLog::record('requested Export', $export, [
            'kind' => $kind->value,
            'scope' => $scope->value,
            'proactive' => $requestedBy === null,
        ], $project);

        BuildExport::dispatch($export);

        return $export;
    }
}
