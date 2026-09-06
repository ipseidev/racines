<?php

declare(strict_types=1);

namespace App\Books;

use App\Audit\AuditLog;
use App\Models\AccessToken;
use App\Models\Story;
use Illuminate\Support\Facades\Log;

/**
 * Éteindre le QR imprimé d'une histoire.
 *
 * Le geste que le livre rend nécessaire : un narrateur peut changer d'avis
 * après l'impression, et le papier ne se rappelle pas. Ce qu'on peut faire,
 * c'est **cesser de servir la voix** — et le dire honnêtement plutôt que de
 * laisser croire qu'un retrait efface aussi les exemplaires.
 *
 * Un seul jeton par histoire (`IssueQrToken` le dérive), donc **un seul geste
 * éteint tous les exemplaires déjà imprimés**. C'est toute la raison pour
 * laquelle le code est dérivé et non tiré au sort : sinon il faudrait
 * retrouver et révoquer chaque jeton jamais imprimé.
 *
 * La réémission redonne le **même** code : le livre reste utilisable. Un
 * nouveau code condamnerait les exemplaires déjà chez la famille.
 */
final readonly class RevokeQrToken
{
    public function handle(Story $story): void
    {
        $tokens = AccessToken::query()
            ->where('subject_type', $story->getMorphClass())
            ->where('subject_id', (string) $story->getKey())
            ->where('type', 'qr')
            ->whereNull('revoked_at')
            ->get();

        foreach ($tokens as $token) {
            $token->forceFill(['revoked_at' => now()])->save();
        }

        if ($tokens->isEmpty()) {
            return;
        }

        AuditLog::record('revoked QrToken', $story, [
            'tokens' => $tokens->count(),
        ], $story->project);

        Log::info('book.qr_revoked', [
            'story_id' => $story->getKey(),
            'tokens' => $tokens->count(),
        ]);
    }
}
