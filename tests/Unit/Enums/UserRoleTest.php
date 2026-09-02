<?php

declare(strict_types=1);

use App\Enums\UserRole;

it('distingue le personnel des Initiateur·rice·s', function (): void {
    expect(UserRole::Admin->isStaff())->toBeTrue()
        ->and(UserRole::Support->isStaff())->toBeTrue()
        ->and(UserRole::SupportReadonly->isStaff())->toBeTrue()
        ->and(UserRole::Initiator->isStaff())->toBeFalse();
});

it('expose une clé de traduction par rôle', function (): void {
    foreach (UserRole::cases() as $role) {
        expect($role->label())->toStartWith('admin.roles.');
    }
});

it('a pour valeur par défaut le rôle Initiateur·rice', function (): void {
    expect(UserRole::default())->toBe(UserRole::Initiator);
});
