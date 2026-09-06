<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Health\Notifications\CheckFailedNotification;

uses(RefreshDatabase::class);

/**
 * L'alerte, sans service externe payant.
 *
 * Oh Dear était prévu par la feuille du bloc 16 ; il est écarté sur son coût
 * (T-201). Ce qu'il faisait se sépare en deux, et **la moitié qu'on remplace
 * ici est celle qui peut l'être** : « un contrôle est au rouge » se dit
 * depuis le serveur. L'autre moitié — « le serveur ne répond plus » — ne peut
 * par construction pas venir de lui.
 *
 * Ce que ce test garde, c'est qu'on n'a pas simplement retiré Oh Dear en
 * laissant les contrôles tourner pour personne : un échec **part** quelque
 * part, et il part vers une adresse que quelqu'un relève.
 */
it('envoie l’alerte par courriel', function (): void {
    expect(config('health.notifications.notifications'))
        ->toHaveKey(CheckFailedNotification::class)
        ->and(config('health.notifications.notifications')[CheckFailedNotification::class])
        ->toContain('mail');
});

it('écrit à une adresse que quelqu’un relève', function (): void {
    config()->set('health.notifications.mail.to', '');

    // Une alerte envoyée à une boîte vide est une alerte perdue ; en
    // production, l'adresse est celle du support.
    expect(config('health.notifications.enabled'))->toBeTrue();

    config()->set('health.notifications.mail.to', 'support@exemple.test');

    expect(config('health.notifications.mail.to'))->toBe('support@exemple.test');
});

it('se limite à un message par heure', function (): void {
    // Sans limite, un contrôle au rouge enverrait soixante courriels par
    // heure — et la soixante-et-unième alerte, celle qui compte, serait lue
    // comme les précédentes : pas du tout.
    expect(config('health.notifications.throttle_notifications_for_minutes'))->toBe(60);
});

it('prévient aussi sur un avertissement, pas seulement sur une panne', function (): void {
    // Un disque à 80 % et un stockage à trois secondes ne sont pas des
    // pannes : ce sont les heures qu'on a pour agir avant d'en avoir une.
    expect(config('health.notifications.only_on_failure'))->toBeFalse();
});

it('laisse une route publique pour un appel de l’extérieur', function (): void {
    // `/up` ne dit que « le cadre démarre ». C'est exactement ce qu'un
    // service de surveillance gratuit doit pouvoir appeler sans secret, et
    // rien de plus : la liste des services vit derrière `/health`.
    $reponse = $this->get('/up');

    $reponse->assertOk();

    expect($reponse->getContent())
        ->not->toContain('redis')
        ->not->toContain('pgsql')
        ->not->toContain('database');
});
