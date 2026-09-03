<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * Le fichier déposé a été refusé par l'antivirus.
 *
 * Le message est destiné à être montré, et il évite deux écueils : accuser la
 * personne, et faire peur. Quelqu'un dont le téléphone a un fichier vérolé
 * n'y est pour rien, et souvent ne le sait pas.
 */
final class InfectedUpload extends RuntimeException
{
    public static function make(): self
    {
        return new self(
            'Nous n’avons pas pu accepter ce fichier : notre contrôle de '
            .'sécurité l’a signalé. Essayez une autre photo, ou envoyez-la '
            .'depuis un autre appareil.',
        );
    }
}
