<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * Une propriété d'analytique contient une donnée personnelle.
 *
 * Levée, jamais filtrée. Un filtre silencieux laisserait le développeur qui a
 * ajouté la propriété croire qu'il mesure quelque chose, et la mesure serait
 * vide — le pire des deux mondes.
 *
 * Le message **nomme la propriété et ne recopie pas sa valeur** : la trace
 * d'erreur part chez Flare, et y déposer l'adresse qu'on refusait d'envoyer
 * à PostHog serait une fuite par la porte à côté.
 */
final class AnalyticsPiiLeak extends RuntimeException
{
    public static function property(string $path, string $reason): self
    {
        return new self(sprintf(
            'La propriété d’analytique [%s] contient %s : elle ne peut pas partir. '
            .'Les analyses fines se font en SQL sur la base, jamais en enrichissant '
            .'l’outil de mesure avec des données personnelles.',
            $path,
            $reason,
        ));
    }
}
