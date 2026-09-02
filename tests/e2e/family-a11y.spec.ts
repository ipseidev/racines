import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

/**
 * L'espace famille, passé au crible de l'accessibilité.
 *
 * Le public de ce produit lit petit, entend mal et touche imprécisément. Une
 * violation grave ici n'est pas un détail de conformité : c'est un proche qui
 * n'écoute pas.
 */
const LISTEN = `/l/${'demo-listen-a11y-link'.padEnd(43, 'x')}`;

async function blockingViolations(
    page: Parameters<typeof AxeBuilder>[0]['page'],
) {
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();

    return results.violations.filter(
        (violation) =>
            violation.impact === 'critical' || violation.impact === 'serious',
    );
}

test('aucune violation grave sur la liste des histoires', async ({ page }) => {
    await page.goto(LISTEN);

    const violations = await blockingViolations(page);

    expect(
        violations.map((one) => `${one.id}: ${one.help}`),
        JSON.stringify(violations, null, 2),
    ).toEqual([]);
});

test('aucune violation grave sur la page d’écoute', async ({ page }) => {
    await page.goto(LISTEN);
    await page.getByText('L’odeur du pain').click();
    await expect(page.getByRole('button', { name: 'Écouter' })).toBeVisible();

    const violations = await blockingViolations(page);

    expect(
        violations.map((one) => `${one.id}: ${one.help}`),
        JSON.stringify(violations, null, 2),
    ).toEqual([]);
});

test('le texte fait au moins 18 px et les commandes au moins 44 px', async ({
    page,
}) => {
    await page.goto(LISTEN);
    await page.getByText('L’odeur du pain').click();
    await expect(page.getByRole('button', { name: 'Écouter' })).toBeVisible();

    // Le bouton principal du lecteur est plus grand que le minimum : c'est
    // celui qu'on cherche du doigt en premier.
    for (const name of ['Écouter', 'Reculer de 15 secondes', 'Merci']) {
        const box = await page.getByRole('button', { name }).boundingBox();

        expect(box?.height ?? 0, name).toBeGreaterThanOrEqual(44);
    }

    // Mesuré sur le contenu, comme côté narrateur : la mise en page porte la
    // taille de base, et c'est ce que la personne lit.
    const mainFontSize = await page
        .locator('main')
        .evaluate((element) =>
            Number.parseFloat(window.getComputedStyle(element).fontSize),
        );

    expect(mainFontSize).toBeGreaterThanOrEqual(18);
});
