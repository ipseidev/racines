<?php

declare(strict_types=1);

namespace App\Actions;

use App\Audit\AuditLog;
use App\Models\Story;
use App\States\Story\Proposed;
use App\States\Story\Recorded;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use Illuminate\Support\Facades\Log;

/**
 * Elle recommence : la question est de nouveau posée.
 *
 * Trois états seulement y donnent droit — enregistrée, transcrite, à relire.
 * Une histoire validée, partagée ou partie au livre en est exclue, et pas par
 * prudence de principe : à partir de la validation, des proches ont pu
 * l'entendre. Remplacer alors l'audio derrière un lien qu'ils gardent leur
 * ferait écouter autre chose que ce qu'on leur avait annoncé. Le geste existe
 * pour cela, mais il s'appelle retrait, et il passe par l'espace narrateur.
 *
 * L'enregistrement précédent n'est pas touché ici : c'est `InitiateRecording`
 * qui le fera passer `is_current` à faux quand le suivant s'ouvrira. Tant
 * qu'elle n'a pas reparlé, rien n'est perdu — y compris si elle ferme la page
 * juste après avoir cliqué.
 */
final readonly class RestartRecording
{
    /**
     * Les états depuis lesquels le geste est **permis**.
     *
     * `Proposed` y figure alors qu'il n'y a rien à y faire, et c'est le point
     * du correctif : « il n'y a rien à faire » et « c'est interdit » sont deux
     * réponses différentes. Un second clic — bouton retour, double tap, réseau
     * lent — ne doit pas produire un refus sec au visage de quelqu'un qui
     * voulait simplement recommencer. La page qui suit est de toute façon
     * celle qu'il attendait.
     *
     * @var list<class-string>
     */
    private const ALLOWED = [
        Proposed::class,
        Recorded::class,
        Transcribed::class,
        ToReview::class,
    ];

    public function mayRestart(Story $story): bool
    {
        foreach (self::ALLOWED as $state) {
            if ($story->state instanceof $state) {
                return true;
            }
        }

        return false;
    }

    public function handle(Story $story): Story
    {
        if ($story->state instanceof Proposed) {
            return $story;
        }

        $from = $story->state->getValue();

        $story->state->transitionTo(Proposed::class);

        // Journalisé, et non simplement consigné dans les journaux
        // applicatifs : « pourquoi la famille entend-elle un autre
        // enregistrement qu'à l'origine ? » est une question à laquelle il
        // faut pouvoir répondre des mois plus tard.
        AuditLog::record('restarted Story', $story, [
            'from' => $from,
            'superseded_recording_id' => $story->currentRecording()->first()?->id,
        ], $story->project);

        Log::info('story.restarted', ['story_id' => $story->id, 'from' => $from]);

        return $story;
    }
}
