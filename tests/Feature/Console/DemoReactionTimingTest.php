<?php

declare(strict_types=1);

use App\Enums\ReactionType;
use App\Features\ReactionNotificationTiming as Timing;
use App\Models\Reaction;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

/**
 * Les deux gestes du point 4 du bloc 08, qui ne se font pas à la main.
 *
 * Le drapeau des réactions est un **état invisible** : un projet passé à
 * « lendemain matin » n'envoie plus rien tout de suite, et rien à l'écran ne
 * le dit. Il a déjà coûté une vérification — la consigne du point 4 était
 * jouable avant le point 3, et l'a été (écart T-131). Une commande qui le
 * bascule et l'affiche vaut mieux qu'une ligne de `tinker` de deux cents
 * caractères recopiée dans un terminal.
 *
 * Et l'antidatage : le résumé lit les réactions de la **veille**, donc il n'y
 * a rien à résumer le jour où on réagit. Forcer l'horloge n'est pas possible
 * depuis un terminal ; antidater la réaction l'est.
 */
beforeEach(function (): void {
    $this->seed(E2ELinksSeeder::class);
});

it('affiche le drapeau quand on ne lui demande rien', function (): void {
    $this->artisan('demo:reaction-timing')
        ->expectsOutputToContain('immediate')
        ->assertSuccessful();
});

it('bascule le drapeau, dans les deux sens', function (): void {
    $project = E2ELinksSeeder::subjectOf('listen-react')->project;

    $this->artisan('demo:reaction-timing', ['timing' => 'next-morning'])->assertSuccessful();
    expect(Feature::for($project)->value(Timing::class))->toBe(Timing::NEXT_MORNING);

    $this->artisan('demo:reaction-timing', ['timing' => 'immediate'])->assertSuccessful();
    expect(Feature::for($project)->value(Timing::class))->toBe(Timing::IMMEDIATE);
});

it('refuse une valeur qui n’est pas celle du drapeau', function (): void {
    $this->artisan('demo:reaction-timing', ['timing' => 'demain-peut-être'])->assertFailed();
});

it('antidate la dernière réaction d’un jour, pour que le résumé la voie', function (): void {
    $story = E2ELinksSeeder::subjectOf('listen-react')->project->stories->first();
    $member = E2ELinksSeeder::subjectOf('listen-react');

    $reaction = new Reaction(['type' => ReactionType::Thanks, 'comment' => 'Merci.']);
    $reaction->story()->associate($story);
    $reaction->familyMember()->associate($member);
    $reaction->save();

    $this->artisan('demo:reaction-timing', ['--veille' => true])->assertSuccessful();

    $updated = DB::table('reactions')->where('id', $reaction->id)->value('updated_at');

    expect($updated)->not->toBeNull()
        ->and(now()->parse($updated)->isYesterday())->toBeTrue();
});

it('le dit quand il n’y a aucune réaction à antidater', function (): void {
    $this->artisan('demo:reaction-timing', ['--veille' => true])
        ->expectsOutputToContain('aucune réaction')
        ->assertSuccessful();
});

it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:reaction-timing')->assertFailed();
});
