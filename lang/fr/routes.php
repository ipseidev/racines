<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Les adresses des pages publiques
|--------------------------------------------------------------------------
|
| Une page publique a une adresse par langue : `/comment-ca-marche` en
| français, `/it/come-funziona` en italien. Ce fichier donne le segment
| d'adresse de chaque page ; le préfixe de langue, lui, vient de la locale
| (`Locale::urlPrefix()`). Les pages qui ne sont pas ici — l'espace, les
| pages à jeton, les comptes — n'ont qu'une adresse, la même dans toutes les
| langues. Le français de Suisse reprend ces mêmes segments sous `/fr-ch/`.
|
| Une clé par nom de route de base (`LocalizedRoutes::PAGES`). Un segment
| est en minuscules, sans accent ni espace : c'est une URL.
|
*/

return [
    'home' => '/',
    'demo' => 'essai',
    'how_it_works' => 'comment-ca-marche',
    'faq' => 'questions-frequentes',
    'books' => 'nos-livres',
    'legal.terms' => 'cgv',
    'legal.privacy' => 'confidentialite',
    'legal.imprint' => 'mentions-legales',
    'legal.consents' => 'consentements',
    'checkout.show' => 'acheter',
    'checkout.thanks' => 'acheter/merci',
];
