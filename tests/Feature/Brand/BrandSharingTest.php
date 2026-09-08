<?php

declare(strict_types=1);

use App\Actions\UpdateBrandSettings;
use App\Support\Brand;
use Inertia\Testing\AssertableInertia;

it('injecte les variables CSS de marque dans la vue racine', function (): void {
    $response = $this->get('/')->assertOk();

    foreach (Brand::cssVariables() as $variable => $value) {
        $response->assertSee("{$variable}: {$value}", escape: false);
    }
});

it('reflète immédiatement un changement de couleur sans redéploiement', function (): void {
    app(UpdateBrandSettings::class)->handle([
        'color_primary' => '#8B0000',
        'color_primary_foreground' => '#FFFFFF',
    ]);

    $this->get('/')->assertSee('--brand-primary: #8B0000', escape: false);
});

it('titre la page avec le nom du produit', function (): void {
    app(UpdateBrandSettings::class)->handle(['product_name' => 'Essai']);

    // Le titre porte le sujet de la page depuis T-225 — c'est lui qui devient
    // le libellé d'un lien de site. Ce qui compte ici reste que le nom de
    // marque des réglages y arrive, servi par le serveur.
    $this->get('/')->assertSee('<title>Essai —', escape: false);
});

it('partage la marque et les traductions avec chaque page Inertia', function (): void {
    $this->get('/')->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('brand', fn (AssertableInertia $brand) => $brand
                ->where('name', Brand::name())
                ->where('links_domain', Brand::linksDomain())
                ->has('short_name')
                ->has('tagline')
                ->has('support_email')
                ->has('support_phone')
                ->has('mark_url')
                ->has('logo_url')
            )
            ->has('i18n.public.landing.promise')
            ->has('i18n.common.actions.continue')
            ->has('locale')
    );
});

it('n’expose jamais l’expéditeur SMS ni les couleurs au front', function (): void {
    $this->get('/')->assertInertia(
        fn (AssertableInertia $page) => $page
            ->missing('brand.sms_sender_id')
            ->missing('brand.color_primary')
    );
});

it('sert le pictogramme livré tant que l’administration n’en téléverse pas', function (): void {
    expect(Brand::markUrl())->toBe(asset('/img/brand/mark.svg'));

    app(UpdateBrandSettings::class)->handle(['mark_path' => 'marque/pictogramme.svg']);

    expect(Brand::markUrl())->toBe(asset('storage/marque/pictogramme.svg'));
});

it('rend le manifeste depuis les réglages, jamais depuis un fichier', function (): void {
    app(UpdateBrandSettings::class)->handle([
        'product_name' => 'Essai',
        'short_name' => 'Essai',
    ]);

    $this->get('/site.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('name', 'Essai')
        ->assertJsonPath('short_name', 'Essai')
        ->assertJsonPath('theme_color', Brand::settings()->color_background);
});

it('cite le manifeste et le jeu complet d’icônes dans la vue racine', function (): void {
    $this->get('/')
        ->assertSee('<link rel="manifest" href="/site.webmanifest">', escape: false)
        ->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', escape: false)
        ->assertSee('rel="apple-touch-icon"', escape: false);
});
