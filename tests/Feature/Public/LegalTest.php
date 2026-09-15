<?php

declare(strict_types=1);

use App\Http\Controllers\Public\LegalController;
use App\Settings\PilotSettings;
use App\Support\Brand;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\Finder\Finder;

/**
 * Les pages légales.
 *
 * Rendues depuis des fichiers markdown, avec l'identité de l'éditeur substituée
 * depuis les réglages : un texte juridique qui nomme la mauvaise entité est un
 * texte inopposable, et un texte qui n'en nomme aucune est une non-conformité
 * LCEN — c'était le défaut T-242.
 *
 * L'état de validation juridique est exposé aux pages ; il ne change pas de
 * lui-même, c'est un acte posé dans l'administration (T-145 : plus de bandeau).
 */
it('rend les trois pages légales', function (string $path, string $needle): void {
    $this->get($path)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Legal')
            ->has('title')
            ->where('html', fn (mixed $html) => is_string($html) && str_contains($html, $needle)),
        );
})->with([
    ['/cgv', 'rétractation'],
    ['/confidentialite', 'sous-traitance'],
    ['/mentions-legales', 'Éditeur'],
]);

it('substitue l’identité de l’éditeur, et n’en laisse aucune trace de gabarit', function (string $path): void {
    $this->get($path)->assertInertia(fn (AssertableInertia $page) => $page
        ->where('html', function (mixed $html): bool {
            expect(is_string($html))->toBeTrue();

            // Aucun gabarit non résolu ne doit atteindre l'écran, connu de la
            // table de substitution ou non.
            return preg_match('/\{\{.*?\}\}/', (string) $html) !== 1;
        }),
    );
})->with(['/mentions-legales', '/cgv', '/confidentialite']);

/**
 * Le garde du défaut T-242.
 *
 * `legal_entity` était vide : `str_replace` faisait son travail, la page
 * s'affichait, et elle annonçait « Le représentant légal de . ». Rien
 * n'échouait — c'est exactement pourquoi ce test existe. Il refuse deux
 * choses : un gabarit que la table de substitution ne connaît pas, qui
 * s'afficherait accolades comprises, et un gabarit dont la valeur est vide,
 * qui ampute la phrase sans qu'on le voie.
 */
it('n’emploie dans les textes légaux que des gabarits connus et renseignés', function (): void {
    $tokens = LegalController::tokens();
    $offenders = [];

    foreach (Finder::create()->files()->in(resource_path('views/legal'))->name('*.md') as $file) {
        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/', $file->getContents(), $matches);

        foreach (array_unique($matches[1]) as $name) {
            if (! array_key_exists($name, $tokens)) {
                $offenders[] = $file->getRelativePathname()." : {{ {$name} }} n’existe pas";

                continue;
            }

            if (trim($tokens[$name]) === '') {
                $offenders[] = $file->getRelativePathname()." : {{ {$name} }} est vide";
            }
        }
    }

    expect($offenders)->toBe([], implode(' ; ', $offenders));
});

/**
 * Ce que la LCEN veut lire sur la page, et ce qu'un moteur cherche pour savoir
 * qui répond du site : le vendeur, son immatriculation, et l'hébergeur nommé
 * — « communiqué sur demande » ne satisfait ni l'un ni l'autre.
 */
it('nomme l’éditeur, son immatriculation et son hébergeur', function (): void {
    $brand = Brand::settings();

    $this->get('/mentions-legales')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('html', function (mixed $html) use ($brand): bool {
            $text = (string) $html;

            foreach ([
                $brand->legal_entity,
                $brand->legal_form,
                $brand->legal_address,
                $brand->legal_siren,
                $brand->legal_siret,
                $brand->legal_vat,
                $brand->legal_publication_director,
                $brand->legal_host,
                $brand->legal_host_media,
                $brand->legal_host_location,
            ] as $expected) {
                expect($text)->toContain($expected);
            }

            return ! str_contains($text, 'communiqués sur demande');
        }),
    );
});

it('expose aux pages l’état de la validation juridique, posé dans l’administration', function (): void {
    $this->get('/cgv')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('legalValidated', false),
    );

    app(PilotSettings::class)->fill(['legal_validated_at' => now()->toIso8601String()])->save();

    $this->get('/cgv')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('legalValidated', true),
    );
});

it('affiche les accords dans leur version en vigueur', function (): void {
    $this->get('/consentements')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Consents')
            // Un texte de consentement sans version datée rend inopposable
            // tout ce qui a été accepté avant.
            ->has('texts.0.version')
            ->has('texts.0.effectiveFrom')
            ->has('texts.0.body')
            ->where('texts.0.label', fn (mixed $label) => is_string($label)
                && ! str_starts_with($label, 'enums.')),
        );
});
