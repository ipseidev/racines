import { expect, test } from '@playwright/test';

/**
 * Le parcours nominal, joué dans un vrai navigateur avec un micro simulé.
 *
 * Ce que ce test protège vraiment : qu'aucun écran ne dise « votre histoire
 * est enregistrée » avant que le serveur l'ait confirmé, et que les six
 * écrans s'enchaînent sans qu'on ait besoin d'aider la personne.
 */
const RECORD_LINK = `/r/${'demo-record-link'.padEnd(43, 'x')}`;

test('un narrateur enregistre, met en pause, reprend et envoie', async ({
    page,
}) => {
    await page.goto(RECORD_LINK);

    // Écran 1 — l'explication précède la demande de micro.
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    const ready = page.getByRole('button', { name: /je suis prêt/i });
    await expect(ready).toBeVisible();

    // Écran 2 et 3 — permission puis enregistrement.
    await ready.click();

    const start = page.getByRole('button', { name: /^commencer$/i });
    await expect(start).toBeVisible({ timeout: 15_000 });
    await start.click();

    await expect(page.getByRole('button', { name: /^pause$/i })).toBeVisible();
    await page.waitForTimeout(3000);

    await page.getByRole('button', { name: /^pause$/i }).click();
    await expect(
        page.getByRole('button', { name: /^reprendre$/i }),
    ).toBeVisible();
    await page.getByRole('button', { name: /^reprendre$/i }).click();

    await page.waitForTimeout(1500);

    // Écran 4 — vérification.
    await page.getByRole('button', { name: /^terminer$/i }).click();
    await expect(page.getByRole('button', { name: /^envoyer$/i })).toBeVisible({
        timeout: 15_000,
    });

    // Écrans 5 et 6 — envoi puis confirmation, jamais avant.
    await page.getByRole('button', { name: /^envoyer$/i }).click();

    await expect(page.getByText(/votre histoire est enregistrée/i)).toBeVisible(
        {
            timeout: 30_000,
        },
    );
});

test('la confirmation ne s’affiche jamais avant l’envoi', async ({ page }) => {
    await page.goto(RECORD_LINK);

    await expect(page.getByText(/votre histoire est enregistrée/i)).toHaveCount(
        0,
    );

    await page.getByRole('button', { name: /je suis prêt/i }).click();
    await expect(
        page.getByRole('button', { name: /^commencer$/i }),
    ).toBeVisible({
        timeout: 15_000,
    });

    await expect(page.getByText(/votre histoire est enregistrée/i)).toHaveCount(
        0,
    );
});
