<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Models\Story;
use App\Services\Tokens\IssuedToken;
use App\Services\Tokens\TokenService;

/**
 * Émet le lien d'enregistrement d'une histoire.
 *
 * Le périmètre est explicite et minimal : enregistrer, et décider du partage.
 * Rien d'autre. Un lien qui traîne dans une boîte SMS ne doit pas permettre de
 * lire les autres histoires ni de régler le projet.
 */
final readonly class IssueRecordToken
{
    /** @var list<string> */
    private const SCOPE = ['record', 'decide_share'];

    public function __construct(private TokenService $tokens) {}

    public function handle(Story $story, TokenIssuedReason $reason = TokenIssuedReason::Initial): IssuedToken
    {
        return $this->tokens->issue(TokenType::Record, $story, self::SCOPE, reason: $reason);
    }
}
