<?php

declare(strict_types=1);

use App\Actions\RequestExport;
use App\Enums\ExportKind;
use App\Enums\ExportScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Le pack hors-ligne.
 *
 * C'est la **contrepartie de la durée d'engagement** (D-8). Le colophon du
 * livre annonce une date après laquelle les QR cesseront de fonctionner ;
 * sans ce pack, cette phrase serait une menace. Avec lui, elle devient une
 * information : le jour où nous nous arrêterons, une clé USB continuera de
 * jouer les voix.
 *
 * D'où la seule exigence qui compte ici, et elle est absolue : **la page
 * s'ouvre sans réseau et sans nous**. Un `index.html` qui appellerait une
 * police, un script ou un audio distant ne vaudrait rien le jour où il
 * servirait — c'est-à-dire précisément le jour où le site n'existe plus.
 */
beforeEach(function (): void {
    Notification::fake();
});

it('écrit une page d’accueil autonome', function (): void {
    [$project] = projetExportable();

    $entrees = contenuDeLArchive(construire(app(RequestExport::class)->handle(
        $project,
        ExportScope::Initiator,
        ExportKind::OfflinePack,
    )));

    expect($entrees)->toHaveKey('hors-ligne/index.html');

    $html = $entrees['hors-ligne/index.html'];

    // Aucune ressource distante : ni police, ni script, ni feuille de style,
    // ni audio servi par une URL. Le jour où cette page sert, il n'y a plus
    // de site pour répondre.
    expect($html)->not->toMatch('/<(script|link|img|audio|source)[^>]+(src|href)=["\']https?:/i')
        ->and($html)->not->toContain('narrae.fr');
});

it('liste les histoires et pointe les fichiers du dossier', function (): void {
    [$project] = projetExportable();

    $entrees = contenuDeLArchive(construire(app(RequestExport::class)->handle(
        $project,
        ExportScope::Initiator,
        ExportKind::OfflinePack,
    )));

    $html = $entrees['hors-ligne/index.html'];

    expect($html)->toContain('Le fournil')
        // Un chemin **relatif**, qui remonte dans le dossier des histoires :
        // c'est ce qui marche quand on double-clique le fichier depuis une
        // clé USB, et un chemin absolu ne marcherait nulle part.
        ->and($html)->toContain('../histoires/');
});

it('emporte les mêmes fichiers que l’export complet', function (): void {
    [$project] = projetExportable();

    $entrees = contenuDeLArchive(construire(app(RequestExport::class)->handle(
        $project,
        ExportScope::Initiator,
        ExportKind::OfflinePack,
    )));

    // Le pack n'est pas un export au rabais : il contient tout, plus le
    // lecteur. Une famille ne doit pas avoir à choisir lequel conserver.
    expect($entrees)->toHaveKey('LISEZ-MOI.txt')
        ->toHaveKey('manifest.json')
        ->toHaveKey('histoires/01-le-fournil/texte.txt');
});
