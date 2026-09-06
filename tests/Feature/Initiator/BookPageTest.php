<?php

declare(strict_types=1);

use App\Enums\BookStatus;
use App\Enums\ProjectStatus;
use App\Jobs\RenderBookPdf;
use App\Models\Book;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\User;
use App\States\Story\InBook;
use App\States\Story\Shared;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * L'espace « Le livre ».
 *
 * Ce que la page doit faire comprendre avant tout bouton : **le livre se
 * déclenche quand la matière suffit, pas à un nombre d'histoires** (R-6).
 * D'où une jauge à quatre mesures — et le seuil qui manque en dernier est
 * celui des pages, pas celui des mots.
 *
 * @return array{User, Project}
 */
function projetDeLivre(): array
{
    $owner = User::factory()->create();
    $owner->markEmailAsVerified();

    $project = Project::factory()->create([
        'owner_user_id' => $owner->id,
        'status' => ProjectStatus::Active,
        'collection_started_at' => now()->subMonths(3),
    ]);

    Narrator::factory()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'first_name' => 'Marcelle',
    ]);

    return [$owner, $project->refresh()];
}

function histoireDuLivre(Project $project, string $titre, string $quand): Story
{
    $story = Story::factory()->validated()->create([
        'project_id' => $project->id,
        'title' => $titre,
        'recorded_at' => $quand,
    ]);

    Transcript::factory()->for($story)->fluide()->create(['text' => "Le texte de {$titre}."]);

    return $story;
}

beforeEach(function (): void {
    Storage::fake('r2');
});

it('montre la jauge, ses quatre mesures et leurs seuils', function (): void {
    [$owner, $project] = projetDeLivre();
    histoireDuLivre($project, 'Le fournil', '2026-02-01');

    $this->actingAs($owner)->get('/espace/livre')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('initiator/Book')
            ->where('narratorFirstName', 'Marcelle')
            // Quatre mesures, pas un pourcentage : R-6 interdit un seuil en
            // nombre d'histoires, et un chiffre unique le laisserait croire.
            ->has('gauge.words')
            ->has('gauge.audioMinutes')
            ->has('gauge.pages')
            ->has('gauge.themes')
            ->where('gauge.minPages', 60)
            ->where('gauge.ready', false));
});

it('liste les chapitres dans l’ordre du souvenir', function (): void {
    [$owner, $project] = projetDeLivre();
    histoireDuLivre($project, 'Le bal', '2026-03-01');
    histoireDuLivre($project, 'Le fournil', '2026-01-01');

    $this->actingAs($owner)->get('/espace/livre')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('chapters.0.title', 'Le fournil')
            ->where('chapters.1.title', 'Le bal')
            ->where('chapters.0.included', true));
});

it('enregistre l’ordre et les exclusions', function (): void {
    [$owner, $project] = projetDeLivre();
    histoireDuLivre($project, 'Le fournil', '2026-01-01');
    histoireDuLivre($project, 'Le bal', '2026-03-01');

    $this->actingAs($owner)->get('/espace/livre');
    $book = Book::query()->firstOrFail();
    $chapitres = $book->chapters()->orderBy('position')->get();

    $this->actingAs($owner)->post('/espace/livre', [
        'chapters' => [
            ['id' => $chapitres[1]->getKey(), 'included' => true],
            ['id' => $chapitres[0]->getKey(), 'included' => false],
        ],
        'foreword' => 'Pour mes petits-enfants.',
    ])->assertRedirect();

    $apres = $book->refresh()->chapters()->orderBy('position')->get();

    expect($apres[0]->getKey())->toBe($chapitres[1]->getKey())
        ->and($apres[1]->included)->toBeFalse()
        ->and($book->foreword)->toBe('Pour mes petits-enfants.');
});

it('lance la fabrication du bon à tirer', function (): void {
    Queue::fake();
    [$owner, $project] = projetDeLivre();
    histoireDuLivre($project, 'Le fournil', '2026-01-01');

    $this->actingAs($owner)->get('/espace/livre');
    $this->actingAs($owner)->post('/espace/livre/bat')->assertRedirect();

    Queue::assertPushed(RenderBookPdf::class);
});

it('refuse un bon à tirer sans aucun chapitre', function (): void {
    Queue::fake();
    [$owner] = projetDeLivre();

    $this->actingAs($owner)->get('/espace/livre');

    $this->actingAs($owner)->from('/espace/livre')
        ->post('/espace/livre/bat')
        ->assertSessionHasErrors('chapters');

    Queue::assertNothingPushed();
});

it('refuse l’accord sans les deux cases', function (): void {
    [$owner, $project] = projetDeLivre();
    histoireDuLivre($project, 'Le fournil', '2026-01-01');

    $this->actingAs($owner)->get('/espace/livre');
    Book::query()->firstOrFail()->forceFill([
        'status' => BookStatus::Proofing,
        'proof_pdf_path' => 'books/x/proof-v1.pdf',
        'proof_version' => 1,
    ])->save();

    $this->actingAs($owner)->from('/espace/livre')
        ->post('/espace/livre/accord', ['final_print' => true, 'lexicon_reviewed' => false])
        ->assertSessionHasErrors('lexicon_reviewed');

    expect(Book::query()->firstOrFail()->status)->toBe(BookStatus::Proofing);
});

it('approuve, commande, et verrouille la page', function (): void {
    [$owner, $project] = projetDeLivre();
    $story = histoireDuLivre($project, 'Le fournil', '2026-01-01');
    $story->state->transitionTo(Shared::class);

    $this->actingAs($owner)->get('/espace/livre');
    Book::query()->firstOrFail()->forceFill([
        'status' => BookStatus::Proofing,
        'proof_pdf_path' => 'books/x/proof-v1.pdf',
        'proof_version' => 1,
    ])->save();

    $this->actingAs($owner)
        ->post('/espace/livre/accord', ['final_print' => true, 'lexicon_reviewed' => true])
        ->assertRedirect();

    $book = Book::query()->firstOrFail();

    expect($book->status)->toBe(BookStatus::Ordered)
        ->and($story->refresh()->state)->toBeInstanceOf(InBook::class);

    // La sélection ne bouge plus : ce qui a été approuvé est ce qui s'imprime.
    $this->actingAs($owner)->post('/espace/livre', [
        'chapters' => [],
    ])->assertForbidden();
});

it('n’ouvre pas la page à qui n’a pas de projet', function (): void {
    $etranger = User::factory()->create();
    $etranger->markEmailAsVerified();

    // 404 et non 403 : dire « interdit » confirmerait qu'un livre existe
    // quelque part, ce qui n'est l'affaire de personne d'autre.
    $this->actingAs($etranger)->get('/espace/livre')->assertNotFound();
});
