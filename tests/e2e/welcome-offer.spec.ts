import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

/**
 * La fenêtre de bienvenue de la page d'accueil (T-141).
 *
 * Elle attend six secondes avant de se montrer : l'horloge du navigateur est
 * pilotée pour ne pas attendre pour de vrai. Deux scénarios : demander le
 * code, et refuser. Dans les deux cas, la fenêtre ne revient pas au
 * rechargement — une offre qui revient à chaque page vue est une relance.
 */
test.describe('la fenêtre de bienvenue', () => {
    test('attend, se montre, prend l’adresse, et ne revient pas', async ({
        page,
    }) => {
        // Sans mouvement : axe mesure les couleurs telles qu'elles sont, pas au
        // milieu d'un fondu, et l'horloge pilotée ne touche pas aux animations.
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.clock.install();
        await page.goto('/');
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

        const dialog = page.getByRole('dialog', {
            name: 'Une réduction de bienvenue',
        });

        // Pas au chargement : on lit la promesse d'abord.
        await expect(dialog).toBeHidden();

        await page.clock.fastForward(7_000);
        await expect(dialog).toBeVisible();
        await expect(dialog.getByText('10 % offerts')).toBeVisible();

        // Accessible, fenêtre ouverte : le reste de la page est inerte, la
        // fenêtre doit se suffire.
        const results = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa'])
            .analyze();
        const blocking = results.violations.filter((violation) =>
            ['serious', 'critical'].includes(violation.impact ?? ''),
        );
        expect(blocking, JSON.stringify(blocking, null, 2)).toEqual([]);

        // Deux temps : d'abord la promesse, puis le champ, qui prend le focus.
        await dialog
            .getByRole('button', { name: 'Je prends ma réduction' })
            .click();
        const email = dialog.getByLabel('Votre adresse de courriel');
        await expect(email).toBeFocused();

        const news = dialog.getByRole('checkbox');
        await expect(news).not.toBeChecked();

        await email.fill(`bienvenue+${Date.now()}@example.test`);
        await dialog.getByRole('button', { name: 'Recevoir mon code' }).click();

        await expect(dialog.getByText('C’est envoyé')).toBeVisible();
        // Le code ne s'affiche jamais : il part par courriel.
        await expect(dialog).not.toContainText(/\b[A-Z2-9]{4}-[A-Z2-9]{4}\b/);

        await dialog.getByRole('button', { name: 'Fermer' }).click();
        await expect(dialog).toBeHidden();

        // Ni au rechargement, ni après le délai.
        await page.reload();
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
        await page.clock.fastForward(10_000);
        await expect(dialog).toBeHidden();
    });

    test('se tait trente jours quand on la ferme', async ({ page }) => {
        // Sans mouvement : axe mesure les couleurs telles qu'elles sont, pas au
        // milieu d'un fondu, et l'horloge pilotée ne touche pas aux animations.
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.clock.install();
        await page.goto('/');
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

        const dialog = page.getByRole('dialog', {
            name: 'Une réduction de bienvenue',
        });

        await page.clock.fastForward(7_000);
        await expect(dialog).toBeVisible();

        await dialog.getByRole('button', { name: 'Non merci' }).click();
        await expect(dialog).toBeHidden();

        await page.reload();
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
        await page.clock.fastForward(10_000);
        await expect(dialog).toBeHidden();
    });
});
