<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Support\LocalizedRoutes;
use App\Support\Seo;
use Illuminate\Support\Facades\Lang;

/*
 * Une page publique, cinq adresses.
 *
 * Ce que ces tests protègent : qu'aucune page ne disparaisse dans une langue,
 * qu'aucune adresse n'en serve deux, et que chaque page déclare ses sœurs. Un
 * `hreflang` qui manque, ou qui pointe vers une autre page, et Google indexe
 * la mauvaise version — c'est le genre de défaut qu'on ne voit jamais depuis
 * son navigateur.
 */

it('sert chaque page publique dans les cinq langues', function (string $name): void {
    foreach (Locale::cases() as $locale) {
        $this->get(LocalizedRoutes::route($name, [], $locale))
            ->assertOk();
    }
})->with(LocalizedRoutes::PAGES);

it('donne à chaque langue une adresse différente', function (string $name): void {
    $urls = array_map(
        fn (Locale $locale): string => LocalizedRoutes::route($name, [], $locale),
        Locale::cases(),
    );

    expect($urls)->toHaveCount(count(array_unique($urls)), "Deux langues partagent l'adresse de [{$name}].");
})->with(LocalizedRoutes::PAGES);

it('traduit le segment d’adresse, et pas seulement le préfixe', function (): void {
    expect(LocalizedRoutes::route('how_it_works', [], Locale::Italian, false))
        ->toBe('/it/come-funziona')
        ->and(LocalizedRoutes::route('legal.terms', [], Locale::Spanish, false))
        ->toBe('/es/condiciones-de-venta')
        // Le français de Suisse parle français : mêmes mots, autre préfixe.
        ->and(LocalizedRoutes::route('how_it_works', [], Locale::SwissFrench, false))
        ->toBe('/fr-ch/comment-ca-marche');
});

it('déclare toutes les langues d’une page, elle comprise, plus x-default', function (): void {
    $html = $this->get('/it/come-funziona')->getContent();

    foreach (Locale::cases() as $locale) {
        $href = LocalizedRoutes::route('how_it_works', [], $locale);

        expect($html)->toContain('hreflang="'.$locale->value.'" href="'.$href.'"');
    }

    // Sans la ligne réflexive, Google ignore le groupe entier ; `x-default`
    // désigne la version servie à qui ne demande aucune de ces langues.
    expect($html)->toContain('hreflang="x-default" href="'.LocalizedRoutes::route('how_it_works', [], Locale::French).'"');
});

it('écrit la langue du document et celle d’Open Graph', function (): void {
    $this->get('/es/como-funciona')
        ->assertSee('<html lang="es-ES"', false)
        ->assertSee('<meta property="og:locale" content="es_ES">', false);
});

it('donne à chaque langue sa canonique, jamais celle du français', function (): void {
    $html = $this->get('/it/come-funziona')->getContent();

    expect($html)->toContain('rel="canonical" href="'.LocalizedRoutes::route('how_it_works', [], Locale::Italian).'"');
});

it('ne déclare aucune langue sœur sur une page qui n’a qu’une adresse', function (): void {
    // Le témoin du test sert la même offre que l'accueil : lui donner des
    // `hreflang` vers l'accueil dirait à Google que ce sont les mêmes pages.
    $this->get('/lp/temoin')
        ->assertOk()
        ->assertDontSee('hreflang=', false);
});

it('porte au plan de site les cinq langues de chaque page, avec leurs sœurs', function (): void {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    foreach (Seo::SITEMAP as $name) {
        foreach (Locale::cases() as $locale) {
            $href = LocalizedRoutes::route($name, [], $locale);

            expect($xml)->toContain('<loc>'.htmlspecialchars($href, ENT_XML1).'</loc>')
                ->and($xml)->toContain('hreflang="'.$locale->value.'" href="'.htmlspecialchars($href, ENT_XML1).'"');
        }
    }
});

it('ne met au plan de site ni le tunnel ni le témoin', function (): void {
    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->not->toContain('<loc>'.LocalizedRoutes::route('checkout.show').'</loc>')
        ->and($xml)->not->toContain('/lp/temoin');
});

it('garde le tunnel hors de l’index dans toutes les langues', function (): void {
    foreach (Locale::cases() as $locale) {
        $this->get(LocalizedRoutes::route('checkout.show', [], $locale))
            ->assertSee('name="robots" content="noindex, follow"', false);
    }
});

it('donne un segment d’adresse à chaque page, dans chaque langue', function (): void {
    // Une clé oubliée dans `lang/it/routes.php` ferait exploser
    // l'enregistrement des routes au démarrage : autant le dire ici, avec le
    // nom de la page qui manque.
    foreach (Locale::cases() as $locale) {
        foreach (LocalizedRoutes::PAGES as $name) {
            expect(Lang::has('routes.'.$name, $locale->language()))
                ->toBeTrue("routes.{$name} manque dans lang/{$locale->language()}/routes.php");
        }
    }
});

it('sert au front les adresses de la langue courante', function (): void {
    $this->get('/es/como-funciona')
        ->assertInertia(fn ($page) => $page
            ->where('locale.urls.checkout_show', '/es/comprar')
            ->where('locale.urls.legal_privacy', '/es/privacidad'));
});

it('propose les cinq langues, chacune nommée dans la sienne', function (): void {
    $this->get('/comment-ca-marche')
        ->assertInertia(fn ($page) => $page
            ->has('locale.locales', 5)
            ->where('locale.locales.1.name', 'Italiano')
            ->where('locale.locales.1.url', LocalizedRoutes::route('how_it_works', [], Locale::Italian)));
});
