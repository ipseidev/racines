<?php

declare(strict_types=1);

use App\Enums\Offer;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Models\User;
use App\States\Story\StoryState;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Le code n'est pas le seul garde-fou : ces invariants sont écrits en base
 * (convention §13). Les insertions passent donc par le constructeur de
 * requêtes, sans modèle, pour prouver que Postgres refuse de lui-même.
 */
function insertProject(User $owner, array $overrides = []): string
{
    $id = (string) Str::uuid7();

    DB::table('projects')->insert(array_merge([
        'id' => $id,
        'owner_user_id' => $owner->id,
        'status' => ProjectStatus::Active->value,
        'offer' => Offer::Pilot->value,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return $id;
}

it('refuse une visibilité d’histoire hors de la liste R-4', function (): void {
    $owner = User::factory()->create();
    $projectId = insertProject($owner);

    $narratorId = (string) Str::uuid7();
    DB::table('narrators')->insert([
        'id' => $narratorId,
        'project_id' => $projectId,
        'first_name' => 'Marie',
        'display_name' => 'Marie',
        'phone_e164' => '+33600000000',
        'is_primary' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('stories')->insert([
        'id' => (string) Str::uuid7(),
        'project_id' => $projectId,
        'narrator_id' => $narratorId,
        'custom_question_text' => 'Une question',
        'sequence' => 1,
        'state' => 'proposed',
        'visibility' => 'tout_le_monde',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('refuse un état d’histoire hors des onze états documentés', function (): void {
    $owner = User::factory()->create();
    $projectId = insertProject($owner);

    $narratorId = (string) Str::uuid7();
    DB::table('narrators')->insert([
        'id' => $narratorId,
        'project_id' => $projectId,
        'first_name' => 'Marie',
        'display_name' => 'Marie',
        'phone_e164' => '+33600000000',
        'is_primary' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('stories')->insert([
        'id' => (string) Str::uuid7(),
        'project_id' => $projectId,
        'narrator_id' => $narratorId,
        'custom_question_text' => 'Une question',
        'sequence' => 1,
        'state' => 'presque_validee',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('refuse deux fois le même utilisateur sur un projet', function (): void {
    $owner = User::factory()->create();
    $projectId = insertProject($owner);

    $row = [
        'project_id' => $projectId,
        'user_id' => $owner->id,
        'role' => ProjectMemberRole::Initiator->value,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('project_members')->insert($row);

    expect(fn () => DB::table('project_members')->insert($row))->toThrow(QueryException::class);
});

it('refuse un narrateur sans courriel ni téléphone', function (): void {
    $owner = User::factory()->create();
    $projectId = insertProject($owner);

    expect(fn () => DB::table('narrators')->insert([
        'id' => (string) Str::uuid7(),
        'project_id' => $projectId,
        'first_name' => 'Marie',
        'display_name' => 'Marie',
        'is_primary' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('refuse deux narrateurs principaux sur un même projet', function (): void {
    $owner = User::factory()->create();
    $projectId = insertProject($owner);

    $row = [
        'project_id' => $projectId,
        'first_name' => 'Marie',
        'display_name' => 'Marie',
        'phone_e164' => '+33600000000',
        'is_primary' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('narrators')->insert(['id' => (string) Str::uuid7()] + $row);

    expect(fn () => DB::table('narrators')->insert(['id' => (string) Str::uuid7()] + $row))
        ->toThrow(QueryException::class);
});

it('accepte plusieurs narrateurs secondaires, le multi-narrateurs restant en base', function (): void {
    $owner = User::factory()->create();
    $projectId = insertProject($owner);

    foreach (['Marie', 'Henri'] as $name) {
        DB::table('narrators')->insert([
            'id' => (string) Str::uuid7(),
            'project_id' => $projectId,
            'first_name' => $name,
            'display_name' => $name,
            'email' => Str::lower($name).'@example.test',
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    expect(DB::table('narrators')->where('project_id', $projectId)->count())->toBe(2);
});

it('refuse une histoire sans question ni texte personnalisé', function (): void {
    $owner = User::factory()->create();
    $projectId = insertProject($owner);

    $narratorId = (string) Str::uuid7();
    DB::table('narrators')->insert([
        'id' => $narratorId,
        'project_id' => $projectId,
        'first_name' => 'Marie',
        'display_name' => 'Marie',
        'phone_e164' => '+33600000000',
        'is_primary' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('stories')->insert([
        'id' => (string) Str::uuid7(),
        'project_id' => $projectId,
        'narrator_id' => $narratorId,
        'sequence' => 1,
        'state' => 'proposed',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('refuse deux textes de consentement de même version pour un même type', function (): void {
    $row = [
        'kind' => 'voice_recording',
        'version' => '1.0',
        'locale' => 'fr',
        'body' => 'Texte provisoire.',
        'effective_from' => now(),
        'created_at' => now(),
    ];

    DB::table('consent_texts')->insert($row);

    expect(fn () => DB::table('consent_texts')->insert($row))->toThrow(QueryException::class);
});

it('contraint stories.state aux onze états déclarés par la machine', function (): void {
    $constraint = DB::selectOne(
        "select pg_get_constraintdef(oid) as definition from pg_constraint where conname = 'stories_state_check'"
    );

    expect($constraint)->not->toBeNull();

    /** @var object{definition: string} $constraint */
    $declared = StoryState::all()->keys()->all();

    foreach ($declared as $state) {
        expect($constraint->definition)->toContain("'{$state}'");
    }

    expect($declared)->toHaveCount(11);
});
