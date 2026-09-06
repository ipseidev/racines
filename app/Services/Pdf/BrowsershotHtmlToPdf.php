<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use RuntimeException;
use Spatie\Browsershot\Browsershot;

/**
 * Le port `HtmlToPdf`, servi par Chromium via Browsershot.
 *
 * Trois réglages ne sont pas des préférences :
 *
 *  - **Marges à zéro côté navigateur.** Elles vivent dans la règle `@page`
 *    du CSS, où Paged.js les lit pour composer les pages ; les déclarer ici
 *    aussi les cumulerait, et le texte se retrouverait au milieu de la page
 *    sans qu'on comprenne pourquoi.
 *  - **Attendre `PAGEDJS_DONE`.** Chromium rendrait la page avant que
 *    Paged.js n'ait fini de paginer : le PDF sortirait avec une seule page
 *    interminable, ce qui ressemble à un bug de gabarit et n'en est pas un.
 *  - **Cinq minutes de délai.** Un livre de soixante pages avec ses photos
 *    dépasse largement les trente secondes par défaut, et l'échec se
 *    présenterait comme une exception de processus, pas comme un dépassement.
 *
 * Les chemins de Node et de Chromium viennent de l'environnement : l'image de
 * développement, l'intégration continue et le serveur ne les placent pas au
 * même endroit, et les deviner produirait une panne à la première mise en
 * production.
 */
final readonly class BrowsershotHtmlToPdf implements HtmlToPdf
{
    public function render(string $html, PdfOptions $options): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bat').'.pdf';

        /*
         * Le HTML passe par un **fichier**, pas par `Browsershot::html()`.
         *
         * Cette dernière refuse tout contenu où figure la chaîne `file:`,
         * sans distinguer une URL d'un commentaire — et le paquet Paged.js
         * que nous incrustons en contient un : « If the only part of the root
         * that is left is the scheme (i.e. http://, file:///, etc.) ». Le
         * rendu échouait donc sur une garde de sécurité que rien dans notre
         * page ne déclenche vraiment (T-197).
         *
         * `setHtmlFromFilePath` est la voie prévue par le paquet pour ce cas,
         * et c'est d'ailleurs ce qu'il fait lui-même en interne. Rien ne se
         * perd au passage : la page n'a aucune ressource relative, tout y est
         * incrusté.
         */
        $source = tempnam(sys_get_temp_dir(), 'bat').'.html';
        file_put_contents($source, $html);

        $shot = (new Browsershot)
            ->setHtmlFromFilePath($source)
            ->paperSize($options->widthMm, $options->heightMm, 'mm')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            /*
             * Deux réglages imposés par le conteneur, pas par le livre.
             *
             * `noSandbox` : le bac à sable de Chromium demande des espaces de
             * noms utilisateur que l'image n'accorde pas à l'utilisateur
             * `sail`, et le navigateur meurt au démarrage avec une trace de
             * pile de deux cents lignes qui ne le dit pas (T-197). Le risque
             * accepté est mesuré : la page rendue est **la nôtre**, sans
             * aucune ressource distante, avec du texte échappé par Blade et
             * des images déjà passées par l'antivirus puis réencodées.
             *
             * `disable-dev-shm-usage` : Docker alloue 64 Mo à `/dev/shm`, et
             * un livre de soixante pages avec ses photos dépasse cela — le
             * plantage arrive alors au milieu du rendu, pas au démarrage,
             * donc au pire endroit pour être compris.
             */
            ->noSandbox()
            ->addChromiumArguments(['disable-dev-shm-usage'])
            ->waitUntilNetworkIdle()
            ->waitForFunction($options->waitForFunction, timeout: $options->timeoutSeconds * 1000)
            ->timeout($options->timeoutSeconds);

        $node = self::binary('BROWSERSHOT_NODE_PATH');
        $npm = self::binary('BROWSERSHOT_NPM_PATH');
        $chrome = self::binary('BROWSERSHOT_CHROME_PATH');

        if ($node !== null) {
            $shot->setNodeBinary($node);
        }

        if ($npm !== null) {
            $shot->setNpmBinary($npm);
        }

        if ($chrome !== null) {
            $shot->setChromePath($chrome);
        }

        try {
            $shot->savePdf($path);
        } finally {
            @unlink($source);
        }

        if (! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('Le rendu du BAT n’a produit aucun fichier.');
        }

        return $path;
    }

    private static function binary(string $key): ?string
    {
        $value = config('services.browsershot.'.mb_strtolower(mb_substr($key, mb_strlen('BROWSERSHOT_'))));

        return is_string($value) && $value !== '' ? $value : null;
    }
}
