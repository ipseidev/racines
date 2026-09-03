import { expect, type Page, test } from '@playwright/test';

import { freshTotp } from './support/totp';

/**
 * Le back-office, de la connexion à la trace laissée.
 *
 * Deux choses s'y jouent, et la seconde est la raison d'être du bloc 11 : la
 * double authentification est franchissable — donc réellement obligatoire, et
 * pas contournée en test — et ouvrir la fiche d'une histoire n'offre **aucun**
 * moyen de la valider ou de la partager. C'est ce qui rend le back-office
 * acceptable : sans ça, « seuls les proches autorisés peuvent écouter » serait
 * faux.
 *
 * Une seule connexion pour tout le parcours : la page de connexion du panneau
 * limite le nombre de tentatives, et trois connexions d'affilée rendraient le
 * test fragile pour une raison qui n'a rien à voir avec le produit.
 */
const ADMIN_EMAIL = process.env.ADMIN_EMAIL ?? 'admin@example.test';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD ?? 'password';

async function connexion(page: Page) {
    await page.goto('/admin/login');
    await page.locator('input[type="email"]').first().fill(ADMIN_EMAIL);
    await page.locator('input[type="password"]').first().fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /^connexion$/i }).click();

    // Un second écran, et pas un simple message : le mot de passe seul
    // n'ouvre rien.
    await expect(
        page.getByRole('heading', { name: /vérifier votre identité/i }),
    ).toBeVisible();

    await page.getByRole('group').locator('input').first().click();
    await page.keyboard.type(await freshTotp(), { delay: 30 });
    await page.getByRole('button', { name: /confirmer la connexion/i }).click();

    await page.waitForURL((url) => /\/admin\/?$/.test(url.pathname), {
        timeout: 15_000,
    });
}

test('le back-office s’ouvre après le second facteur, et n’offre aucun partage', async ({
    page,
}) => {
    await connexion(page);

    // 1. Les nombres qui déclenchent un geste.
    await expect(page.getByText('Projets actifs')).toBeVisible();
    await expect(page.getByText('Envois échoués')).toBeVisible();

    // 2. La fiche d'une histoire : consultable, jamais validable.
    await page.goto('/admin/stories');
    // « Les histoires », et non « Les Histoires » : le français ne met la
    // majuscule qu'au premier mot, y compris dans le back-office.
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(
        'Les histoires',
    );

    await page.locator('table tbody tr a').first().click();
    await expect(page.getByText('Où en est').first()).toBeVisible();

    /*
     * L'invariant du produit, vérifié là où la tentation se manifesterait :
     * à l'écran. Le test Pest le vérifie sur le code ; celui-ci vérifie qu'il
     * n'y a pas de bouton.
     */
    await expect(page.getByRole('button', { name: /^valider$/i })).toHaveCount(
        0,
    );
    await expect(page.getByRole('button', { name: /^partager$/i })).toHaveCount(
        0,
    );

    // 3. Les playbooks, dans l'outil de travail, le plus grave en premier.
    await page.goto('/admin/playbooks');

    const titres = await page.locator('summary').allInnerTexts();

    expect(titres).toHaveLength(6);
    expect(titres[0]).toContain('Décès');
});
