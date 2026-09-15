<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\ConsentKind;
use App\Enums\Locale;
use App\Models\ConsentText;
use App\Settings\PilotSettings;
use App\Support\Brand;
use App\Support\Locales;
use Illuminate\Support\Facades\File;
use Inertia\Response;
use League\CommonMark\CommonMarkConverter;

/**
 * Les pages légales, rendues depuis des fichiers markdown.
 *
 * Pas depuis des composants React, et c'est délibéré : ces textes seront
 * relus par un conseil, et un conseil relit un texte — pas du JSX entrecoupé
 * de classes CSS. Le markdown se diffuse, s'annote et se compare.
 *
 * `PilotSettings::legal_validated_at` date la relecture des textes par un
 * conseil. Les pages n'affichent plus de bandeau d'attente (T-145) mais
 * reçoivent l'état ; il ne change pas de lui-même : c'est un acte, posé dans
 * l'administration, et `golive:check` le vérifie (bloc 17).
 */
final class LegalController
{
    public function terms(): Response
    {
        return self::page('cgv', 'public.legal.terms');
    }

    public function privacy(): Response
    {
        return self::page('confidentialite', 'public.legal.privacy');
    }

    public function imprint(): Response
    {
        return self::page('mentions-legales', 'public.legal.imprint');
    }

    /**
     * Les consentements, dans leur version **en vigueur**.
     *
     * Rendus depuis la base et non depuis un fichier : c'est la version que
     * les gens ont réellement acceptée qui doit s'afficher, et elle est
     * datée.
     */
    public function consents(): Response
    {
        $texts = [];

        foreach (ConsentKind::cases() as $kind) {
            $current = ConsentText::current($kind);

            if ($current === null) {
                continue;
            }

            $texts[] = [
                'kind' => $kind->value,
                'label' => __($kind->label()),
                'version' => $current->version,
                'effectiveFrom' => $current->effective_from->toIso8601String(),
                'body' => $current->body,
            ];
        }

        return inertia('public/Consents', [
            'texts' => $texts,
            'legalValidated' => app(PilotSettings::class)->legalValidated(),
        ]);
    }

    private static function page(string $file, string $titleKey): Response
    {
        $path = self::pathFor($file);

        abort_unless($path !== null, 404);

        $converter = new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return inertia('public/Legal', [
            'title' => __($titleKey),
            'html' => (string) $converter->convert(self::withBrand(File::get($path))),
            'legalValidated' => app(PilotSettings::class)->legalValidated(),
        ]);
    }

    /**
     * Le texte dans la langue de la page, ou le texte français.
     *
     * Les traductions sont informatives et le disent elles-mêmes : le contrat
     * est régi par le droit français, et c'est la version française qui fait
     * foi. Un texte manquant retombe donc sur elle plutôt que sur un 404 —
     * une page légale absente est pire qu'une page légale en français.
     */
    private static function pathFor(string $file): ?string
    {
        $language = Locales::current()->language();

        foreach ([$language, Locale::default()->language()] as $candidate) {
            $path = $candidate === Locale::default()->language()
                ? resource_path("views/legal/{$file}.md")
                : resource_path("views/legal/{$candidate}/{$file}.md");

            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Substitue les variables de marque avant le rendu.
     *
     * L'identité de l'éditeur n'est pas écrite dans les textes : elle vient de
     * `BrandSettings`, comme partout ailleurs. Un texte juridique qui nomme la
     * mauvaise entité est un texte inopposable.
     *
     * La substitution est faite **avant** la conversion markdown, pour qu'une
     * adresse contenant un caractère spécial soit échappée par le convertisseur
     * (`html_input => escape`) comme le reste du texte.
     */
    private static function withBrand(string $markdown): string
    {
        $tokens = self::tokens();

        return str_replace(
            array_map(static fn (string $name): string => '{{ '.$name.' }}', array_keys($tokens)),
            array_values($tokens),
            $markdown,
        );
    }

    /**
     * Les valeurs que les textes légaux peuvent appeler, et la seule table qui
     * en fasse la liste.
     *
     * Elle est publique parce qu'un test la relit : `LegalTest` extrait les
     * gabarits de chaque fichier markdown, refuse celui que cette table ne
     * connaît pas — il s'afficherait tel quel, accolades comprises — et refuse
     * surtout une valeur **vide**. C'est le défaut T-242 : la substitution
     * marchait, la valeur était vide, et les mentions légales annonçaient
     * « Le représentant légal de . » sans que rien n'échoue.
     *
     * @return array<string, string>
     */
    public static function tokens(): array
    {
        $brand = Brand::settings();

        return [
            'product_name' => $brand->product_name,
            'legal_entity' => $brand->legal_entity,
            'legal_form' => $brand->legal_form,
            'legal_address' => $brand->legal_address,
            'legal_siren' => $brand->legal_siren,
            'legal_siret' => $brand->legal_siret,
            'legal_vat' => $brand->legal_vat,
            'legal_publication_director' => $brand->legal_publication_director,
            'legal_host' => $brand->legal_host,
            'legal_host_media' => $brand->legal_host_media,
            'legal_host_location' => $brand->legal_host_location,
            'support_email' => $brand->support_email,
        ];
    }
}
