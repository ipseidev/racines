import { expect, test } from '@playwright/test';

/**
 * « Comment ça marche », la page (T-213).
 *
 * L'accessibilité est passée au crible dans `landing-a11y.spec.ts`, avec les
 * autres pages publiques. Ici, on vérifie ce que la page raconte et dans
 * quel ordre : la structure du leader, section par section. On lit les
 * identifiants des titres de section, pas leurs libellés.
 */
test('la page déroule ses sections dans l’ordre du leader', async ({
    page,
}) => {
    await page.goto('/comment-ca-marche');
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(
        'Comment ça marche',
    );

    const order = await page
        .getByRole('heading', { level: 2 })
        .evaluateAll((nodes) => nodes.map((node) => node.id));

    // Le bandeau de bas de page n'apparaît que si la réduction est proposée
    // à ce visiteur : il est le seul à pouvoir manquer.
    expect(order.slice(0, 7)).toEqual([
        'intro',
        'steps',
        'questions',
        'cta',
        'rendering',
        'voice',
        'together',
    ]);
    expect(order.slice(7)).toEqual(order.length > 7 ? ['newsletter'] : []);

    // Six étapes, et « elle décide » avant « la famille écoute ».
    const steps = await page
        .getByRole('list', { name: 'Les six étapes' })
        .getByRole('heading', { level: 3 })
        .evaluateAll((nodes) => nodes.map((node) => node.textContent ?? ''));

    expect(steps).toEqual([
        'Vous choisissez les questions',
        'Une question arrive. Elle parle.',
        'Ses mots deviennent un texte',
        'Elle relit, puis elle décide',
        'La famille écoute et lui répond',
        'Le livre relié, avec sa voix à chaque page',
    ]);
});

test('les onglets basculent tout le parcours, pas seulement l’accroche', async ({
    page,
}) => {
    await page.goto('/comment-ca-marche');

    await expect(
        page.getByRole('heading', { level: 2, name: /Ses souvenirs/ }),
    ).toBeVisible();
    await expect(
        page.getByRole('heading', {
            level: 3,
            name: 'Elle relit, puis elle décide',
        }),
    ).toBeAttached();

    await page.getByRole('button', { name: 'Pour moi' }).click();

    await expect(
        page.getByRole('heading', { level: 2, name: /Votre vie/ }),
    ).toBeVisible();
    await expect(
        page.getByRole('heading', {
            level: 3,
            name: 'Vous relisez, puis vous décidez',
        }),
    ).toBeAttached();
    await expect(
        page.getByRole('link', { name: /Je commence mon livre/ }).first(),
    ).toBeAttached();
});

test('le texte se lit en deux versions, le mot à mot d’abord', async ({
    page,
}) => {
    await page.goto('/comment-ca-marche');

    const verbatim = page.getByRole('tab', { name: 'Mot à mot' });
    const fluide = page.getByRole('tab', { name: 'Texte mis au propre' });

    await expect(verbatim).toHaveAttribute('aria-selected', 'true');
    await fluide.click();
    await expect(fluide).toHaveAttribute('aria-selected', 'true');
    await expect(page.getByRole('tabpanel')).toContainText('Ma grand-mère');
});

test('la navigation mène à la page, et l’accueil aussi', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto('/');
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    // L'entrée de navigation ne pointe plus vers l'ancre de l'accueil.
    await page
        .getByRole('navigation', { name: 'Sections' })
        .getByRole('link', { name: 'Comment ça marche' })
        .click();
    await expect(page).toHaveURL(/\/comment-ca-marche$/);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    // Et, sous les quatre étapes de l'accueil, le lien vers les six.
    await page.goto('/');
    await page
        .getByRole('link', { name: /Voir le parcours en détail/ })
        .click();
    await expect(page).toHaveURL(/\/comment-ca-marche$/);
});
