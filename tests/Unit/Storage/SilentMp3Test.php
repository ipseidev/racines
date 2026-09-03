<?php

declare(strict_types=1);

use Database\Seeders\E2ELinksSeeder;

/**
 * L'audio du décor doit être un MP3 que le navigateur sait lire.
 *
 * Défaut trouvé au checkpoint du bloc 08 : le compteur du lecteur restait à
 * zéro, et l'élément `<audio>` rendait `MEDIA_ERR_SRC_NOT_SUPPORTED`. Les
 * trames semées annonçaient 128 kbit/s dans leur en-tête — ce qui impose des
 * trames de 417 octets — mais n'en faisaient que 404. Aucun décodeur ne peut
 * lire cela, et sans lecture il n'y a ni progression, ni seuil de 30 secondes
 * franchi, ni écoute enregistrée : la moitié du bloc 08 devenait injouable.
 *
 * On fabrique donc des trames **conformes** plutôt qu'approchantes, et on le
 * vérifie ici par le calcul : 32 kbit/s à 44,1 kHz donne des trames de
 * 104 octets qui durent 1152/44100 seconde chacune.
 */
it('fabrique des trames MP3 conformes à leur propre en-tête', function (): void {
    $bytes = E2ELinksSeeder::silentMp3(3);

    // MPEG-1 Layer III, 32 kbit/s, 44,1 kHz, mono, sans CRC.
    expect(substr($bytes, 0, 4))->toBe("\xFF\xFB\x10\xC4");

    $frameBytes = (int) floor(144 * 32000 / 44100);
    expect($frameBytes)->toBe(104)
        // Le fichier est un multiple entier de la taille de trame : pas de
        // trame tronquée à la fin, qui est exactement ce qui cassait l'ancien.
        ->and(strlen($bytes) % $frameBytes)->toBe(0);

    $frames = strlen($bytes) / $frameBytes;
    $seconds = $frames * 1152 / 44100;

    expect($seconds)->toBeGreaterThanOrEqual(3.0)
        ->and($seconds)->toBeLessThan(3.1);
});

it('rend chaque trame identifiable, sans octet perdu entre elles', function (): void {
    $bytes = E2ELinksSeeder::silentMp3(1);

    for ($offset = 0; $offset < strlen($bytes); $offset += 104) {
        expect(substr($bytes, $offset, 2))->toBe("\xFF\xFB", "Trame décalée à l’octet {$offset}.");
    }
});

it('sème un audio assez long pour franchir le seuil des trente secondes', function (): void {
    // Le checkpoint §7.3 demande d'écouter 35 secondes. Un fichier plus court
    // rend la demande impossible à satisfaire.
    $seconds = strlen(E2ELinksSeeder::silentMp3()) / 104 * 1152 / 44100;

    expect($seconds)->toBeGreaterThan(40.0);
});
