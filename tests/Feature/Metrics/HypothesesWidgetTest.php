<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Widgets\HypothesesOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Le tableau des hypothèses.
 *
 * Trois partis pris, et chacun corrige une façon classique de se mentir avec
 * un tableau de bord : le dénominateur est visible, il y a **trois** états
 * et non deux, et rien n'est rouge.
 */
function mesure(string $metric, float $value, ?int $num = null, ?int $den = null): void
{
    DB::table('daily_metrics')->insert([
        'date' => now()->toDateString(),
        'cohort_id' => null,
        'metric' => $metric,
        'value' => $value,
        'numerator' => $num,
        'denominator' => $den,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('affiche le dénominateur à côté du taux', function (): void {
    mesure('h0_acceptance_14d', 0.62, 25, 40);

    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(HypothesesOverview::class)
        ->assertSee('62 %')
        // « 62 % » ne veut rien dire sans « sur 40 ».
        ->assertSee('25 sur 40');
});

it('ne se prononce pas sur un échantillon minuscule', function (): void {
    mesure('h0_acceptance_14d', 1.0, 3, 3);

    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(HypothesesOverview::class)
        // 100 % sur trois familles : un résultat que le hasard produit une
        // fois sur trois. L'afficher en vert ferait décider sur du bruit.
        ->assertSee('échantillon trop petit pour conclure');
});

it('dit « pas encore calculé » plutôt que zéro', function (): void {
    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(HypothesesOverview::class)
        // Zéro et « pas mesuré » sont deux choses différentes, et les
        // confondre sur un tableau de comité mène à la mauvaise conclusion.
        ->assertSee('pas encore calculé');
});

it('reste fermé à qui n’a pas la lecture du support', function (): void {
    $etranger = User::factory()->create(['role' => UserRole::Initiator]);

    Livewire::actingAs($etranger);

    expect(HypothesesOverview::canView())->toBeFalse();
});
