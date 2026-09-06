<?php

declare(strict_types=1);

use App\Exceptions\Domain\ObjectNotStored;
use App\Services\Storage\MediaStorage;

/*
 * Ce qu'on vérifie ici n'est pas le verdict — il dépend d'un décor et n'a de
 * sens qu'en production. C'est que la commande **arrive au bout** : elle sera
 * lancée sur un serveur, rarement, souvent dans l'urgence, et une clé de
 * configuration mal orthographiée n'a alors aucune chance d'être vue avant.
 */

it('parcourt toute la chaîne sans se casser', function () {
    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('Encaisser')
        ->expectsOutputToContain('Livrer')
        ->expectsOutputToContain('Joindre les gens')
        ->expectsOutputToContain('Transformer la voix en texte')
        ->expectsOutputToContain('Stockage des voix')
        ->run();
});

it('dit ce que perd le client quand une clé manque, pas ce qui manque', function () {
    config()->set('cashier.secret', '');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('le bouton « Payer » ne mène nulle part')
        ->run();
});

it('écrit puis relit le stockage, et ne laisse rien derrière lui', function () {
    $this->artisan('prod:check', ['--rapide' => true])->run();

    expect(fn () => app(MediaStorage::class)->head('health/prod-check.txt'))
        ->toThrow(ObjectNotStored::class);
});
