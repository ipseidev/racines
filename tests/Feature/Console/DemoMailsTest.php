<?php

declare(strict_types=1);

use App\Actions\UpdateBrandSettings;
use App\Models\Story;
use Illuminate\Support\Facades\File;

/**
 * Tous les courriels du produit, rendus d'un coup, pour les relire (T-237).
 *
 * La commande fabrique un décor jetable dans une transaction annulée, rend
 * chaque courriel comme il partirait et l'écrit dans un dossier ; avec
 * `--envoyer`, elle le poste par le mailer configuré. Ce qui se vérifie ici :
 * qu'elle refuse la production, qu'elle écrit un fichier par courriel avec le
 * gabarit de la marque, qu'elle envoie quand on le lui demande, et qu'elle ne
 * laisse rien derrière elle.
 */
beforeEach(function (): void {
    $this->dossier = storage_path('framework/testing/courriels-'.uniqid());
});

afterEach(function (): void {
    File::deleteDirectory($this->dossier);
});

it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:courriels', ['--dossier' => $this->dossier])->assertFailed();
});

it('écrit un fichier HTML par courriel, dans le gabarit de la marque', function (): void {
    app(UpdateBrandSettings::class)->handle(['product_name' => 'Essai Souvenirs']);

    $this->artisan('demo:courriels', ['--dossier' => $this->dossier])->assertSuccessful();

    $files = collect(File::files($this->dossier))->map(fn ($file) => $file->getFilename())->sort()->values();

    expect($files->count())->toBe(10)
        ->and($files)->toContain('question.html', 'invitation-cadeau.html', 'code-usage-unique.html', 'mot-de-passe.html');

    $question = File::get($this->dossier.'/question.html');

    expect($question)->toContain('Essai Souvenirs')
        ->and($question)->toContain('class="question-text')
        ->and($question)->toContain('Bonjour Odette,');
});

it('poste chaque courriel par le mailer configuré quand on le lui demande', function (): void {
    $transport = app('mail.manager')->mailer('array')->getSymfonyTransport();
    $transport->flush();

    $this->artisan('demo:courriels', ['--dossier' => $this->dossier, '--envoyer' => true, '--a' => 'relecture@example.test'])
        ->assertSuccessful();

    $messages = $transport->messages();

    expect($messages)->toHaveCount(10)
        ->and($messages->first()->getOriginalMessage()->getTo()[0]->getAddress())->toBe('relecture@example.test');
});

it('n’envoie rien sans --envoyer', function (): void {
    $transport = app('mail.manager')->mailer('array')->getSymfonyTransport();
    $transport->flush();

    $this->artisan('demo:courriels', ['--dossier' => $this->dossier])->assertSuccessful();

    expect($transport->messages())->toHaveCount(0);
});

it('ne laisse rien derrière elle en base', function (): void {
    $this->artisan('demo:courriels', ['--dossier' => $this->dossier])->assertSuccessful();

    expect(Story::query()->count())->toBe(0);
});
