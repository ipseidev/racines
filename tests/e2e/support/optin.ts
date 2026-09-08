import { expect, type Page } from '@playwright/test';

/**
 * Ouvrir une invitation, et laisser l'ouverture se jouer jusqu'au bout.
 *
 * La page d'opt-in s'ouvre par un rideau (T-232) : le nom, « Bonjour
 * Odette, », puis ce qu'on lui offre, une phrase à la fois, sous douze
 * secondes (`overtureDuration`, gardé par un test Vitest). Le rideau est un décor
 * `aria-hidden` : ce qui est dessous répond déjà aux sélecteurs, mais pas
 * aux gestes — un clic pendant l'ouverture l'attendrait en silence. On la
 * regarde donc jusqu'au bout, comme le ferait la personne.
 */
export async function openInvitation(page: Page, path: string): Promise<void> {
    await page.goto(path);

    // La page est rendue par le navigateur : tant qu'elle n'est pas montée,
    // « aucun rideau » est vrai trop tôt. On attend d'abord son titre.
    await page.locator('h1').first().waitFor({ state: 'attached' });

    await expect(page.locator('[data-overture]')).toHaveCount(0, {
        timeout: 20_000,
    });
}
