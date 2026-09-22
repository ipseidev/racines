<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Qui peut déposer et retirer une photo.
 *
 * Une seule porte, comme `VisibleStoriesForFamilyMember` au bloc 08 : une
 * seconde règle écrite plus tard oublierait un cas, et le cas oublié serait
 * celui du proche qui retire la photo de quelqu'un d'autre.
 *
 * Les quatre règles, et ce qu'elles protègent :
 *
 *  - **Le narrateur peut tout retirer**, y compris ce qu'un proche a déposé
 *    sur son histoire. C'est son récit ; sa souveraineté ne s'arrête pas au
 *    texte.
 *  - **L'Initiateur·rice dépose et retire.** Elle organise le projet, et
 *    c'est souvent elle qui a les photos de famille numérisées.
 *  - **Un proche ne dépose plus sur une histoire** (dossier v3.1). Il joint
 *    ses photos à **la question qu'il pose**, où elles appellent le récit, au
 *    lieu de les coller sur un récit déjà clos où elles n'étaient qu'une
 *    décoration. Il retire toujours **ses** photos, y compris celles déposées
 *    du temps où il le pouvait : autoriser le retrait des photos d'autrui
 *    ferait du cercle d'écoute un lieu de conflit.
 *  - **Personne ne dépose sur une histoire qui n'est pas la sienne.** La
 *    vérification du projet précède tout le reste.
 */
final class PhotoAccess
{
    public static function canAttach(Story $story, Model $actor): bool
    {
        return match (true) {
            $actor instanceof Narrator => self::ownsStory($story, $actor),
            $actor instanceof User => self::isInitiator($story, $actor),
            /*
             * Jamais un proche (dossier v3.1).
             *
             * Le droit qu'il reçoit est celui de **poser une question**, avec
             * ses photos jointes à elle — `can_ask`, et le chemin est celui de
             * `Family\QuestionController`. Illustrer une histoire déjà close
             * reste à l'Initiateur·rice, qui prépare le livre et a souvent
             * seule les photos de famille numérisées.
             */
            $actor instanceof FamilyMember => false,
            default => false,
        };
    }

    public static function canRemove(Story $story, Media $photo, Model $actor): bool
    {
        return match (true) {
            // Le narrateur retire tout : c'est son récit.
            $actor instanceof Narrator => self::ownsStory($story, $actor),
            $actor instanceof User => self::isInitiator($story, $actor),
            // Un proche ne retire que ce qu'il a déposé.
            $actor instanceof FamilyMember => self::deposited($photo, $actor),
            default => false,
        };
    }

    /**
     * Modifier la légende : le déposant, le narrateur, l'Initiateur·rice.
     *
     * Plus large que le retrait, et volontairement : corriger l'orthographe
     * d'un nom de village sur la photo d'un cousin est un service, pas une
     * intrusion.
     */
    public static function canEditCaption(Story $story, Media $photo, Model $actor): bool
    {
        if ($actor instanceof FamilyMember) {
            // Sa propre photo, et rien d'autre : il ne dépose plus sur les
            // histoires, mais celles qu'il a jointes à ses questions restent
            // les siennes.
            return self::deposited($photo, $actor);
        }

        return self::canRemove($story, $photo, $actor);
    }

    private static function deposited(Media $photo, FamilyMember $member): bool
    {
        return $member->removed_at === null
            && $photo->getCustomProperty('depositor_type') === $member->getMorphClass()
            && $photo->getCustomProperty('depositor_id') === $member->id;
    }

    private static function ownsStory(Story $story, Narrator $narrator): bool
    {
        return $story->narrator_id === $narrator->id
            || $story->project_id === $narrator->project_id;
    }

    private static function isInitiator(Story $story, User $user): bool
    {
        return $story->project->owner_user_id === $user->id;
    }
}
