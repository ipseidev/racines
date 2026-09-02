<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\Project;
use App\Models\User;
use App\Notifications\FamilyInvitationNotification;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Invite un proche à écouter, avec **son** lien.
 *
 * Un lien par personne, jamais un lien « famille » commun : un lien partagé
 * ne se révoque pas sans punir tout le monde, et on ne saurait plus qui a
 * écouté quoi — donc plus rien du maillon H2 que le bloc doit mesurer.
 *
 * Douze mois : un lien d'écoute vit le temps du projet, pas le temps d'une
 * session. Le renouvellement est une action explicite (`ReissueFamilyLink`).
 */
final readonly class InviteFamilyMember
{
    /** @var list<string> */
    private const SCOPE = ['listen', 'react'];

    private const MONTHS = 12;

    public function __construct(
        private AddFamilyMember $members,
        private TokenService $tokens,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Project $project, User $invitedBy, array $attributes): FamilyMember
    {
        $email = $attributes['email'] ?? null;
        $phone = $attributes['phone_e164'] ?? null;

        if (! is_string($email) && ! is_string($phone)) {
            // Sans coordonnée, l'Initiateur·rice copie le lien et le
            // transmet lui-même (règle §9) — mais on ne crée pas en silence
            // un proche que personne ne pourra joindre.
            throw new InvalidArgumentException(
                'Un proche invité doit avoir un courriel ou un téléphone.',
            );
        }

        return DB::transaction(function () use ($project, $invitedBy, $attributes): FamilyMember {
            $member = $this->members->handle($project, $invitedBy, $attributes);

            $issued = $this->tokens->issue(
                TokenType::ListenProject,
                $member,
                self::SCOPE,
                now()->addMonths(self::MONTHS),
                $invitedBy,
            );

            $member->notify(new FamilyInvitationNotification($project, $issued->plain, $invitedBy));

            Log::info('family.invited', [
                'project_id' => $project->id,
                'family_member_id' => $member->id,
                'invited_by' => (string) $invitedBy->getKey(),
            ]);

            return $member;
        });
    }
}
