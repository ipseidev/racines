import { expect, test } from '@playwright/test';

/**
 * L'alerte du moteur, résolue en un tap.
 *
 * Ce que ce fichier protège : que la page **montre** avant d'agir, et qu'un
 * seul geste suffise ensuite. L'Initiateur·rice ouvre ce lien depuis un SMS,
 * en faisant autre chose — elle a trente secondes, pas un choix à peser.
 */
/*
 * Un lien par test : le second scénario consomme le sien, et un lien partagé
 * ferait échouer les voisins — la suite tourne en parallèle.
 */
const ONE_TAP = `/a/${'demo-onetap-link'.padEnd(43, 'x')}`;

const ONE_TAP_USE = `/a/${'demo-onetap-use-link'.padEnd(43, 'x')}`;

const ONE_TAP_READ = `/a/${'demo-onetap-read-link'.padEnd(43, 'x')}`;

test('la page montre l’action sans l’exécuter', async ({ page }) => {
    const response = await page.goto(ONE_TAP);

    expect(response?.status()).toBe(200);

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        /deux semaines/i,
    );

    // Un seul bouton : la personne ouvre ce lien en faisant autre chose.
    const buttons = page.getByRole('button');
    await expect(buttons).toHaveCount(1);

    const box = await buttons.first().boundingBox();
    expect(box?.height ?? 0).toBeGreaterThanOrEqual(44);

    // Rien n'a été exécuté : recharger la page la montre encore.
    await page.reload();
    await expect(page.getByRole('button')).toHaveCount(1);
});

test('le bouton change la cadence, et le lien ne resert pas', async ({
    page,
}) => {
    await page.goto(ONE_TAP_USE);

    await page.getByRole('button').click();

    await expect(page.getByRole('status')).toContainText(/toutes les deux/i);

    // Usage unique : le lien a servi, il ne sert plus.
    const again = await page.goto(ONE_TAP_USE);
    expect(again?.status()).toBe(410);
});

test('la page ne demande ni compte ni mot de passe', async ({ page }) => {
    await page.goto(ONE_TAP_READ);

    const body = (await page.locator('body').textContent()) ?? '';

    expect(body).not.toMatch(/mot de passe|connexion|se connecter/i);
});
