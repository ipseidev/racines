<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncQuestionCorpus;
use App\Models\Question;
use App\Models\Story;
use App\States\Story\Proposed;
use App\Support\QuestionCorpus;
use Illuminate\Console\Command;

/**
 * Applique `database/corpus/questions.php` à la base.
 *
 * Se lance à la main, en SSH sur la production, après le déploiement : d'abord
 * à blanc, pour lire le rapport, puis avec `--apply`. Rejouable : la seconde
 * fois, elle dit qu'il n'y a rien à changer.
 */
final class SyncQuestionCorpusCommand extends Command
{
    protected $signature = 'corpus:sync
        {--apply : Écrire en base (sans cette option, rien n’est écrit)}
        {--file= : Un autre fichier de corpus que celui du dépôt}';

    protected $description = 'Met les questions en base d’accord avec le corpus du dépôt';

    public function handle(SyncQuestionCorpus $sync): int
    {
        $file = $this->option('file');
        $corpus = is_string($file) && $file !== ''
            ? QuestionCorpus::fromFile($file)
            : QuestionCorpus::default();

        $problems = $corpus->problems();

        if ($problems !== []) {
            $this->components->error('Le corpus est invalide, rien n’est écrit :');

            foreach ($problems as $problem) {
                $this->line("  {$problem}");
            }

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $plan = $apply ? $sync->apply($corpus) : $sync->plan($corpus);

        $this->components->info($apply
            ? 'Corpus appliqué.'
            : 'À blanc : rien n’est écrit. Relancer avec --apply pour appliquer.');

        $this->line(sprintf(
            '%d ajoutée(s), %d modifiée(s), %d inchangée(s), %d hors corpus (laissées telles quelles).',
            count($plan['added']),
            count($plan['changed']),
            $plan['unchanged'],
            count($plan['outside']),
        ));

        if ($plan['added'] === [] && $plan['changed'] === []) {
            $this->line('Rien à changer.');

            return self::SUCCESS;
        }

        foreach ($plan['added'] as $slug) {
            $this->line("  + {$slug}");
        }

        foreach ($plan['changed'] as $slug => $fields) {
            $this->line("  ~ {$slug} : ".implode(', ', $fields));
        }

        $this->reportPendingStories(array_keys($plan['changed']));

        return self::SUCCESS;
    }

    /**
     * Combien d'histoires en attente liront le nouveau texte.
     *
     * Les histoires enregistrées ont photographié leur question et ne bougent
     * pas ; celles qui attendent leur réponse, si — et c'est voulu, mais
     * mieux vaut le savoir avant d'appuyer.
     *
     * @param  list<string>  $slugs
     */
    private function reportPendingStories(array $slugs): void
    {
        if ($slugs === []) {
            return;
        }

        $pending = Story::query()
            ->where('state', Proposed::$name)
            ->whereNull('question_text')
            ->whereIn('question_id', Question::query()->whereIn('slug', $slugs)->select('id'))
            ->count();

        $this->line(sprintf('%d histoire(s) en attente de réponse liront le texte modifié.', $pending));
    }
}
