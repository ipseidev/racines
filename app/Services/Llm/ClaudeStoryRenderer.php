<?php

declare(strict_types=1);

namespace App\Services\Llm;

use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Messages\TextBlock;
use App\Enums\QuestionTheme;
use App\Models\Transcript;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Mise au propre par Claude.
 *
 * Trois choix méritent d'être dits :
 *
 *  1. **Le prompt système est un fichier versionné**
 *     (`resources/prompts/fluide-v1.txt`), pas une chaîne dans le code. Il est
 *     mis en cache côté API : identique d'un appel à l'autre, il ne coûte
 *     qu'une fois. Le modifier veut dire créer `fluide-v2.txt` et mettre à
 *     jour l'instantané de test — le texte qui met en forme les souvenirs de
 *     quelqu'un ne change pas discrètement.
 *  2. **La sortie est contrainte par un schéma JSON.** Le modèle ne rend pas
 *     de la prose libre qu'on parserait à la main : il rend un objet dont on
 *     connaît la forme.
 *  3. **Un refus n'est pas rattrapé.** Le SDK n'expose `fallbacks` que sur son
 *     interface bêta, et surtout : un refus sur le récit de vie de quelqu'un
 *     est un signal à regarder, pas à contourner en silence. On garde le
 *     verbatim, on marque le refus, on alerte (règle §9 du bloc).
 */
final readonly class ClaudeStoryRenderer implements StoryRenderer
{
    public const PROMPT_VERSION = 'fluide-v1';

    /** @var list<string> */
    private const SENSITIVE_FLAGS = ['health', 'religion', 'conflict', 'intimacy', 'money', 'other'];

    public function __construct(private AnthropicMessages $messages) {}

    public function render(Transcript $verbatim, RenderingContext $context): FluideResult
    {
        $startedAt = microtime(true);

        try {
            $message = $this->messages->create(
                model: (string) config('services.anthropic.model'),
                maxTokens: (int) config('services.anthropic.max_tokens'),
                system: [[
                    'type' => 'text',
                    'text' => self::systemPrompt(),
                    // Le prompt ne change pas d'un appel à l'autre : mis en
                    // cache, il ne se paie qu'une fois par fenêtre.
                    'cacheControl' => ['type' => 'ephemeral'],
                ]],
                messages: [[
                    'role' => 'user',
                    'content' => self::userMessage($verbatim, $context),
                ]],
                outputConfig: [
                    'effort' => (string) config('services.anthropic.effort'),
                    'format' => ['type' => 'json_schema', 'schema' => self::schema()],
                ],
            );
        } catch (APIStatusException $exception) {
            // Débordement ou surcharge : le job réessaiera avec son backoff.
            if (in_array($exception->type?->value, ['rate_limit_error', 'overloaded_error'], true)) {
                throw $exception;
            }

            Log::error('fluide.api_error', [
                'story_id' => $verbatim->story_id,
                'type' => $exception->type?->value,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $metadata = [
            'provider' => 'claude',
            'model' => $message->model,
            'prompt_version' => self::PROMPT_VERSION,
            'effort' => (string) config('services.anthropic.effort'),
            'duration_ms' => $durationMs,
            'usage' => [
                'input_tokens' => $message->usage->inputTokens,
                'output_tokens' => $message->usage->outputTokens,
                'cache_read_input_tokens' => $message->usage->cacheReadInputTokens,
                'cache_creation_input_tokens' => $message->usage->cacheCreationInputTokens,
            ],
        ];

        if ($message->stopReason === 'refusal') {
            Log::warning('fluide.refused', [
                'story_id' => $verbatim->story_id,
                'category' => $message->stopDetails?->category,
            ]);

            return FluideResult::refused([
                ...$metadata,
                'refusal_category' => $message->stopDetails?->category,
                'refusal_explanation' => $message->stopDetails?->explanation,
            ]);
        }

        return self::map(self::firstJson($message->content), $metadata);
    }

    public static function systemPrompt(): string
    {
        $path = resource_path('prompts/'.self::PROMPT_VERSION.'.txt');

        return trim((string) file_get_contents($path));
    }

    /**
     * Le message utilisateur, dans un ordre délibéré : la question d'abord —
     * c'est elle qui donne le sujet —, puis à qui on parle, puis le lexique,
     * et le verbatim en dernier, entre balises, pour qu'il ne se confonde
     * jamais avec une consigne.
     */
    public static function userMessage(Transcript $verbatim, RenderingContext $context): string
    {
        $lines = [];

        if ($context->question !== null) {
            $lines[] = 'Question posée : '.$context->question;
        }

        $lines[] = 'Prénom de la personne : '.$context->firstName;
        $lines[] = 'Forme d’adresse du projet : '.$context->addressForm->value;

        if ($context->lexicon !== []) {
            $lines[] = 'Lexique (terme entendu → graphie attendue) :';

            foreach ($context->lexicon as $term => $spelling) {
                $lines[] = "- {$term} → {$spelling}";
            }
        }

        $themes = $context->themes === []
            ? array_column(QuestionTheme::cases(), 'value')
            : $context->themes;

        $lines[] = 'Thèmes possibles : '.implode(', ', $themes);
        $lines[] = '';
        $lines[] = '<verbatim>';
        $lines[] = $verbatim->text;
        $lines[] = '</verbatim>';

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'maxLength' => 60],
                'text' => ['type' => 'string'],
                'themes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => array_column(QuestionTheme::cases(), 'value')],
                ],
                'proper_nouns' => ['type' => 'array', 'items' => ['type' => 'string']],
                'sensitive_flags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => self::SENSITIVE_FLAGS],
                ],
            ],
            'required' => ['title', 'text', 'themes', 'proper_nouns', 'sensitive_flags'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Le modèle rend le JSON dans un bloc de texte : on prend le premier qui
     * se décode, et on refuse le reste plutôt que de deviner.
     *
     * @param  list<mixed>  $content
     * @return array<string, mixed>
     */
    private static function firstJson(array $content): array
    {
        foreach ($content as $block) {
            if (! $block instanceof TextBlock) {
                continue;
            }

            $decoded = json_decode($block->text, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('La réponse ne contient aucun objet JSON exploitable.');
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @param  array<string, mixed>  $metadata
     */
    private static function map(array $decoded, array $metadata): FluideResult
    {
        $text = $decoded['text'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('La réponse ne contient pas de texte.');
        }

        $strings = static fn (mixed $value): array => array_values(array_filter(
            is_array($value) ? $value : [],
            static fn (mixed $item): bool => is_string($item) && $item !== '',
        ));

        $title = $decoded['title'] ?? null;

        return new FluideResult(
            title: is_string($title) && trim($title) !== '' ? mb_substr(trim($title), 0, 60) : null,
            text: trim($text),
            themes: $strings($decoded['themes'] ?? []),
            properNouns: $strings($decoded['proper_nouns'] ?? []),
            sensitiveFlags: $strings($decoded['sensitive_flags'] ?? []),
            metadata: $metadata,
        );
    }
}
