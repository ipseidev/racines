import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

/**
 * Les pages publiques, passées au crible de l'accessibilité.
 *
 * Ce sont les seules pages du produit qu'un moteur de recherche voit, et les
 * premières qu'un enfant de soixante ans lit sur son téléphone. Une violation
 * grave ici est une famille qui ne comprend pas ce qu'on vend.
 */
async function blockingViolations(
    page: Parameters<typeof AxeBuilder>[0]['page'],
) {
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();

    return results.violations.filter(
        (violation) =>
            violation.impact === 'critical' || violation.impact === 'serious',
    );
}

const PAGES = [
    ['/', 'la page d’accueil'],
    ['/essai', 'l’essai'],
    ['/acheter', 'le tunnel'],
    ['/cgv', 'les conditions générales'],
    ['/confidentialite', 'la politique de confidentialité'],
    ['/consentements', 'les accords'],
] as const;

for (const [path, label] of PAGES) {
    test(`aucune violation grave sur ${label}`, async ({ page }) => {
        // Sans mouvement : les pages entrent en fondu, et une couleur mesurée
        // au milieu du fondu n'est pas celle qu'on lit. On juge l'état posé.
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.goto(path);

        // Attendre le titre avant d'analyser, et ce n'est pas du zèle : ces
        // pages sont rendues par React après le chargement. Analyser trop tôt
        // passerait sur un DOM vide, et un test qui ne trouve rien à
        // reprocher à une page vide ne prouve rien.
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

        const violations = await blockingViolations(page);

        expect(
            violations.map((one) => `${one.id}: ${one.help}`),
            JSON.stringify(violations, null, 2),
        ).toEqual([]);
    });
}

test('la page d’accueil annonce ses sections dans l’ordre du dossier', async ({
    page,
}) => {
    await page.goto('/');
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    // L'ordre est celui de Remento, adapté à notre univers (décision du
    // fondateur, 4 septembre 2026, T-134). On le lit sur les identifiants des
    // titres de section, pas sur leur libellé.
    const order = await page
        .getByRole('heading', { level: 2 })
        .evaluateAll((nodes) => nodes.map((node) => node.id));

    expect(order).toEqual([
        'promises',
        'what',
        'how',
        'story',
        'product',
        'forever',
        'guarantee',
        'tested',
        'book',
        'review',
        'gift',
        'faq',
    ]);

    // Et les titres canoniques restent visibles, chacun à sa place.
    for (const label of [
        'Comment ça marche',
        'Notre histoire',
        'Le livre',
        'Questions fréquentes',
    ]) {
        await expect(
            page.getByText(label, { exact: true }).first(),
        ).toBeVisible();
    }
});
