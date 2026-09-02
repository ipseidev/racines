<?php

declare(strict_types=1);

namespace App\Engine\Actions;

use App\Actions\AddFamilyMember;
use App\Actions\ReactToStory;
use App\Enums\ReactionType;
use App\Models\AccessToken;
use App\Models\FamilyMember;
use App\Models\Project;
use App\Models\Story;
use App\States\Story\Shared;

/**
 * Un cœur sur la dernière histoire partagée.
 *
 * Le geste le plus petit du produit, et celui que le dossier place au centre :
 * trois histoires sans une seule réaction, et le narrateur cesse de raconter.
 * Un tap suffit à rompre le silence.
 *
 * L'Initiateur·rice n'a pas toujours de fiche de proche — elle a acheté le
 * service, elle n'a pas été invitée. On la crée alors : réagir, c'est faire
 * partie du cercle d'écoute, et une réaction anonyme ne dirait rien au
 * narrateur.
 */
final readonly class ReactHeart implements OneTapAction
{
    public function __construct(
        private ReactToStory $reactions,
        private AddFamilyMember $members,
    ) {}

    public static function name(): string
    {
        return 'react_heart';
    }

    /** @return array<string, mixed> */
    public function preview(AccessToken $token): array
    {
        $story = self::lastSharedStory($token);

        return [
            'title' => __('initiator.one_tap.react_heart.title'),
            'body' => __('initiator.one_tap.react_heart.body', [
                'title' => $story === null ? '' : ($story->title ?? $story->questionText() ?? ''),
            ]),
            'button' => __('initiator.one_tap.react_heart.button'),
        ];
    }

    /** @return array<string, mixed> */
    public function execute(AccessToken $token): array
    {
        $project = $token->subject;
        $story = self::lastSharedStory($token);

        if (! $project instanceof Project || $story === null) {
            return ['done' => false, 'message' => __('initiator.one_tap.react_heart.no_story')];
        }

        $this->reactions->handle($story, $this->initiatorAsMember($project), ReactionType::Heart);

        return [
            'done' => true,
            'message' => __('initiator.one_tap.react_heart.done'),
        ];
    }

    private static function lastSharedStory(AccessToken $token): ?Story
    {
        $project = $token->subject;

        if (! $project instanceof Project) {
            return null;
        }

        return $project->stories()
            ->where('state', Shared::$name)
            ->orderByDesc('shared_at')
            ->first();
    }

    /**
     * La fiche de proche de l'Initiateur·rice, créée si besoin.
     */
    private function initiatorAsMember(Project $project): FamilyMember
    {
        $owner = $project->owner;

        $existing = $project->familyMembers()
            ->where('email', $owner->email)
            ->first();

        if ($existing instanceof FamilyMember) {
            return $existing;
        }

        return $this->members->handle($project, $owner, [
            'display_name' => $owner->name,
            'email' => $owner->email,
        ]);
    }
}
