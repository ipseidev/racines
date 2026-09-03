<?php

declare(strict_types=1);

use App\Enums\ConsentKind;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;

/**
 * Deux consentements de l'acheteur : démarrage immédiat, et nouvelles par
 * courriel.
 *
 * Séparés l'un de l'autre, et séparés de l'acceptation des CGV. Un démarrage
 * immédiat fait perdre une partie du droit de rétractation ; une case qui
 * mêlerait les deux ne vaudrait pas consentement. Et la case marketing est
 * décochée par défaut — elle n'est jamais requise pour payer (critère de
 * sortie du bloc 10).
 */
return new class extends Migration
{
    public function up(): void
    {
        EnumCheck::drop('consents', 'kind');
        EnumCheck::add('consents', 'kind', EnumCheck::of(ConsentKind::class));
    }

    public function down(): void
    {
        EnumCheck::drop('consents', 'kind');
        EnumCheck::add('consents', 'kind', array_values(array_filter(
            EnumCheck::of(ConsentKind::class),
            fn (string $kind): bool => ! in_array($kind, ['early_service_start', 'marketing_email'], true),
        )));
    }
};
