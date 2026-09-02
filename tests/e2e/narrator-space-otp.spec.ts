import { expect, test } from '@playwright/test';

/**
 * L'espace narrateur : un code, jamais un mot de passe.
 *
 * Le code est **semé** avec une valeur connue plutôt qu'exposé par une route
 * de test : une route qui révèle des codes à usage unique est exactement le
 * genre d'affordance qui finit activée quelque part (T-78).
 */
/*
 * Une coordonnée par test : trois demandes de code par heure et par
 * coordonnée est la bonne règle produit, et un décor partagé la ferait
 * échouer au troisième test au lieu de la vérifier.
 */
const PHONE = '+33600000042';

const PHONE_CODE = '+33600000043';

const PHONE_WRONG = '+33600000044';

const CODE = '424242';

/** Lecture seule : aucun test n'agit sur les histoires de ce lien. */
const SPACE_LINK = `/n/${'demo-space-link'.padEnd(43, 'x')}`;

/** Un lien à part pour le test qui déclenche un acte sensible. */
const SPACE_ACT_LINK = `/n/${'demo-space-act-link'.padEnd(43, 'x')}`;

test('la page d’entrée ne dit pas si la coordonnée est connue', async ({
    page,
}) => {
    await page.goto('/n/request');

    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    await page.getByLabel(/numéro ou adresse/i).fill('+33699999999');
    await page.getByRole('button', { name: /recevoir un code/i }).click();

    // La même phrase pour un inconnu que pour un narrateur : sinon la page
    // devient un annuaire — « ce numéro est-il chez vous ? ».
    const unknown = await page.getByRole('status').textContent();

    await page.goto('/n/request');
    await page.getByLabel(/numéro ou adresse/i).fill(PHONE);
    await page.getByRole('button', { name: /recevoir un code/i }).click();

    await expect(page.getByRole('status')).toHaveText(unknown ?? '');
});

test('un code valable ouvre les histoires du narrateur', async ({ page }) => {
    await page.goto('/n/request');

    // On n'en redemande pas un : le code semé est valable, et en demander un
    // autre invaliderait celui-là. C'est le chemin de quelqu'un qui a fermé
    // l'onglet et retrouve son SMS.
    await page.getByRole('button', { name: /j’ai déjà un code/i }).click();

    await page.getByLabel(/numéro ou adresse/i).fill(PHONE_CODE);
    await page.getByLabel(/votre code/i).fill(CODE);
    await page.getByRole('button', { name: /ouvrir mes histoires/i }).click();

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        /vos histoires/i,
    );

    // Un libellé en langage simple, jamais un nom d'état technique.
    await expect(page.getByText(/partagée avec vos proches/i)).toBeVisible();
    await expect(page.locator('body')).not.toContainText('shared');
});

test('un code faux n’ouvre rien', async ({ page }) => {
    await page.goto('/n/request');

    await page.getByRole('button', { name: /j’ai déjà un code/i }).click();

    await page.getByLabel(/numéro ou adresse/i).fill(PHONE_WRONG);
    await page.getByLabel(/votre code/i).fill('000000');
    await page.getByRole('button', { name: /ouvrir mes histoires/i }).click();

    await expect(page.getByRole('alert')).toBeVisible();
    // On est resté sur l'entrée : le titre de l'espace n'apparaît pas.
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        /accéder à vos histoires/i,
    );
});

test('l’espace n’est ni mis en cache ni indexé', async ({ page }) => {
    const response = await page.goto(SPACE_LINK);

    expect(response?.status()).toBe(200);
    expect(response?.headers()['cache-control']).toContain('no-store');
    expect(response?.headers()['x-robots-tag']).toBe('noindex, nofollow');
});

test('un acte sensible demande un code, même depuis l’espace', async ({
    page,
}) => {
    await page.goto(SPACE_ACT_LINK);

    // Le jeton d'espace ouvre toutes les histoires et a pu être obtenu il y a
    // longtemps : mettre un récit à la corbeille repasse par un code.
    await page.getByRole('button', { name: /mettre à la corbeille/i }).click();
    await page
        .getByRole('button', { name: /^mettre à la corbeille$/i })
        .last()
        .click();

    await expect(page).toHaveURL(/\/code$/);
});
