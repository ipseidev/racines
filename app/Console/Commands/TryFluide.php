<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AddressForm;
use App\Enums\QuestionTheme;
use App\Enums\TranscriptKind;
use App\Models\Transcript;
use App\Services\Llm\RenderingContext;
use App\Services\Llm\StoryRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Soumet un mot à mot au rendu « Fluide » et montre les deux côte à côte.
 *
 * Le bloc 06 demande une **lecture humaine** du rendu sur cinq histoires
 * réelles, et cette lecture est le seul moyen de savoir si l'engagement tient :
 * « l'IA range, elle n'invente pas ». Un test automatique ne peut pas juger
 * qu'une phrase sonne encore comme la personne — il faut des yeux.
 *
 * La commande ne touche pas à la base : elle appelle le rendu directement,
 * sans créer d'histoire ni de transcription. Une lecture d'évaluation ne doit
 * pas laisser de traces dans les données d'une famille.
 *
 * Elle affiche aussi le compte de mots avant et après. Le prompt promet « au
 * plus 20 % plus court » : c'est vérifiable d'un coup d'œil, et un rendu qui
 * coupe 40 % du récit a résumé, ce qui est interdit.
 */
final class TryFluide extends Command
{
    protected $signature = 'fluide:try
        {--text= : Le mot à mot à soumettre}
        {--file= : Un fichier contenant le mot à mot}
        {--question= : La question posée, pour le contexte}
        {--name=Odette : Le prénom de la personne}
        {--lexicon= : Graphies attendues, au format « terme=graphie,terme=graphie »}';

    protected $description = 'Soumet un mot à mot au rendu Fluide et montre le résultat';

    public function handle(StoryRenderer $renderer): int
    {
        $text = $this->verbatimText();

        if ($text === null) {
            $this->components->error('Donnez --text ou --file.');

            return self::FAILURE;
        }

        $driver = (string) config('services.anthropic.provider', config('product.llm.provider', ''));

        $this->components->info(sprintf(
            'Fournisseur : %s · %d mots en entrée',
            get_class($renderer) === 'App\Services\Llm\ClaudeStoryRenderer' ? 'Claude (réel)' : 'simulé',
            self::words($text),
        ));

        // Une transcription **non enregistrée** : le rendu n'a besoin que du
        // texte, et une lecture d'évaluation ne laisse pas de traces.
        $verbatim = new Transcript([
            'kind' => TranscriptKind::Verbatim,
            'language' => 'fr',
            'text' => $text,
        ]);

        $context = new RenderingContext(
            question: $this->option('question') === null ? null : (string) $this->option('question'),
            firstName: (string) $this->option('name'),
            addressForm: AddressForm::Vous,
            lexicon: self::lexicon((string) ($this->option('lexicon') ?? '')),
            themes: array_map(
                fn (object $case): string => (string) $case->value,
                QuestionTheme::cases(),
            ),
        );

        $started = microtime(true);
        $result = $renderer->render($verbatim, $context);
        $elapsed = microtime(true) - $started;

        if ($result->text === '') {
            $this->components->error('Le modèle a refusé de rendre ce texte.');
            $this->line(json_encode($result->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '');

            return self::FAILURE;
        }

        $this->section('Mot à mot', $text);
        $this->section('Fluide', $result->text);

        $before = self::words($text);
        $after = self::words($result->text);
        $delta = $before === 0 ? 0 : (int) round(($after - $before) / $before * 100);

        $this->newLine();
        $this->table(
            ['', 'valeur'],
            [
                ['titre proposé', $result->title ?? '—'],
                ['thèmes', implode(', ', $result->themes) ?: '—'],
                ['noms propres', implode(', ', $result->properNouns) ?: '—'],
                ['sujets sensibles', implode(', ', $result->sensitiveFlags) ?: '—'],
                ['mots', sprintf('%d → %d (%+d %%)', $before, $after, $delta)],
                ['durée', sprintf('%.1f s', $elapsed)],
                ['jetons', sprintf(
                    '%s entrée / %s sortie',
                    $result->metadata['usage']['input_tokens'] ?? '?',
                    $result->metadata['usage']['output_tokens'] ?? '?',
                )],
            ],
        );

        if ($delta < -20) {
            // Le prompt promet « au plus 20 % plus court ». En dessous, le
            // modèle a résumé — et résumer est interdit.
            $this->components->warn(sprintf(
                'Le rendu est %d %% plus court : le prompt n’autorise que 20 %%. Il a probablement résumé.',
                abs($delta),
            ));
        }

        return self::SUCCESS;
    }

    private function verbatimText(): ?string
    {
        $file = $this->option('file');

        if (is_string($file) && $file !== '') {
            return File::exists($file) ? trim(File::get($file)) : null;
        }

        $text = $this->option('text');

        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }

    private function section(string $title, string $body): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>── '.$title.' '.str_repeat('─', max(0, 70 - mb_strlen($title))).'</>');
        $this->newLine();

        foreach (explode("\n", wordwrap($body, 76, "\n", false)) as $line) {
            $this->line('  '.$line);
        }
    }

    private static function words(string $text): int
    {
        $parts = preg_split('/\s+/', trim($text));

        return $parts === false ? 0 : count(array_filter($parts));
    }

    /**
     * @return array<string, string>
     */
    private static function lexicon(string $raw): array
    {
        $lexicon = [];

        foreach (array_filter(explode(',', $raw)) as $pair) {
            [$term, $spelling] = array_pad(explode('=', trim($pair), 2), 2, null);

            if (is_string($term) && is_string($spelling) && $term !== '' && $spelling !== '') {
                $lexicon[trim($term)] = trim($spelling);
            }
        }

        return $lexicon;
    }
}
