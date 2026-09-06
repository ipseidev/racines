<?php

declare(strict_types=1);

use App\Enums\BookStatus;
use App\Enums\ExportKind;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketKind;
use App\Jobs\BuildExport;
use App\Models\Book;
use App\Models\Export;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * La page « Vos données ».
 *
 * Elle rend la non-captivité **visible**. Un export possible mais caché dans
 * un échange avec le support ne vaut rien : ce que le dossier promet, c'est
 * que la famille puisse partir, et une porte qu'on ne voit pas n'est pas une
 * porte.
 *
 * @return array{User, Project}
 */
function proprietaireAvecProjet(): array
{
    $owner = User::factory()->create();
    $owner->markEmailAsVerified();

    $project = Project::factory()->create([
        'owner_user_id' => $owner->id,
        'status' => ProjectStatus::Active,
    ]);
    Narrator::factory()->create(['project_id' => $project->id, 'is_primary' => true]);

    return [$owner, $project->refresh()];
}

beforeEach(function (): void {
    Queue::fake();
});

it('montre les trois formes d’export et l’historique', function (): void {
    [$owner, $project] = proprietaireAvecProjet();

    $this->actingAs($owner)->get('/espace/donnees')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('initiator/Data')
            ->where('erasureRequested', false)
            ->where('printInProgress', false)
            ->has('exports', 0));
});

it('met un export en file sans le fabriquer dans la requête', function (): void {
    [$owner, $project] = proprietaireAvecProjet();

    $this->actingAs($owner)
        ->post('/espace/donnees/export', ['kind' => 'offline_pack'])
        ->assertRedirect();

    $export = Export::query()->firstOrFail();

    expect($export->kind)->toBe(ExportKind::OfflinePack)
        ->and($export->project_id)->toBe($project->getKey());

    // Cinq gigaoctets ne se construisent pas dans une requête HTTP.
    Queue::assertPushed(BuildExport::class);
});

it('exige le mot en entier pour l’effacement', function (): void {
    [$owner] = proprietaireAvecProjet();

    $this->actingAs($owner)->from('/espace/donnees')
        ->post('/espace/donnees/effacement', ['confirmation' => 'oui'])
        ->assertSessionHasErrors('confirmation');

    // Une case à cocher se clique sans lire ; un mot se tape.
    expect(SupportTicket::query()->count())->toBe(0);
});

it('ouvre un ticket, et n’efface rien tout de suite', function (): void {
    [$owner, $project] = proprietaireAvecProjet();

    $this->actingAs($owner)
        ->post('/espace/donnees/effacement', ['confirmation' => 'EFFACER'])
        ->assertRedirect();

    expect(SupportTicket::query()->where('kind', SupportTicketKind::ErasureRequested->value)->count())->toBe(1)
        // Rien n'est parti : un humain confirme.
        ->and($project->refresh()->erased_at)->toBeNull();
});

it('dit que l’effacement attend quand un livre est à l’impression', function (): void {
    [$owner, $project] = proprietaireAvecProjet();
    Book::factory()->create(['project_id' => $project->id, 'status' => BookStatus::Ordered]);

    $this->actingAs($owner)->get('/espace/donnees')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('printInProgress', true));
});
