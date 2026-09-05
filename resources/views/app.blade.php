<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- Nom de marque lu une fois par le front : Inertia remplace la balise title. --}}
        <meta name="brand" content="{{ $brandName }}">
        {{-- Les appels `fetch` de la page d'enregistrement en ont besoin. --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Inertia crée sa barre de progression, et sa feuille de styles,
             à l'exécution : sans ce nonce, la politique stricte des pages
             narrateur la refusait (T-75). --}}
        <meta name="csp-nonce" content="{{ Vite::cspNonce() }}">

@if (str_starts_with($page['component'], 'public/'))
        {{-- Description et aperçu de partage. Rendus par le serveur : un robot
             qui n'exécute pas de JavaScript ne verrait rien d'un `<Head>`
             Inertia. Réservés aux pages publiques : une page narrateur s'ouvre
             par un lien porteur, et n'a rien à donner à lire à un aperçu.
             Pas d'`og:url` pour la même raison, il porterait le jeton. --}}
        <meta name="description" content="{{ __('public.meta.description') }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $brandName }}">
        <meta property="og:title" content="{{ $brandName }}">
        <meta property="og:description" content="{{ __('public.meta.description') }}">
        <meta property="og:image" content="{{ url('/img/landing/hero.jpg') }}">
        <meta name="twitter:card" content="summary_large_image">
@endif

        {{-- Marque : éditable dans l'administration, appliquée sans redéploiement.
             Le nonce vient de SecurityHeaders : sans lui, la politique de
             contenu stricte refuserait ce style. Thème clair seul, décision du
             3 septembre 2026 : un livre se lit sur du papier. --}}
        <style nonce="{{ Vite::cspNonce() }}">
            :root {
@foreach ($brandCss as $variable => $value)
                {{ $variable }}: {{ $value }};
@endforeach
            }

            html {
                background-color: var(--brand-background);
            }
        </style>

@if ($brandFavicon)
        <link rel="icon" href="{{ $brandFavicon }}">
@else
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
@endif

        {{-- Polices : Inter et Fraunces sont déclarées dans app.css, depuis
             public/fonts. On ne précharge que les deux graisses d'Inter visibles
             d'emblée, le texte courant et les boutons : ce sont 48 Ko qui ne
             disputent pas la bande passante à la photo du héros. Fraunces s'en
             passe, son `font-display: swap` coûte un remplacement de police et
             pas une secousse (0,001 de décalage mesuré le 5 septembre 2026).
             Le `crossorigin` est obligatoire, une police se charge en CORS. --}}
        <link rel="preload" as="font" type="font/woff2" href="/fonts/inter-400.woff2" crossorigin="anonymous">
        <link rel="preload" as="font" type="font/woff2" href="/fonts/inter-600.woff2" crossorigin="anonymous">

@if ($page['component'] === 'public/Landing')
        {{-- La photo du héros est l'élément le plus grand de la page d'accueil.
             Sans ce préchargement, le navigateur ne la découvre qu'une fois
             React monté : 890 ms d'attente mesurés le 5 septembre 2026. Les
             `imagesrcset` et `imagesizes` répètent mot pour mot ceux de la
             balise, faute de quoi le fichier est demandé deux fois — l'échelle
             est celle de resources/js/lib/photo.ts, et un test de LandingTest
             tombe si les deux divergent. --}}
        <link rel="preload" as="image" href="/img/landing/hero.webp"
              imagesrcset="/img/landing/hero-400.webp 400w, /img/landing/hero-550.webp 550w, /img/landing/hero-700.webp 700w, /img/landing/hero-900.webp 900w, /img/landing/hero-1100.webp 1100w, /img/landing/hero.webp 1400w"
              imagesizes="(min-width: 1024px) 34rem, 100vw"
              fetchpriority="high">
@endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $brandName }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
