<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ValidationVariant as Variant;
use App\Features\ValidationVariant;
use App\Models\Project;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

/**
 * Assigne une variante de validation à un projet, pour le pilote.
 *
 * Il n'y a pas d'écran pour ça, et c'est volontaire : c'est une décision
 * d'expérimentation prise sur un tableau, pas un réglage de la famille.
 */
final class SetValidationVariant extends Command
{
    protected $signature = 'features:set-variant {project : Identifiant du projet} {variant : immediate ou deferred}';

    protected $description = 'Assigne la variante de validation d’un projet (pilote, Phase 0A)';

    public function handle(): int
    {
        $variant = Variant::tryFrom((string) $this->argument('variant'));

        if ($variant === null) {
            $this->components->error(sprintf(
                'Variante inconnue. Attendu : %s.',
                implode(' ou ', array_column(Variant::cases(), 'value')),
            ));

            return self::FAILURE;
        }

        $project = Project::query()->find($this->argument('project'));

        if ($project === null) {
            $this->components->error('Projet introuvable.');

            return self::FAILURE;
        }

        $project->validation_variant = $variant;
        $project->save();

        // Pennant mémorise la première valeur résolue : sans cet oubli, la
        // colonne changerait sans que le produit change, ce qui est le pire
        // des deux mondes.
        Feature::for($project)->forget(ValidationVariant::class);

        $this->components->info(sprintf(
            'Projet %s : variante %s.',
            $project->id,
            Feature::for($project)->value(ValidationVariant::class),
        ));

        return self::SUCCESS;
    }
}
