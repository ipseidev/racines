<?php

declare(strict_types=1);

use App\Books\IssueQrToken;
use App\Enums\ProjectStatus;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * Poser, changer, retirer le code du livre.
 *
 * Le code famille était **lu** par la page QR et posé nulle part : le point 3
 * du checkpoint du bloc 13 demandait un geste que le produit n'offrait pas
 * (T-200). Un écart entre §6 et §7 de la feuille, pas un oubli d'écriture —
 * la protection était décrite du côté qui la vérifie, jamais du côté qui la
 * décide.
 *
 * Ce que la protection doit rester : **facultative**. Un livre se prête, et
 * l'exiger par défaut ferait échouer le premier scan du premier lecteur.
 *
 * @return array{User, Project}
 */
function projetAvecLivre(): array
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

it('n’en demande aucun par défaut', function (): void {
    [$owner, $project] = projetAvecLivre();

    $this->actingAs($owner)->get('/espace/livre')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('familyCodeSet', false));

    expect($project->refresh()->family_code_hash)->toBeNull();
});

it('pose un code, haché, jamais en clair', function (): void {
    [$owner, $project] = projetAvecLivre();

    $this->actingAs($owner)
        ->post('/espace/livre/code', ['code' => 'KERHOSTIN'])
        ->assertRedirect();

    $hash = $project->refresh()->family_code_hash;

    expect($hash)->not->toBeNull()
        // Haché comme un mot de passe : la base ne doit pas rendre le code
        // imprimé sur le rabat de trente exemplaires.
        ->and($hash)->not->toContain('KERHOSTIN')
        ->and(Hash::check('KERHOSTIN', (string) $hash))->toBeTrue();
});

it('ferme la page QR une fois le code posé', function (): void {
    [$owner, $project] = projetAvecLivre();

    $story = Story::factory()->shared()->create(['project_id' => $project->id]);
    $book = Book::factory()->create(['project_id' => $project->id]);
    $chapter = new BookChapter(['position' => 10, 'included' => true]);
    $chapter->story()->associate($story);
    $chapter->book()->associate($book);
    $chapter->save();
    app(IssueQrToken::class)->handle($chapter);
    $code = IssueQrToken::plainFor($chapter);

    $this->get("/q/{$code}")
        ->assertInertia(fn (AssertableInertia $page) => $page->component('family/Story'));

    $this->actingAs($owner)->post('/espace/livre/code', ['code' => 'KERHOSTIN']);

    $this->get("/q/{$code}")
        ->assertInertia(fn (AssertableInertia $page) => $page->component('qr/FamilyCode'));
});

it('retire le code et rouvre le livre', function (): void {
    [$owner, $project] = projetAvecLivre();
    $this->actingAs($owner)->post('/espace/livre/code', ['code' => 'KERHOSTIN']);

    $this->actingAs($owner)->delete('/espace/livre/code')->assertRedirect();

    expect($project->refresh()->family_code_hash)->toBeNull();
});

it('refuse un code trop court pour protéger quoi que ce soit', function (): void {
    [$owner] = projetAvecLivre();

    $this->actingAs($owner)->from('/espace/livre')
        ->post('/espace/livre/code', ['code' => '12'])
        ->assertSessionHasErrors('code');
});
