<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Enums\TokenType;
use Illuminate\Http\Request;

/**
 * Où la mesure d'audience a le droit d'exister.
 *
 * La règle est celle du dossier (§12) : les pages publiques et l'espace de
 * l'Initiateur·rice sont mesurés, **aucun espace ouvert par un lien porteur
 * ne l'est**. Un narrateur de quatre-vingt-cinq ans n'a pas de compte, n'a
 * rien accepté, et ne sait pas ce qu'est un traceur ; aucun tiers ne regarde
 * par-dessus son épaule pendant qu'il raconte sa vie.
 *
 * Cette classe existe parce que la liste des espaces était copiée à trois
 * endroits — les props Inertia, la politique de contenu, le front. Trois
 * copies veut dire qu'un espace ajouté un jour serait mesuré par oubli dans
 * l'une d'elles, et personne ne le verrait : un traceur qui part n'affiche
 * rien. Les préfixes viennent donc de `TokenType`, qui les définit déjà pour
 * construire les liens.
 */
final class Measured
{
    /**
     * Vrai si cette requête a le droit de recevoir de quoi mesurer.
     *
     * Le premier segment de l'URL suffit : un lien porteur vit toujours à la
     * racine d'un espace d'une lettre.
     */
    public static function allows(Request $request): bool
    {
        return ! in_array($request->segment(1), self::tokenSpaces(), true);
    }

    /**
     * Les espaces ouverts par un lien porteur.
     *
     * `s` s'ajoute aux sept que `TokenType` construit : c'est l'espace réservé
     * aux actes sensibles, qui s'ouvre après une authentification OTP. Il
     * n'existe pas encore comme préfixe de lien, et il est tenu hors de la
     * mesure d'avance — l'ordre inverse (mesurer d'abord, exclure quand on y
     * pense) est celui qui produit des fuites.
     *
     * @return list<string>
     */
    public static function tokenSpaces(): array
    {
        return [...TokenType::urlPrefixes(), 's'];
    }
}
