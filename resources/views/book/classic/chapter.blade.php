{{--
    Un chapitre : le numéro, le titre, la question posée, la date, le texte,
    les photos, et le QR qui rend la voix.

    L'ancre porte le numéro et non l'identifiant de l'histoire : le sommaire y
    renvoie par `target-counter`, et un UUID dans une ancre imprimée n'aurait
    aucun sens si quelqu'un lisait le PDF source.
--}}
<section class="chapter" id="chapitre-{{ $chapter['number'] }}">
    <p class="chapter-number">{{ __('book.chapter_number', ['number' => $chapter['number']]) }}</p>
    <h2 class="chapter-title">{{ $chapter['title'] }}</h2>

    @if ($chapter['question'] !== null)
        <p class="question">« {{ $chapter['question'] }} »</p>
    @endif

    @if ($chapter['date'] !== null)
        <p class="date">{{ $chapter['date'] }}</p>
    @endif

    <div class="chapter-text">
        @foreach ($chapter['paragraphs'] as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>

    @foreach ($chapter['photos'] as $photo)
        <figure class="photo">
            <img src="{{ $photo['data'] }}" alt="{{ $photo['alt'] }}">
            @if ($photo['caption'] !== null)
                <figcaption>{{ $photo['caption'] }}</figcaption>
            @endif
        </figure>
    @endforeach

    @if ($chapter['qr'] !== null)
        <div class="qr-box">
            {!! $chapter['qr'] !!}
            <p class="qr-legend">{{ $chapter['qrLegend'] }}</p>
        </div>
    @endif
</section>
