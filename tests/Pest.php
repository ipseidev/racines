<?php

declare(strict_types=1);

use App\Services\Analytics\Analytics;
use App\Services\Analytics\LogAnalytics;
use App\Services\Sms\FakeSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Storage\FakeMediaStorage;
use App\Services\Storage\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        // Aucun test ne doit appeler le réseau : tout fournisseur externe a un Fake.
        Http::preventStrayRequests();
    })
    ->in('Feature');

// Les tests unitaires touchent la base dès le bloc 02 : la machine d'états
// et les actions de domaine s'éprouvent sur de vraies lignes.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
    })
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Expéditeur de SMS observable, substitué dans le conteneur.
 *
 * Déclaré ici et non dans chaque fichier : Pest charge tous les fichiers de
 * test dans l'espace global, et deux déclarations du même nom se percutent.
 */
function fakeSms(): FakeSmsSender
{
    $sender = new FakeSmsSender;
    app()->instance(SmsSender::class, $sender);

    return $sender;
}

/**
 * Les mesures, en mémoire.
 *
 * Le même parti que pour le stockage et le SMS : une implémentation
 * inspectable, liée dans le conteneur, plutôt qu'un espion de bibliothèque.
 */
function fakeAnalytics(): LogAnalytics
{
    $analytics = new LogAnalytics;
    app()->instance(Analytics::class, $analytics);

    return $analytics;
}

/**
 * Charge une réponse de fournisseur enregistrée sous `tests/Fixtures/`.
 *
 * @return array<string, mixed>
 */
function providerFixture(string $name): array
{
    $decoded = json_decode(
        (string) file_get_contents(base_path("tests/Fixtures/{$name}.json")),
        true,
    );

    return is_array($decoded) ? $decoded : [];
}

/**
 * Stockage de médias en mémoire, substitué dans le conteneur.
 */
function fakeMediaStorage(): FakeMediaStorage
{
    $storage = new FakeMediaStorage;
    app()->instance(MediaStorage::class, $storage);

    return $storage;
}
