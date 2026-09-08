<?php

declare(strict_types=1);

namespace App\Http\Controllers\Links;

use App\Support\Brand;
use Illuminate\Http\Response;

/**
 * Fiche contact de la marque, à importer dans le téléphone du narrateur.
 *
 * C'est une mesure d'anti-hameçonnage concrète (doc 04 §9) : un SMS venant
 * d'un contact enregistré s'affiche avec un nom, pas avec un numéro inconnu.
 * Une personne de 82 ans distingue alors d'un coup d'œil notre message d'une
 * tentative d'arnaque qui, elle, viendra toujours d'un inconnu.
 */
final class VcardController
{
    public function __invoke(): Response
    {
        $lines = array_filter([
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:;'.Brand::name().';;;',
            'FN:'.Brand::name(),
            'ORG:'.Brand::name(),
            Brand::settings()->support_phone === null ? null : 'TEL;TYPE=WORK,VOICE:'.Brand::settings()->support_phone,
            self::smsNumber() === null ? null : 'TEL;TYPE=CELL:'.self::smsNumber(),
            'EMAIL;TYPE=INTERNET:'.Brand::supportEmail(),
            'URL:'.config('app.url'),
            'NOTE:'.__('public.vcard.note'),
            'END:VCARD',
        ]);

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="contact.vcf"',
        ]);
    }

    /**
     * Le numéro d'où partent nos SMS, et **seulement** s'il en est un.
     *
     * `TWILIO_FROM` est le repli des pays qui refusent un expéditeur
     * alphanumérique ; rien n'empêchait d'y écrire le nom de la marque, et un
     * `.env` de production en portait un. La fiche contact partait alors avec
     * ce nom en `TEL;TYPE=CELL` — une carte qu'aucun téléphone n'importe, donc
     * l'inverse exact de ce que le §9 du doc 04 lui demande : que le narrateur
     * reconnaisse l'expéditeur avant d'ouvrir le message. T-211 nommait déjà
     * l'exigence — un numéro en E.164 — sans que rien ne la tienne.
     *
     * Omis plutôt que corrigé : une carte sans mobile s'importe, une carte
     * avec un mobile faux se garde et trompe. `prod:check` dit le reste.
     */
    private static function smsNumber(): ?string
    {
        $from = config('services.twilio.from');

        return is_string($from) && str_starts_with($from, '+') ? $from : null;
    }
}
