<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * Cette personne n'a pas encore de projet.
 *
 * Ce n'est **pas une erreur** : le cas arrive à quelqu'un qui a créé son
 * compte à la quatrième étape du tunnel puis s'est interrompu, à qui attend
 * un webhook Stripe, ou à qui clique sur un onglet pendant que sa commande se
 * règle. Aucun de ces gens n'a rien fait de mal, et leur servir la page
 * d'erreur brute de Laravel — une trace technique en anglais — les laisse
 * croire qu'ils ont cassé quelque chose (T-199).
 *
 * L'exception existe pour que la réponse soit décidée **une fois**, dans le
 * gestionnaire, plutôt que dans chacun des six contrôleurs de l'espace : la
 * prochaine page ajoutée en hérite sans qu'on y pense.
 */
final class NoInitiatorProject extends RuntimeException
{
    public static function make(): self
    {
        return new self('Cette personne n’a pas encore de projet.');
    }
}
