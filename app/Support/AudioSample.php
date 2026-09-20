<?php

declare(strict_types=1);

namespace App\Support;

/**
 * L'extrait d'écoute des pages publiques.
 *
 * Rendu seulement si le fichier est là : une page qui affiche un bouton
 * « Écouter » au-dessus d'un fichier absent est pire que pas de bouton du
 * tout. Deux pages s'en servent — l'accueil et le tunnel de découverte — et
 * une seconde copie de ce test aurait fini par diverger sur la seule chose
 * qui compte, la présence réelle du fichier.
 */
final class AudioSample
{
    /**
     * @return array{src: string, disclosed: bool}|null
     */
    public static function hero(): ?array
    {
        $path = (string) config('product.landing.hero_sample');

        if ($path === '' || ! is_file(public_path($path))) {
            return null;
        }

        return [
            'src' => '/'.ltrim($path, '/'),
            'disclosed' => (bool) config('product.landing.hero_sample_disclosed'),
        ];
    }
}
