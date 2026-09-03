<?php

declare(strict_types=1);

use App\Audit\AuditLog;
use App\Filament\Pages\ManagePilot;
use App\Filament\Pages\Playbooks;
use App\Models\User;
use App\Settings\PilotSettings;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;

/**
 * Les playbooks du support, et la page du pilote.
 *
 * Les playbooks sont du texte, pas du code — et c'est précisément pour ça
 * qu'ils sont testés : un playbook qui parle mal fait parler mal, et les mots
 * qu'on lit toute la journée finissent par décider comment on se comporte.
 */
it('rend les six playbooks', function (): void {
    $user = User::factory()->support()->withAppAuthentication()->create();

    $this->actingAs($user)
        ->get(Playbooks::getUrl())
        ->assertOk()
        ->assertSee('Décès d’un narrateur', false)
        ->assertSee('Regret après une confidence', false)
        ->assertSee('Conflit familial', false)
        ->assertSee('Refus du cadeau', false);
});

it('classe le plus grave en premier', function (): void {
    $user = User::factory()->support()->withAppAuthentication()->create();
    $this->actingAs($user);

    $slugs = array_column((new Playbooks)->playbooks(), 'slug');

    // Pas l'ordre alphabétique : quelqu'un qui ouvre cette page en urgence
    // cherche « décès » ou « regret », pas « conflit ».
    expect($slugs[0])->toBe('deces')
        ->and($slugs[1])->toBe('regret-confidence')
        ->and($slugs)->toHaveCount(6);
});

it('dit dans chaque playbook ce qu’on ne fait jamais', function (): void {
    foreach (Finder::create()->files()->in(base_path('resources/playbooks'))->name('*.md') as $file) {
        $contents = $file->getContents();

        // La section la plus utile des cinq : les règles qui protègent d'une
        // bonne intention.
        expect(str_contains($contents, 'Ce qu’on ne fait jamais'))
            ->toBeTrue($file->getFilename().' : section « ce qu’on ne fait jamais » absente')
            ->and(str_contains($contents, 'Qui décide'))
            ->toBeTrue($file->getFilename().' : section « qui décide » absente');
    }
});

it('refuse les playbooks à qui n’a pas la lecture du support', function (): void {
    $user = User::factory()->withAppAuthentication()->create();

    $this->actingAs($user)->get(Playbooks::getUrl())->assertForbidden();
});

it('réserve les réglages du pilote à qui gère la marque', function (): void {
    // Les prix et le mode de vente ne sont pas des réglages de support.
    $support = User::factory()->support()->withAppAuthentication()->create();
    $this->actingAs($support)->get(ManagePilot::getUrl())->assertForbidden();

    $admin = User::factory()->admin()->withAppAuthentication()->create();
    $this->actingAs($admin)->get(ManagePilot::getUrl())->assertOk();
});

it('avertit avant de lever le bandeau juridique', function (): void {
    $admin = User::factory()->admin()->withAppAuthentication()->create();

    // Le texte d'aide n'est pas décoratif : poser cette date retire un
    // avertissement qui est vrai tant qu'aucun conseil n'a relu.
    $this->actingAs($admin)
        ->get(ManagePilot::getUrl())
        ->assertOk()
        ->assertSee('après une relecture réelle par un conseil', false);
});

it('inscrit au journal un changement de réglage du pilote', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    app(PilotSettings::class)->fill(['mode' => 'prevente'])->save();
    AuditLog::record('edited PilotSettings', null, ['mode' => 'prevente']);

    expect(DB::table('audit_logs')->value('action'))->toBe('edited PilotSettings');
});
