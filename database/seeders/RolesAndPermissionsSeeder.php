<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions du back-office (doc 04 §12).
 *
 * Le principe est le moindre privilège : le support fait son travail sans
 * pouvoir toucher à la marque ni rembourser, et un profil de lecture seule
 * existe pour l'astreinte et l'audit. Le rôle reste porté par `users.role` ;
 * ce seeder ne fait qu'en traduire les permissions fines.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    /** @var list<string> */
    public const PERMISSIONS = [
        'admin.access',
        'support.read',
        'support.write',
        'brand.manage',
        'refunds.issue',
        'tokens.reissue',
        'transcripts.edit',
        'audit.read',
    ];

    /** @var array<string, list<string>> */
    public const ROLES = [
        UserRole::Admin->value => self::PERMISSIONS,
        UserRole::Support->value => [
            'admin.access', 'support.read', 'support.write',
            'tokens.reissue', 'transcripts.edit', 'audit.read',
        ],
        UserRole::SupportReadonly->value => ['admin.access', 'support.read', 'audit.read'],
    ];

    public function run(): void
    {
        $guard = (string) config('auth.defaults.guard');

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        foreach (self::ROLES as $name => $permissions) {
            Role::findOrCreate($name, $guard)->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
