<?php

declare(strict_types=1);

namespace App\Books;

use App\Audit\AuditLog;
use App\Enums\BookStatus;
use App\Exceptions\Domain\ProofNotApprovable;
use App\Models\Book;
use App\Models\Story;
use App\Models\User;
use App\Services\Print\PrintProvider;
use App\States\Story\InBook;
use App\States\Story\Shared;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Approuver le bon à tirer, et commander.
 *
 * **Les deux cases sont obligatoires**, et ce ne sont pas des cases de
 * confort : « l'imprimé est définitif » et « j'ai relu les noms propres ». Le
 * dossier les impose (doc 04 §10) parce que la seule erreur irréparable du
 * produit se commet ici — après, il y a trente exemplaires chez une famille,
 * et une coquille sur le nom d'un aïeul ne se corrige plus.
 *
 * Ni l'une ni l'autre n'est pré-cochée, et l'absence de clic ne vaut jamais
 * accord : c'est la même règle que la validation d'une histoire (R-4).
 *
 * L'approbation **verrouille la sélection** : ce qui est parti à l'impression
 * ne doit plus pouvoir changer sous la commande. Et chaque histoire incluse
 * passe à `INCLUSE AU LIVRE` — un état du récit, pas une colonne technique :
 * le narrateur qui la retirerait ensuite retire une histoire qui est déjà sur
 * du papier, et le produit doit le savoir.
 */
final readonly class ApproveBookProof
{
    public function __construct(private PrintProvider $printer) {}

    public function handle(Book $book, User $approver, bool $finalPrint, bool $lexiconReviewed): Book
    {
        /*
         * `isApprovable()` du modèle répond à « les cases sont-elles
         * cochées en base ? » ; ici, elles arrivent du formulaire. Ce qui se
         * vérifie donc, ce sont les deux préalables matériels : un PDF
         * existe, et la commande n'est pas déjà partie.
         */
        if ($book->proof_pdf_path === null || $book->proof_pdf_path === '' || ! $book->isEditable()) {
            throw ProofNotApprovable::notReady();
        }

        if (! $finalPrint || ! $lexiconReviewed) {
            throw ProofNotApprovable::missingAcknowledgement();
        }

        return DB::transaction(function () use ($book, $approver): Book {
            $book->forceFill([
                'proof_acknowledged_final_print' => true,
                'proof_acknowledged_lexicon_reviewed' => true,
                'proof_approved_at' => now(),
                'proof_approved_by_user_id' => $approver->getKey(),
                'status' => BookStatus::Approved,
            ])->save();

            $order = $this->printer->order($book);

            $book->forceFill([
                'status' => BookStatus::Ordered,
                'ordered_at' => now(),
                'print_order_ref' => $order->reference,
            ])->save();

            foreach ($book->chapters()->where('included', true)->with('story')->get() as $chapter) {
                $this->markPrinted($chapter->story);
            }

            AuditLog::record('approved BookProof', $book, [
                'proof_version' => $book->proof_version,
                'chapters' => $book->chapters()->where('included', true)->count(),
                'print_order_ref' => $order->reference,
            ], $book->project);

            Log::info('book.ordered', [
                'book_id' => $book->getKey(),
                'reference' => $order->reference,
            ]);

            return $book->refresh();
        });
    }

    /**
     * L'histoire entre au livre, et le sait.
     *
     * `printed_in_book` en plus de l'état : l'état peut repartir — un récit
     * masqué après l'impression quitte `INCLUSE AU LIVRE` — alors que le fait
     * d'avoir été imprimé, lui, ne se défait pas. C'est ce drapeau qui permet
     * à la page QR de dire « le texte imprimé reste le vôtre ».
     */
    private function markPrinted(Story $story): void
    {
        if ($story->state instanceof Shared || $story->state instanceof InBook) {
            if (! $story->state instanceof InBook) {
                $story->state->transitionTo(InBook::class);
            }
        }

        $story->forceFill(['printed_in_book' => true])->save();
    }
}
