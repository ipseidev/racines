<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\Domain\NarratorNotReachable;
use App\Models\Narrator;
use App\Models\Project;

/**
 * Ajoute un narrateur à un projet.
 *
 * Le premier ajouté est le narrateur principal ; les suivants existent en base
 * sans apparaître dans l'interface du MVP (PRD §2). Un narrateur qu'on ne peut
 * pas joindre est refusé tout de suite, et non au moment de l'envoi.
 */
final class AddNarrator
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Project $project, array $attributes): Narrator
    {
        // Le nom affiché est celui que le narrateur verra dans ses SMS : à
        // défaut d'indication, on s'en tient à son prénom.
        $attributes['display_name'] ??= $attributes['first_name'] ?? null;

        $narrator = new Narrator($attributes);
        $narrator->project()->associate($project);
        $narrator->is_primary = ! $project->narrators()->exists();

        if ($narrator->email === null && $narrator->phone_e164 === null) {
            throw NarratorNotReachable::make();
        }

        $narrator->save();

        return $narrator;
    }
}
