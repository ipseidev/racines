<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\Transcribed;
use Spatie\ModelStates\Transition;

/**
 * À RELIRE → TRANSCRITE. Le narrateur a relu et préfère garder pour lui.
 *
 * Cette transition existe pour une raison de fond : « garder pour moi » est
 * une réponse, mais ce n'est pas une validation. Laisser l'histoire dans
 * `to_review` la maintiendrait dans la file des relances (règle
 * `recorded_not_validated`, bloc 09) alors que la personne a répondu ; la
 * valider graverait un choix qui doit rester réversible. Elle redescend donc
 * à « transcrite » : privée, non validée, modifiable depuis l'espace
 * narrateur.
 *
 * La décision est écrite en même temps que l'état, sans quoi la chaîne de
 * transcription, rejouée, redemanderait une relecture déjà faite.
 */
final class KeepStoryPrivate extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        if ($this->story->visibility === StoryVisibility::BookOnly) {
            // Réservée au livre, elle a été validée : la garder pour soi
            // maintenant demande de repasser par le retrait, pas par ici.
            throw ForbiddenTransition::guardFailed(
                $this->story->state->getValue(),
                'transcribed',
                'a story reserved for the printed book leaves review by withdrawal',
            );
        }

        $this->story->state = new Transcribed($this->story);
        $this->story->share_decision = ShareDecision::KeepPrivate;
        $this->story->share_decided_at = now();
        $this->story->save();

        return $this->story;
    }
}
