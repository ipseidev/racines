<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RecordShareDecision;
use App\Enums\AnswerType;
use App\Enums\QuestionTheme;
use App\Enums\ShareDecision;
use App\Enums\TranscriptKind;
use App\Enums\ValidatedVia;
use App\Models\Project;
use App\Models\Question;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\States\Story\Recorded;
use App\States\Story\Shared;
use App\States\Story\Transcribed;
use App\States\Story\Validated;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * De quoi jouer le checkpoint du bloc 13 : un projet qui a de la matière.
 *
 * Le point 1 demande dix histoires validées, puis de forcer douze mille mots
 * pour voir le format basculer du livret au livre. Fabriquer cela à la main
 * prend une heure et se refait mal : dix histoires, dix transcriptions, cinq
 * thèmes distincts, des durées d'enregistrement crédibles, et des noms
 * propres pour que le contrôle du lexique ait quelque chose à montrer.
 *
 * `--riche` fait franchir tous les seuils R-6 d'un coup ; sans lui, la
 * matière reste intermédiaire — c'est ce qui permet de voir la jauge dire
 * « il manque encore » avant de la voir dire « il y a de quoi ».
 */
final class DemoBook extends Command
{
    protected $signature = 'demo:livre {--riche : assez de matière pour un livre complet}';

    protected $description = 'Enrichit le projet de démonstration pour le checkpoint du livre';

    /** Cinq thèmes suffisent au seuil R-6 ; six laissent de la marge. */
    private const THEMES = [
        QuestionTheme::Childhood,
        QuestionTheme::FamilyOrigins,
        QuestionTheme::Youth,
        QuestionTheme::Work,
        QuestionTheme::Love,
        QuestionTheme::Places,
    ];

    /**
     * Des noms propres, pour que le contrôle du lexique ait de la matière.
     *
     * Ce sont eux que la transcription écorche en vrai, et le point 2 du
     * checkpoint demande d'en ajouter un au lexique.
     */
    private const NOMS = ['Kerhostin', 'Ambroise', 'Quiberon', 'Marcelle', 'Saint-Pierre'];

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne touche qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        $project = Project::query()
            ->whereHas('owner', fn ($query) => $query->where('email', 'demo@example.test'))
            ->latest()
            ->first();

        if (! $project instanceof Project) {
            $this->components->error('Projet de démonstration introuvable. Passez `sail artisan migrate:fresh --seed`.');

            return self::FAILURE;
        }

        $narrator = $project->primaryNarrator;

        if ($narrator === null) {
            $this->components->error('Le projet de démonstration n’a pas de narrateur principal.');

            return self::FAILURE;
        }

        $riche = (bool) $this->option('riche');
        // 1 600 mots par histoire pour dépasser 60 pages à 280 mots la page ;
        // 500 sinon, ce qui donne un livret et pas un livre.
        $mots = $riche ? 1_600 : 500;
        $minutes = $riche ? 11.0 : 4.0;

        $faits = 0;

