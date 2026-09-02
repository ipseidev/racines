<?php

declare(strict_types=1);

use App\Enums\ValidationVariant;
use App\Features\MandateDelegation;
use App\Features\ValidationVariant as ValidationVariantFeature;
use App\Models\Project;
use Laravel\Pennant\Feature;

it('résout la variante par projet, depuis la colonne du projet', function (): void {
    $deferred = Project::factory()->create(['validation_variant' => ValidationVariant::Deferred]);
    $immediate = Project::factory()->create(['validation_variant' => ValidationVariant::Immediate]);

    expect(Feature::for($deferred)->value(ValidationVariantFeature::class))->toBe('deferred')
        ->and(Feature::for($immediate)->value(ValidationVariantFeature::class))->toBe('immediate');
});

it('retombe sur la validation immédiate quand rien n’a été choisi', function (): void {
    // La colonne n'est pas nullable : elle vaut `immediate` par défaut. Le
    // choix du défaut n'est pas neutre — la variante A est celle qui
    // ressemble à une récompense d'un tap, et c'est l'hypothèse à réfuter.
    $project = Project::factory()->create();

    expect($project->validation_variant)->toBe(ValidationVariant::Immediate)
        ->and(Feature::for($project)->value(ValidationVariantFeature::class))->toBe('immediate');
});

it('donne le même verdict deux fois de suite', function (): void {
    $project = Project::factory()->create(['validation_variant' => ValidationVariant::Deferred]);

    $first = Feature::for($project)->value(ValidationVariantFeature::class);
    // La colonne change, mais la valeur retenue pour ce projet est mémorisée
    // par Pennant : une famille ne change pas de variante en cours de route.
    $project->forceFill(['validation_variant' => ValidationVariant::Immediate])->save();

    expect(Feature::for($project)->value(ValidationVariantFeature::class))->toBe($first);
});

it('garde le mandat fermé par défaut', function (): void {
    $project = Project::factory()->create();

    // Déléguer la validation est une exception, jamais un réglage par défaut :
    // la souveraineté du narrateur est le principe.
    expect(Feature::for($project)->active(MandateDelegation::class))->toBeFalse();
});

it('change la variante d’un projet par la commande du pilote', function (): void {
    $project = Project::factory()->create();

    $this->artisan('features:set-variant', ['project' => $project->id, 'variant' => 'deferred'])
        ->assertSuccessful();

    expect($project->refresh()->validation_variant)->toBe(ValidationVariant::Deferred)
        ->and(Feature::for($project)->value(ValidationVariantFeature::class))->toBe('deferred');
});

it('refuse une variante inconnue', function (): void {
    $project = Project::factory()->create();

    $this->artisan('features:set-variant', ['project' => $project->id, 'variant' => 'plus-tard'])
        ->assertFailed();

    expect($project->refresh()->validation_variant)->not->toBe('plus-tard');
});

it('oublie la valeur mémorisée quand la commande change la variante', function (): void {
    $project = Project::factory()->create(['validation_variant' => ValidationVariant::Immediate]);

    expect(Feature::for($project)->value(ValidationVariantFeature::class))->toBe('immediate');

    $this->artisan('features:set-variant', ['project' => $project->id, 'variant' => 'deferred'])
        ->assertSuccessful();

    // Sans l'oubli explicite, la valeur mémorisée gagnerait contre la colonne
    // et la commande du pilote n'aurait aucun effet visible.
    expect(Feature::for($project)->value(ValidationVariantFeature::class))->toBe('deferred');
});
