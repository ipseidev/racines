import { test as base } from '@playwright/test';

/*
 * L'espace demande un compte, contrairement à toutes les autres pages du
 * produit. La connexion est limitée à cinq essais par minute (Fortify), et une
 * suite qui se connecte à chaque test finit refusée pour une raison qui n'a
 * rien à voir avec le produit. On se connecte donc **une fois par ouvrier**,
 * et chaque test reprend la session.
 */
export const INITIATOR_EMAIL = 'espace@example.test';
const PASSWORD = process.env.ADMIN_PASSWORD ?? 'password';

type SessionState = Awaited<
    ReturnType<import('@playwright/test').BrowserContext['storageState']>
>;

export const test = base.extend<object, { initiatorSession: SessionState }>({
    initiatorSession: [
        async ({ browser }, use) => {
            const baseURL =
                test.info().project.use.baseURL ?? 'http://localhost';
            const page = await browser.newPage({ baseURL });

            await page.goto('/login');
            await page
                .locator('input[type="email"]')
                .first()
                .fill(INITIATOR_EMAIL);
            await page.locator('input[type="password"]').first().fill(PASSWORD);
            await page.locator('form button[type="submit"]').first().click();
            await page.waitForURL(/dashboard|espace/);

            const state = await page.context().storageState();
            await page.close();

            await use(state);
        },
        { scope: 'worker' },
    ],
    storageState: async ({ initiatorSession }, use) => {
        await use(initiatorSession);
    },
});

export { expect } from '@playwright/test';
