<?php

declare(strict_types=1);

use App\Health\AuditChainCheck;
use App\Health\ClamavCheck;
use App\Health\R2ReachableCheck;
use App\Health\ReplicationLagCheck;
use App\Models\Recording;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * L'endpoint de santé, interrogé par Oh Dear.
 *
 * Deux exigences, et la seconde est celle qu'on oublie. **Il doit répondre
 * juste** : un endpoint qui renvoie vert quoi qu'il arrive est pire qu'aucune
 * supervision, puisqu'il fait dormir. Et **il doit être fermé** : la liste
 * des services d'un système, avec leurs temps de réponse et l'état de sa
 * file, est un plan pour qui cherche par où entrer.
 */
// Le secret vient de `phpunit.xml` : l'endpoint n'existe que s'il est posé,
// et les routes sont enregistrées au démarrage — le régler ici arriverait
// trop tard.
beforeEach(function (): void {
    config()->set('health.oh_dear_endpoint.secret', 'secret-de-test');
});

it('refuse un appel sans le secret', function (): void {
    $this->getJson('/health')->assertForbidden();
});

it('refuse un secret faux', function (): void {
    $this->getJson('/health', ['oh-dear-health-check-secret' => 'au-hasard'])
        ->assertForbidden();
});

it('répond avec le secret', function (): void {
    // Le secret voyage dans un en-tête, jamais dans l'URL : un secret en
    // paramètre finit dans les journaux du serveur et dans l'historique du
    // navigateur — même règle que pour les jetons porteurs (doc 04 §12).
    $this->getJson('/health', ['oh-dear-health-check-secret' => 'secret-de-test'])
        ->assertOk()
        ->assertJsonStructure(['finishedAt', 'checkResults']);
});

/*
 * Les quatre contrôles propres au produit doivent être **déclarés**. Un
 * contrôle qu'on retire « le temps de déboguer » et qu'on oublie de remettre
 * est la panne suivante.
 */
it('surveille le stockage, l’audit, l’antivirus et la réplication', function (): void {
    $noms = array_map(
        fn (object $check): string => $check::class,
        config('health.checks'),
    );

    expect($noms)->toContain(R2ReachableCheck::class)
        ->toContain(AuditChainCheck::class)
        ->toContain(ClamavCheck::class)
        ->toContain(ReplicationLagCheck::class);
});

it('signale un enregistrement confirmé et non répliqué depuis une heure', function (): void {
    // `confirmed()` pose `confirmed_at` à maintenant dans un `afterMaking` :
    // on antidate après coup, sinon l'état de la fabrique gagne.
    Recording::factory()->confirmed()->create()
        ->forceFill(['confirmed_at' => now()->subHours(2), 'replicated_at' => null])->save();

    $resultat = (new ReplicationLagCheck)->run();

    // La promesse de non-perte n'est plus tenue, même si rien n'est encore
    // perdu : c'est le moment d'être prévenu, pas après l'incident.
    expect($resultat->status->value)->toBe('failed')
        ->and($resultat->notificationMessage)->toContain('non-perte');
});

it('ne dit rien quand la réplication suit', function (): void {
    Recording::factory()->confirmed()->create()
        ->forceFill(['confirmed_at' => now()->subHours(2), 'replicated_at' => now()->subHours(2)])->save();

    // Et surtout : un enregistrement confirmé il y a deux minutes et pas
    // encore répliqué est normal, pas une alerte.
    Recording::factory()->confirmed()->create()
        ->forceFill(['confirmed_at' => now()->subMinutes(2), 'replicated_at' => null])->save();

    expect((new ReplicationLagCheck)->run()->status->value)->toBe('ok');
});

it('voit une chaîne d’audit intacte', function (): void {
    expect((new AuditChainCheck)->run()->status->value)->toBe('ok');
});
