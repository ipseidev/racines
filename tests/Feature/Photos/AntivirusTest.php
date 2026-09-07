<?php

declare(strict_types=1);

use App\Health\ClamavCheck;
use App\Services\Antivirus\FakeScanner;
use App\Services\Antivirus\NullScanner;
use App\Services\Antivirus\Scanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Spatie\Health\Enums\Status;

/**
 * Le contrôle antivirus des fichiers déposés.
 *
 * Le doc 04 §12 l'exige, et la raison est concrète : les photos arrivent des
 * téléphones de toute une famille, y compris d'un cousin dont l'appareil est
 * infecté. Ce qu'on stocke est ensuite servi à d'autres membres de la famille
 * — et un fichier vérolé qui traverse notre stockage devient notre
 * responsabilité.
 *
 * Le scanner passe par un port, comme l'ASR, le LLM et le paiement : ClamAV
 * parle un protocole de socket que rien dans Laravel n'intercepte, et un test
 * qui aurait oublié un doublon aurait attendu une connexion pendant trente
 * secondes avant d'échouer sans dire pourquoi.
 *
 * La chaîne EICAR est le fichier de test standard des antivirus : inoffensif,
 * et reconnu par tous. On ne met pas de vrai virus dans un dépôt.
 */
const EICAR = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

it('accepte un fichier sain', function (): void {
    $file = UploadedFile::fake()->createWithContent('photo.jpg', 'du contenu ordinaire');

    expect(app(Scanner::class)->isClean($file))->toBeTrue();
});

it('refuse un fichier contenant la signature de test', function (): void {
    $file = UploadedFile::fake()->createWithContent('piege.jpg', EICAR);

    expect(app(Scanner::class)->isClean($file))->toBeFalse();
});

it('journalise le refus sans conserver le fichier', function (): void {
    Log::spy();

    $file = UploadedFile::fake()->createWithContent('piege.jpg', EICAR);
    $path = $file->getRealPath();

    app(Scanner::class)->isClean($file);

    // Ce qui part au journal : le nom, la taille, l'empreinte. Jamais le
    // contenu — un journal n'est pas un endroit où déposer un fichier
    // suspect, et le nom suffit à répondre à la famille.
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $message === 'antivirus.rejected'
            && $context['file_name'] === 'piege.jpg'
            && ! array_key_exists('content', $context));

    expect($path)->toBeString();
});

it('nomme le scanner par la configuration, jamais par l’environnement', function (): void {
    // Leçon T-61 : un fournisseur déduit de l'environnement finit par être le
    // faux en production, ou le vrai dans un test.
    config()->set('services.antivirus.scanner', 'fake');
    app()->forgetInstance(Scanner::class);

    expect(app(Scanner::class))->toBeInstanceOf(FakeScanner::class);
});

it('refuse de démarrer sur un scanner inconnu', function (): void {
    config()->set('services.antivirus.scanner', 'inventé');
    app()->forgetInstance(Scanner::class);

    // Échouer fort plutôt que de laisser passer : un scanner inconnu qui
    // rendrait « propre » par défaut serait pire que pas de scanner du tout.
    expect(fn () => app(Scanner::class))->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| Le scanner débranché (D-12, T-216)
|--------------------------------------------------------------------------
|
| Le démon n'a jamais été installé en production — la ligne 48 du bloc 16 est
| restée à faire — et `ClamavScanner` refusant tout quand il ne joint personne,
| **aucune photo n'a jamais pu être déposée en ligne**. Le fondateur a tranché
| de débrancher franchement plutôt que d'installer le démon (D-12).
|
| Franchement, c'est-à-dire par un driver qui porte son nom. `fake` aurait
| débloqué les dépôts en une variable, mais il prétend reconnaître l'EICAR :
| une sonde verte, un scanner qui répond, et personne pour se souvenir dans
| six mois que rien n'est contrôlé. Le faux et le débranché sont deux états
| différents et se lisent différemment.
|
*/

it('résout le scanner débranché sur « off »', function (): void {
    config()->set('services.antivirus.scanner', 'off');
    app()->forgetInstance(Scanner::class);

    expect(app(Scanner::class))->toBeInstanceOf(NullScanner::class);
});

it('laisse passer ce qu’un vrai scanner refuserait', function (): void {
    config()->set('services.antivirus.scanner', 'off');
    app()->forgetInstance(Scanner::class);

    // La signature de test passe. C'est l'aveu du driver : il ne scanne rien,
    // et ce test est là pour que personne ne le prenne pour un scanner.
    $file = UploadedFile::fake()->createWithContent('piege.jpg', EICAR);

    expect(app(Scanner::class)->isClean($file))->toBeTrue();
});

it('écrit au journal chaque fichier admis sans contrôle', function (): void {
    config()->set('services.antivirus.scanner', 'off');
    app()->forgetInstance(Scanner::class);

    Log::spy();

    $file = UploadedFile::fake()->createWithContent('photo.jpg', 'du contenu ordinaire');
    app(Scanner::class)->isClean($file);

    // La trace est la contrepartie du débranchement : le jour où l'on
    // rebranche, la question sera « qu'est-ce qui est entré pendant ? », et
    // elle n'a de réponse que si on l'a écrite au moment du dépôt.
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $message === 'antivirus.disabled'
            && $context['file_name'] === 'photo.jpg'
            && $context['decision'] === 'D-12');
});

it('dit « débranché » et non « doublé » sur la sonde de santé', function (): void {
    // Le piège que ce test garde : `ClamavCheck` rendait `ok` avec le résumé
    // « doublé » pour tout scanner qui n'est pas `clamav`. En production
    // débranchée, ce mot aurait fait lire un environnement de test là où il y
    // a une décision assumée — un vert qui raconte autre chose que l'état.
    config()->set('services.antivirus.scanner', 'off');

    $resultat = (new ClamavCheck)->run();

    expect($resultat->shortSummary)->toBe('débranché (D-12)')
        ->and($resultat->status)->toBe(Status::ok());
});
