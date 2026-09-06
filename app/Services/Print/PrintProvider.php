<?php

declare(strict_types=1);

namespace App\Services\Print;

use App\Models\Book;

/**
 * Passer un livre à l'impression.
 *
 * Un port, comme l'ASR, le LLM, le SMS et le paiement — et pour la même
 * raison de fond : on ne connaît pas encore l'imprimeur. Le dossier exige une
 * stratégie de sortie documentée pour chaque prestataire, et celui-ci n'est
 * même pas choisi : le devis 0A n'est pas fait, et le format 16 × 24 cm a été
 * arrêté précisément pour qu'il soit imprimable partout (T-179).
 *
 * Au pilote, la seule implémentation est **manuelle** : un ticket au support
 * avec le PDF. Ce n'est pas un pis-aller — c'est la décision §9 du bloc. Une
 * dizaine de familles ne justifie pas une intégration, et passer la première
 * commande à la main est le meilleur moyen de découvrir ce qu'un imprimeur
 * demande vraiment avant de l'automatiser.
 */
interface PrintProvider
{
    public function order(Book $book): PrintOrder;
}
