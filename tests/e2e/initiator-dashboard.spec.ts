import { expect, test } from './support/initiator-auth';

/**
 * L'espace de l'Initiateur·rice.
 *
 * Une chose y compte plus que le reste : **elle voit où en est chaque
 * histoire, jamais son contenu tant que le narrateur ne l'a pas partagée.**
 * C'est le même invariant que pour les proches, et il vaut aussi pour celle
 * qui paie.
 *
 * Depuis la passe de design (T-149), chaque geste répond là où il est fait :
 * un toast en bas de l'écran, la fiche de partage dans la carte, l'ordre des
 * questions qui part tout seul.
 */
test('montre la frise des histoires et le titre des seules partagées', async ({
    page,
}) => {
    await page.goto('/espace');

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'Odette',
    );

    // L'histoire partagée porte son titre.
    await expect(page.getByText('Le village de mon enfance')).toBeVisible();

    // La question en cours est visible — c'est elle qui l'a choisie — mais
    // aucun lecteur audio n'apparaît : le contenu n'est pas de son ressort.
    await expect(
        page.getByText('Quel était le métier de votre mère ?').first(),
    ).toBeVisible();
    await expect(page.locator('audio')).toHaveCount(0);
});

test('envoie le lien de la semaine : la fiche apparaît dans la carte', async ({
    page,
}) => {
    await page.goto('/espace');

    await page
        .getByRole('button', { name: 'Envoyer le lien à Odette' })
        .click();

    await expect(page.getByText('Le lien est prêt')).toBeVisible();

    const whatsapp = page.getByRole('link', { name: 'WhatsApp' });
    await expect(whatsapp).toHaveAttribute('href', /wa\.me/);
    await expect(page.getByRole('link', { name: 'SMS' })).toHaveAttribute(
        'href',
        /^sms:/,
    );

    await page.getByRole('button', { name: 'Copier le lien' }).click();
    await expect(page.getByRole('button', { name: 'Copié' })).toBeVisible();
});

test('ouvre l’écoute directement, dans un nouvel onglet', async ({ page }) => {
    await page.goto('/espace');

    const listen = page.getByRole('link', { name: /Ouvrir ma page d’écoute/ });

    await expect(listen).toHaveAttribute('href', '/espace/ecoute');
    await expect(listen).toHaveAttribute('target', '_blank');
});

test('réordonne les questions, et l’ordre part tout seul', async ({ page }) => {
    await page.goto('/espace/questions');

    const cards = page
        .getByRole('list', { name: 'Les prochaines questions' })
        .getByRole('listitem');
    const first = (await cards.nth(0).locator('p').first().textContent()) ?? '';
    const second =
        (await cards.nth(1).locator('p').first().textContent()) ?? '';

    await page.getByRole('button', { name: 'Descendre' }).first().click();

    // Aucun bouton « Enregistrer » : l'ordre part après le dernier geste.
    await expect(page.getByText('L’ordre est enregistré.')).toBeVisible();

    await page.reload();

    await expect(cards.nth(0).locator('p').first()).toHaveText(second);
    await expect(cards.nth(1).locator('p').first()).toHaveText(first);

    // On remet les choses en place pour la personne qui rejouera le décor.
    await page.getByRole('button', { name: 'Monter' }).nth(1).click();
    await expect(page.getByText('L’ordre est enregistré.')).toBeVisible();
});

test('écarte une question, puis la remet', async ({ page }) => {
    await page.goto('/espace/questions');

    const cards = page
        .getByRole('list', { name: 'Les prochaines questions' })
        .getByRole('listitem');
    const first = (await cards.nth(0).locator('p').first().textContent()) ?? '';

    await page.getByRole('button', { name: 'Écarter' }).first().click();
    await expect(page.getByText('C’est enregistré.')).toBeVisible();

    await expect(cards.nth(0).locator('p').first()).not.toHaveText(first);

    await page.getByText(/Questions écartées \(\d+\)/).click();
    await page.getByRole('button', { name: 'Remettre' }).first().click();

    await expect(page.getByText('C’est enregistré.')).toBeVisible();
});

test('invite un proche', async ({ page }) => {
    await page.goto('/espace/proches');

    await page.getByLabel('Son prénom').fill('Claire');
    await page
        .getByLabel('Son courriel')
        .fill(`claire+${Date.now()}@example.test`);
    await page.getByRole('button', { name: 'Envoyer l’invitation' }).click();

    await expect(page.getByText('L’invitation est partie.')).toBeVisible();
    await expect(
        page.getByText('Claire', { exact: true }).first(),
    ).toBeVisible();
});

test('change la cadence, puis la remet', async ({ page }) => {
    await page.goto('/espace/reglages');

    await page
        .getByLabel('Fréquence des questions')
        .selectOption({ label: 'Une question tous les quinze jours' });
    await page.getByRole('button', { name: 'Enregistrer' }).first().click();

    await expect(
        page.getByText('Vos réglages sont enregistrés.'),
    ).toBeVisible();
    await expect(page.getByText('Enregistré', { exact: true })).toBeVisible();

    await page
        .getByLabel('Fréquence des questions')
        .selectOption({ label: 'Une question par semaine' });
    await page.getByRole('button', { name: 'Enregistrer' }).first().click();

    await expect(
        page.getByText('Vos réglages sont enregistrés.'),
    ).toBeVisible();
});

test('demande une confirmation avant la rétractation', async ({ page }) => {
    await page.goto('/espace/commandes');

    await page
        .getByRole('button', { name: 'Exercer mon droit de rétractation' })
        .click();

    const dialog = page.getByRole('alertdialog');
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText('rétractation');

    await dialog.getByRole('button', { name: 'Annuler' }).click();
    await expect(dialog).toBeHidden();
});
