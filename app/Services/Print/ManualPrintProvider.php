<?php

declare(strict_types=1);

namespace App\Services\Print;

use App\Actions\OpenSupportTicket;
use App\Enums\SupportTicketKind;
use App\Models\Book;
use App\Services\Storage\MediaStorage;

/**
 * L'impression du pilote : un ticket au support, avec le PDF.
 *
 * Pas un pis-aller, une décision (§9 du bloc 13). Une dizaine de familles ne
 * justifie pas d'intégrer une API d'imprimeur — et passer les premières
 * commandes à la main est le meilleur moyen de découvrir ce qu'un imprimeur
 * demande vraiment : profil colorimétrique, fonds perdus, dos calculé,
 * autant de choses qu'un devis révèle et qu'une intégration écrite d'avance
 * aurait devinées de travers.
 *
 * Le lien du PDF est **temporaire, vingt-quatre heures**, et régénérable
 * depuis la fiche : un lien permanent vers le livre d'une famille dans un
 * ticket de support est exactement ce que le dossier interdit.
 */
final readonly class ManualPrintProvider implements PrintProvider
{
    private const LINK_MINUTES = 24 * 60;

    public function __construct(
        private OpenSupportTicket $tickets,
        private MediaStorage $storage,
    ) {}

    public function order(Book $book): PrintOrder
    {
        $path = $book->proof_pdf_path;

        $ticket = $this->tickets->handle(
            $book->project,
            SupportTicketKind::PrintOrder,
            payload: [
                'book_id' => $book->getKey(),
                'proof_version' => $book->proof_version,
                'format' => $book->format->value,
                'pages' => $book->page_count_estimate,
                'trim_size_mm' => config('product.book.trim_size_mm'),
                // Le lien expire ; la clé, elle, permet de le régénérer.
                'proof_key' => $path,
                'proof_url' => is_string($path) && $path !== ''
                    ? $this->storage->temporaryUrl($path, self::LINK_MINUTES)
                    : null,
            ],
        );

        // La référence de l'imprimeur n'existe pas encore : elle sera saisie
        // par le support une fois la commande passée. Le ticket en tient lieu
        // en attendant, ce qui évite une colonne vide qu'on prendrait pour
        // une panne.
        return new PrintOrder(
            reference: 'ticket:'.$ticket->getKey(),
            ticketId: (string) $ticket->getKey(),
        );
    }
}
