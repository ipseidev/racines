<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Une écriture Inertia qui échoue reste sur la page, avec un message.
 *
 * Les pages d'erreur en Blade existent et sont volontairement sans JavaScript
 * (T-199) : elles doivent tenir quand le bundle ne se charge pas. Mais Inertia
 * ne sait pas consommer du HTML — sur un `POST`, sa réponse à ces pages est
 * « All Inertia Requests must receive a valid Inertia response », un message
 * de développeur affiché à un narrateur qui vient de cliquer « Partager avec
 * mes proches » (T-229). C'est la même leçon que T-168, où « Payer » était
 * inerte parce qu'Inertia ne peut pas suivre une redirection ordinaire.
 */
it('renvoie sur la page avec un message plutôt qu’une page d’erreur', function (): void {
    $reponse = $this->withHeaders(['X-Inertia' => 'true', 'Referer' => '/une-page'])
        ->post('/r/'.str_repeat('a', 43).'/share-decision', ['decision' => 'share']);

    // Une redirection, qu'Inertia sait suivre — jamais du HTML.
    $reponse->assertRedirect()
        ->assertSessionHas('error');

    expect($reponse->getContent())->not->toContain('Inertia');
});

it('nomme la session expirée, le cas le plus fréquent', function (): void {
    /*
     * La page d'enregistrement reste ouverte longtemps — on y parle, on
     * réécoute, on recommence — et le jeton de formulaire vieillit pendant ce
     * temps. C'est la première cause d'écriture refusée sur cet écran.
     */
    $reponse = $this->withHeaders(['X-Inertia' => 'true', 'Referer' => '/une-page'])
        ->post('/r/'.str_repeat('a', 43).'/share-decision', ['decision' => 'share']);

    expect(session('error'))->toBeString()
        ->and(session('error'))->not->toContain('Inertia')
        // Pas un mot d'anglais ni de terme technique dans ce qu'on affiche.
        ->and(session('error'))->not->toContain('419');
});

it('laisse la page Blade répondre à une navigation, script-free', function (): void {
    // Une navigation `GET` qui échoue est un lien cassé — un défaut à
    // corriger, pas une situation à habiller — et la page Blade doit tenir
    // même quand le bundle ne se charge pas.
    $html = $this->get('/une-adresse-qui-nexiste-pas')->getContent();

    expect($html)->not->toContain('<script');
});

it('n’intercepte pas une écriture qui réussit', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    Narrator::factory()->create(['project_id' => $project->id, 'is_primary' => true]);
    $story = Story::factory()->recorded()->create(['project_id' => $project->id]);

    expect($story->refresh()->share_decision)->toBeNull();
});
