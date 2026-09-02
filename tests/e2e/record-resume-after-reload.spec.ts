import { expect, test } from '@playwright/test';

/**
 * Le risque technique n°1 du dossier, joué de bout en bout : la page est
 * rechargée en pleine phrase, et rien n'est perdu.
 */
const RECORD_LINK = `/r/${'demo-resume-link'.padEnd(43, 'x')}`;

test('un rechargement en pleine phrase propose de reprendre', async ({
    page,
}) => {
    await page.goto(RECORD_LINK);

    await page.getByRole('button', { name: /je suis prêt/i }).click();

    const start = page.getByRole('button', { name: /^commencer$/i });
    await expect(start).toBeVisible({ timeout: 15_000 });
    await start.click();

    // Assez long pour que plusieurs tranches soient écrites sur le « téléphone ».
    await page.waitForTimeout(6000);

    await page.reload();

    const resume = page.getByRole('button', {
        name: /reprendre mon enregistrement/i,
    });
    await expect(resume).toBeVisible({ timeout: 15_000 });

    await resume.click();

    await expect(
        page.getByRole('button', { name: /^envoyer$/i }),
    ).toBeVisible();
    await page.getByRole('button', { name: /^envoyer$/i }).click();

    await expect(page.getByText(/votre histoire est enregistrée/i)).toBeVisible(
        {
            timeout: 30_000,
        },
    );
});
