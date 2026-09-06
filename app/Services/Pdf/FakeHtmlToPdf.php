<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Le port `HtmlToPdf`, sans navigateur.
 *
 * Il écrit un PDF **minimal mais valide** — une page, un objet catalogue —
 * plutôt qu'un fichier vide : le reste de la chaîne compte des pages, calcule
 * une taille et stocke le fichier, et un octet nul ferait passer des tests
 * qui ne prouveraient rien.
 *
 * Il retient le dernier HTML reçu : c'est sur lui que portent les assertions
 * du bloc — un QR par chapitre, le colophon, aucune ressource distante.
 */
final class FakeHtmlToPdf implements HtmlToPdf
{
    private ?string $lastHtml = null;

    private ?PdfOptions $lastOptions = null;

    /** @var list<string> */
    private array $written = [];

    public function render(string $html, PdfOptions $options): string
    {
        $this->lastHtml = $html;
        $this->lastOptions = $options;

        $path = tempnam(sys_get_temp_dir(), 'bat').'.pdf';
        file_put_contents($path, self::minimalPdf());
        $this->written[] = $path;

        return $path;
    }

    public function lastHtml(): ?string
    {
        return $this->lastHtml;
    }

    public function lastOptions(): ?PdfOptions
    {
        return $this->lastOptions;
    }

    /** @return list<string> */
    public function written(): array
    {
        return $this->written;
    }

    /**
     * Un PDF d'une page, accepté par un lecteur et par `pdfinfo`.
     *
     * Écrit à la main : ajouter une bibliothèque de génération de PDF pour
     * fabriquer un double serait une dépendance de production pour un besoin
     * de test.
     */
    private static function minimalPdf(): string
    {
        return "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 454 680]>>endobj\n"
            ."trailer<</Root 1 0 R>>\n"
            ."%%EOF\n";
    }
}