        for ($i = 0; $i < 10; $i++) {
            $theme = self::THEMES[$i % count(self::THEMES)];
            $titre = 'Souvenir '.($i + 1).' — '.$theme->value;

            $existante = $project->stories()->where('title', $titre)->first();

            if ($existante instanceof Story) {
                // Réparer plutôt que sauter : un premier passage interrompu
                // laisse des histoires à mi-chemin, et une commande de décor
                // qui les ignore oblige à repartir d'un `migrate:fresh` qui
                // coûte tout le reste (même leçon qu'en T-155).
                $faits += $this->finish($existante) ? 1 : 0;

                continue;
            }

            $question = Question::factory()->create([
                'theme' => $theme,
                'text' => 'Racontez-moi un souvenir lié à '.$theme->value.'.',
            ]);

            $story = new Story([
                'title' => $titre,
                'sequence' => 900 + $i,
                'recorded_at' => now()->subDays(60 - $i * 5),
            ]);
            $story->project()->associate($project);
            $story->narrator()->associate($narrator);
            $story->question()->associate($question);
            $story->save();

            $recording = Recording::factory()->confirmed()->create([
                'story_id' => $story->id,
                'duration_seconds' => $minutes * 60,
            ]);

            $texte = $this->texte($mots, $i);

            Transcript::factory()->create([
                'story_id' => $story->id,
                'recording_id' => $recording->id,
                'kind' => TranscriptKind::Verbatim,
                'is_current' => true,
                'text' => 'Alors euh, '.mb_strtolower(mb_substr($texte, 0, 200)),
            ]);

            Transcript::factory()->create([
                'story_id' => $story->id,
                'recording_id' => $recording->id,
                'kind' => TranscriptKind::Fluide,
                'is_current' => true,
                'text' => $texte,
                'metadata' => [
                    'themes' => [$theme->value],
                    // Le contrôle du lexique lit ces suggestions ; le point 2
                    // du checkpoint en ajoute une au lexique du projet.
                    'proper_nouns' => array_slice(self::NOMS, 0, 2 + ($i % 3)),
                    'sensitive_flags' => [],
                ],
            ]);

            /*
             * Par les transitions, jamais par une écriture directe : `state`
             * ne s'écrit pas à la main dans ce dépôt, et un test le vérifie.
             *
             * La décision de partage précède la validation, et ce n'est pas
             * une formalité de décor : la garde R-4 refuse qu'une histoire
             * devienne validée sans qu'un narrateur l'ait décidé. Un décor qui
             * la contournerait ne ressemblerait plus au produit.
             */
            $story->state->transitionTo(Recorded::class, AnswerType::Audio);
            $this->finish($story->refresh());

            $faits++;
        }

        $this->components->info(sprintf(
            '%d histoire(s) ajoutée(s) au projet de démonstration. Matière : %s.',
            $faits,
            $riche ? 'assez pour un livre' : 'intermédiaire (livret)',
        ));

        $this->components->info('Passez ensuite `sail artisan books:evaluate`, puis ouvrez /espace/livre.');

        return self::SUCCESS;
    }

    /**
     * Mener une histoire jusqu'à `PARTAGÉE`, d'où qu'elle parte.
     *
     * Rend `true` si quelque chose a bougé.
     */
    private function finish(Story $story): bool
    {
        if ($story->state instanceof Shared) {
            return false;
        }

        if ($story->state instanceof Recorded) {
            $story->state->transitionTo(Transcribed::class);
            $story->refresh();
        }

        if ($story->state instanceof Transcribed) {
            app(RecordShareDecision::class)->handle($story, ShareDecision::Share);
            $story->refresh()->state->transitionTo(Validated::class, ValidatedVia::RecordingEnd);
            $story->refresh();
        }

        if ($story->state instanceof Validated) {
            $story->state->transitionTo(Shared::class);
        }

        return true;
    }

    /**
     * Un texte de la bonne longueur, lisible, avec des noms propres dedans.
     *
     * Du faux texte reconnaissable plutôt que du latin : le bon à tirer doit
     * pouvoir se relire pour juger la mise en page, et un pavé de « lorem
     * ipsum » ne dit rien des césures ni des veuves.
     */
    private function texte(int $mots, int $index): string
    {
        $phrases = [
            'Nous partions le matin par la route de %s, mon père devant et moi derrière.',
            'Ma grand-mère faisait le pain le mardi, et l’odeur montait jusqu’à la chambre.',
            'On disait que %s connaissait tout le monde, et c’était à peu près vrai.',
            'L’hiver, la mer prenait une couleur que je n’ai revue nulle part ailleurs.',
            'Il y avait dans la cuisine une horloge qui avançait de trois minutes.',
            'Le dimanche, on marchait jusqu’à la chapelle de %s, quel que soit le temps.',
        ];

        $texte = '';
        $n = 0;

        while ($n < $mots) {
            $phrase = sprintf(
                $phrases[($n + $index) % count($phrases)],
                self::NOMS[($n + $index) % count(self::NOMS)],
            );

            $texte .= $phrase.' ';
            $n += count(preg_split('/\s+/u', trim($phrase)) ?: []);

            // Un paragraphe toutes les six phrases : le gabarit en a besoin
            // pour montrer ce qu'il fait des coupures de page.
            if ($n % 60 < 12) {
                $texte .= "\n\n";
            }
        }

        return Str::of($texte)->trim()->toString();
    }
}
