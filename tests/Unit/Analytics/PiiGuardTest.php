<?php

declare(strict_types=1);

use App\Analytics\PiiGuard;
use App\Exceptions\Domain\AnalyticsPiiLeak;

/**
 * La garde anti-fuite.
 *
 * Le doc 04 §12 interdit qu'un jeton porteur ou une donnée personnelle
 * atteigne un outil d'analytique. La garde **lève** plutôt qu'elle ne filtre,
 * et ce choix est le sujet du fichier : un filtre silencieux laisserait le
 * développeur qui ajoute la propriété ignorer qu'elle est jetée — il
 * croirait mesurer quelque chose, et la mesure serait vide.
 *
 * Une fuite ne se rattrape pas : un événement parti chez un tiers y reste.
 * Il faut donc que cela casse **en test**, pas en production.
 */
it('laisse passer des identifiants opaques et des durées', function (): void {
    PiiGuard::assertClean([
        'project_hash' => str_repeat('a', 64),
        'story_sequence' => 3,
        'delay_hours' => 26.5,
        'channel' => 'sms',
        'rule_id' => 'silence_21d',
    ]);

    expect(true)->toBeTrue();
});

it('refuse une adresse de courriel', function (): void {
    expect(fn () => PiiGuard::assertClean(['who' => 'marie@example.test']))
        ->toThrow(AnalyticsPiiLeak::class);
});

it('refuse un numéro au format international', function (): void {
    expect(fn () => PiiGuard::assertClean(['phone' => '+33612345678']))
        ->toThrow(AnalyticsPiiLeak::class);
});

it('refuse une URL à jeton, quel que soit l’espace', function (string $url): void {
    expect(fn () => PiiGuard::assertClean(['url' => $url]))
        ->toThrow(AnalyticsPiiLeak::class);
})->with([
    'https://narrae.fr/r/'.'a1B2c3D4e5F6g7H8i9J0k1L2m3N4o5P6q7R8s9T0u1V',
    'https://narrae.fr/l/'.str_repeat('x', 43),
    '/q/'.str_repeat('y', 43),
    '/n/'.str_repeat('z', 43).'/stories/1',
]);

it('refuse un jeton nu, sans URL autour', function (): void {
    // Un jeton peut arriver seul, dans une propriété nommée innocemment.
    expect(fn () => PiiGuard::assertClean(['ref' => str_repeat('k', 43)]))
        ->toThrow(AnalyticsPiiLeak::class);
});

it('inspecte aussi les valeurs imbriquées', function (): void {
    // Une propriété profonde est exactement là où une fuite se cache : on la
    // passe sans la lire, dans un tableau construit ailleurs.
    expect(fn () => PiiGuard::assertClean([
        'context' => ['user' => ['email' => 'paul@example.test']],
    ]))->toThrow(AnalyticsPiiLeak::class);
});

it('inspecte les clés autant que les valeurs', function (): void {
    expect(fn () => PiiGuard::assertClean(['marie@example.test' => 'ok']))
        ->toThrow(AnalyticsPiiLeak::class);
});

it('dit quelle propriété est en cause, sans recopier sa valeur', function (): void {
    try {
        PiiGuard::assertClean(['destinataire' => 'marie@example.test']);
    } catch (AnalyticsPiiLeak $exception) {
        // Le nom suffit à corriger ; recopier la valeur mettrait la donnée
        // personnelle dans la trace d'erreur, c'est-à-dire chez Flare.
        expect($exception->getMessage())->toContain('destinataire')
            ->and($exception->getMessage())->not->toContain('marie@example.test');

        return;
    }

    $this->fail('La garde aurait dû lever.');
});
