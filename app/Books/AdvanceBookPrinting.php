<?php

declare(strict_types=1);

namespace App\Books;

use App\Actions\RequestExport;
use App\Audit\AuditLog;
use App\Enums\BookStatus;
use App\Enums\ExportKind;
use App\Enums\ExportScope;
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

        /*
         * À la **livraison**, et pas avant : la famille a le livre en main,
         * le projet se termine, et c'est le moment précis où tout le monde
         * passe à autre chose. Lui envoyer alors l'intégralité de ce qu'elle
         * a confié — plus un pack qui joue les voix sans nous — est ce que
         * R-10.2 appelle la remise proactive.
         *
         * Pas à « imprimé » : le livre est encore chez l'imprimeur, et
         * « voici votre livre » serait faux d'une semaine.
         */
        if ($status === BookStatus::Delivered) {
            foreach ([ExportKind::Full, ExportKind::OfflinePack] as $kind) {
                app(RequestExport::class)->handle($book->project, ExportScope::Initiator, $kind);
            }
        }

        Log::info('book.advanced', [
            'book_id' => $book->getKey(),
            'status' => $status->value,
        ]);

        return $book;
    }
}
