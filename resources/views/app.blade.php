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

@php($seo = \App\Support\Seo::forComponent($page['component'], request()->path()))
@if ($seo['description'] !== '')
        {{-- Titre, description et canonique **par page** (T-225).
             Rendus par le serveur : le titre du `<Head>` Inertia n'existe
             qu'après l'exécution du JavaScript, et c'est ce titre-là qui
             devient le libellé d'un lien de site sous un résultat de marque.
             Les deux lisent la même clé de catalogue, sinon le document en
             afficherait deux à la suite. --}}
        <meta name="description" content="{{ $seo['description'] }}">
        <link rel="canonical" href="{{ $seo['canonical'] }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $brandName }}">
        <meta property="og:url" content="{{ $seo['canonical'] }}">
        <meta property="og:title" content="{{ $seo['brand'] ? $seo['title'] : $seo['title'].' · '.$brandName }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:image" content="{{ url('/img/landing/hero.jpg') }}">
        <meta property="og:locale" content="fr_FR">
        <meta name="twitter:card" content="summary_large_image">
@endif
@php($graph = \App\Support\Seo::jsonLd($page['component']))
@if ($graph !== [])
        {{-- Données structurées. Le nonce est obligatoire : la politique de
             contenu refuse un script en ligne sans lui, et le refus est
             silencieux. --}}
        <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">{!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@unless ($seo['indexable'])
        {{-- Le tunnel, le remerciement et le témoin : suivis, jamais indexés.
             `follow` et non `nofollow` : les liens qu'ils portent mènent aux
             pages qui, elles, doivent être explorées. --}}
        <meta name="robots" content="noindex, follow">
@endunless

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
        {{-- Le .ico pour les anciens navigateurs et les favoris de bureau, le
             SVG pour tous les autres — il pèse deux kilo-octets et reste net
             sur un écran de 5K comme dans un onglet de 16 pixels. Le PNG de 96
             est le repli de ceux qui ignorent le SVG. --}}
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
@endif

        {{-- Écran d'accueil et barre du navigateur. Le manifeste vient d'une
             route et non d'un fichier : il porte le nom et une couleur de la
             marque, qui ne vivent que dans les réglages. Adresse relative et
             non `route('manifest')` : un manifeste d'une autre origine est
             refusé par le navigateur, et les pages à jeton sont servies depuis
             le domaine court des liens. --}}
        <link rel="manifest" href="/site.webmanifest">
        <meta name="apple-mobile-web-app-title" content="{{ $brandShortName }}">
        <meta name="theme-color" content="{{ $brandCss['--brand-background'] }}">

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
        {{-- L'image d'attente de la vidéo du héros est l'élément le plus grand
             de l'écran d'accueil sur téléphone : c'est elle que mesure le LCP.
             Un attribut `poster` ne porte pas de priorité, et le navigateur
             ne le découvre qu'en arrivant à la balise : sans cette ligne,
             mesuré le 8 septembre 2026, elle partait derrière le JavaScript.
             Le fichier est un carré de 720 px — la vidéo est affichée en carré
             partout — et pèse 27 Ko. --}}
        <link rel="preload" as="image" href="/img/landing/hero-video-poster.webp" fetchpriority="high">
@endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $seo['title'] === '' ? $brandName : ($seo['brand'] ? $seo['title'] : $seo['title'].' · '.$brandName) }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
