<?php

declare(strict_types=1);

use App\Actions\RecordConsent;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use App\Enums\TokenType;
use App\Models\Consent;
use App\Models\Narrator;
use App\Services\Tokens\TokenService;

/**
 * La déclaration d'avance, arrêtée depuis son espace (D-10).
 *
 * Le dossier n'autorise la déclaration que « révocable d'un geste ». Ces
 * tests tiennent le geste : qu'il existe, qu'il ne demande pas de code, et
 * qu'il ne touche pas rétroactivement à ce qui est déjà parti.
 */
function declaredNarrator(bool $declared = true): array
{
    $narrator = Narrator::factory()->primary()->create();
    $project = $narrator->project;
    $project->declared_sharing_at = $declared ? now()->subWeek() : null;
    $project->save();

    $issued = app(TokenService::class)->issue(
        TokenType::NarratorSpace,
        $narrator,
        ['read', 'withdraw'],
    );

    return [$project, $narrator, $issued->plain];
}

it('arrête la déclaration d’un seul geste, sans code', function (): void {
    [$project, $narrator, $plain] = declaredNarrator();

    app(RecordConsent::class)->handle(
        $narrator,
        $project,
        ConsentKind::DeclaredSharing,
        ConsentChannel::Web,
    );

    // Pas de `sensitive` sur ce chemin : le geste rend les récits suivants
    // plus privés, et exiger un second facteur pour devenir plus discrète
    // serait la garde à l'envers.
    $this->post("/n/{$plain}/partage/arreter")->assertRedirect();

    expect($project->refresh()->declared_sharing_at)->toBeNull();
});

it('trace l’arrêt comme une révocation de consentement', function (): void {
    [$project, $narrator, $plain] = declaredNarrator();

    app(RecordConsent::class)->handle(
        $narrator,
        $project,
        ConsentKind::DeclaredSharing,
        ConsentChannel::Web,
    );

    $this->post("/n/{$plain}/partage/arreter")->assertRedirect();

    // La ligne d'origine n'est pas modifiée : la révocation en ajoute une,
    // pour que l'historique dise ce qui a été accordé et quand cela a cessé.
    expect(Consent::query()
        ->where('subject_id', $narrator->id)
        ->where('kind', ConsentKind::DeclaredSharing->value)
        ->where('status', ConsentStatus::Revoked->value)
        ->exists())->toBeTrue();
});

it('reste sans effet quand rien n’a été déclaré', function (): void {
    [$project, , $plain] = declaredNarrator(declared: false);

    // Rejouer le geste ne doit pas lever d'exception faute de consentement
    // à révoquer : un bouton pressé deux fois n'est pas une faute.
    $this->post("/n/{$plain}/partage/arreter")->assertRedirect();

    expect($project->refresh()->declared_sharing_at)->toBeNull();
});

it('exige un code pour reprendre, parce que reprendre expose', function (): void {
    [$project, , $plain] = declaredNarrator(declared: false);

    $this->post("/n/{$plain}/partage/reprendre")
        ->assertRedirect("/n/{$plain}/code");

    expect($project->refresh()->declared_sharing_at)->toBeNull();
});
