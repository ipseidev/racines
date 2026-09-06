<?php

declare(strict_types=1);

namespace App\Books;

use App\Audit\AuditLog;
use App\Enums\BookStatus;
use App\Models\Book;
use Illuminate\Support\Facades\Log;

/**
 * Faire avancer un livre dans l'impression : imprimé, puis livré.
 *
 * Une action du domaine et non deux lignes dans le panneau : le back-office
 * n'écrit jamais en base directement, un critère de sortie du bloc 11 le
 * vérifie sur tout `app/Filament`, et la raison tient à l'audit — une
 * écriture faite depuis une ressource Filament n'a pas de place naturelle où
 * inscrire *qui* l'a faite et *pourquoi*.
 *
 * « Livré » n'est pas une étape comme les autres : elle déclenche l'export
 * proactif que le dossier promet à la finalisation du livre (R-10). D'où la
 * confirmation côté panneau, et la trace ici.
 */
final readonly class AdvanceBookPrinting
{
    public function handle(Book $book, BookStatus $status): Book
    {
        $column = match ($status) {
            BookStatus::Printed => 'printed_at',
            BookStatus::Delivered => 'delivered_at',
            default => null,
        };

        if ($column === null) {
            return $book;
        }

        $book->forceFill([
            'status' => $status,
            $column => now(),
        ])->save();

        AuditLog::record('advanced Book', $book, [
            'status' => $status->value,
        ], $book->project);

        Log::info('book.advanced', [
            'book_id' => $book->getKey(),
            'status' => $status->value,
        ]);

        return $book;
    }
}
