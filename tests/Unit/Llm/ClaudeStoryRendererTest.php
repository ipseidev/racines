<?php

declare(strict_types=1);

use Anthropic\Messages\Message;
use App\Enums\AddressForm;
use App\Enums\QuestionTheme;
use App\Models\Transcript;
use App\Services\Llm\AnthropicMessages;
use App\Services\Llm\ClaudeStoryRenderer;
use App\Services\Llm\RenderingContext;

/**
 * Un port implémenté à la main plutôt qu'un double de bibliothèque : le
 * service de messages du SDK est `final`, et le renderer n'a pas besoin de
 * connaître l'arborescence du SDK. Aucun test n'appelle un fournisseur
 * (convention §5).
 */
function claudeDouble(Message $message, array &$captured): AnthropicMessages
{
    return new class($message, $captured) implements AnthropicMessages
    {
        /**
         * @param  array<string, mixed>  $captured
         */
        public function __construct(
            private readonly Message $message,
            private array &$captured,
        ) {}

        public function create(
            string $model,
            int $maxTokens,
            array $system,
            array $messages,
            array $outputConfig,
        ): Message {
            $this->captured = compact('model', 'maxTokens', 'system', 'messages', 'outputConfig');

            return $this->message;
        }
    };
}

function claudeMessage(array $payload, string $stopReason = 'end_turn', ?array $refusal = null): Message
{
    return Message::with(
        id: 'msg_1',
        container: null,
        content: [['type' => 'text', 'text' => (string) json_encode($payload)]],
        model: 'claude-opus-5',
        stopDetails: $refusal,
        stopReason: $stopReason,
        stopSequence: null,
        usage: [
            'inputTokens' => 1200,
            'outputTokens' => 640,
            'cacheReadInputTokens' => 900,
            'cacheCreationInputTokens' => 0,
        ],
    );
}

function fluidePayload(): array
{
    return [
        'title' => 'La maison de Kerhostin',
        'text' => 'Je me souviens de la maison de Kerhostin. Ma grand-mère y faisait des crêpes.',
        'themes' => ['childhood', 'places'],
        'proper_nouns' => ['Kerhostin'],
        'sensitive_flags' => [],
    ];
}

function renderingContext(): RenderingContext
{
    return new RenderingContext(
        question: 'À quoi ressemblait la maison de votre enfance ?',
        firstName: 'Odette',
        addressForm: AddressForm::Vous,
        lexicon: ['Ker Austin' => 'Kerhostin'],
        themes: ['childhood', 'places', 'legacy'],
    );
}

it('envoie le prompt système mis en cache, la question, le lexique et le verbatim', function (): void {
    $captured = [];
    $renderer = new ClaudeStoryRenderer(claudeDouble(claudeMessage(fluidePayload()), $captured));

    $renderer->render(Transcript::factory()->create(), renderingContext());

    expect($captured['model'])->toBe('claude-opus-5')
        // Le prompt ne change pas d'un appel à l'autre : mis en cache, il ne
        // se paie qu'une fois par fenêtre.
        ->and($captured['system'][0]['cacheControl'])->toBe(['type' => 'ephemeral'])
        ->and($captured['system'][0]['text'])->toContain('tu conserves ses mots');

    $user = $captured['messages'][0]['content'];

    expect($user)->toContain('À quoi ressemblait la maison de votre enfance ?')
        ->and($user)->toContain('Odette')
        ->and($user)->toContain('Ker Austin → Kerhostin')
        // Le verbatim est balisé : il ne doit jamais se confondre avec une
        // consigne.
        ->and($user)->toContain('<verbatim>')
        ->and($user)->toContain('</verbatim>');
});

it('demande une sortie conforme à un schéma JSON et la traduit', function (): void {
    $captured = [];
    $renderer = new ClaudeStoryRenderer(claudeDouble(claudeMessage(fluidePayload()), $captured));

    $result = $renderer->render(Transcript::factory()->create(), renderingContext());

    expect($captured['outputConfig']['format']['type'])->toBe('json_schema')
        ->and($captured['outputConfig']['format']['schema']['additionalProperties'])->toBeFalse()
        ->and($captured['outputConfig']['format']['schema']['required'])
        ->toBe(['title', 'text', 'themes', 'proper_nouns', 'sensitive_flags'])
        ->and($captured['outputConfig']['effort'])->toBe('medium');

    expect($result->refused)->toBeFalse()
        ->and($result->title)->toBe('La maison de Kerhostin')
        ->and($result->text)->toContain('Kerhostin')
        ->and($result->themes)->toBe(['childhood', 'places'])
        ->and($result->properNouns)->toBe(['Kerhostin'])
        ->and($result->sensitiveFlags)->toBe([]);
});

