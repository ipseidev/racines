<?php

declare(strict_types=1);

use App\Filament\Pages\ManageBrand;
use App\Models\User;
use App\Settings\BrandSettings;
use Livewire\Livewire;

/*
 * Les comptes de ce fichier portent `withAppAuthentication()` : depuis le
 * bloc 11, la double authentification est **obligatoire** sur le panneau, et
 * un compte sans second facteur est renvoyé vers sa configuration avant toute
 * page. Le décor doit ressembler au produit — un membre du personnel en
 * activité l'a forcément configurée.
 */

it('refuse la page de marque à une Initiateur·rice', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(ManageBrand::getUrl())
        ->assertForbidden();
});

it('refuse la page de marque au support en lecture seule', function (): void {
    $this->actingAs(User::factory()->supportReadonly()->withAppAuthentication()->create())
        ->get(ManageBrand::getUrl())
        ->assertForbidden();
});

it('ouvre la page de marque à une administratrice', function (): void {
    $this->actingAs(User::factory()->admin()->withAppAuthentication()->create())
        ->get(ManageBrand::getUrl())
        ->assertOk();
});

it('enregistre un nouveau nom et une nouvelle couleur', function (): void {
    $this->actingAs(User::factory()->admin()->withAppAuthentication()->create());

    Livewire::test(ManageBrand::class)
        ->fillForm([
            'product_name' => 'Essai',
            'color_primary' => '#8B0000',
            'color_primary_foreground' => '#FFFFFF',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $brand = app(BrandSettings::class);

    expect($brand->product_name)->toBe('Essai')
        ->and($brand->color_primary)->toBe('#8B0000');
});

it('refuse un contraste insuffisant et laisse la marque intacte', function (): void {
    $this->actingAs(User::factory()->admin()->withAppAuthentication()->create());
    $avant = app(BrandSettings::class)->color_text;

    Livewire::test(ManageBrand::class)
        ->fillForm(['color_text' => '#CCCCCC', 'color_background' => '#FFFFFF'])
        ->call('save')
        ->assertHasFormErrors(['color_text']);

    expect(app(BrandSettings::class)->color_text)->toBe($avant);
});

it('refuse un expéditeur SMS non conforme', function (): void {
    $this->actingAs(User::factory()->admin()->withAppAuthentication()->create());

    Livewire::test(ManageBrand::class)
        ->fillForm(['sms_sender_id' => '123456789012'])
        ->call('save')
        ->assertHasFormErrors(['sms_sender_id']);
});
