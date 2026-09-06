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

        $shot = Browsershot::html($html)
            ->paperSize($options->widthMm, $options->heightMm, 'mm')
            ->margins(0, 0, 0, 0)
            ->showBackground()
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

        $shot->savePdf($path);

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
