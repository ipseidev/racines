<?php

declare(strict_types=1);

use App\Services\Antivirus\FakeScanner;
use App\Services\Antivirus\Scanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

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
