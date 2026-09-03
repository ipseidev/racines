<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Illuminate\Support\Str;

/**
 * Les remboursements, en mémoire.
 *
 * Elle garde ce qu'on lui a demandé : c'est ainsi qu'on vérifie que le
 * back-office exige un motif et envoie le bon montant, sans risquer de
 * rembourser un vrai paiement.
 */
final class FakeRefunds implements Refunds
{
    /** @var list<array{payment_intent: string, amount: int|null, reason: string}> */
    private array $issued = [];

    public function refund(string $paymentIntentId, ?int $amountCents, string $reason): Refund
    {
        $this->issued[] = [
            'payment_intent' => $paymentIntentId,
            'amount' => $amountCents,
            'reason' => $reason,
        ];

        return new Refund(
            id: 're_test_'.Str::random(16),
            amountCents: $amountCents ?? 0,
            status: 'succeeded',
        );
    }

    /**
     * @return array{payment_intent: string, amount: int|null, reason: string}|null
     */
    public function last(): ?array
    {
        return $this->issued === [] ? null : $this->issued[count($this->issued) - 1];
    }

    /**
     * @return list<array{payment_intent: string, amount: int|null, reason: string}>
     */
    public function all(): array
    {
        return $this->issued;
    }
}
