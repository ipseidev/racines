<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Books\IssueQrToken;
use App\Books\RenderBookHtml;
use App\Books\SelectBookChapters;
use App\Enums\BookStatus;
use App\Models\Book;
use App\Notifications\BookNotification;
use App\Services\Pdf\HtmlToPdf;
use App\Services\Pdf\PdfOptions;
use App\Services\Pdf\PdfPageCount;
use App\Services\Storage\MediaStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Fabriquer le bon à tirer.
 *
 * En file, et longuement : un livre de soixante pages avec ses photos demande
 * plusieurs minutes à Chromium. Le faire dans la requête ferait expirer la
 * page de l'Initiateur·rice au moment précis où elle attend le plus.
 *
 * L'ordre des cinq gestes n'est pas indifférent :
 *
 *  1. **Rafraîchir les chapitres** : une histoire validée depuis la dernière
 *     génération doit entrer dans le livre sans qu'on y pense.
 *  2. **Émettre les QR manquants avant le rendu**, jamais après : un chapitre
 *     rendu sans son jeton sortirait sans QR, et le défaut ne se verrait
 *     qu'à l'impression.
 *  3. **Rendre**, puis compter les pages sur le fichier produit — le compte
 *     estimé d'avant le rendu est un calcul, celui-ci est un fait.
 *  4. **Stocker sous un numéro de version**, sans écraser : le BAT précédent
 *     reste lisible, ce qui est la seule façon de répondre à « qu'est-ce que
 *     j'avais approuvé ? ».
 *  5. **Prévenir** seulement une fois le fichier en place.
 */
final class RenderBookPdf implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(private readonly Book $book)
    {
        $this->onQueue('exports');
    }

    public function handle(
        SelectBookChapters $chapters,
        IssueQrToken $qr,
        RenderBookHtml $html,
        HtmlToPdf $pdf,
        MediaStorage $storage,
    ): void {
        $book = $this->book->fresh();

        if ($book === null) {
            return;
        }

        $chapters->handle($book);

        foreach ($book->chapters()->where('included', true)->get() as $chapter) {
            $qr->handle($chapter);
        }

        $path = $pdf->render($html->handle($book->refresh()), PdfOptions::book());
        $version = $book->proof_version + 1;
        $key = sprintf('books/%s/proof-v%d.pdf', $book->getKey(), $version);

        // Compter **avant** d'effacer : le fichier temporaire est la seule
        // source du nombre réel de pages.
        $pages = PdfPageCount::of($path);

        $storage->put($key, (string) file_get_contents($path), 'application/pdf');
        @unlink($path);

        $book->forceFill([
            'proof_pdf_path' => $key,
            'proof_version' => $version,
            'proof_generated_at' => now(),
            'page_count_estimate' => $pages ?: $book->page_count_estimate,
            // Un nouveau BAT annule l'accord précédent : ce qui avait été
            // approuvé n'est plus ce qu'on imprimerait.
            'status' => BookStatus::Proofing,
            'proof_approved_at' => null,
            'proof_approved_by_user_id' => null,
            'proof_acknowledged_final_print' => false,
            'proof_acknowledged_lexicon_reviewed' => false,
        ])->save();

        Log::info('book.proof_rendered', [
            'book_id' => $book->getKey(),
            'version' => $version,
            'pages' => $book->page_count_estimate,
        ]);

        $book->project->owner->notify(new BookNotification($book, 'proof_ready'));
    }
}
