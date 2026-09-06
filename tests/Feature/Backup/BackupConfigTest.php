<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;

/**
 * La configuration des sauvegardes.
 *
 * Un test de **configuration** et non de comportement, et c'est délibéré :
 * une sauvegarde mal réglée ne se voit pas — elle tourne toutes les nuits,
 * réussit, et le jour où on en a besoin il manque la base, ou l'archive est
 * en clair sur un stockage qu'on ne contrôle plus. Le seul moment où l'erreur
 * apparaît est le pire moment possible.
 *
 * Ce que le test garde, ce sont les quatre décisions qui rendent une
 * restauration possible : **où** (un stockage objet, jamais le disque du
 * serveur qui vient de brûler), **quoi** (la base, et les fichiers privés),
 * **combien de temps** (90 jours, la durée publiée), et **chiffrée**.
 */
it('sauvegarde vers le stockage objet, jamais sur le serveur lui-même', function (): void {
    $disks = config('backup.backup.destination.disks');

    // Le disque local est exactement ce qu'on perd dans l'incident qui
    // justifie la sauvegarde.
    expect($disks)->toBe(['r2_backups'])
        ->and($disks)->not->toContain('local');
});

it('emporte la base et les fichiers privés, jamais les journaux', function (): void {
    $include = config('backup.backup.source.files.include');
    $exclude = config('backup.backup.source.files.exclude');

    expect($include)->toContain(storage_path('app/private'))
        ->and($exclude)->toContain(storage_path('logs'))
        ->and($exclude)->toContain(base_path('vendor'))
        ->and($exclude)->toContain(base_path('node_modules'));

    // La base est la seule chose vraiment irremplaçable : les médias vivent
    // sur R2 et y sont répliqués, le code est dans Git.
    expect(config('backup.backup.source.databases'))->toContain('pgsql');
});

it('chiffre l’archive', function (): void {
    // Une archive de récits de famille, en clair chez un hébergeur, est la
    // fuite qu'aucune promesse ne rattrape.
    expect(config('backup.backup.encryption'))->toBe('default');
});

/*
 * Le mot de passe ne peut pas être vérifié par un test de configuration : il
 * n'est pas renseigné en développement, et le rendre obligatoire partout
 * empêcherait de lancer la suite. Sans lui, pourtant, `laravel-backup`
 * **n'échoue pas** — il écrit une archive en clair, et tout paraît normal.
 *
 * La garde est donc au démarrage, comme celle du secret Stripe (T-171) : la
 * faute se voit au déploiement, là où elle se corrige en une ligne.
 */
it('refuse de démarrer en production sans mot de passe d’archive', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config()->set('backup.backup.password', '');

    expect(fn () => AppServiceProvider::guardBackupPassword())
        ->toThrow(RuntimeException::class, 'BACKUP_ARCHIVE_PASSWORD');
});

it('laisse démarrer une machine de développement sans lui', function (): void {
    config()->set('backup.backup.password', '');

    AppServiceProvider::guardBackupPassword();

    expect(true)->toBeTrue();
});

it('garde quatre-vingt-dix jours, la durée publiée', function (): void {
    $strategy = config('backup.cleanup.default_strategy');

    // La politique de confidentialité annonce 90 jours : une rétention plus
    // courte trahirait l'engagement, une plus longue garderait des données
    // qu'on a promis d'effacer.
    expect($strategy['keep_all_backups_for_days'])->toBe(7)
        ->and($strategy['keep_daily_backups_for_days'])->toBe(90);
});

it('prévient quelqu’un quand une sauvegarde échoue', function (): void {
    $notifications = config('backup.notifications.notifications');

    // Une sauvegarde qui échoue en silence est pire qu'une absence de
    // sauvegarde : elle donne la tranquillité sans la protection.
    expect($notifications[BackupHasFailedNotification::class])
        ->not->toBeEmpty();
});
