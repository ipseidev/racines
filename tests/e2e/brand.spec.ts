import { expect, type Page, test } from '@playwright/test';

const ADMIN_EMAIL = process.env.ADMIN_EMAIL ?? 'admin@example.test';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD ?? 'password';

const NOM_INITIAL = 'Racines';
const PRIMAIRE_INITIALE = '#1F3D2B';
const TEXTE_INITIAL = '#1B1B1B';
const FOND_INITIAL = '#F7F5EF';

/**
 * Les libellés Filament portent un astérisque quand le champ est obligatoire :
 * on cible par identifiant de champ, stable et sans ambiguïté.
 */
function champ(page: Page, nom: string) {
    return page.locator(`#form\\.${nom}`);
}

async function enregistrer(page: Page) {
    await page
        .getByRole('button', { name: /^enregistrer$/i })
        .first()
        .click();
}

/**
 * Une seule connexion pour tout le parcours : la page de connexion limite le
 * nombre de tentatives, et l'enchaînement de connexions rendait le test fragile.
 */
test('la marque se change depuis l’administration et s’applique sans redéploiement', async ({
    page,
}) => {
    await page.goto('/admin/login');
    await page.locator('input[type="email"]').first().fill(ADMIN_EMAIL);
    await page.locator('input[type="password"]').first().fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /^connexion$/i }).click();

    // Un échec de connexion ne doit pas se manifester par un délai muet :
    // on rapporte le message affiché à l'écran.
    try {
        await page.waitForURL((url) => /\/admin\/?$/.test(url.pathname), {
            timeout: 15_000,
        });
    } catch (cause) {
        const messages = await page
            .locator(
                '[data-validation-error], .fi-fo-field-wrp-error-message, [role="alert"]',
            )
            .allTextContents();

        throw new Error(
            `Connexion impossible. URL : ${page.url()}. Messages : ${
                messages.join(' | ') || '(aucun)'
            }`,
            { cause },
        );
    }

    // 1. Un changement de nom et de couleur se voit immédiatement côté public.
    await page.goto('/admin/manage-brand');
    await champ(page, 'product_name').fill('Essai Playwright');
    await champ(page, 'color_primary').fill('#8B0000');
    await enregistrer(page);
    await expect(page.getByText(/marque enregistrée/i)).toBeVisible();

    await page.goto('/');
    await expect(page).toHaveTitle(/· Essai Playwright$/);
    expect(await page.content()).toContain('--brand-primary: #8B0000');
    await expect(
        page.getByText('Essai Playwright', { exact: true }),
    ).toBeVisible();

    // 2. Une combinaison illisible est refusée.
    await page.goto('/admin/manage-brand');
    await champ(page, 'color_text').fill('#CCCCCC');
    await champ(page, 'color_background').fill('#FFFFFF');
    await enregistrer(page);
    await expect(page.getByText(/contraste insuffisant/i)).toBeVisible();

    // 3. Remise en état, pour que le test soit rejouable.
    await page.reload();
    await champ(page, 'product_name').fill(NOM_INITIAL);
    await champ(page, 'color_primary').fill(PRIMAIRE_INITIALE);
    await champ(page, 'color_text').fill(TEXTE_INITIAL);
    await champ(page, 'color_background').fill(FOND_INITIAL);
    await enregistrer(page);
    await expect(page.getByText(/marque enregistrée/i)).toBeVisible();

    await page.goto('/');
    await expect(page).toHaveTitle(new RegExp(`· ${NOM_INITIAL}$`));
});
