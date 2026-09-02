<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ConsentKind;
use App\Enums\QuestionTheme;
use App\Enums\TranscriptKind;
use App\Events\TranscriptionReady;
use App\Models\Transcript;
use App\Services\Llm\RenderingContext;
use App\Services\Llm\StoryRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Met le récit au propre, si et seulement si le narrateur y a consenti.
 *
 * Le consentement `ai_rendering` est distinct et révocable (doc 04 §2). Sans
 * lui, ce job ne fait **rien** de plus que laisser passer l'histoire : le
 * verbatim suffit, et `TranscriptionReady` est émis quand même — le narrateur
 * a droit à sa relecture, c'est son récit.
 *
 * Un refus du modèle n'est pas rattrapé par un autre modèle : le verbatim
 * reste, le refus est consigné, et le support regarde (règle §9 du bloc 06).
 */
final class RenderFluide implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public readonly string $verbatimId)
    {
        $this->onQueue('llm');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(StoryRenderer $renderer): void
    {
        $verbatim = Transcript::query()->find($this->verbatimId);

        if ($verbatim === null || $verbatim->kind !== TranscriptKind::Verbatim) {
            return;
        }

        $story = $verbatim->story;

        if (! $story->narrator->hasConsent(ConsentKind::AiRendering)) {
            Log::info('fluide.skipped_no_consent', ['story_id' => $story->id]);

            TranscriptionReady::dispatch($story, false);

            return;
        }

        if ($story->transcripts()->ofKind(TranscriptKind::Fluide)->current()->exists()) {
            return;
        }

        $result = $renderer->render($verbatim, new RenderingContext(
            question: $story->questionText(),
            firstName: $story->narrator->first_name,
            addressForm: $story->project->address_form,
            lexicon: $story->project->lexiconEntries
                ->mapWithKeys(fn ($entry): array => [$entry->term => $entry->spelling()])
                ->all(),
            themes: array_column(QuestionTheme::cases(), 'value'),
        ));

        if ($result->refused) {
            // Rien n'est écrit : le verbatim reste seul, et l'histoire suit
            // son cours. Le refus vit dans les métadonnées du travail.
            Log::warning('fluide.refused', [
                'story_id' => $story->id,
                'category' => $result->metadata['refusal_category'] ?? null,
            ]);

            TranscriptionReady::dispatch($story, false);

            return;
        }

        DB::transaction(function () use ($story, $verbatim, $result): void {
            $fluide = new Transcript([
                'kind' => TranscriptKind::Fluide,
                'version' => 1,
                'provider' => (string) ($result->metadata['provider'] ?? 'claude'),
                'language' => $verbatim->language,
                'text' => $result->text,
                'metadata' => [
                    ...$result->metadata,
                    'themes' => $result->themes,
                    // Suggestions, **pas** des entrées de lexique : c'est la
                    // famille qui décide de la graphie de ses noms.
                    'proper_nouns' => $result->properNouns,
                    'sensitive_flags' => $result->sensitiveFlags,
                ],
            ]);

            $fluide->story()->associate($story);
            $fluide->recording()->associate($verbatim->recording);
            $fluide->source()->associate($verbatim);
            $fluide->save();

            if (($story->title ?? '') === '' && $result->title !== null) {
                $story->title = $result->title;
                $story->save();
            }
        });

        Log::info('fluide.rendered', [
            'story_id' => $story->id,
            'characters' => mb_strlen($result->text),
            'sensitive_flags' => $result->sensitiveFlags,
        ]);

        TranscriptionReady::dispatch($story, true);
    }
}
