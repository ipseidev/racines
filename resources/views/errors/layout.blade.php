{{--
    Les pages d'erreur.

    En Blade et **sans JavaScript**, à dessein : elles doivent s'afficher quand
    l'application ne va pas bien, y compris quand le bundle React ne se charge
    pas. Elles n'appellent donc ni Vite, ni Inertia, ni la base — les couleurs
    et le nom de marque sont lus depuis les réglages, avec un repli en dur si
    même cela échoue (`nameSafe`, `cssVariables` sont écrits pour ça).

    Le ton suit la règle du dossier : dire ce qui se passe en langage simple,
    proposer une reprise, et **ne jamais accuser la personne**. Pas de code
    d'erreur en gros, pas de « Oups », pas d'anglais.
--}}
<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::current()->tag() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $titre }} — {{ $marque }}</title>
    <style>
        :root {
@foreach ($couleurs as $variable => $valeur)
            {{ $variable }}: {{ $valeur }};
@endforeach
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: var(--brand-background, #faf7f2);
            color: var(--brand-text, #26211c);
            font-family: var(--brand-font-body, system-ui), system-ui, sans-serif;
            font-size: 1.0625rem;
            line-height: 1.6;
        }

        main { max-width: 34rem; }

        .marque {
            font-size: 0.8125rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--brand-muted, #5a5049);
            margin: 0 0 1.5rem;
        }

        h1 {
            font-family: var(--brand-font-display, Georgia), Georgia, serif;
            font-size: 1.875rem;
            line-height: 1.15;
            margin: 0 0 1rem;
        }

        p { margin: 0 0 1rem; color: var(--brand-muted, #5a5049); }

        /* 44 px de haut : la cible tactile minimale que le dossier exige. */
        a.reprise {
            display: inline-block;
            margin-top: 1rem;
            min-height: 2.75rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--brand-primary, #c16a55);
            border-radius: 0.5rem;
            color: var(--brand-primary, #c16a55);
            font-weight: 600;
            text-decoration: none;
        }

        a.reprise:focus-visible {
            outline: 3px solid var(--brand-primary, #c16a55);
            outline-offset: 3px;
        }
    </style>
</head>
<body>
    <main>
        <p class="marque">{{ $marque }}</p>
        <h1>{{ $titre }}</h1>
        <p>{{ $corps }}</p>
        @yield('extra')
        <a class="reprise" href="{{ $retour }}">{{ $lien }}</a>
    </main>
</body>
</html>
