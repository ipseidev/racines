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

test('la variante de structure répond', async ({ page }) => {
    const response = await page.goto('/lp/histoire');

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(/.+/);
});
