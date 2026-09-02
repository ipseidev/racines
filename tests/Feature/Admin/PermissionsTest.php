<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('sème les trois rôles du personnel et les huit permissions', function (): void {
    expect(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'support', 'support_readonly'])
        ->and(Permission::query()->pluck('name')->sort()->values()->all())
        ->toBe([
            'admin.access', 'audit.read', 'brand.manage', 'refunds.issue',
            'support.read', 'support.write', 'tokens.reissue', 'transcripts.edit',
        ]);
});

it('donne tout à l’administration', function (): void {
    $user = User::factory()->admin()->create();

    foreach (Permission::query()->pluck('name') as $permission) {
        expect($user->can((string) $permission))->toBeTrue("l’administration devrait avoir {$permission}");
    }
});

it('refuse la marque et les remboursements au support', function (): void {
    $user = User::factory()->support()->create();

    expect($user->can('brand.manage'))->toBeFalse()
        ->and($user->can('refunds.issue'))->toBeFalse()
        ->and($user->can('support.write'))->toBeTrue()
        ->and($user->can('transcripts.edit'))->toBeTrue()
        ->and($user->can('tokens.reissue'))->toBeTrue();
});

it('n’accorde que la lecture au support en lecture seule', function (): void {
    $user = User::factory()->supportReadonly()->create();

    expect($user->can('admin.access'))->toBeTrue()
        ->and($user->can('support.read'))->toBeTrue()
        ->and($user->can('audit.read'))->toBeTrue()
        ->and($user->can('support.write'))->toBeFalse()
        ->and($user->can('transcripts.edit'))->toBeFalse()
        ->and($user->can('tokens.reissue'))->toBeFalse()
        ->and($user->can('brand.manage'))->toBeFalse();
});

it('ne donne aucun rôle de back-office à une Initiateur·rice', function (): void {
    $user = User::factory()->create();

    expect($user->roles)->toBeEmpty()
        ->and($user->can('admin.access'))->toBeFalse();
});

it('aligne le rôle du back-office quand le rôle de l’utilisateur change', function (): void {
    $user = User::factory()->create();

    // Le rôle n'est jamais assignable en masse (bloc 01) : on le pose
    // explicitement, comme le fera l'administration.
    $user->role = UserRole::Support;
    $user->save();

    expect($user->fresh()?->hasRole('support'))->toBeTrue();

    $user->role = UserRole::Initiator;
    $user->save();

    expect($user->fresh()?->roles)->toBeEmpty();
});

it('n’accepte pas un rôle passé par assignation de masse', function (): void {
    $user = User::factory()->create();

    $user->update(['role' => UserRole::Admin]);

    expect($user->fresh()?->role)->toBe(UserRole::Initiator)
        ->and($user->fresh()?->can('admin.access'))->toBeFalse();
});

it('ouvre le panneau sur la permission et non sur le nom du rôle', function (): void {
    $user = User::factory()->support()->create();
    $user->revokePermissionTo('admin.access');
    $user->removeRole('support');

    expect($user->fresh()?->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
});
