<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    fakeMediaStorage();
});

function benchCorpus(): string
{
    $directory = storage_path('app/testing/bench-corpus');

    File::deleteDirectory($directory);
    File::ensureDirectoryExists($directory);

    // Le fournisseur simulé rend toujours la même phrase : on écrit des
    // références qui produisent des WER connus.
    File::put($directory.'/parfait.mp3', 'audio');
    File::put($directory.'/parfait.txt', 'Alors euh je me souviens de la maison de Kerhostin, ma grand-mère y faisait des crêpes.');

    File::put($directory.'/imparfait.mp3', 'audio');
    File::put($directory.'/imparfait.txt', 'Alors je me souviens de la maison de Kerhostin, ma mère y faisait des crêpes.');

    // Sans référence : ignoré, et c'est voulu.
    File::put($directory.'/sans-reference.mp3', 'audio');

    return $directory;
}

it('produit un compte rendu par fournisseur, avec médiane et p90', function (): void {
    $directory = benchCorpus();

    $this->artisan('asr:bench', ['dir' => $directory, '--providers' => 'fake'])
        ->assertSuccessful();

    $path = base_path('docs/spikes/asr-'.now()->toDateString().'.md');

    expect(File::exists($path))->toBeTrue();

    $report = File::get($path);

    expect($report)->toContain('# Banc d’essai ASR')
        ->toContain('Corpus : 2 enregistrement(s)')
        ->toContain('| parfait | fake |')
        ->toContain('| imparfait | fake |')
        ->toContain('WER médian')
        ->toContain('WER p90')
        // La règle de choix est écrite dans le compte rendu : elle ne se
        // décide pas au moment de lire les chiffres.
        ->toContain('hébergement UE');

    // Une transcription identique à la référence donne 0 %.
    expect($report)->toContain('| parfait | fake | 0.0 % |');

    File::delete($path);
    File::deleteDirectory($directory);
});

it('refuse un dossier introuvable', function (): void {
    $this->artisan('asr:bench', ['dir' => storage_path('app/testing/nulle-part')])
        ->assertFailed();
});

it('refuse un corpus sans paire audio + référence', function (): void {
    $directory = storage_path('app/testing/bench-vide');
    File::deleteDirectory($directory);
    File::ensureDirectoryExists($directory);
    File::put($directory.'/seul.mp3', 'audio');

    $this->artisan('asr:bench', ['dir' => $directory])->assertFailed();

    File::deleteDirectory($directory);
});

it('efface les objets temporaires du stockage', function (): void {
    $directory = benchCorpus();
    $storage = fakeMediaStorage();

    $this->artisan('asr:bench', ['dir' => $directory, '--providers' => 'fake'])->assertSuccessful();

    // Le corpus contient la voix de personnes identifiables : il ne reste pas
    // sur le stockage après la mesure.
    expect($storage->exists('bench/parfait.mp3'))->toBeFalse()
        ->and($storage->deletedKeys())->toContain('bench/parfait.mp3', 'bench/imparfait.mp3');

    File::delete(base_path('docs/spikes/asr-'.now()->toDateString().'.md'));
    File::deleteDirectory($directory);
});

it('refuse un fournisseur inconnu', function (): void {
    $directory = benchCorpus();

    try {
        $this->artisan('asr:bench', ['dir' => $directory, '--providers' => 'inventé'])
            ->assertFailed();
    } finally {
        // Le `throws` interrompt le test : sans `finally`, le corpus simulé
        // restait sur le disque après la suite.
        File::deleteDirectory($directory);
    }
})->throws(RuntimeException::class);
