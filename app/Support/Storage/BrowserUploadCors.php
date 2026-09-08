<?php

declare(strict_types=1);

namespace App\Support\Storage;

/**
 * Ce qu'une règle CORS doit permettre pour qu'un enregistrement puisse partir.
 *
 * L'envoi ne passe pas par le serveur : le navigateur dépose ses parts en
 * direct sur R2, par URL présignée. Trois conditions le gouvernent, et il en
 * faut les trois — la troisième étant celle qu'on oublie.
 *
 *  - l'**origine** qui sert la page d'enregistrement ; en production c'est le
 *    domaine court, pas celui de l'application ;
 *  - la méthode `PUT` ;
 *  - l'**exposition** de l'en-tête `ETag`. Sans elle, le dépôt réussit, le
 *    navigateur cache l'en-tête, et l'envoi multipart ne peut pas se conclure :
 *    `api.ts` lève « Le stockage n'a pas rendu d'ETag », le narrateur lit
 *    « L'envoi n'a pas abouti » après avoir parlé, et les journaux du serveur
 *    sont vides parce que rien n'a échoué chez nous (T-224).
 *
 * La règle vit dans la console du fournisseur, hors de portée des tests ; ce
 * qui est éprouvable est **le jugement** porté sur elle, et c'est ce que cette
 * classe isole.
 */
final class BrowserUploadCors
{
    /**
     * Ce qui manque, en français, pour que le navigateur puisse déposer.
     *
     * @param  list<array<string, mixed>>|null  $rules  `null` = aucune règle.
     * @return list<string> Vide quand tout est permis.
     */
    public static function missing(?array $rules, string $origin): array
    {
        if ($rules === null || $rules === []) {
            return ['toute règle CORS'];
        }

        $missing = [];

        if (! self::allows($rules, 'AllowedOrigins', $origin)) {
            $missing[] = 'l’origine '.$origin;
        }

        if (! self::allows($rules, 'AllowedMethods', 'PUT')) {
            $missing[] = 'la méthode PUT';
        }

        if (! self::allows($rules, 'ExposeHeaders', 'ETag')) {
            $missing[] = 'l’exposition de l’en-tête ETag';
        }

        return $missing;
    }

    /**
     * La règle à coller, l'origine réelle déjà dedans.
     *
     * Rendue et non décrite : elle se pose à la main dans la console, et une
     * règle retapée de mémoire perd l'`ExposeHeaders`.
     *
     * @return list<string>
     */
    public static function suggestion(string $origin): array
    {
        return [
            '[{"AllowedOrigins":["'.$origin.'"],"AllowedMethods":["PUT","GET","HEAD"],',
            ' "AllowedHeaders":["*"],"ExposeHeaders":["ETag"],"MaxAgeSeconds":3000}]',
        ];
    }

    /**
     * Une des règles couvre-t-elle cette valeur ?
     *
     * `*` compte, y compris pour `ExposeHeaders` : une règle large est un
     * choix, pas un oubli. La comparaison ignore la casse — un en-tête HTTP
     * n'y est pas sensible, et « etag » est aussi valide que « ETag ».
     *
     * @param  list<array<string, mixed>>  $rules
     */
    private static function allows(array $rules, string $field, string $value): bool
    {
        foreach ($rules as $rule) {
            $values = $rule[$field] ?? [];

            if (! is_array($values)) {
                continue;
            }

            foreach ($values as $candidate) {
                if (! is_string($candidate)) {
                    continue;
                }

                if ($candidate === '*' || mb_strtolower($candidate) === mb_strtolower($value)) {
                    return true;
                }
            }
        }

        return false;
    }
}
