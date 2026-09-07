import { expect, test } from '@playwright/test';

import { blockingViolations } from './support/a11y';

/**
 * Les pages publiques **hors pages de vente**, passées au crible de
 * l'accessibilité.
 *
 * Les pages de vente — l'accueil, « Comment ça marche », les variantes de
 * `/lp/*` — n'y sont plus (T-218). Elles changent plusieurs fois par jour, et
 * chaque audit tombait sur un contraste ou un ordre de titres déplacé par une
 * itération de rédaction : le coût de la boucle dépassait ce que le filet
 * attrapait. Ce qui reste couvert est ce qui bouge peu et se doit d'être
 * irréprochable : le tunnel, où l'on paie, et les textes légaux, où l'on
 * comprend ce qu'on achète.
 *
 * L'essai reste ici : il demande le micro, et c'est une page d'enregistrement
 * avant d'être une page publique.
 */
const PAGES = [
    ['/essai', 'l’essai'],
    ['/acheter', 'le tunnel'],
    ['/cgv', 'les conditions générales'],
    ['/confidentialite', 'la politique de confidentialité'],
    ['/consentements', 'les accords'],
] as const;

for (const [path, label] of PAGES) {
    test(`aucune violation grave sur ${label}`, async ({ page }) => {
        // Sans mouvement : les pages entrent en fondu, et une couleur mesurée
        // au milieu du fondu n'est pas celle qu'on lit. On juge l'état posé.
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.goto(path);

        // Attendre le titre avant d'analyser, et ce n'est pas du zèle : ces
        // pages sont rendues par React après le chargement. Analyser trop tôt
        // passerait sur un DOM vide, et un test qui ne trouve rien à
        // reprocher à une page vide ne prouve rien.
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

        const violations = await blockingViolations(page, [
            'wcag2a',
            'wcag2aa',
        ]);

        expect(
            violations.map((one) => `${one.id}: ${one.help}`),
            JSON.stringify(violations, null, 2),
        ).toEqual([]);
    });
}
