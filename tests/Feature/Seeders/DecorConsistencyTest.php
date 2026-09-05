<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Models\Project;
use Database\Seeders\DemoProjectSeeder;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le décor ne ment pas sur l'état de ses projets.
 *
 * `DemoProjectSeeder` posait `status = active` sans jamais dater
 * `accepted_at` : un projet actif que personne n'a accepté, ce qui n'existe
 * pas dans le produit. Les deux règles de silence du moteur exigent cette
 * date — un narrateur qui n'a jamais accepté relève d'`invitation_not_accepted`,
 * pas d'une relance — et elles se **taisaient sans rien dire** sur ce décor.
 * Il a fallu écrire `demo:moteur` et le déboguer pour s'en apercevoir (T-153).
 *
 * L'invariant vaut plus que le correctif : un décor qui ment sur la forme
 * d'une donnée ne prépare pas le terrain, il le mine.
 */
it('ne sème aucun projet actif sans date d’acceptation', function (): void {
    $this->seed(DemoProjectSeeder::class);
    $this->seed(E2ELinksSeeder::class);

    $menteurs = Project::query()
        ->where('status', ProjectStatus::Active->value)
        ->whereNull('accepted_at')
        ->pluck('id')
        ->all();

    expect($menteurs)->toBeEmpty();
});

it('ne sème aucun projet accepté qui ne soit pas parti', function (): void {
    $this->seed(DemoProjectSeeder::class);
    $this->seed(E2ELinksSeeder::class);

    // L'inverse du précédent : une date d'acceptation sur un projet resté en
    // brouillon décrirait un narrateur qui a dit oui à rien.
    $menteurs = Project::query()
        ->whereNotNull('accepted_at')
        ->where('status', ProjectStatus::Draft->value)
        ->pluck('id')
        ->all();

    expect($menteurs)->toBeEmpty();
});
