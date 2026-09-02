<?php

declare(strict_types=1);

use App\Actions\UpdateBrandSettings;
use App\Settings\BrandSettings;
use App\Support\Brand;
use Illuminate\Validation\ValidationException;

it('initialise chaque valeur depuis la configuration', function (): void {
    $settings = app(BrandSettings::class);

    expect($settings->product_name)->toBe(config('brand.product_name'))
        ->and($settings->links_domain)->toBe(config('brand.links_domain'))
        ->and($settings->color_primary)->toBe(config('brand.colors.primary'))
        ->and($settings->font_display)->toBe(config('brand.fonts.display'));
});

it('préfère la valeur enregistrée à celle de la configuration', function (): void {
    $settings = app(BrandSettings::class);
    $settings->product_name = 'Essai';
    $settings->save();

    expect(app(BrandSettings::class)->product_name)->toBe('Essai')
        ->and(Brand::name())->toBe('Essai')
        ->and(config('brand.product_name'))->not->toBe('Essai');
});

it('refuse un expéditeur SMS trop long, trop court ou sans lettre', function (string $sender): void {
    expect(fn () => app(UpdateBrandSettings::class)->handle(['sms_sender_id' => $sender]))
        ->toThrow(ValidationException::class);
})->with([
    'douze caractères' => '123456789012',
    'deux caractères' => 'AB',
    'chiffres seuls' => '12345',
    'espace interdit' => 'MA MARQUE',
]);

it('accepte un expéditeur SMS conforme', function (): void {
    app(UpdateBrandSettings::class)->handle(['sms_sender_id' => 'ESSAI7']);

    expect(app(BrandSettings::class)->sms_sender_id)->toBe('ESSAI7');
});

it('refuse un couple de couleurs dont le contraste est sous 4,5', function (array $payload): void {
    expect(fn () => app(UpdateBrandSettings::class)->handle($payload))
        ->toThrow(ValidationException::class);
})->with([
    'texte sur fond' => [['color_text' => '#CCCCCC', 'color_background' => '#FFFFFF']],
    'primaire sur son avant-plan' => [['color_primary' => '#EEEEEE', 'color_primary_foreground' => '#FFFFFF']],
]);

it('refuse une couleur mal formée', function (): void {
    expect(fn () => app(UpdateBrandSettings::class)->handle(['color_primary' => 'vert']))
        ->toThrow(ValidationException::class);
});

it('accepte un changement de couleur lisible et le rend au front', function (): void {
    app(UpdateBrandSettings::class)->handle([
        'color_primary' => '#8B0000',
        'color_primary_foreground' => '#FFFFFF',
    ]);

    expect(Brand::cssVariables())->toHaveKey('--brand-primary', '#8B0000');
});

it('expose les variables CSS attendues par le front', function (): void {
    expect(array_keys(Brand::cssVariables()))->toContain(
        '--brand-primary',
        '--brand-primary-foreground',
        '--brand-accent',
        '--brand-accent-foreground',
        '--brand-background',
        '--brand-surface',
        '--brand-text',
        '--brand-muted',
        '--brand-font-display',
        '--brand-font-body',
    );
});
