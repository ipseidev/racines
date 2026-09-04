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

        @fonts

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
