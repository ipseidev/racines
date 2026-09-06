<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

/**
 * Le code famille facultatif d'un livre (D-8, doc 04 §7).
 *
 * Une famille peut vouloir que les QR de son livre ne s'ouvrent pas pour le
 * premier venu — un livre se perd, se revend, se retrouve dans une brocante.
 * Le code est **posé par elle**, jamais par nous, et son absence est le cas
 * normal : sans code, la page s'ouvre.
 *
 * Le déverrouillage vit dans un **cookie signé de trente jours**, par projet.
 * Trente jours parce qu'un code redemandé à chaque scan finit sur un post-it
 * collé dans le livre, ce qui le rend inutile ; et par projet parce qu'un
 * cookie global ouvrirait le livre d'une autre famille lu sur le même
 * téléphone.
 */
final class QrFamilyCode
{
    private const DAYS = 30;

    public static function required(Project $project): bool
    {
        return is_string($project->family_code_hash) && $project->family_code_hash !== '';
    }

    public static function isUnlocked(Request $request, Project $project): bool
    {
        if (! self::required($project)) {
            return true;
        }

        return $request->cookie(self::cookieName($project)) === '1';
    }

    public static function matches(Project $project, string $code): bool
    {
        $hash = $project->family_code_hash;

        return is_string($hash) && $hash !== '' && Hash::check(trim($code), $hash);
    }

    /** Le cookie de déverrouillage, signé et chiffré comme tous les autres. */
    public static function unlockCookie(Project $project): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make(self::cookieName($project), '1', self::DAYS * 24 * 60);
    }

    private static function cookieName(Project $project): string
    {
        // L'identifiant du projet est dans le nom, pas dans la valeur : un
        // cookie par livre, et rien à comparer côté serveur.
        return 'qr_'.substr(hash('sha256', (string) $project->getKey()), 0, 16);
    }
}
