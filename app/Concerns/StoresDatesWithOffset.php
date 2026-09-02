<?php

declare(strict_types=1);

namespace App\Concerns;

/**
 * Enregistre les dates avec leur décalage.
 *
 * Par défaut, Eloquent écrit `2026-09-09 08:00:00` — sans décalage. Postgres
 * l'interprète alors dans le fuseau de la session, ce qui rend le résultat
 * dépendant du fuseau de l'objet PHP : un `CarbonImmutable` converti en UTC
 * avant enregistrement se retrouvait décalé de deux heures.
 *
 * En écrivant `2026-09-09 08:00:00+00:00`, l'instant est explicite et le
 * fuseau de l'objet devient indifférent. C'est la seule posture sûre pour un
 * produit dont toutes les règles — relances, fenêtres, corbeille — se
 * comptent en heures.
 */
trait StoresDatesWithOffset
{
    /**
     * Format d'écriture et de lecture des dates.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:sP';
}
