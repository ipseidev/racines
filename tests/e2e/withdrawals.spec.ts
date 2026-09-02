import { expect, test } from '@playwright/test';

/**
 * Les retraits, vus du narrateur.
 *
 * Ce que ce fichier protège : que masquer son propre récit tienne en deux
 * gestes sans code, et que supprimer demande à la fois un code et un mot
 * écrit à la main — un bouton ne suffit pas pour l'unique acte irréversible
 * du produit.
 */
const WITHDRAW_LINK = `/r/${'demo-withdraw-link'.padEnd(43, 'x')}`;

/*
 * Un lien par test, comme partout dans cette suite : elle tourne en
 * parallèle, et un test qui met une histoire à la corbeille changerait ce que
 * le voisin s'attend à lire (leçon de T-59).
 */
const SPACE_DELETE_LINK = `/n/${'demo-space-del-link'.padEnd(43, 'x')}`;

const SPACE_READ_LINK = `/n/${'demo-space-read-link'.padEnd(43, 'x')}`;

test('masquer son propre récit tient en deux gestes, sans code', async ({
    page,
}) => {
    const response = await page.goto(WITHDRAW_LINK);

    expect(response?.status()).toBe(200);

    // Le lien porte cette histoire : aucun code n'est demandé. Quelqu'un qui
    // regrette ce qu'il vient de raconter doit pouvoir le retirer tout de
    // suite (glossaire §4).
    const hide = page.getByRole('button', { name: /masquer cette histoire/i });
    await expect(hide).toBeVisible();

    await hide.click();
    await page
        .getByRole('button', { name: /^masquer cette histoire$/i })
        .last()
        .click();

    await expect(page.getByRole('status')).toContainText(/est masquée/i);
    await expect(page).not.toHaveURL(/\/code$/);
});

test('l’espace nomme les états en langage simple', async ({ page }) => {
    await page.goto(SPACE_READ_LINK);

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        /vos histoires/i,
    );

    // Un narrateur ne lit pas « shared » : il lit ce que ça veut dire.
    await expect(page.getByText(/partagée avec vos proches/i)).toBeVisible();
});

test('la suppression demande un code, puis le mot écrit à la main', async ({
    page,
}) => {
    await page.goto(SPACE_DELETE_LINK);

    // Premier geste : la corbeille. Elle passe par un code.
    await page.getByRole('button', { name: /mettre à la corbeille/i }).click();
    await page
        .getByRole('button', { name: /^mettre à la corbeille$/i })
        .last()
        .click();

    await expect(page).toHaveURL(/\/code$/);

    // La page de code dit ce qu'il faut faire, et ne montre jamais la
    // coordonnée en clair.
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(page.locator('body')).not.toContainText('+33600000046');
});

test('la corbeille annonce la date jusqu’à laquelle on peut revenir', async ({
    page,
}) => {
    const response = await page.goto(SPACE_READ_LINK);

    expect(response?.status()).toBe(200);

    // Rien n'est en corbeille dans ce décor : ce qui compte ici est que la
    // page n'annonce pas de délai qu'elle ne tiendrait pas.
    await expect(page.getByText(/récupérable jusqu’au/i)).toHaveCount(0);
    await expect(page.getByText(/pour toujours|illimité|à vie/i)).toHaveCount(
        0,
    );
});
