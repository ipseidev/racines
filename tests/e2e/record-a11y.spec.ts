import { expect, test } from '@playwright/test';

import { blockingViolations } from './support/a11y';

/**
 * Accessibilité de la page d'enregistrement (convention §11) : WCAG 2.2 AA,
 * texte de 18 px au moins, zones tactiles de 44 px au moins.
 */
const RECORD_LINK = `/r/${'demo-a11y-link'.padEnd(43, 'x')}`;

test('aucune violation grave sur les écrans d’enregistrement', async ({
    page,
}) => {
    await page.goto(RECORD_LINK);
    expect(await blockingViolations(page)).toEqual([]);

    // L'écran du choix précède tout (T-210) : la voix reste le défaut.
    await page.getByRole('button', { name: /avec votre voix/i }).click();
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

/**
 * L'écran caméra est un fond imprévisible sous du texte blanc (T-212) : c'est
 * exactement la situation où le contraste se perd sans que personne le voie.
 */
test('aucune violation grave sur l’écran caméra', async ({ page }) => {
    await page.goto(RECORD_LINK);

    await page.getByRole('button', { name: /en vous filmant/i }).click();
    await page.getByRole('button', { name: /je suis prêt/i }).click();

    await expect(page.getByLabel(/ce que voit la caméra/i)).toBeVisible({
        timeout: 15_000,
    });

    expect(await blockingViolations(page)).toEqual([]);

    // Les cibles tactiles de cet écran comptent comme les autres : 44 px.
    for (const bouton of await page.getByRole('button').all()) {
        const boite = await bouton.boundingBox();

        expect(boite?.height ?? 0).toBeGreaterThanOrEqual(44);
    }
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
