import { expect, test } from '@playwright/test';

/**
 * Le micro est refusé — cas identifié comme risque pour des narrateurs âgés.
 * L'écran d'aide doit apparaître, et l'écrit rester accessible.
 */
const RECORD_LINK = `/r/${'demo-denied-link'.padEnd(43, 'x')}`;

test.use({ permissions: [] });

test('un micro refusé mène à l’aide, puis à la réponse écrite', async ({
    page,
    context,
}) => {
    await context.clearPermissions();

    await page.addInitScript(() => {
        // Le navigateur de test accorde le micro par défaut : on simule le
        // refus au niveau de l'API, comme le ferait un vrai refus.
        Object.defineProperty(navigator, 'mediaDevices', {
            value: {
                getUserMedia: () =>
                    Promise.reject(new Error('NotAllowedError')),
            },
            configurable: true,
        });
    });

    await page.goto(RECORD_LINK);

    await page.getByRole('button', { name: /je suis prêt/i }).click();

    await expect(
        page.getByRole('heading', { name: /micro n’est pas autoris/i }),
    ).toBeVisible({
        timeout: 15_000,
    });

    await page.getByRole('button', { name: /répondre par écrit/i }).click();

    const textarea = page.getByLabel(/votre réponse/i);
    await expect(textarea).toBeVisible();

    await textarea.fill(
        'Ma mère faisait des confitures de coings chaque automne.',
    );
    await page.getByRole('button', { name: /^envoyer$/i }).click();

    // La personne est prévenue que sa réponse est arrivée, et voit que
    // l'histoire est désormais racontée.
    await expect(page.getByRole('status')).toBeVisible({ timeout: 15_000 });
    await expect(
        page.getByRole('heading', { name: /déjà répondu/i }),
    ).toBeVisible();
});
