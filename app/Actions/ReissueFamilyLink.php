<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Services\Tokens\IssuedToken;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Renouvelle le lien d'écoute d'un proche.
 *
 * Les anciens sont **révoqués**, pas seulement remplacés : un lien transmis
 * par erreur resterait ouvert après qu'on a cru le changer, et c'est
 * précisément la raison pour laquelle on renouvelle. Un seul lien vivant par
 * proche, toujours.
 */
final readonly class ReissueFamilyLink
{
    public function __construct(private TokenService $tokens) {}

    public function handle(
        FamilyMember $member,
        TokenIssuedReason $reason = TokenIssuedReason::Rotation,
    ): IssuedToken {
        return DB::transaction(function () use ($member, $reason): IssuedToken {
            $this->tokens->revokeAllFor($member, TokenType::ListenProject, $reason->value);

            $issued = $this->tokens->issue(
                TokenType::ListenProject,
                $member,
                ['listen', 'react'],
                now()->addMonths(12),
                reason: $reason,
            );

            Log::info('family.link_reissued', [
                'family_member_id' => $member->id,
                'reason' => $reason->value,
            ]);

            return $issued;
        });
    }
}
