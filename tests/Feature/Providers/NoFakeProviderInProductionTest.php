<?php

declare(strict_types=1);

use App\Services\Llm\FakeStoryRenderer;
use App\Services\Llm\StoryRenderer;
use App\Services\Transcription\FakeTranscriptionProvider;
use App\Services\Transcription\TranscriptionProvider;

/**
 * En production, un double ne se substitue pas au vrai fournisseur.
 *
 * `ASR_PROVIDER` et `LLM_PROVIDER` retombent sur `fake` quand la variable
 * manque. En production, cela ne cassait rien de visible : la chaîne
 * fonctionnait, elle produisait simplement du faux — un texte dérivé d'un
 * identifiant de base, relu par une famille comme la parole de son parent,
 * validé, imprimé (T-208).
 */

// L'environnement est remis en place après chaque cas : le laisser sur
// « production » contaminerait toute la suite qui suit dans le même processus.
afterEach(function (): void {
    app()->detectEnvironment(fn (): string => 'testing');
    app()->forgetInstance(TranscriptionProvider::class);
    app()->forgetInstance(StoryRenderer::class);
});

it('refuse la transcription simulée en production', function (): void {
    config()->set('services.asr.provider', 'fake');
    app()->detectEnvironment(fn (): string => 'production');
    app()->forgetInstance(TranscriptionProvider::class);

    expect(fn () => app(TranscriptionProvider::class))
        ->toThrow(RuntimeException::class, 'ASR_PROVIDER');
});

it('refuse la mise au propre simulée en production', function (): void {
    config()->set('services.anthropic.provider', 'fake');
    app()->detectEnvironment(fn (): string => 'production');
    app()->forgetInstance(StoryRenderer::class);

    expect(fn () => app(StoryRenderer::class))
        ->toThrow(RuntimeException::class, 'LLM_PROVIDER');
});

/*
 * Et la garde s'arrête là. Hors production, le double est la valeur par
 * défaut et il le reste : c'est lui qui rend la suite exécutable sans réseau.
 */
it('laisse les doubles vivre hors production', function (): void {
    config()->set('services.asr.provider', 'fake');
    config()->set('services.anthropic.provider', 'fake');

    app()->forgetInstance(TranscriptionProvider::class);
    app()->forgetInstance(StoryRenderer::class);

    expect(app(TranscriptionProvider::class))->toBeInstanceOf(FakeTranscriptionProvider::class)
        ->and(app(StoryRenderer::class))->toBeInstanceOf(FakeStoryRenderer::class);
});

/*
 * La garde est dans la **fabrique**, jamais au démarrage : la leçon de T-206
 * est qu'une variable manquante ne doit pas mettre la boutique hors ligne. Le
 * travail de transcription échoue, bruyamment, et le site continue de vendre.
 */
it('ne fait pas tomber les pages qui n’ont rien à transcrire', function (): void {
    config()->set('services.asr.provider', 'fake');
    config()->set('services.anthropic.provider', 'fake');
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('/up')->assertOk();
});
