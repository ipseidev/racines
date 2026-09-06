<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * L'effacement ne peut pas avoir lieu maintenant.
 *
 * Un seul cas aujourd'hui, et il n'est pas technique : **un livre est parti à
 * l'impression**. Effacer les récits pendant qu'une machine les imprime
 * produirait un objet dont plus rien ne dit d'où il vient, et qu'on ne
 * pourrait ni honorer ni annuler.
 *
 * Le message dit **quoi faire**, pas seulement que c'est refusé : une
 * personne qui demande l'effacement de ses données et reçoit un « non » sec
 * a toutes les raisons de penser qu'on gagne du temps.
 */
final class ErasureBlocked extends RuntimeException
{
    /**
     * Le ticket ne pointe aucun projet.
     *
     * Impossible en théorie — la colonne est obligatoire — mais confirmer un
     * effacement dans le vide serait le pire endroit pour découvrir qu'une
     * hypothèse est fausse. Le message est pour le support, pas pour une
     * famille : ce cas ne devrait jamais l'atteindre.
     */
    public static function noProject(): self
    {
        return new self('Ce ticket d’effacement ne désigne aucun projet : rien n’a été effacé.');
    }

    public static function printInProgress(): self
    {
        return new self(
            'Un livre de ce projet est en cours d’impression. L’effacement aura lieu dès '
            .'sa livraison — ou tout de suite si vous annulez la commande, ce que le support '
            .'peut faire pour vous. Nous ne pouvons pas effacer des récits pendant qu’une '
            .'machine les imprime.'
        );
    }
}
