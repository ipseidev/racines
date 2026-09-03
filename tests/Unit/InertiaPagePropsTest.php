<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Aucune page rendue par le serveur ne demande une **fonction** en prop.
 *
 * La garde qui manquait, et qui a coûté un bouton mort pendant tout un bloc
 * (T-127). `AlreadyRecorded` déclarait `onRestart?: () => void` et son bouton
 * l'appelait ; la page étant rendue par `inertia()`, la prop ne pouvait
 * jamais arriver — une fonction ne se sérialise pas en JSON. Le clic ne
 * faisait donc rien, en silence, dans tous les cas.
 *
 * Rien ne pouvait l'attraper : PHPStan ne lit pas les props React, et
 * TypeScript trouvait la prop optionnelle, donc valide. Ce test lit les deux
 * côtés — les pages que les contrôleurs rendent, et le type `Props` de chaque
 * fichier correspondant — et refuse la combinaison.
 *
 * Un composant qui reçoit un rappel d'un parent React est parfaitement
 * légitime : c'est pourquoi le test ne regarde que les fichiers **nommés par
 * un `inertia()`**, et seulement leur type `Props` de premier niveau.
 */

/**
 * Les composants de page cités par un `inertia('…')` dans le code serveur.
 *
 * @return list<string>
 */
function serverRenderedPages(): array
{
    $files = (new Finder)->files()->in(app_path())->name('*.php');
    $pages = [];

    foreach ($files as $file) {
        preg_match_all(
            "/inertia\(\s*'([a-zA-Z0-9\/_-]+)'/",
            (string) file_get_contents($file->getRealPath()),
            $matches,
        );

        foreach ($matches[1] as $page) {
            $pages[$page] = true;
        }
    }

    return array_keys($pages);
}

/** Le bloc `type Props = { … }` d'un composant, ou null s'il n'en a pas. */
function pagePropsBlock(string $path): ?string
{
    $source = (string) file_get_contents($path);

    if (! preg_match('/\ntype Props = \{(.*?)\n\};/s', $source, $matches)) {
        return null;
    }

    return $matches[1];
}

it('ne déclare aucune prop de type fonction sur une page rendue par le serveur', function (): void {
    $offenders = [];

    foreach (serverRenderedPages() as $page) {
        $path = resource_path("js/pages/{$page}.tsx");

        if (! is_file($path)) {
            continue;
        }

        $props = pagePropsBlock($path);

        if ($props === null) {
            continue;
        }

        foreach (preg_split('/\r?\n/', $props) ?: [] as $line) {
            // Une flèche dans une déclaration de prop : `() => void`,
            // `(v: string) => void`, `Promise<…>` compris.
            if (preg_match('/^\s*\w+\??\s*:\s*.*=>/', $line) === 1) {
                $offenders[] = "{$page} : ".trim($line);
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('trouve bien les pages, sans quoi le test précédent serait vide', function (): void {
    $pages = serverRenderedPages();

    expect($pages)->toContain('narrator/AlreadyRecorded')
        ->and($pages)->toContain('narrator/Record')
        ->and($pages)->toContain('family/Home')
        ->and(count($pages))->toBeGreaterThan(15);
});

it('lit bien le bloc Props, sans quoi le test précédent serait vide', function (): void {
    $props = pagePropsBlock(resource_path('js/pages/narrator/AlreadyRecorded.tsx'));

    expect($props)->not->toBeNull()
        ->and($props)->toContain('canRestart')
        ->and($props)->toContain('restartAction');
});
