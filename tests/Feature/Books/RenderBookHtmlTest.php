<?php

declare(strict_types=1);

use App\Books\IssueQrToken;
use App\Books\RenderBookHtml;
use App\Books\SelectBookChapters;
use App\Models\Book;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Models\Transcript;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le HTML du BAT.
 *
 * Le test qui compte le plus est le dernier : **aucune ressource distante**.
 * C'est un critère de sortie du bloc, et la raison est qu'un rendu qui va
 * chercher une police ou un script sur le réseau produit un livre différent
 * selon l'humeur du réseau — une police manquante décale toute la pagination,
 * et personne ne s'en aperçoit avant de recevoir les exemplaires.
 */
function livreDeDeuxChapitres(): Book
{
    $project = Project::factory()->create(['collection_started_at' => '2026-01-15']);
    Narrator::factory()->create([
        'project_id' => $project->id,
        'first_name' => 'Marcelle',
        'is_primary' => true,
    ]);

    foreach (['Le fournil', 'Le bal du 14 juillet'] as $index => $titre) {
        $story = Story::factory()->validated()->create([
            'project_id' => $project->id,
            'title' => $titre,
            'recorded_at' => '2026-0'.($index + 2).'-01',
        ]);
        Transcript::factory()->for($story)->fluide()->create([
            'text' => "Premier paragraphe de {$titre}.\n\nSecond paragraphe.",
        ]);
    }

    $book = Book::factory()->for($project)->create();
    app(SelectBookChapters::class)->handle($book);

    foreach ($book->chapters as $chapter) {
        app(IssueQrToken::class)->handle($chapter);
    }

    return $book->refresh();
}

it('compose une couverture, un sommaire, un chapitre par histoire et un colophon', function (): void {
    $html = app(RenderBookHtml::class)->handle(livreDeDeuxChapitres());

    expect($html)->toContain('class="cover"')
        ->and($html)->toContain('class="toc"')
        ->and($html)->toContain('Le fournil')
        ->and($html)->toContain('Le bal du 14 juillet')
        ->and(substr_count($html, 'class="chapter"'))->toBe(2)
        ->and($html)->toContain('class="colophon"')
        // Les paragraphes sont séparés, pas collés en un pavé.
        ->and($html)->toContain('<p>Premier paragraphe de Le fournil.</p>');
});

it('met un QR par chapitre, et pas un de plus', function (): void {
    $html = app(RenderBookHtml::class)->handle(livreDeDeuxChapitres());

    // On compte les boîtes QR, pas les balises `<svg` : le paquet Paged.js
    // incrusté en contient lui-même, et le test mesurerait la bibliothèque
    // au lieu du livre.
    expect(substr_count($html, 'class="qr-box"'))->toBe(2)
        ->and($html)->toContain('Scannez pour entendre Marcelle raconter.');

    /*
     * Deux motifs différents.
     *
     * On ne peut pas chercher l'adresse dans le HTML : un QR est fait de
     * rectangles, et le lien n'y figure nulle part en clair — c'est d'ailleurs
     * la seule raison pour laquelle un jeton porteur peut être imprimé. Ce qui
     * se vérifie, c'est que les deux chapitres ne portent pas le **même**
     * motif : un QR qui mènerait au chapitre voisin ne se découvrirait
     * qu'une fois le livre entre les mains de la famille.
     */
    preg_match_all('/<svg[^>]*>.*?<\/svg>/s', $html, $motifs);
    $qr = array_values(array_filter($motifs[0], static fn (string $svg): bool => str_contains($svg, 'viewBox="0 0 304 304"')));

    expect($qr)->toHaveCount(2)
        ->and($qr[0])->not->toBe($qr[1]);
});

it('porte la mention d’engagement des QR et le pack hors-ligne', function (): void {
    $html = app(RenderBookHtml::class)->handle(livreDeDeuxChapitres());

    // D-8 : une durée annoncée, jamais « pour toujours » (R-11).
    expect($html)->toContain('Les QR de ce livre fonctionnent jusqu’au')
        ->and($html)->toContain('pack hors-ligne')
        ->and($html)->toContain('relu et validé par Marcelle')
        ->and($html)->not->toContain('pour toujours');
});

it('ne charge aucune ressource distante', function (): void {
    $html = app(RenderBookHtml::class)->handle(livreDeDeuxChapitres());

    // Ni script, ni feuille de style, ni image, ni police servis par une URL :
    // les seules adresses acceptables sont celles qu'un QR **encode**, et
    // elles ne sont chargées par personne au moment du rendu.
    expect($html)->not->toMatch('/<link[^>]+href=["\']https?:/i')
        ->and($html)->not->toMatch('/<script[^>]+src=/i')
        ->and($html)->not->toMatch('/<img[^>]+src=["\']https?:/i')
        ->and($html)->not->toMatch('/url\(\s*["\']?https?:/i');

    // Et les polices sont bien là, incrustées.
    expect($html)->toContain('@font-face')
        ->and($html)->toContain('data:font/woff2;base64,')
        ->and($html)->toContain('window.PAGEDJS_DONE');
});
