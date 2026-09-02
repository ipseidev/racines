<?php

declare(strict_types=1);

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

/**
 * Pennant est installé au bloc 02 ; les drapeaux eux-mêmes arrivent avec les
 * blocs qui les utilisent (convention §15). Ce test prouve seulement que le
 * magasin fonctionne et que la portée par projet, celle dont dépendent les
 * deux variantes de validation, est bien résolue.
 */
it('résout un drapeau par projet et conserve la valeur', function (): void {
    Feature::define('validation-variant', fn (Project $project): string => 'immediate');

    $immediate = Project::factory()->create();
    $deferred = Project::factory()->create();

    Feature::for($deferred)->activate('validation-variant', 'deferred');

    expect(Feature::for($immediate)->value('validation-variant'))->toBe('immediate')
        ->and(Feature::for($deferred)->value('validation-variant'))->toBe('deferred');
});

it('stocke les valeurs résolues dans la table des drapeaux', function (): void {
    Feature::define('mandate-delegation', fn (Project $project): bool => false);

    $project = Project::factory()->create();
    Feature::for($project)->active('mandate-delegation');

    expect(DB::table('features')->where('name', 'mandate-delegation')->count())->toBe(1);
});
