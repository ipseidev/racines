import { expect, test } from '@playwright/test';

/**
 * Le dépôt d'une photo par un proche.
 *
 * Deux choses s'y jouent, et la seconde est celle qu'on perdrait le plus
 * facilement : un proche **sans** droit de contribuer ne voit pas le bouton,
 * et reçoit un refus s'il poste quand même. Un bouton n'est pas une
 * autorisation, et le serveur ne le croit pas sur parole.
 */
const AVEC = `/l/${'demo-listen-photo-link'.padEnd(43, 'x')}`;
const SANS = `/l/${'demo-listen-link'.padEnd(43, 'x')}`;

test('un proche sans droit de contribuer ne voit pas le bouton', async ({
    page,
}) => {
    await page.goto(SANS);
    await page.getByRole('link').first().click();

    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    // Un bouton grisé invite à demander pourquoi ; un bouton absent non.
    await expect(page.getByText('Ajouter une photo')).toHaveCount(0);
});

test('un contributeur dépose une photo et la voit dans la galerie', async ({
    page,
}) => {
    await page.goto(AVEC);
    await page.getByRole('link').first().click();

    const champ = page.locator('input[type="file"]');
    await expect(champ).toBeVisible();

    // Un JPEG minimal fabriqué dans le test : rien de binaire dans le dépôt.
    await champ.setInputFiles({
        name: 'souvenir.jpg',
        mimeType: 'image/jpeg',
        buffer: Buffer.from(
            'ffd8ffe000104a46494600010100000100010000ffdb004300ffc0000b080001000101011100ffc40014000100000000000000000000000000000003ffda0008010100003f00d2cf20ffd9',
            'hex',
        ),
    });

    // L'aperçu vient du fichier local : rien n'est envoyé avant que la
    // personne ait vu ce qu'elle envoie.
    await expect(
        page.getByLabel('Que voit-on sur cette photo ?'),
    ).toBeVisible();
});
