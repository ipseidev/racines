import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

/**
 * Les liens de démonstration sont semés par DemoProjectSeeder, qui refuse de
 * tourner en production. Leurs valeurs sont connues pour que ce test puisse
 * ouvrir les trois écrans sans passer par la base.
 */
const link = (name: string) =>
    `/r/${'demo-'.concat(name, '-link').padEnd(43, 'x')}`;

const blockingViolations = async (
    page: Parameters<typeof AxeBuilder>[0]['page'],
) => {
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
        .analyze();

    return results.violations.filter((violation) =>
        ['serious', 'critical'].includes(violation.impact ?? ''),
    );
};

test('un lien valable ouvre la page du narrateur', async ({ page }) => {
    const response = await page.goto(link('record'));

    expect(response?.status()).toBe(200);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    // Aucune donnée personnelle sur une page atteinte par un lien porteur.
    const body = (await page.textContent('body')) ?? '';
    expect(body).not.toContain('+33600000000');
    expect(body).not.toContain('Delaunay');
});

test('un lien expiré affiche la page amicale, sans erreur technique', async ({
    page,
}) => {
    const response = await page.goto(link('expired'));

    expect(response?.status()).toBe(410);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(
        page.getByRole('button', { name: /nouveau lien/i }),
    ).toBeVisible();

    const body = (await page.textContent('body')) ?? '';
    expect(body).not.toMatch(/exception|stack trace|SQLSTATE/i);

    expect(await blockingViolations(page)).toEqual([]);
});

test('un lien révoqué affiche la page amicale', async ({ page }) => {
    const response = await page.goto(link('revoked'));

    expect(response?.status()).toBe(410);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    expect(await blockingViolations(page)).toEqual([]);
});

test('un lien bricolé reçoit un 404 sec', async ({ page }) => {
    const response = await page.goto('/r/trop-court');

    expect(response?.status()).toBe(404);
});

test('le bouton de renvoi enregistre la demande', async ({ page }) => {
    await page.goto(link('expired'));

    await page.getByRole('button', { name: /nouveau lien/i }).click();

    await expect(page.getByRole('status')).toBeVisible();
});

test('les pages à jeton ne sont ni mises en cache ni indexées', async ({
    page,
}) => {
    const response = await page.goto(link('record'));

    expect(response?.headers()['cache-control']).toContain('no-store');
    expect(response?.headers()['x-robots-tag']).toBe('noindex, nofollow');
    expect(response?.headers()['referrer-policy']).toBe('no-referrer');
    expect(response?.headers()['permissions-policy']).toContain(
        'microphone=(self)',
    );
});
