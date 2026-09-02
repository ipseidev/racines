import { expect, test, type Page } from '@playwright/test';

/**
 * Variante A : les trois choix arrivent juste après la confirmation.
 *
 * C'est l'hypothèse centrale de la Phase 0A — la validation comme récompense
 * d'un tap — et ce test la joue en vrai, micro simulé compris. Ce qu'il
 * protège : que la question se pose **après** que le serveur a confirmé, et
 * que rien ne soit présélectionné ni minuté.
 */
const VARIANT_A = `/r/${'demo-variant-a-link'.padEnd(43, 'x')}`;

const VARIANT_B = `/r/${'demo-variant-b-link'.padEnd(43, 'x')}`;

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

async function recordAndSend(page: Page, link: string): Promise<void> {
    await page.goto(link);

    await page.getByRole('button', { name: /je suis prêt/i }).click();

    const start = page.getByRole('button', { name: /^commencer$/i });
    await expect(start).toBeVisible({ timeout: 15_000 });
    await start.click();

    await page.waitForTimeout(2500);
    await page.getByRole('button', { name: /^terminer$/i }).click();

    await expect(page.getByRole('button', { name: /^envoyer$/i })).toBeVisible({
        timeout: 15_000,
    });
    await page.getByRole('button', { name: /^envoyer$/i }).click();

    await expect(page.getByText(/votre histoire est enregistrée/i)).toBeVisible(
        {
            timeout: 30_000,
        },
    );
}

test('les trois choix apparaissent après la confirmation, et le partage se note', async ({
    page,
}) => {
    reportBrowserProblems(page);

    await recordAndSend(page, VARIANT_A);

    // La question ne se pose qu'après la confirmation du serveur : la poser
    // avant reviendrait à demander de valider ce qui n'est pas encore là.
    const share = page.getByRole('button', {
        name: /partager avec mes proches/i,
    });
    await expect(share).toBeVisible();

    for (const label of [
        /partager avec mes proches/i,
        /garder pour moi/i,
        /décider plus tard/i,
    ]) {
        const button = page.getByRole('button', { name: label });
        const box = await button.boundingBox();

        expect(box?.height ?? 0).toBeGreaterThanOrEqual(44);
        // Rien de présélectionné : l'absence de réaction ne vaut jamais accord.
        expect(await button.getAttribute('aria-pressed')).toBeNull();
    }

    // Aucun minuteur : une hésitation n'est pas un consentement.
    await expect(page.locator('progress')).toHaveCount(0);

    await share.click();

    await expect(page.getByRole('status')).toContainText(
        /vos proches pourront/i,
    );
});

test('la variante B ne pose aucune question à l’enregistrement', async ({
    page,
}) => {
    reportBrowserProblems(page);

    // Ce lien porte une histoire déjà transcrite : la page dit qu'elle a déjà
    // été racontée, et ne propose surtout pas de décider du partage ici.
    await page.goto(VARIANT_B);

    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(
        page.getByRole('button', { name: /partager avec mes proches/i }),
    ).toHaveCount(0);
});
