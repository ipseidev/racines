import { expect, test } from '@playwright/test';

const link = (name: string) =>
    `/r/${'demo-'.concat(name, '-link').padEnd(43, 'x')}`;

/**
 * Variante B : le narrateur relit son texte transcrit, puis décide.
 *
 * Rien ne lui a été demandé à l'enregistrement. Ce que la page doit lui
 * donner : son audio, les deux textes, la mention de l'IA, la possibilité de
 * corriger, et les trois choix sans présélection.
 */
test('la relecture montre les deux textes et la mention de l’IA', async ({
    page,
}) => {
    const response = await page.goto(`${link('variant-b')}/review`);

    expect(response?.status()).toBe(200);

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'L’odeur du pain',
    );

    // Le texte mis au propre, et la mention qui dit d'où il vient.
    await expect(
        page.getByText('Je me souviens de l’odeur du pain'),
    ).toBeVisible();
    await expect(page.getByText(/mis au propre par une IA/i)).toBeVisible();

    // Le mot à mot est offert au même niveau : c'est la parole de la personne.
    await page.getByRole('tab', { name: /mot à mot/i }).click();
    await expect(page.getByText(/Alors euh je me souviens/)).toBeVisible();
});

test('les trois choix sont là, sans présélection ni minuteur', async ({
    page,
}) => {
    await page.goto(`${link('variant-b')}/review`);

    for (const label of [
        'Partager avec mes proches',
        'Garder pour moi',
        'Décider plus tard',
    ]) {
        const button = page.getByRole('button', { name: new RegExp(label) });
        await expect(button).toBeVisible();

        const box = await button.boundingBox();
        expect(box?.height ?? 0).toBeGreaterThanOrEqual(44);
    }

    // Aucun bouton radio de visibilité coché autrement que sur le défaut
    // « tous mes proches », et aucun minuteur nulle part.
    await expect(page.getByRole('radio', { checked: true })).toHaveCount(1);
    await expect(page.locator('progress')).toHaveCount(0);
});

test('une correction s’enregistre sans écraser le mot à mot', async ({
    page,
}) => {
    await page.goto(`${link('variant-b-edit')}/review`);

    await page.getByRole('button', { name: /corriger le texte/i }).click();

    const textarea = page.getByLabel('Votre texte');
    await textarea.fill('Je me souviens de l’odeur du pain chaud, le matin.');
    await page
        .getByRole('button', { name: /enregistrer ma correction/i })
        .click();

    await expect(page.getByRole('status')).toContainText(
        /correction est enregistrée/i,
    );
    await expect(page.getByText('odeur du pain chaud, le matin')).toBeVisible();

    // Le mot à mot est intact : une correction ajoute, elle ne remplace pas.
    await page.getByRole('tab', { name: /mot à mot/i }).click();
    await expect(page.getByText(/Alors euh je me souviens/)).toBeVisible();
});

test('partager mène au remerciement, et ferme le lien', async ({ page }) => {
    await page.goto(`${link('variant-b-share')}/review`);

    await page
        .getByRole('button', { name: /partager avec mes proches/i })
        .click();

    await expect(page.getByRole('status')).toContainText(/vos proches/i);

    // Le lien a servi : il ne porte plus rien, et la page qui le dit est
    // amicale, pas une erreur technique.
    const again = await page.goto(`${link('variant-b-share')}/review`);
    expect(again?.status()).toBe(410);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
});
