<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\Stories\Pages\ViewStory;
use App\Models\Recording;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Écouter un enregistrement depuis le back-office laisse une trace.
 *
 * Le dossier exige la journalisation des **lectures** et pas seulement des
 * écritures : un support qui écoute l'histoire de quelqu'un doit laisser une
 * trace, et c'est ce qui distingue un back-office d'un accès libre aux
 * souvenirs d'une famille.
 *
 * Trouvé en jouant le checkpoint du bloc 11 : la fiche d'histoire n'avait
 * **aucun lecteur**, et rien n'émettait jamais `played Recording`. La case
 * §6 était pourtant cochée, et le test censé la couvrir appelait
 * `AuditLog::record('played Recording', …)` **à la main** — il prouvait que
 * le journal sait enregistrer cette action, jamais que quelque chose la
 * déclenche. Même motif qu'en T-154 et T-178 (T-181).
 */
function supportUser(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

it('n’offre pas d’écoute quand l’histoire n’a pas d’enregistrement', function (): void {
    $story = Story::factory()->transcribed()->create();

    Livewire::actingAs(supportUser())
        ->test(ViewStory::class, ['record' => $story->getKey()])
        ->assertActionHidden('listen');
});

it('inscrit « played Recording » quand le support demande l’écoute', function (): void {
    $story = Story::factory()->shared()->create();
    Recording::factory()->for($story)->confirmed()->create();

    Livewire::actingAs(supportUser())
        ->test(ViewStory::class, ['record' => $story->getKey()])
        ->assertActionVisible('listen')
        ->callAction('listen');

    $trace = DB::table('audit_logs')->where('action', 'played Recording')->first();

    expect($trace)->not->toBeNull()
        ->and($trace->subject_id)->not->toBeNull();
});

/*
 * La lecture seule peut écouter — c'est son métier — mais la trace reste la
 * même. Ce qu'elle ne peut pas, c'est agir, et d'autres tests le couvrent.
 */
it('trace aussi l’écoute d’un compte en lecture seule', function (): void {
    $story = Story::factory()->shared()->create();
    Recording::factory()->for($story)->confirmed()->create();

    $lecteur = User::factory()->create(['role' => UserRole::SupportReadonly]);

    Livewire::actingAs($lecteur)
        ->test(ViewStory::class, ['record' => $story->getKey()])
        ->callAction('listen');

    expect(DB::table('audit_logs')->where('action', 'played Recording')->count())->toBe(1);
});
