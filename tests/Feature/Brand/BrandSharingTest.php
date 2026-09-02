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

    $this->get('/')->assertSee('<title>Essai</title>', escape: false);
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
