import { expect, test, type Page } from '@playwright/test';
import { skipFirstRun } from './support/first-run';

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

    // L'écran du choix précède tout (T-210) : la voix reste le défaut.
    await skipFirstRun(page);
    await page.getByRole('button', { name: /avec votre voix/i }).click();

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

test('aucune question après la confirmation : on annonce, et la sortie reste ouverte', async ({
    page,
}) => {
    reportBrowserProblems(page);

    await recordAndSend(page, VARIANT_A);

    /*
     * Les trois choix — « partager avec mes proches », « garder pour moi »,
     * « décider plus tard » — se posaient ici, et c'était l'objet du test de
     * Phase 0A. T-250 les a retirés : le partage permanent est devenu le
     * sixième accord du « J'accepte », et plus aucune décision n'est
     * demandée après un récit. Ce test garde la place : il vérifie que la
     * question ne revient pas.
     */
    for (const label of [/partager avec mes proches/i, /décider plus tard/i]) {
        await expect(page.getByRole('button', { name: label })).toHaveCount(0);
    }

    // Aucun minuteur : une hésitation n'est pas un consentement.
    await expect(page.locator('progress')).toHaveCount(0);

    /*
     * On annonce ce qui va se passer…
     *
     * Deux régions vivantes sur cet écran, et c'est voulu : la confirmation
     * de l'enregistrement, puis ce qu'il advient du récit. Ce sont deux
     * nouvelles distinctes, et un lecteur d'écran doit dire les deux — d'où
     * le filtre plutôt qu'un `getByRole('status')` qui les confondrait.
     */
    await expect(
        page.getByRole('status').filter({ hasText: /vos proches pourront/i }),
    ).toBeVisible();

    // …et la sortie de ce récit-là est à un doigt : un accord permanent
    // n'est pas un engagement histoire par histoire.
    const keep = page.getByRole('button', {
        name: /garder celle-ci pour moi/i,
    });
    await expect(keep).toBeVisible();
    /*
     * Arrondi au pixel : Chromium rend ce bouton à 43,99997 px pour un
     * `min-h-[2.75rem]` qui vaut 44 px exactement — une quantification de sa
     * boîte, pas une cible trop petite. Comparer brut faisait échouer un test
     * d'accessibilité sur trois cent-millièmes de pixel.
     */
    expect(
        Math.round((await keep.boundingBox())?.height ?? 0),
    ).toBeGreaterThanOrEqual(44);

    // Et la page dit qu'on peut la quitter : rien n'attend plus personne.
    await expect(
        page.getByText(/vous pouvez fermer cette page/i),
    ).toBeVisible();

    await keep.click();

    await expect(
        page.getByRole('status').filter({ hasText: /reste pour vous/i }),
    ).toBeVisible();
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
