import { expect, test } from '@playwright/test';

/**
 * L'espace de l'Initiateur·rice.
 *
 * Une chose y compte plus que le reste : **elle voit où en est chaque
 * histoire, jamais son contenu tant que le narrateur ne l'a pas partagée.**
 * C'est le même invariant que pour les proches, et il vaut aussi pour celle
 * qui paie.
 */
const EMAIL = 'espace@example.test';
const PASSWORD = process.env.ADMIN_PASSWORD ?? 'password';

/*
 * L'espace demande un compte, contrairement à toutes les autres pages du
 * produit : c'est l'Initiateur·rice qui organise et qui paie. Les libellés du
 * formulaire de connexion viennent du kit et sont en anglais — ils seront
 * traduits au bloc 16 avec le reste de l'espace authentifié, et le test se
 * cale donc sur les types de champ plutôt que sur les mots.
 */
test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[type="email"]').first().fill(EMAIL);
    await page.locator('input[type="password"]').first().fill(PASSWORD);
    await page.locator('form button[type="submit"]').first().click();
    await page.waitForURL(/dashboard|espace/);
});

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
        page.getByText('Quel était le métier de votre mère ?'),
    ).toBeVisible();
    await expect(page.locator('audio')).toHaveCount(0);
});

test('réémet le lien de la semaine et prépare le message WhatsApp', async ({
    page,
}) => {
    await page.goto('/espace');

    await page
        .getByRole('button', { name: 'Copier le lien de cette semaine' })
        .click();

    await expect(page.getByText('Lien prêt à coller')).toBeVisible();

    const whatsapp = page.getByRole('link', { name: 'Envoyer par WhatsApp' });
    await expect(whatsapp).toBeVisible();
    await expect(whatsapp).toHaveAttribute('href', /wa\.me/);
});

test('réordonne les questions', async ({ page }) => {
    await page.goto('/espace/questions');

    await page.getByRole('button', { name: 'Descendre' }).first().click();
    await page.getByRole('button', { name: 'Enregistrer l’ordre' }).click();

    await expect(page.getByText('L’ordre est enregistré.')).toBeVisible();
});

test('invite un proche', async ({ page }) => {
    await page.goto('/espace/proches');

    await page.getByLabel('Son prénom').fill('Claire');
    await page
        .getByLabel('Son courriel')
        .fill(`claire+${Date.now()}@example.test`);
    await page.getByRole('button', { name: 'Envoyer l’invitation' }).click();

    await expect(page.getByText('L’invitation est partie.')).toBeVisible();
});

test('change la cadence', async ({ page }) => {
    await page.goto('/espace/reglages');

    await page
        .getByLabel('Fréquence des questions')
        .selectOption({ label: 'Une question tous les quinze jours' });
    await page.getByRole('button', { name: 'Enregistrer' }).first().click();

    await expect(
        page.getByText('Vos réglages sont enregistrés.'),
    ).toBeVisible();
});
