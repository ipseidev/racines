<?php

declare(strict_types=1);

namespace App\Books;

use App\Actions\OpenSupportTicket;
use App\Audit\AuditLog;
use App\Enums\BookStatus;
use App\Enums\SupportTicketKind;
use App\Models\Book;

/**
 * Réimprimer un livre défectueux.
 *
 * **Gratuite et sans condition** (doc 04 §10). Le motif sert à savoir ce qui
 * s'est mal passé chez l'imprimeur, jamais à juger la demande : un livre
 * abîmé, mal massicoté ou aux pages inversées coûte moins cher à réimprimer
 * qu'à discuter, et discuter serait de toute façon la mauvaise réponse à une
 * famille qui attendait ce livre depuis un an.
 *
 * Le bon à tirer n'est pas regénéré : on réimprime **ce qui a été approuvé**.
 * Refaire le rendu pourrait produire un livre différent — une histoire
 * validée depuis, une photo ajoutée — et le second exemplaire ne serait plus
 * le même livre que le premier.
 */
final readonly class RequestBookReprint
{
    public function __construct(private OpenSupportTicket $tickets) {}

    public function handle(Book $book, string $reason): Book
    {
        $this->tickets->handle(
            $book->project,
            SupportTicketKind::PrintDefect,
            payload: [
                'book_id' => $book->getKey(),
                'reason' => $reason,
                'proof_key' => $book->proof_pdf_path,
                'proof_version' => $book->proof_version,
            ],
        );

        $book->forceFill(['status' => BookStatus::Reprint])->save();

        AuditLog::record('reprinted Book', $book, [
            'reason' => $reason,
        ], $book->project);

        return $book;
    }
}
