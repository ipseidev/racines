<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- Nom de marque lu une fois par le front : Inertia remplace la balise title. --}}
        <meta name="brand" content="{{ $brandName }}">
        {{-- Les appels `fetch` de la page d'enregistrement en ont besoin. --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        {{-- Le nonce vient de SecurityHeaders : sans lui, la politique de
             contenu stricte refuserait ce script et ce style. --}}
        <script nonce="{{ Vite::cspNonce() }}">
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Marque : éditable dans l'administration, appliquée sans redéploiement. --}}
        <style nonce="{{ Vite::cspNonce() }}">
            :root {
@foreach ($brandCss as $variable => $value)
                {{ $variable }}: {{ $value }};
@endforeach
            }

            html {
                background-color: var(--brand-background);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
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
