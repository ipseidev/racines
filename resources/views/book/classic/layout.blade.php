{{--
    Le BAT, en une seule page HTML.

    Tout est **incrusté** : le CSS, les polices en base64, Paged.js, les QR en
    SVG et les photos en URI de données. C'est un critère de sortie du bloc, et
    la raison tient en une phrase : un rendu qui va chercher une ressource sur
    le réseau produit un livre différent selon l'humeur du réseau — une police
    manquante et toute la pagination se décale, un QR manquant et le chapitre
    part à l'impression sans son lien.

    `PagedConfig` est posé **avant** le polyfill : c'est le seul moment où il
    est lu. Son `after` lève le drapeau que Browsershot attend, faute de quoi
    Chromium rendrait la page avant la pagination et sortirait un PDF d'une
    seule page interminable.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>{!! $css !!}</style>
</head>
<body>
    <section class="cover">
        <h1 class="cover-title">{{ $title }}</h1>
        @if ($subtitle !== null)
            <p class="cover-subtitle">{{ $subtitle }}</p>
        @endif
        <p class="cover-brand">{{ $brandName }}</p>
    </section>

    @if ($foreword !== null)
        <section class="foreword">
            <h2 class="section-title">{{ __('book.foreword') }}</h2>
            @foreach ($foreword as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </section>
    @endif

    <section class="toc">
        <h2 class="section-title">{{ __('book.contents') }}</h2>
        <ol class="toc-list">
            @foreach ($chapters as $chapter)
                <li class="toc-item">
                    <span class="toc-label">{{ $chapter['title'] }}</span>
                    <span class="toc-dots"></span>
                    <a class="toc-page" href="#chapitre-{{ $chapter['number'] }}"></a>
                </li>
            @endforeach
        </ol>
    </section>

    @foreach ($chapters as $chapter)
        @include('book.classic.chapter', ['chapter' => $chapter])
    @endforeach

    <section class="colophon">
        @foreach ($colophon as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </section>

    <script>window.PagedConfig = { auto: true, after: function () { window.PAGEDJS_DONE = true; } };</script>
    <script>{!! $pagedJs !!}</script>
</body>
</html>