it('borne le titre à soixante caractères', function (): void {
    $captured = [];
    $payload = [...fluidePayload(), 'title' => str_repeat('un titre très long ', 10)];
    $renderer = new ClaudeStoryRenderer(claudeDouble(claudeMessage($payload), $captured));

    $result = $renderer->render(Transcript::factory()->create(), renderingContext());

    expect(mb_strlen((string) $result->title))->toBe(60);
});

it('garde le verbatim seul et signale le refus quand le modèle décline', function (): void {
    $captured = [];
    $message = claudeMessage(
        fluidePayload(),
        stopReason: 'refusal',
        refusal: ['type' => 'refusal', 'category' => 'other', 'explanation' => 'contenu sensible'],
    );

    $result = (new ClaudeStoryRenderer(claudeDouble($message, $captured)))
        ->render(Transcript::factory()->create(), renderingContext());

    // Un refus sur le récit de vie de quelqu'un est un signal à regarder, pas
    // à contourner en silence (règle §9 du bloc 06).
    expect($result->refused)->toBeTrue()
        ->and($result->text)->toBe('')
        ->and($result->title)->toBeNull()
        ->and($result->metadata['refusal_category'])->toBe('other')
        ->and($result->metadata['refusal_explanation'])->toBe('contenu sensible');
});

it('consigne le modèle, la consommation et la durée', function (): void {
    $captured = [];
    $result = (new ClaudeStoryRenderer(claudeDouble(claudeMessage(fluidePayload()), $captured)))
        ->render(Transcript::factory()->create(), renderingContext());

    expect($result->metadata['model'])->toBe('claude-opus-5')
        ->and($result->metadata['prompt_version'])->toBe('fluide-v2')
        ->and($result->metadata['usage']['input_tokens'])->toBe(1200)
        ->and($result->metadata['usage']['output_tokens'])->toBe(640)
        // La lecture du cache est ce qui dit si le prompt est bien réutilisé.
        ->and($result->metadata['usage']['cache_read_input_tokens'])->toBe(900)
        ->and($result->metadata['duration_ms'])->toBeGreaterThanOrEqual(0);
});

it('refuse une réponse sans texte exploitable', function (): void {
    $captured = [];
    $payload = [...fluidePayload(), 'text' => '   '];
    $renderer = new ClaudeStoryRenderer(claudeDouble(claudeMessage($payload), $captured));
    $transcript = Transcript::factory()->create();
    $context = renderingContext();

    expect(fn () => $renderer->render($transcript, $context))->toThrow(RuntimeException::class);
});

it('refuse une réponse qui n’est pas du JSON', function (): void {
    $captured = [];
    $message = Message::with(
        id: 'msg_1',
        container: null,
        content: [['type' => 'text', 'text' => 'Bien sûr, voici le texte…']],
        model: 'claude-opus-5',
        stopDetails: null,
        stopReason: 'end_turn',
        stopSequence: null,
        usage: ['inputTokens' => 1, 'outputTokens' => 1],
    );

    $renderer = new ClaudeStoryRenderer(claudeDouble($message, $captured));
    $transcript = Transcript::factory()->create();
    $context = renderingContext();

    expect(fn () => $renderer->render($transcript, $context))->toThrow(RuntimeException::class);
});

it('versionne le prompt système dans un fichier, et rien d’autre', function (): void {
    $prompt = ClaudeStoryRenderer::systemPrompt();
    $snapshot = base_path('tests/Unit/Llm/__snapshots__/system-prompt.txt');

    // Le texte qui met en forme les souvenirs de quelqu'un ne change pas
    // discrètement : le modifier veut dire créer `fluide-v2.txt` et mettre à
    // jour cet instantané.
    if (! file_exists($snapshot)) {
        file_put_contents($snapshot, $prompt);
    }

    expect($prompt)->toBe(trim((string) file_get_contents($snapshot)))
        ->and($prompt)->toContain('tu conserves ses mots')
        ->and($prompt)->toContain("Tu n'ajoutes aucun fait")
        ->and($prompt)->toContain('Tu ne corriges pas les souvenirs')
        // Deux règles nées de la lecture du corpus : le modèle rétablissait la
        // négation complète une fois sur trois, et signalait un conflit
        // familial là où le récit était seulement triste.
        ->and($prompt)->toContain('Tu ne rétablis pas la négation complète')
        ->and($prompt)->toContain('pas quand il est seulement triste')
        ->and(ClaudeStoryRenderer::PROMPT_VERSION)->toBe('fluide-v2');
});

it('n’accepte dans le schéma que les thèmes du référentiel', function (): void {
    $schema = ClaudeStoryRenderer::schema();

    expect($schema['properties']['themes']['items']['enum'])
        ->toBe(array_column(QuestionTheme::cases(), 'value'))
        ->and($schema['properties']['sensitive_flags']['items']['enum'])
        ->toBe(['health', 'religion', 'conflict', 'intimacy', 'money', 'other']);
});
