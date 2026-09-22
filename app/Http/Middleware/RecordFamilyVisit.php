<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AccessToken;
use App\Models\FamilyMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La première fois qu'un proche ouvre son lien.
 *
 * `family_members.first_seen_at` existe depuis le bloc 02, l'espace de
 * l'Initiateur·rice l'affiche en pastille — « a ouvert son lien » ou « n'a
 * jamais ouvert » — et le panneau d'administration en fait une colonne. Rien,
 * nulle part, ne l'écrivait : la pastille disait donc « n'a jamais ouvert »
 * pour l'éternité, y compris pour quelqu'un qui écoutait chaque semaine.
 *
 * Ce n'est pas qu'un affichage faux. C'est le premier maillon de H2 —
 * invitation délivrée → **ouverte** → écoutée 30 s → réaction — et un maillon
 * qu'on ne mesure pas est un maillon qu'on ne saura pas réparer.
 *
 * Posé en middleware du groupe et non dans un contrôleur : un proche n'arrive
 * pas toujours par l'accueil. Un lien d'histoire, une histoire épinglée qui
 * redirige, un signet posé sur une page profonde — trois portes, et la visite
 * compte pareil par les trois.
 *
 * **Après la réponse, et seulement si la page a été rendue.** Une requête qui
 * finit en 404 ou en 403 n'est pas une ouverture : compter le jeton d'un lien
 * révoqué ferait dire à la pastille le contraire de ce qui s'est passé.
 */
final class RecordFamilyVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->isSuccessful() && ! $response->isRedirection()) {
            return $response;
        }

        $member = self::memberOf($request);

        // Une seule écriture dans la vie du lien : la date de la **première**
        // ouverture, pas celle de la dernière. Savoir qu'une personne est
        // venue une fois est ce que l'Initiateur·rice cherche ; la suivre
        // semaine après semaine serait autre chose, et personne ne l'a
        // demandé.
        if ($member instanceof FamilyMember && $member->first_seen_at === null) {
            $member->forceFill(['first_seen_at' => now()])->save();
        }

        return $response;
    }

    /**
     * Le proche derrière le jeton, quelle que soit la porte.
     *
     * `listen_project` porte le proche en sujet ; `listen_story` porte
     * l'histoire, et le proche dans `issued_to`. Les deux mènent à la même
     * personne, et c'est elle qu'on marque.
     */
    private static function memberOf(Request $request): ?FamilyMember
    {
        $subject = $request->attributes->get('token_subject');

        if ($subject instanceof FamilyMember) {
            return $subject;
        }

        $token = $request->attributes->get('access_token');

        return $token instanceof AccessToken && $token->issuedTo instanceof FamilyMember
            ? $token->issuedTo
            : null;
    }
}
