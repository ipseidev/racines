import { expect, test, type Page } from '@playwright/test';

/**
 * Le parcours d'un proche : ouvrir, écouter, réagir.
 *
 * Ce que ce fichier protège vraiment : que la liste ne montre que ce que le
 * narrateur a partagé, que trente secondes d'écoute soient comptées comme une
 * écoute, et qu'un mot arrive avec le prénom de qui l'écrit.
 */
const LISTEN = `/l/${'demo-listen-link'.padEnd(43, 'x')}`;

const LISTEN_REACT = `/l/${'demo-listen-react-link'.padEnd(43, 'x')}`;

function reportBrowserProblems(page: Page): void {
    page.on('console', (message) => {
        if (message.type() === 'error') {
            console.log('[navigateur]', message.text().slice(0, 300));
        }
    });

    page.on('response', (response) => {
        if (response.status() >= 400) {
            console.log(
                '[réponse]',
                response.status(),
                response.url().slice(0, 160),
            );
        }
    });
}

test('la liste nomme le narrateur et marque ce qui est nouveau', async ({
    page,
}) => {
    reportBrowserProblems(page);

    const response = await page.goto(LISTEN);

    expect(response?.status()).toBe(200);

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'Les histoires d',
    );
    await expect(page.getByText('L’odeur du pain')).toBeVisible();
    await expect(page.getByText('Nouvelle')).toBeVisible();

    // Le pied de page dit pourquoi cette personne a ce lien.
    await expect(
        page.getByText(/Ne le transmettez qu’à des proches/),
    ).toBeVisible();
});

test('la page d’histoire donne l’audio, les deux textes et la mention d’IA', async ({
    page,
}) => {
    reportBrowserProblems(page);

    await page.goto(LISTEN);
    await page.getByText('L’odeur du pain').click();

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'L’odeur du pain',
    );

    // Des commandes nommées en mots, jamais les contrôles natifs.
    await expect(page.getByRole('button', { name: 'Écouter' })).toBeVisible();
    await expect(page.getByText(/mis au propre par une IA/i)).toBeVisible();

    await page.getByRole('tab', { name: /mot à mot/i }).click();
    await expect(page.getByText(/Alors euh je me souviens/)).toBeVisible();
});

test('écouter puis réagir avec un mot', async ({ page }) => {
    reportBrowserProblems(page);

    await page.goto(LISTEN_REACT);
    await page.getByText('L’odeur du pain').click();

    // La navigation d'Inertia est asynchrone : sans cette attente, l'appel
    // ci-dessous partirait encore depuis l'URL de la liste.
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'L’odeur du pain',
    );

    // On force le cumul d'écoute par la même route que le lecteur : la
    // lecture réelle d'un MP3 dans un navigateur sans son est trop fragile
    // pour un test, mais le maillon qu'on veut éprouver est la mesure.
    const reported = await page.evaluate(async () => {
        const response = await fetch(`${window.location.pathname}/listen`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? '',
            },
            body: JSON.stringify({ seconds: 35 }),
        });

        return response.json();
    });

    expect(reported).toMatchObject({ seconds_listened: 35, reached_30s: true });

    await page
        .getByRole('textbox', { name: /laisser un mot/i })
        .fill('Merci maman, c’était beau.');
    await page.getByRole('button', { name: 'Merci' }).click();

    await expect(page.getByRole('status')).toContainText(/c’est envoyé/i);

    // Le mot est rendu avec le prénom de qui l'écrit : c'est ce qui fait
    // ressentir au narrateur qu'on l'a écouté.
    await expect(page.getByText(/Ont réagi/)).toBeVisible();
    await expect(page.getByText(/Merci maman, c’était beau\./)).toBeVisible();
});

test('une histoire non partagée reste introuvable', async ({ page }) => {
    reportBrowserProblems(page);

    const response = await page.goto(
        `${LISTEN}/stories/01a00000-0000-7000-8000-000000000000`,
    );

    // Page « non disponible », et aucune donnée dans la réponse.
    expect(response?.status()).toBe(404);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
});
