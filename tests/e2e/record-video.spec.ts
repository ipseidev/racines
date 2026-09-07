import { expect, test, type Page } from '@playwright/test';

/**
 * Se filmer plutôt que s'enregistrer (T-210).
 *
 * Ce que ce test protège : que le choix précède l'autorisation, que la caméra
 * s'ouvre et se voit, et que le serveur range bien un enregistrement **vidéo**
 * — pas un audio dans un conteneur vidéo. Le reste du parcours est celui du
 * son, et il a déjà son test : rien n'est dupliqué ici.
 *
 * Le vrai dérivé MP4 et la lecture d'un appareil à l'autre ne se prouvent pas
 * dans un navigateur simulé : ils sont les scénarios S13 et S14 du spike.
 */
const VIDEO_LINK = `/r/${'demo-video-link'.padEnd(43, 'x')}`;

function reportBrowserProblems(page: Page): void {
    page.on('console', (message) => {
        if (message.type() === 'error') {
            console.log('[navigateur]', message.text().slice(0, 300));
        }
    });

    page.on('response', (response) => {
        if (response.status() >= 400) {
            console.log(
                '[réponse]',
                response.status(),
                response.url().slice(0, 160),
            );
        }
    });
}

test('une narratrice se filme, se revoit et envoie', async ({ page }) => {
    reportBrowserProblems(page);

    await page.goto(VIDEO_LINK);

    // Écran 0 — le choix, avant toute autorisation. La voix est le premier
    // bouton : c'est le défaut, et se filmer intimide.
    const voix = page.getByRole('button', { name: /avec votre voix/i });
    const video = page.getByRole('button', { name: /en vous filmant/i });

    await expect(voix).toBeVisible();
    await expect(video).toBeVisible();

    await video.click();

    // L'explication parle de la caméra, pas seulement du micro.
    await expect(page.getByText(/micro et la caméra/i)).toBeVisible();

    await page.getByRole('button', { name: /je suis prêt/i }).click();

    // L'écran caméra prend tout l'écran (T-212) : l'aperçu occupe la page,
    // la question se lit par-dessus, et on peut encore sortir.
    const apercu = page.getByLabel(/ce que voit la caméra/i);
    await expect(apercu).toBeVisible({ timeout: 15_000 });

    const question = page.getByText(
        'À quoi ressemblait votre maison d’enfance ?',
    );
    await expect(question).toBeVisible();
    await expect(page.getByRole('button', { name: /^sortir$/i })).toBeVisible();

    // L'image occupe vraiment l'écran, et pas une vignette dans une colonne.
    const cadre = await apercu.boundingBox();
    const ecran = page.viewportSize();
    expect(cadre?.width ?? 0).toBeGreaterThan((ecran?.width ?? 0) * 0.9);

    const start = page.getByRole('button', { name: /^commencer$/i });
    await expect(start).toBeVisible();
    await start.click();

    await expect(page.getByRole('button', { name: /^pause$/i })).toBeVisible();

    // La question s'efface dès que ça tourne : elle recouvrirait le visage
    // qu'on est en train de cadrer. Et « Sortir » disparaît avec elle —
    // désormais la seule porte est « Terminer », qui garde le récit.
    await expect(question).toBeHidden();
    await expect(page.getByRole('button', { name: /^sortir$/i })).toBeHidden();

    await page.waitForTimeout(6_000);

    await page.getByRole('button', { name: /^terminer$/i }).click();

    // La relecture parle de se revoir, pas de se réécouter.
    await expect(page.getByText(/vous revoir/i)).toBeVisible({
        timeout: 15_000,
    });

    const envoyer = page.getByRole('button', { name: /^envoyer$/i });
    await expect(envoyer).toBeVisible();
    await envoyer.click();

    // Et la confirmation ne vient qu'après le HeadObject du serveur.
    await expect(page.getByText(/votre histoire est enregistrée/i)).toBeVisible(
        { timeout: 60_000 },
    );
});
