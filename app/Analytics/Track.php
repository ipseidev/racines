<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Enums\AnalyticsEvent;
use App\Models\Project;
use App\Services\Analytics\Analytics;
use Illuminate\Support\Facades\Log;

/**
 * Émettre une mesure en une ligne, depuis n'importe où.
 *
 * Le port `Analytics` s'injecte dans un constructeur, ce qui est juste pour
 * une action qui mesure à chaque appel. Pour les trente points du funnel
 * — dont beaucoup vivent dans des transitions d'état, des écouteurs et des
 * commandes — ajouter une dépendance à chaque classe alourdirait trente
 * constructeurs pour une ligne d'appel.
 *
 * L'assistant résout aussi la répétition qui compte : **le projet et sa
 * cohorte accompagnent presque tout**. Les oublier, c'est se retrouver avec
 * un entonnoir qu'on ne peut pas découper par cohorte — et le pilote se lit
 * par cohorte, parce que deux vagues de familles n'ont pas reçu le même
 * produit.
 */
final class Track
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function project(AnalyticsEvent $event, ?Project $project, array $properties = []): void
    {
        if ($project === null) {
            /*
             * Un projet absent ne fait pas échouer l'appel — une mesure n'a
             * jamais le droit de casser un geste métier — mais elle ne se
             * tait pas non plus.
             *
             * Le silence était le premier réflexe, et il est mauvais : une
             * contre-métrique qui ne s'émet jamais parce que la relation est
             * nulle serait **sous-estimée sans que rien ne le dise**, et l'on
             * conclurait à un taux de remboursement excellent. Le journal
             * garde donc la trace du trou.
             */
            Log::warning('analytics.no_project', ['event' => $event->value]);

            return;
        }

        app(Analytics::class)->capture(
            $event,
            [
                'project_id' => $project->getKey(),
                'cohort' => $project->cohort_id,
                'offer' => $project->offer->value,
                // La variante de validation change le parcours du narrateur :
                // sans elle, les deux bras de l'expérience se mélangent.
                'validation_variant' => $project->validation_variant->value,
                ...$properties,
            ],
            (string) $project->getKey(),
        );
    }
}
