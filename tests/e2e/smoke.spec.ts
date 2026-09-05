import { expect, test } from '@playwright/test';

import { blockingViolations } from './support/a11y';

test('la page d’accueil répond et porte le nom du produit', async ({
    page,
}) => {
    const response = await page.goto('/');

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(/.+/);
});

test('la page d’accueil n’a aucune violation d’accessibilité grave', async ({
    page,
}) => {
    await page.goto('/');

    const blocking = await blockingViolations(page);

    expect(blocking, JSON.stringify(blocking, null, 2)).toEqual([]);
});
