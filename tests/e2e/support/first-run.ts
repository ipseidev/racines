import { type Page } from '@playwright/test';

/**
 * Passer le tour de chauffe (T-247).
 *
 * Le tout premier lien d'un narrateur ouvre sur une proposition d'essai de
 * quinze secondes, avant l'écran du choix voix/vidéo : c'est ce qui lève la
 * peur de la première fois, et il ne revient plus ensuite.
 *
 * Les parcours qui testent l'enregistrement lui-même le passent — ils
 * commencent après. Un seul test vérifie qu'il s'affiche, et qu'il ne
 * s'affiche qu'une fois.
 */
export async function skipFirstRun(page: Page): Promise<void> {
    const skip = page.getByRole('button', {
        name: /passer et répondre/i,
    });

    /*
     * Une vraie attente, pas un `isVisible()` sec : celui-ci ne patiente pas,
     * et il répondait « non » pendant l'hydratation — le titre était déjà
     * peint, le bouton pas encore branché. Le test passait alors sans rien
     * cliquer et échouait trois lignes plus loin, sur l'écran du choix.
     */
    const present = await skip
        .waitFor({ state: 'visible', timeout: 5_000 })
        .then(() => true)
        .catch(() => false);

    if (present) {
        await skip.click();
        // L'écran du choix remplace le tour de chauffe : on ne rend la main
        // qu'une fois qu'il est là.
        await page
            .getByRole('button', { name: /avec votre voix/i })
            .waitFor({ state: 'visible' });
    }
}
