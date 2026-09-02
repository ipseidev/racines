import { expect, test, type Page } from '@playwright/test';

/**
 * Le risque technique n°1 du dossier, joué de bout en bout : la page est
 * rechargée en pleine phrase, et rien n'est perdu.
 */
const RECORD_LINK = `/r/${'demo-resume-link'.padEnd(43, 'x')}`;

/**
 * Un test qui dépend d'un aller-retour avec le stockage doit dire *pourquoi*
 * il échoue : sans ces écouteurs, un envoi refusé par la politique de contenu
 * ou par le stockage ressemble à un simple délai dépassé.
 */
function reportBrowserProblems(page: Page): void {
    page.on('console', (message) => {
        if (message.type() === 'error') {
            console.log('[navigateur]', message.text().slice(0, 300));
        }
    });

    page.on('requestfailed', (request) => {
        console.log(
            '[requête échouée]',
            request.method(),
            request.url().slice(0, 160),
            request.failure()?.errorText,
        );
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

test('un rechargement en pleine phrase propose de reprendre', async ({
    page,
}) => {
    reportBrowserProblems(page);

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
