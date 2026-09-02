<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\ApplyShareDecision;
use App\Events\TranscriptionReady;

/**
 * Le texte est prêt : la décision du narrateur prend effet.
 *
 * L'écouteur est délibérément vide de logique. Il n'a qu'un rôle : brancher
 * la fin de la chaîne de transcription (bloc 06) sur la décision de partage
 * (bloc 07), sans que l'une connaisse l'autre.
 *
 * Il s'exécute même quand la mise au propre a été refusée : le verbatim
 * suffit, et un refus du modèle ne prive pas le narrateur de sa décision.
 */
final readonly class ApplyShareDecisionOnTranscriptionReady
{
    public function __construct(private ApplyShareDecision $decisions) {}

    public function handle(TranscriptionReady $event): void
    {
        $this->decisions->handle($event->story);
    }
}
