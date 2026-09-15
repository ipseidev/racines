<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Marque
|--------------------------------------------------------------------------
|
| Valeurs de repli uniquement. La source de vérité est BrandSettings (bloc 01),
| éditable dans l'administration sans déploiement. Le nom, le domaine des liens
| et les couleurs ne doivent apparaître nulle part ailleurs dans le code.
|
*/

return [
    'product_name' => env('BRAND_PRODUCT_NAME', 'Product'),
    'short_name' => env('BRAND_SHORT_NAME', 'Product'),
    'tagline' => 'Le livre de leurs souvenirs, avec leur voix à chaque page.',
    'links_domain' => env('LINKS_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
    'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@example.test'),
    'support_phone' => env('BRAND_SUPPORT_PHONE'),
    'sms_sender_id' => env('BRAND_SMS_SENDER_ID', 'PRODUCT'),

    /*
     * La marque figurée : le pictogramme posé à côté du nom dans les en-têtes,
     * et l'icône des onglets. Deux fichiers servis depuis public/, repli d'un
     * `mark_path` téléversé dans l'administration — comme les couleurs, la
     * configuration est le repli et la base la source de vérité.
     */
    'mark' => '/img/brand/mark.svg',

    /*
     * Le même pictogramme, en PNG, pour les courriels : Gmail et Outlook ne
     * dessinent pas un SVG. Généré depuis mark.svg (192 px de large, soit
     * quatre fois son affichage). Si l'administration téléverse un
     * pictogramme matriciel, il le remplace ; si elle téléverse un SVG, les
     * courriels portent le nom seul plutôt qu'un dessin qui ne serait plus
     * le sien.
     */
    'mark_email' => '/img/brand/mark.png',

    /*
     * Palette issue de l'analyse colorimétrique du fondateur (3 septembre 2026,
     * docs/design/README.md). Deux couleurs signature : le vert forêt, qui
     * porte la marque, et la terracotta, qui porte l'action — et rien d'autre.
     * Un bouton se voit par son isolement, pas par sa teinte : la terracotta
     * est la seule couleur chaude saturée d'une page.
     */
    'colors' => [
        'primary' => '#2F4A3F',
        'primary_foreground' => '#FFFFFF',
        'accent' => '#B0432A',
        'accent_foreground' => '#FFFFFF',
        'background' => '#FBF6EE',
        'surface' => '#FFFFFF',
        'text' => '#26211C',
        'muted' => '#5A5049',
    ],

    'fonts' => [
        'display' => 'Fraunces',
        'body' => 'Inter',
    ],

    /*
     * L'éditeur du site, au sens de la LCEN : qui vend, sous quel numéro, à
     * quelle adresse, et chez qui le service est hébergé.
     *
     * **Aucune de ces valeurs ne passe par `env()`, et c'est la correction du
     * défaut T-242.** Elles y passaient, la ligne `BRAND_LEGAL_ENTITY=` du
     * `.env` était vide, et la page des mentions légales affichait « Le
     * représentant légal de . » : un gabarit vide ne casse rien, il efface
     * simplement l'identité du vendeur. Une identité légale n'est pas une
     * variable d'environnement — elle est la même sur chaque machine, et une
     * ligne oubliée ne doit pas pouvoir la supprimer.
     *
     * Comme le reste de ce fichier, ce ne sont que des valeurs de repli : la
     * source de vérité est BrandSettings, éditable dans l'administration.
     */
    'legal_entity' => 'Nicolas Serra',
    'legal_form' => 'entrepreneur individuel (EI)',
    'legal_address' => 'Route de Pietramaggiore, Villa Laura, 20260 Calvi, France',
    'legal_siren' => '843 299 751',
    'legal_siret' => '843 299 751 00019',
    'legal_vat' => 'FR70843299751',
    'legal_publication_director' => 'Nicolas Serra',

    /*
     * L'hébergeur, que la LCEN veut nommé, avec son adresse et son téléphone
     * — « communiqué sur demande » ne satisfait pas l'article 6 III-1 d).
     * Vérifié le 15 septembre 2026 plutôt que recopié : `whois` sur l'adresse
     * IP du domaine donne DigitalOcean et son siège, le fichier de
     * géolocalisation publié par DigitalOcean situe la plage dans sa région
     * de Francfort, et les coordonnées viennent des déclarations SEC des deux
     * sociétés. C'est ce qui a tranché la question laissée ouverte par T-202.
     *
     * Trois valeurs et non une phrase : les mentions légales existent aussi
     * en italien et en espagnol, et une phrase française substituée dans une
     * page italienne y resterait française. Un nom de société, une adresse
     * postale et un numéro de téléphone, eux, ne se traduisent pas ; la phrase
     * qui les relie est dans chaque fichier markdown, dans sa langue.
     */
    'legal_host' => 'DigitalOcean, LLC, 105 Edgeview Drive, Suite 425, Broomfield, CO 80021, USA, +1 646 827 4366',
    'legal_host_media' => 'Cloudflare, Inc., 101 Townsend Street, San Francisco, CA 94107, USA, +1 888 993 5273',
    'legal_host_location' => 'Francfort-sur-le-Main (Allemagne)',
];
