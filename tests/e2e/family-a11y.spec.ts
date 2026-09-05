import { expect, test } from '@playwright/test';

import { blockingViolations } from './support/a11y';

/**
 * L'espace famille, passé au crible de l'accessibilité.
 *
 * Le public de ce produit lit petit, entend mal et touche imprécisément. Une
 * violation grave ici n'est pas un détail de conformité : c'est un proche qui
 * n'écoute pas.
 */
const LISTEN = `/l/${'demo-listen-a11y-link'.padEnd(43, 'x')}`;

test('aucune violation grave sur la liste des histoires', async ({ page }) => {
    await page.goto(LISTEN);

    const violations = await blockingViolations(page, ['wcag2a', 'wcag2aa']);

    expect(
        violations.map((one) => `${one.id}: ${one.help}`),
        JSON.stringify(violations, null, 2),
    ).toEqual([]);
});

test('aucune violation grave sur la page d’écoute', async ({ page }) => {
    await page.goto(LISTEN);
    await page.getByText('L’odeur du pain').click();
    await expect(page.getByRole('button', { name: 'Écouter' })).toBeVisible();

    const violations = await blockingViolations(page, ['wcag2a', 'wcag2aa']);

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

/*
 * Sur un téléphone étroit, aucun libellé de bouton ne se coupe en deux.
 *
 * Trouvé sur un vrai appareil pendant le checkpoint du bloc 08 : « J'ai
 * aimé » passait à la ligne à l'intérieur de son bouton. Aucune assertion ne
 * pouvait le voir — un bouton sur deux lignes reste haut de plus de 44 px, et
 * l'axe des contrastes ne regarde pas la mise en page. On mesure donc la
 * hauteur au plus étroit des écrans visés.
 */
test('aucun libellé ne se coupe en deux sur un écran étroit', async ({
    page,
}) => {
    await page.setViewportSize({ width: 320, height: 780 });
    await page.goto(LISTEN);
    await page.getByText('L’odeur du pain').click();
    await expect(page.getByRole('button', { name: 'Écouter' })).toBeVisible();

    for (const name of ['J’ai aimé', 'Merci']) {
        const button = page.getByRole('button', { name });
        const box = await button.boundingBox();
        const lines = await button.evaluate((element) => {
            const style = window.getComputedStyle(element);
            const lineHeight = Number.parseFloat(style.lineHeight);

            return Number.isFinite(lineHeight)
                ? element.scrollHeight / lineHeight
                : 1;
        });

        expect(box?.height ?? 0, name).toBeGreaterThanOrEqual(44);
        // Une ligne de texte, quelle que soit la hauteur du bouton.
        expect(lines, `${name} tient sur une ligne`).toBeLessThan(2);
    }
});
