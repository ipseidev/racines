<?php

declare(strict_types=1);

namespace App\States\Story;

use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\Transitions\ArchiveStory;
use App\States\Story\Transitions\DeleteStory;
use App\States\Story\Transitions\HideStory;
use App\States\Story\Transitions\IncludeInBook;
use App\States\Story\Transitions\KeepStoryPrivate;
use App\States\Story\Transitions\MarkTranscribed;
use App\States\Story\Transitions\RecordStory;
use App\States\Story\Transitions\RequestReview;
use App\States\Story\Transitions\RestoreStory;
use App\States\Story\Transitions\ShareStory;
use App\States\Story\Transitions\TrashStory;
use App\States\Story\Transitions\UnhideStory;
use App\States\Story\Transitions\ValidateStory;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Machine d'états d'une histoire (R-4).
 *
 * Toutes les transitions autorisées sont déclarées ici et nulle part ailleurs.
 * Il n'existe aucun chemin qui rende une histoire visible des proches sans un
 * acte explicite du narrateur, et aucune transition ne part de `Deleted`.
 *
 * @extends State<Story>
 */
abstract class StoryState extends State
{
    /**
     * Les états « au moins enregistrée », seuls candidats aux retraits.
     * Une histoire simplement proposée n'a rien à masquer.
     *
     * @var list<class-string<StoryState>>
     */
    public const RECORDED_OR_LATER = [
        Recorded::class,
        Transcribed::class,
        ToReview::class,
        Validated::class,
        Shared::class,
        InBook::class,
    ];

    public static function config(): StateConfig
    {
        $config = parent::config()
            ->default(Proposed::class)
            ->allowTransition(Proposed::class, Recorded::class, RecordStory::class)
            ->allowTransition(Recorded::class, Transcribed::class, MarkTranscribed::class)
            ->allowTransition(Transcribed::class, ToReview::class, RequestReview::class)
            // Retour en arrière assumé : « garder pour moi » est une réponse,
            // pas une validation, et l'histoire doit quitter la file des
            // relances sans que rien soit gravé (bloc 07 §9).
            ->allowTransition(ToReview::class, Transcribed::class, KeepStoryPrivate::class)
            // Une seule classe de validation : elle inspecte le contexte et
            // refuse ce que la garde de l'état source interdit (bloc 02 §9).
            ->allowTransition(
                [Recorded::class, Transcribed::class, ToReview::class],
                Validated::class,
                ValidateStory::class,
            )
            ->allowTransition(Validated::class, Shared::class, ShareStory::class)
            ->allowTransition([Validated::class, Shared::class], InBook::class, IncludeInBook::class)
            ->allowTransition(self::RECORDED_OR_LATER, Hidden::class, HideStory::class)
            ->allowTransition(self::RECORDED_OR_LATER, Archived::class, ArchiveStory::class)
            ->allowTransition(self::RECORDED_OR_LATER, Trashed::class, TrashStory::class)
            ->allowTransition(Trashed::class, Deleted::class, DeleteStory::class);

        // Retours : on ne revient que là d'où l'on vient, et la garde de la
        // transition vérifie que la cible est bien `previous_state`.
        foreach (self::RECORDED_OR_LATER as $state) {
            $config->allowTransition(Hidden::class, $state, UnhideStory::class);
            $config->allowTransition(Trashed::class, $state, RestoreStory::class);
        }

        return $config;
    }

    /**
     * Traduit le refus du paquet en refus métier, pour que l'appelant n'ait
     * qu'une seule exception à connaître.
     *
     * @param  string|StoryState  $newState
     * @param  mixed  ...$transitionArgs
     * @return Story
     */
    public function transitionTo($newState, ...$transitionArgs)
    {
        try {
            return parent::transitionTo($newState, ...$transitionArgs);
        } catch (CouldNotPerformTransition $exception) {
            throw ForbiddenTransition::notAllowed(
                $this->getValue(),
                self::valueOf($newState),
                $exception,
            );
        }
    }

    /**
     * @param  string|StoryState  $state
     */
    public static function valueOf($state): string
    {
        if ($state instanceof self) {
            return $state->getValue();
        }

        $resolved = self::resolveStateClass($state);

        return $resolved !== null && is_subclass_of($resolved, self::class)
            ? $resolved::getMorphClass()
            : (string) $state;
    }
}
