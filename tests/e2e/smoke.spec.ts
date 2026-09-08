import { expect, test } from '@playwright/test';

/*
 * Le minimum qu'on exige des pages de vente : elles répondent.
 *
 * L'audit d'accessibilité de l'accueil est parti avec T-218, comme le reste
 * des vérifications automatiques de la landing. Ce qui reste ici ne dépend
 * d'aucun mot ni d'aucune marge : une page 200 qui porte un titre.
 */
test('la page d’accueil répond et porte le nom du produit', async ({
    page,
}) => {
    const response = await page.goto('/');

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(/.+/);
});

test('le témoin répond, hors index', async ({ page }) => {
    const response = await page.goto('/lp/temoin');

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(/.+/);

    // Ce qui compte sur cette page-là : qu'elle ne se dispute pas le trafic
    // de l'accueil, qui sert la même offre (T-220).
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute(
        'content',
        'noindex, follow',
    );
});

test('l’ancienne URL de la variante mène à l’accueil', async ({ page }) => {
    // Des liens ont pu être posés sur `/lp/histoire` avant qu'elle ne devienne
    // l'accueil : une redirection permanente, pas un 404 (T-220).
    await page.goto('/lp/histoire');

    expect(new URL(page.url()).pathname).toBe('/');
});
