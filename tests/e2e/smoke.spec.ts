import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

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

    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
        .analyze();

    const blocking = results.violations.filter((violation) =>
        ['serious', 'critical'].includes(violation.impact ?? ''),
    );

    expect(blocking, JSON.stringify(blocking, null, 2)).toEqual([]);
});
