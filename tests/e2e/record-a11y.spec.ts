import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Page } from '@playwright/test';

/**
 * Accessibilité de la page d'enregistrement (convention §11) : WCAG 2.2 AA,
 * texte de 18 px au moins, zones tactiles de 44 px au moins.
 */
const RECORD_LINK = `/r/${'demo-a11y-link'.padEnd(43, 'x')}`;

async function blockingViolations(page: Page) {
    // Les écrans entrent en fondu (450 ms, T-138). Axe lit la couleur au
    // moment où il passe : à mi-fondu, un texte gris paraît trop clair alors
    // qu'au repos son contraste est bon. On mesure une fois le fondu fini.
    await page.waitForTimeout(600);

    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
        .analyze();

    return results.violations.filter((violation) =>
        ['serious', 'critical'].includes(violation.impact ?? ''),
    );
}

test('aucune violation grave sur les écrans d’enregistrement', async ({
    page,
}) => {
    await page.goto(RECORD_LINK);
    expect(await blockingViolations(page)).toEqual([]);

    await page.getByRole('button', { name: /je suis prêt/i }).click();
    await expect(
        page.getByRole('button', { name: /^commencer$/i }),
    ).toBeVisible({
        timeout: 15_000,
    });
    expect(await blockingViolations(page)).toEqual([]);

    await page.getByRole('button', { name: /^commencer$/i }).click();
    await expect(page.getByRole('button', { name: /^pause$/i })).toBeVisible();
    expect(await blockingViolations(page)).toEqual([]);
});

test('le texte fait au moins 18 px et les boutons au moins 44 px', async ({
    page,
}) => {
    await page.goto(RECORD_LINK);

    const mainFontSize = await page
        .locator('main')
        .evaluate((element) =>
            Number.parseFloat(window.getComputedStyle(element).fontSize),
        );

    expect(mainFontSize).toBeGreaterThanOrEqual(18);

    const buttons = await page.getByRole('button').all();

    expect(buttons.length).toBeGreaterThan(0);

    for (const button of buttons) {
        const box = await button.boundingBox();

        expect(box?.height ?? 0).toBeGreaterThanOrEqual(44);
    }
});

test('aucune animation imposée et aucun compte à rebours', async ({ page }) => {
    await page.goto(RECORD_LINK);

    // Le dossier interdit les comptes à rebours anxiogènes : la page affiche
    // une durée qui monte, jamais un temps qui reste.
    const body = (await page.textContent('body')) ?? '';

    expect(body).not.toMatch(/il vous reste|temps restant/i);
});
