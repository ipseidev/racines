<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\ValidationVariant as Variant;
use App\Models\Project;
use Laravel\Pennant\Feature;

/**
 * Laquelle des deux façons de valider une histoire ce projet utilise.
 *
 * C'est le test le plus important de la Phase 0A, et un drapeau **par
 * projet**, jamais par requête : une famille qui changerait de variante en
 * cours de route ne mesurerait rien, et vivrait deux produits différents.
 * Pennant mémorise donc la valeur au premier accès ; seule la commande
 * `features:set-variant` la fait oublier.
 *
 *  - `immediate` (variante A) : les trois choix arrivent en fin
 *    d'enregistrement, avant la transcription. La validation ressemble à une
 *    récompense d'un tap.
 *  - `deferred` (variante B) : le narrateur relit son texte transcrit, puis
 *    décide. Plus sûr pour lui, plus long.
 */
final class ValidationVariant
{
    /**
     * Le nom stocké : il vit dans la base de Pennant et dans les journaux, et
     * doit rester lisible sans connaître l'espace de noms.
     */
    public string $name = 'validation-variant';

    public function resolve(Project $project): string
    {
        return $project->validation_variant->value;
    }

    /**
     * Le drapeau tel qu'il s'applique à une histoire.
     */
    public static function isDeferredFor(Project $project): bool
    {
        return Variant::tryFrom(
            (string) Feature::for($project)->value(self::class),
        ) === Variant::Deferred;
    }
}
