<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\Log;

/**
 * Retirer l'accès d'un proche.
 *
 * **Retiré, pas supprimé** : la ligne reste, avec son `removed_at`. Savoir
 * qu'une personne a eu accès à un récit fait partie de ce qu'on doit pouvoir
 * répondre plus tard — à elle, à la famille, ou à une autorité.
 *
 * Le jeton est révoqué dans le même geste. Un `removed_at` sans révocation
 * serait un retrait de façade : le lien resterait utilisable, et la garde de
 * visibilité du bloc 08 s'appuie sur le jeton, pas sur la colonne.
 *
 * Une action et non deux appels recopiés : l'espace de l'Initiateur·rice et le
 * back-office retirent tous deux un accès, et la seconde copie aurait oublié
 * la révocation.
 */
final readonly class RemoveFamilyMember
{
    public function __construct(private TokenService $tokens) {}

    public function handle(FamilyMember $member, string $reason): FamilyMember
    {
        if ($member->removed_at !== null) {
            return $member;
        }

        $member->removed_at = now();
        $member->save();

        $this->tokens->revokeAllFor($member, TokenType::ListenProject, $reason);

        Log::info('family.access_removed', [
            'family_member_id' => $member->id,
            'project_id' => $member->project_id,
            'reason' => $reason,
        ]);

        return $member->refresh();
    }
}
