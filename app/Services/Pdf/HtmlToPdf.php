<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Transformer une page HTML en PDF.
 *
 * Un port, pour la raison habituelle de ce dépôt et pour une de plus : le
 * rendu réel demande un Chromium, un Node et trois cents secondes. Un test
 * qui l'appellerait vraiment prendrait cinq minutes et échouerait sur une
 * machine d'intégration continue sans navigateur — donc il ne serait pas
 * écrit, et c'est le rendu du livre qui finirait sans test.
 *
 * Ce que le double ne prouve pas, un test de bout en bout marqué `@slow` le
 * prouve en local, avec le vrai Chromium : la leçon T-154 vaut ici comme
 * ailleurs, un double dit qu'on appelle quelque chose, jamais que quelque
 * chose se produit.
 */
interface HtmlToPdf
{
    /**
     * Rend le HTML et écrit le PDF, puis rend son chemin local.
     *
     * Le chemin est **local et temporaire** : l'appelant décide où le fichier
     * vit ensuite. Un rendu qui écrirait lui-même dans le stockage objet
     * mêlerait deux responsabilités, et le jour où l'on veut le relire avant
     * de le stocker il faudrait tout défaire.
     */
    public function render(string $html, PdfOptions $options): string;
}
