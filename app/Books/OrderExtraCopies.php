<?php

declare(strict_types=1);

namespace App\Books;

use App\Enums\Sku;
use App\Models\Book;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\CheckoutSessions;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Commander des exemplaires supplémentaires.
 *
 * Séparé du complément de commande (T-184) pour une raison de fond : celui-ci
 * refuse de vendre deux fois le même article, ce qui est juste pour l'option
 * téléphone et faux ici. Une famille commande trois exemplaires en janvier et
 * deux de plus en juin, parce qu'un cousin s'est manifesté — c'est le cas
 * normal, pas une anomalie à empêcher.
 *
 * Le moment compte : les exemplaires se commandent **après** le livre, jamais
 * pendant l'achat initial. On ne sait pas encore combien de pages il fera, et
 * un prix annoncé avant la pagination serait un prix à reprendre.
 */
final readonly class OrderExtraCopies
{
    /**
     * Cinq au maximum en une fois.
     *
     * Pas une limite commerciale : au-delà, c'est une commande qui mérite un
     * échange — un tirage familial de vingt exemplaires ne se traite pas
     * comme un exemplaire de rattrapage, et le support doit le savoir.
     */
    public const MAX_AT_ONCE = 5;

    public function __construct(private CheckoutSessions $sessions) {}

    public function handle(Book $book, int $quantity): CheckoutSession
    {
        if ($quantity < 1 || $quantity > self::MAX_AT_ONCE) {
            throw new RuntimeException('Le nombre d’exemplaires doit être compris entre 1 et '.self::MAX_AT_ONCE.'.');
        }

        if (! $book->status->isLocked()) {
            throw new RuntimeException('Les exemplaires supplémentaires se commandent une fois le livre commandé.');
        }

        $price = Sku::ExtraCopy->stripePriceId(null);

        if (! is_string($price) || $price === '') {
            throw new RuntimeException('Le prix Stripe de l’exemplaire supplémentaire n’est pas configuré.');
        }

        $session = $this->sessions->create(
            customerEmail: (string) $book->project->owner->email,
            lineItems: [['price' => $price, 'quantity' => $quantity]],
            metadata: [
                'book_id' => (string) $book->getKey(),
                'sku' => Sku::ExtraCopy->value,
                'quantity' => (string) $quantity,
            ],
            successUrl: route('initiator.book').'?copies='.$quantity,
            cancelUrl: route('initiator.book'),
        );

        Log::info('checkout.extra_copies_opened', [
            'book_id' => $book->getKey(),
            'quantity' => $quantity,
            'session_id' => $session->id,
        ]);

        return $session;
    }
}
