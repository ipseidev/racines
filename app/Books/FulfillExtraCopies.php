<?php

declare(strict_types=1);

namespace App\Books;

use App\Audit\AuditLog;
use App\Enums\SupportTicketKind;
use App\Models\Book;
use App\Models\SupportTicket;
use App\Services\Storage\MediaStorage;
use Illuminate\Support\Facades\Log;

/**
 * Le paiement d'exemplaires supplémentaires est arrivé : ouvrir le tirage.
 *
 * Un ticket **distinct** de celui de la commande initiale, et volontairement
 * pas idempotent par genre : une famille peut commander deux exemplaires en
 * janvier et trois en juin, et les fondre en un seul ticket ferait imprimer
 * la seconde commande à la place de la première.
 *
 * Le nombre vient de la **session payée** et non d'un paramètre de requête :
 * c'est la leçon de T-184, le montant et la quantité se lisent là où l'argent
 * a réellement changé de main.
 */
final readonly class FulfillExtraCopies
{
    public function __construct(private MediaStorage $storage) {}

    /**
     * @param  array<string, mixed>  $session
     */
    public function handle(array $session): ?SupportTicket
    {
        $bookId = (string) data_get($session, 'metadata.book_id');
        $sessionId = (string) data_get($session, 'id');
        $book = Book::query()->find($bookId);

        if (! $book instanceof Book) {
            // On consigne et on s'arrête : l'argent est pris, et un support
            // doit pouvoir répondre à quelqu'un qui a payé.
            Log::warning('checkout.extra_copies_orphan', [
                'session_id' => $sessionId,
                'book_id' => $bookId,
            ]);

            return null;
        }

        /*
         * Idempotence par session, pas par genre de ticket.
         *
         * Stripe rejoue un webhook au moindre doute ; sans cette garde, une
         * seule commande produirait deux tirages. Et la garde ne peut pas
         * porter sur « un ticket ouvert du même genre » comme ailleurs :
         * deux commandes successives sont deux tirages légitimes.
         */
        $existing = SupportTicket::query()
            ->where('kind', SupportTicketKind::PrintOrder->value)
            ->whereJsonContains('payload->session_id', $sessionId)
            ->first();

        if ($existing instanceof SupportTicket) {
            return $existing;
        }

        $quantity = max(1, (int) data_get($session, 'metadata.quantity', 1));
        $path = $book->proof_pdf_path;

        $ticket = new SupportTicket([
            'kind' => SupportTicketKind::PrintOrder,
            'payload' => [
                'book_id' => $book->getKey(),
                'session_id' => $sessionId,
                'extra_copies' => $quantity,
                'proof_key' => $path,
                'proof_url' => is_string($path) && $path !== ''
                    ? $this->storage->temporaryUrl($path, 24 * 60)
                    : null,
            ],
            'opened_at' => now(),
        ]);

        $ticket->project()->associate($book->project);
        $ticket->save();

        AuditLog::record('ordered ExtraCopies', $book, [
            'quantity' => $quantity,
            'session_id' => $sessionId,
        ], $book->project);

        Log::info('checkout.extra_copies_fulfilled', [
            'book_id' => $book->getKey(),
            'quantity' => $quantity,
        ]);

        return $ticket;
    }
}
