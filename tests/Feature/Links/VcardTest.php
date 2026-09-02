<?php

declare(strict_types=1);

use App\Settings\BrandSettings;

beforeEach(function (): void {
    $brand = app(BrandSettings::class);
    $brand->support_email = 'aide@example.test';
    $brand->support_phone = '+33123456789';
    $brand->save();

    config()->set('services.twilio.from', '+33600000000');
});

it('rend une fiche contact au nom de la marque', function (): void {
    $response = $this->get('/vcard');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/vcard; charset=utf-8');

    $card = $response->getContent();
    $brand = app(BrandSettings::class)->product_name;

    expect($card)->toStartWith("BEGIN:VCARD\r\nVERSION:3.0")
        ->and($card)->toContain("FN:{$brand}")
        ->and($card)->toContain('EMAIL;TYPE=INTERNET:aide@example.test')
        ->and($card)->toContain('TEL;TYPE=CELL:+33600000000')
        ->and($card)->toContain('TEL;TYPE=WORK,VOICE:+33123456789')
        ->and($card)->toEndWith("END:VCARD\r\n");
});

it('explique à quoi sert ce contact et prévient de l’hameçonnage', function (): void {
    $card = (string) $this->get('/vcard')->getContent();

    expect($card)->toContain('NOTE:')
        ->and($card)->toContain('jamais de mot de passe');
});

it('se propose en téléchargement plutôt qu’en page', function (): void {
    $this->get('/vcard')->assertHeader('Content-Disposition', 'attachment; filename="contact.vcf"');
});

it('suit le nom de marque quand il change', function (): void {
    $brand = app(BrandSettings::class);
    $brand->product_name = 'Autre Nom';
    $brand->save();

    expect((string) $this->get('/vcard')->getContent())->toContain('FN:Autre Nom');
});

it('omet proprement un numéro absent', function (): void {
    config()->set('services.twilio.from', null);

    $brand = app(BrandSettings::class);
    $brand->support_phone = null;
    $brand->save();

    $card = (string) $this->get('/vcard')->getContent();

    expect($card)->not->toContain('TEL')
        ->and($card)->toContain('EMAIL;TYPE=INTERNET:');
});
