<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\Domain\DiscountCodeUnavailable;
use App\Models\CheckoutDraft;
use App\Models\Lead;

/**
 * Pose un code de réduction sur un brouillon d'achat (T-141).
 *
 * Le pourcentage est **copié** dans le brouillon au moment où le code est
 * posé, comme le prix d'une commande : c'est ce que l'acheteur a vu, et c'est
 * ce qu'il paiera. Le code n'est marqué utilisé qu'à l'encaissement
 * (`FulfillOrder`) : poser un code n'est pas acheter.
 */
final readonly class ApplyDiscountCode
{
    public const DRAFT_CODE = 'discount_code';

    public const DRAFT_PERCENT = 'discount_percent';

    public function handle(CheckoutDraft $draft, string $code): Lead
    {
        $lead = self::find($code);

        if (! $lead instanceof Lead) {
            throw DiscountCodeUnavailable::unknown();
        }

        match ($lead->codeStatus()) {
            'used' => throw DiscountCodeUnavailable::used(),
            'expired' => throw DiscountCodeUnavailable::expired(),
            default => null,
        };

        $draft->merge([
            self::DRAFT_CODE => $lead->discount_code,
            self::DRAFT_PERCENT => $lead->discount_percent,
        ]);

        return $lead;
    }

    public function remove(CheckoutDraft $draft): void
    {
        $payload = $draft->payload;
        unset($payload[self::DRAFT_CODE], $payload[self::DRAFT_PERCENT]);

        $draft->payload = $payload;
        $draft->save();
    }

    public static function find(string $code): ?Lead
    {
        $normalized = Lead::normalizeCode($code);

        if ($normalized === '') {
            return null;
        }

        return Lead::query()->where('discount_code', $normalized)->first();
    }

    /**
     * Le code posé sur ce brouillon, s'il est encore utilisable.
     *
     * Revérifié à chaque lecture : entre le récapitulatif et le paiement, le
     * même code a pu servir depuis un autre appareil.
     */
    public static function usableOn(CheckoutDraft $draft): ?Lead
    {
        $code = $draft->value(self::DRAFT_CODE);

        if (! is_string($code) || $code === '') {
            return null;
        }

        $lead = self::find($code);

        return $lead instanceof Lead && $lead->codeUsable() ? $lead : null;
    }
}
