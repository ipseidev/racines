import { expect, test } from '@playwright/test';

/**
 * Le moment H0, accepté.
 *
 * Le parcours le plus important du produit après l'enregistrement : un cadeau
 * devient un consentement. Ce que le test vérifie en creux compte autant —
 * aucun micro, aucune question, aucun aperçu avant que la personne ait dit
 * oui.
 */
const OPTIN = `/i/${'demo-optin-accept-link'.padEnd(43, 'x')}`;

test('accepte le cadeau et voit sa première question annoncée', async ({
    page,
}) => {
    await page.goto(OPTIN);

    await expect(
        page.getByText('J’aimerais garder tes histoires, maman.'),
    ).toBeVisible();

    // Rien qui ressemble à un enregistrement avant l'acceptation.
    await expect(page.locator('audio')).toHaveCount(0);
    await expect(page.getByRole('button', { name: /enregistr/i })).toHaveCount(
        0,
    );

    const boxes = page.getByRole('checkbox');
    await expect(boxes).toHaveCount(5);

    for (let index = 0; index < 5; index += 1) {
        await boxes.nth(index).check();
    }

    await page.getByRole('button', { name: 'J’accepte' }).click();

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'Bienvenue',
    );

    // La fiche contact d'abord : un message qui n'arrive pas de ce contact
    // est un faux.
    await expect(
        page.getByRole('link', { name: 'Ajouter le contact' }),
    ).toBeVisible();

    // Et les souhaits pour plus tard, avec « Plus tard » proposé aussi
    // visiblement que l'autre choix.
    const later = page.getByRole('button', { name: 'Plus tard' });
    const now = page.getByRole('button', {
        name: 'Dire mes souhaits maintenant',
    });

    await expect(later).toBeVisible();
    await expect(now).toBeVisible();

    await later.click();

    // « Plus tard » ne poste rien : la section se replie, et la personne sait
    // qu'elle pourra y revenir.
    await expect(page.getByText(/quand vous voudrez/)).toBeVisible();
});

test('refuse d’avancer sans les cinq accords', async ({ page }) => {
    await page.goto(OPTIN);

    const boxes = page.getByRole('checkbox');
    await boxes.nth(0).check();

    await page.getByRole('button', { name: 'J’accepte' }).click();

    // Le serveur tranche, pas seulement le navigateur : la page reste, avec
    // ses cases.
    await expect(page.getByRole('checkbox')).toHaveCount(5);
});
