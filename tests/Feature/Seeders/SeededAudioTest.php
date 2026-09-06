<?php

declare(strict_types=1);

use App\Models\Recording;
use App\Services\Storage\MediaStorage;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Throwable;

uses(RefreshDatabase::class);

/**
 * Un enregistrement semé pointe sur un objet qui existe.
 *
 * Trouvé en jouant le point 2 du bloc 11 : le bouton « Écouter » du
 * back-office menait à un `NoSuchKey` de MinIO. Le décor créait des lignes
 * `recordings` avec un chemin, mais ne téléversait un objet que pour les
 * scénarios marqués `audio` — ceux dont la page famille a besoin. Partout
 * ailleurs, le chemin désignait le vide.
 *
 * Ce n'est pas cosmétique : c'est le quatrième défaut de décor en deux jours
 * à rendre une vérification humaine impossible, et celui-ci ferait perdre du
 * temps à qui déboguerait le lecteur du back-office en croyant le produit
 * fautif (T-183).
 */
it('sème un objet réel derrière chaque enregistrement', function (): void {
    $this->seed(E2ELinksSeeder::class);

    $storage = app(MediaStorage::class);
    $orphelins = [];

    foreach (Recording::query()->get() as $recording) {
        $key = $recording->derived_mp3_path ?? $recording->original_path;

        if (! is_string($key) || $key === '') {
            continue;
        }

        try {
            $storage->head($key);
        } catch (Throwable) {
            $orphelins[] = $key;
        }
    }

    expect($orphelins)->toBeEmpty();
});
