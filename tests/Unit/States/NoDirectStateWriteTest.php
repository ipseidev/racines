<?php

declare(strict_types=1);

use App\Models\Story;
use Symfony\Component\Finder\Finder;

/**
 * Critère de sortie du bloc 02 : aucune transition n'existe en dehors de
 * `StoryState::config()`.
 *
 * Une écriture directe de `state` contournerait toutes les gardes — et donc la
 * règle non négociable « rien n'est visible des proches avant validation ». Ce
 * test la cherche partout et n'accepte que deux exceptions, chacune motivée :
 * les classes de transition, qui sont la machine elle-même, et la fabrique
 * d'histoires, qui construit un décor sans jouer l'histoire.
 */
/** @return array<string, string> */
function phpSourcesOutsideTransitions(): array
{
    $finder = Finder::create()
        ->files()
        ->name('*.php')
        ->in([base_path('app'), base_path('database/seeders')])
        ->notPath('States/Story/Transitions');

    $sources = [];

    foreach ($finder as $file) {
        // La déclaration du cast n'est pas une écriture d'état : c'est ce qui
        // branche la machine sur la colonne. On la retire avant de chercher.
        $contents = str_replace("'state' => StoryState::class", '', $file->getContents());

        $sources[str_replace(base_path().'/', '', $file->getRealPath())] = $contents;
    }

    return $sources;
}

it('n’écrit jamais l’état d’une histoire par affectation de tableau', function (): void {
    foreach (phpSourcesOutsideTransitions() as $path => $contents) {
        expect($contents)->not->toMatch('/[\'"]state[\'"]\s*=>/', "{$path} écrit « state » à la main ; passer par une transition.");
    }
});

it('n’affecte la propriété state que dans les classes de transition', function (): void {
    foreach (phpSourcesOutsideTransitions() as $path => $contents) {
        expect($contents)->not->toMatch('/->state\s*=(?!=)/', "{$path} affecte state directement ; passer par une transition.");
    }
});

it('n’autorise l’écriture directe de l’état que dans la fabrique d’histoires', function (): void {
    $finder = Finder::create()->files()->name('*.php')->in(base_path('database/factories'));

    foreach ($finder as $file) {
        $writesState = preg_match('/[\'"]state[\'"]\s*=>/', $file->getContents()) === 1;

        if ($writesState) {
            expect($file->getFilename())->toBe('StoryFactory.php');
        }
    }
});

it('garde la colonne state hors de l’assignation de masse', function (): void {
    expect((new Story)->getFillable())->not->toContain('state');
});
