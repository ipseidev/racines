<?php

declare(strict_types=1);

use App\Enums\BookStatus;
use App\Jobs\RenderBookPdf;
use App\Models\Book;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Models\Transcript;
use App\Notifications\BookNotification;
use App\Services\Pdf\FakeHtmlToPdf;
use App\Services\Pdf\HtmlToPdf;
use App\Services\Storage\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * La fabrication du bon à tirer.
 *
 * Ce que le job garantit et qu'aucune de ses pièces ne garantit seule :
 * l'ordre. Les chapitres se rafraîchissent avant le rendu, les QR s'émettent
 * avant le rendu, et le fichier existe avant qu'on prévienne la famille — un
 * courriel « votre bon à tirer est prêt » qui mène à un fichier absent est
 * exactement le genre d'incident qui use la confiance.
 *
 * Le rendu lui-même passe par le double : le vrai demande Chromium et cinq
 * minutes. Ce que le double ne prouve pas, la vérification humaine du §7 le
 * prouve.
 */
function livreEnAttenteDeBat(): Book
{
    $project = Project::factory()->create(['collection_started_at' => '2026-01-15']);
    Narrator::factory()->create(['project_id' => $project->id, 'first_name' => 'Marcelle', 'is_primary' => true]);

    $story = Story::factory()->validated()->create(['project_id' => $project->id, 'title' => 'Le fournil']);
    Transcript::factory()->for($story)->fluide()->create(['text' => 'Le texte du fournil.']);

    return Book::factory()->for($project)->create();
}

beforeEach(function (): void {
    $this->pdf = new FakeHtmlToPdf;
    app()->instance(HtmlToPdf::class, $this->pdf);
    Notification::fake();
});

it('sélectionne les chapitres, émet les QR, rend et range le fichier', function (): void {
    $book = livreEnAttenteDeBat();

    app()->call([new RenderBookPdf($book), 'handle']);
    $book->refresh();

    expect($book->chapters)->toHaveCount(1)
        ->and($book->chapters->first()->qr_token_id)->not->toBeNull()
        ->and($book->proof_version)->toBe(1)
        ->and($book->proof_pdf_path)->toBe("books/{$book->getKey()}/proof-v1.pdf")
        ->and($book->proof_generated_at)->not->toBeNull()
        ->and($book->status)->toBe(BookStatus::Proofing);

    // Le fichier est réellement là : un chemin en base sans objet dans le
    // stockage est la panne que le bloc 04 a appris à ne plus tolérer.
    expect(app(MediaStorage::class)->head($book->proof_pdf_path)->bytes)->toBeGreaterThan(0);
});

it('compte les pages du fichier produit', function (): void {
    $book = livreEnAttenteDeBat();

    app()->call([new RenderBookPdf($book), 'handle']);

    // Le double écrit un PDF d'une page : le compte vient du fichier, pas
    // d'une estimation faite avant le rendu.
    expect($book->refresh()->page_count_estimate)->toBe(1);
});

it('incrémente la version et annule l’accord précédent', function (): void {
    $book = livreEnAttenteDeBat();
    $book->forceFill([
        'proof_approved_at' => now(),
        'proof_acknowledged_final_print' => true,
        'proof_acknowledged_lexicon_reviewed' => true,
    ])->save();

    app()->call([new RenderBookPdf($book), 'handle']);
    app()->call([new RenderBookPdf($book->refresh()), 'handle']);
    $book->refresh();

    expect($book->proof_version)->toBe(2)
        ->and($book->proof_pdf_path)->toBe("books/{$book->getKey()}/proof-v2.pdf")
        // Ce qui avait été approuvé n'est plus ce qu'on imprimerait.
        ->and($book->proof_approved_at)->toBeNull()
        ->and($book->proof_acknowledged_final_print)->toBeFalse();
});

it('prévient l’Initiateur·rice, une fois par version', function (): void {
    $book = livreEnAttenteDeBat();

    app()->call([new RenderBookPdf($book), 'handle']);

    Notification::assertSentTo(
        $book->project->owner,
        fn (BookNotification $notification): bool => $notification->reason === 'proof_ready',
    );
});

it('rend un HTML qui porte le chapitre et son QR', function (): void {
    app()->call([new RenderBookPdf(livreEnAttenteDeBat()), 'handle']);

    expect($this->pdf->lastHtml())->toContain('Le fournil')
        ->and($this->pdf->lastHtml())->toContain('class="qr-box"')
        // Le format part bien en 16 × 24 (T-179), et pas en A4.
        ->and($this->pdf->lastOptions()->widthMm)->toBe(160.0)
        ->and($this->pdf->lastOptions()->heightMm)->toBe(240.0);
});
