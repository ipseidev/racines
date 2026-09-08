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

/*
 * Un nom dans `TWILIO_FROM` ne devient pas un numéro de téléphone.
 *
 * Le champ est le repli des pays qui refusent un expéditeur alphanumérique,
 * et rien n'empêchait d'y écrire le nom de la marque — un `.env` de production
 * en portait un. La fiche partait avec ce nom en `TEL;TYPE=CELL`, qu'aucun
 * téléphone n'importe : l'inverse de ce que le §9 du doc 04 lui demande.
 * T-211 nommait l'exigence, rien ne la tenait (T-223).
 */
it('omet le mobile plutôt que d’écrire un nom dans un TEL', function (): void {
    config()->set('services.twilio.from', 'UnNomDeMarque');

    $reponse = $this->get('/vcard');

    // Omis, et non corrigé : une carte sans mobile s'importe, une carte avec
    // un mobile faux se garde et trompe.
    expect($reponse->getContent())->not->toContain('TEL;TYPE=CELL')
        ->and($reponse->getContent())->not->toContain('UnNomDeMarque');
});

it('porte le mobile quand c’en est un', function (): void {
    config()->set('services.twilio.from', '+33612345678');

    $this->get('/vcard')->assertSee('TEL;TYPE=CELL:+33612345678');
});
