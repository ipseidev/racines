<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\StoryVisibility;
use App\Models\FamilyMember;
use App\Models\Story;
use App\States\Story\InBook;
use App\States\Story\Shared;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * La **seule** porte par laquelle une histoire atteint un proche.
 *
 * Le critère de sortie du bloc l'exige : aucune autre requête sur `stories`
 * dans l'espace famille. Ce n'est pas une préférence de style. Une seconde
 * requête, écrite un jour de fatigue et oubliant la liste `restricted`,
 * exposerait le souvenir de quelqu'un — et le dossier appelle ça un bug
 * bloquant, pas une régression d'affichage.
 *
 * Trois conditions, dans cet ordre :
 *  1. l'histoire appartient au projet du proche ;
 *  2. son état est `shared` ou `in_book` — rien avant, jamais ;
 *  3. sa visibilité l'admet : « tous mes proches », ou une liste où il figure.
 *
 * `book_only` est exclu ici plutôt que filtré après : le narrateur a choisi
 * le papier, pas la diffusion.
 */
final readonly class VisibleStoriesForFamilyMember
{
    public function __construct(private FamilyMember $member) {}

    /**
     * @return Builder<Story>
     */
    public function query(): Builder
    {
        return Story::query()
            ->where('project_id', $this->member->project_id)
            ->whereIn('state', [
                Shared::$name,
                InBook::$name,
            ])
            ->where(function (Builder $query): void {
                $query
                    ->where('visibility', StoryVisibility::AllFamily->value)
                    ->orWhere(function (Builder $restricted): void {
                        $restricted
                            ->where('visibility', StoryVisibility::Restricted->value)
                            ->whereHas(
                                'allowedFamilyMembers',
                                fn (Builder $members) => $members->whereKey($this->member->id),
                            );
                    });
            });
    }

    /**
     * Les histoires écoutables, les plus récemment partagées d'abord.
     *
     * @return Collection<int, Story>
     */
    public function list(): Collection
    {
        return $this->query()
            ->with('question')
            ->orderByDesc('shared_at')
            ->orderByDesc('sequence')
            ->get();
    }

    /**
     * Une histoire précise, ou rien.
     *
     * Rien, et non « une histoire dont on cache les champs » : la différence
     * compte, parce qu'un objet chargé finit par fuir dans des props.
     */
    public function find(string $storyId): ?Story
    {
        return $this->query()->with('question')->whereKey($storyId)->first();
    }
}
