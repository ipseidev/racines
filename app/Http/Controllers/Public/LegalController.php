<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\ConsentKind;
use App\Models\ConsentText;
use App\Settings\PilotSettings;
use App\Support\Brand;
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
 * Tant que `PilotSettings::legal_validated_at` est nul, chaque page porte son
 * bandeau « à valider par conseil ». Il ne disparaît pas de lui-même : c'est
 * un acte, posé dans l'administration, et `golive:check` le vérifie (bloc 17).
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
        $path = resource_path("views/legal/{$file}.md");

        abort_unless(File::exists($path), 404);

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
     * Substitue les variables de marque avant le rendu.
     *
     * Le nom de l'entreprise, son adresse et son courriel ne sont pas écrits
     * dans les textes : ils viennent de `BrandSettings`, comme partout
     * ailleurs. Un texte juridique qui nomme la mauvaise entité est un texte
     * inopposable, et le nom n'est pas encore arrêté.
     *
     * La substitution est faite **avant** la conversion markdown, pour qu'une
     * adresse contenant un caractère spécial soit échappée par le convertisseur
     * (`html_input => escape`) comme le reste du texte.
     */
    private static function withBrand(string $markdown): string
    {
        $brand = Brand::settings();

        return str_replace(
            ['{{ product_name }}', '{{ legal_entity }}', '{{ legal_address }}', '{{ support_email }}'],
            [$brand->product_name, $brand->legal_entity, $brand->legal_address, $brand->support_email],
            $markdown,
        );
    }
}
