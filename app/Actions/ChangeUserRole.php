<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Changer le rôle d'un compte.
 *
 * Le rôle n'est **jamais** assignable en masse (bloc 01) : il se pose
 * explicitement, et l'écouteur `saved` du modèle traduit alors le rôle en
 * permissions fines. Un `update(['role' => …])` ne ferait rien, en silence —
 * ce qui est exactement le genre de faute qu'une action nommée empêche.
 *
 * Rendre le rôle précédent, et non le nouveau : c'est lui que le journal doit
 * porter pour qu'une revue d'accès puisse dire ce qui a changé.
 */
final class ChangeUserRole
{
    public function handle(User $user, UserRole $role): UserRole
    {
        $previous = $user->role;

        if ($previous === $role) {
            return $previous;
        }

        $user->role = $role;
        $user->save();

        Log::warning('user.role_changed', [
            'user_id' => $user->id,
            'from' => $previous->value,
            'to' => $role->value,
        ]);

        return $previous;
    }
}
