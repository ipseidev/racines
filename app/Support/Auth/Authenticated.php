<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * Accès typé à l'utilisateur authentifié.
 *
 * Les routes concernées sont derrière le middleware d'authentification, mais
 * Request::user() reste nullable pour l'analyse statique. Plutôt que de
 * masquer le cas, on le traite : absence d'utilisateur = échec
 * d'authentification, donc 401, jamais une erreur fatale.
 */
final class Authenticated
{
    public static function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
