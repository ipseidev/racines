import { expect, test } from '@playwright/test';

/**
 * Le moment H0, refusé.
 *
 * Le chemin qu'on écrit le plus mal d'habitude. Trois choses s'y vérifient :
 * le bouton de refus a le même poids que l'autre, il n'y a pas de « êtes-vous
 * sûr » qui insiste, et la page d'adieu ne négocie rien.
 */
const OPTIN = `/i/${'demo-optin-refuse-link'.padEnd(43, 'x')}`;

test('le refus a le même poids visuel que l’acceptation', async ({ page }) => {
    await page.goto(OPTIN);

    const accept = page.getByRole('button', { name: 'J’accepte' });
    const refuse = page.getByRole('button', { name: 'Non merci' });

    const accepted = await accept.boundingBox();
    const refused = await refuse.boundingBox();

    expect(accepted).not.toBeNull();
    expect(refused).not.toBeNull();

    // Même hauteur, et largeurs à quelques pixels près : rendre le refus
    // discret ne produit pas des oui, ça produit des silences.
    expect(refused?.height).toBeCloseTo(accepted?.height ?? 0, 0);
    expect(
        Math.abs((refused?.width ?? 0) - (accepted?.width ?? 0)),
    ).toBeLessThan(2);
});

test('décline le cadeau, sans avoir à se justifier', async ({ page }) => {
    await page.goto(OPTIN);

    await page.getByRole('button', { name: 'Non merci' }).click();

    // Le motif est facultatif, et « je préfère ne rien dire » est le choix
    // par défaut.
    await expect(page.getByText('Je préfère ne rien dire')).toBeVisible();

    await page.getByRole('button', { name: 'Confirmer mon refus' }).click();

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'C’est noté',
    );

    // Aucune relance, aucune négociation : on dit ce qui va se passer.
    await expect(page.getByText(/trente jours/)).toBeVisible();
    await expect(page.getByRole('button', { name: 'J’accepte' })).toHaveCount(
        0,
    );
});
