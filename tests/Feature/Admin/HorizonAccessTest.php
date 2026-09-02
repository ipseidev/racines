<?php

declare(strict_types=1);

use App\Jobs\ConcatenateSegments;
use App\Jobs\ReplicateRecording;
use App\Models\User;

it('refuse les files à un visiteur anonyme', function (): void {
    // Les noms de jobs disent ce qui se passe chez une famille : la file n'est
    // pas plus publique que le back-office (doc 04 §12). Horizon refuse sans
    // rediriger — il n'a pas de page de connexion à lui.
    $this->get('/horizon')->assertForbidden();
});

it('refuse les files à une Initiateur·rice', function (): void {
    $this->actingAs(User::factory()->create())->get('/horizon')->assertForbidden();
});

it('ouvre les files au personnel', function (): void {
    $this->actingAs(User::factory()->support()->create())->get('/horizon')->assertOk();
});

it('refuse les files à un membre du personnel privé de la permission', function (): void {
    $user = User::factory()->support()->create();
    $user->revokePermissionTo('admin.access');
    $user->removeRole('support');

    $this->actingAs($user->fresh())->get('/horizon')->assertForbidden();
});

it('déclare les files du produit sur deux superviseurs', function (): void {
    $supervisors = config('horizon.defaults');

    expect(array_keys($supervisors))->toBe(['supervisor-1', 'supervisor-media'])
        ->and($supervisors['supervisor-1']['queue'])->toBe(['default', 'notifications', 'engine'])
        ->and($supervisors['supervisor-media']['queue'])->toBe(['media', 'transcription', 'llm', 'exports'])
        // Un travail média peut durer des minutes ; il ne doit pas retarder
        // l'envoi d'une notification.
        ->and($supervisors['supervisor-media']['timeout'])->toBe(900)
        ->and($supervisors['supervisor-1']['timeout'])->toBe(60);
});

it('range les jobs média sur la file media', function (): void {
    expect((new ReplicateRecording('x'))->queue)->toBe('media')
        ->and((new ConcatenateSegments('x'))->queue)->toBe('media');
});
