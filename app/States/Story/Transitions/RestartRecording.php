<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Models\Story;
use App\States\Story\Proposed;
use Spatie\ModelStates\Transition;

/**
 * ENREGISTRÉE, TRANSCRITE ou À RELIRE → PROPOSÉE. Elle recommence.
 *
 * Le seul retour en arrière complet de la machine, et il mérite ses raisons.
 *
 * **Pourquoi jusqu'à « proposée »** et non un second chemin vers
 * « enregistrée ». Du point de vue de la personne, recommencer veut dire que
 * la question est de nouveau posée et qu'elle n'y a pas encore répondu. Tout
 * ce qui suit — l'envoi, la transcription, le rendu, la relecture, la
 * décision de partage — reprend alors le chemin déjà éprouvé, au lieu d'un
 * embranchement parallèle que personne ne relirait. Il n'existe donc toujours
 * qu'une seule façon d'entrer dans « enregistrée ».
 *
 * **Ce que ce retour n'efface pas.** L'enregistrement précédent reste, avec
 * son audio ; `InitiateRecording` le fera passer `is_current` à faux quand le
 * suivant s'ouvrira, et ses transcriptions suivront le même sort. « L'audio
 * source est sacré » vaut aussi contre la personne elle-même : elle peut
 * remplacer ce que la famille entendra, pas défaire ce qu'elle a dit.
 *
 * **Ce que ce retour efface.** La décision de partage. Elle portait sur un
 * enregistrement qui n'est plus le courant, et la laisser en place ferait
 * partager sans qu'on redemande — exactement ce que le bloc 07 interdit.
 *
 * La garde vit dans `App\Actions\RestartRecording` : une histoire validée,
 * partagée ou au livre ne repasse pas par ici, et le refus doit se lire dans
 * la réponse HTTP plutôt que dans une exception d'état.
 */
final class RestartRecording extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        $this->story->state = new Proposed($this->story);
        $this->story->answer_type = null;
        $this->story->recorded_at = null;
        $this->story->share_decision = null;
        $this->story->share_decided_at = null;
        $this->story->save();

        return $this->story;
    }
}
