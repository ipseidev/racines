<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * Le fichier déposé n'est pas une image qu'on sait traiter.
 *
 * Le message est destiné à être **montré**, et il dit quoi faire plutôt que
 * ce qui a échoué. « Format non supporté » n'aide personne ; « réglez votre
 * appareil photo sur Le plus compatible » se suit.
 */
final class UnsupportedImage extends RuntimeException
{
    public static function make(string $reason = ''): self
    {
        return new self($reason === '' ? 'Image illisible.' : $reason);
    }

    /**
     * Le cas du HEIC qu'aucun décodeur ne lit.
     *
     * Règle de décision par défaut du bloc 12 §9 : refuser avec un message
     * actionnable plutôt qu'ajouter un service de conversion. Un service de
     * plus pour un format que l'appareil sait produire autrement serait une
     * pièce mobile de plus dans la chaîne la plus fragile du produit.
     */
    public static function undecodableHeic(): self
    {
        return new self(
            'Nous ne savons pas lire cette photo. Réglez votre appareil photo '
            .'sur « Le plus compatible », ou envoyez la photo depuis votre galerie.',
        );
    }
}
