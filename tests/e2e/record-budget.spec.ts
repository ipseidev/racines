import { existsSync } from 'node:fs';
import { gzipSync } from 'node:zlib';

import { expect, test } from '@playwright/test';

/**
 * Budget JavaScript des pages narrateur (convention §4) : 150 Ko gzip.
 *
 * Ces pages s'ouvrent en 4G sur de vieux téléphones. Un budget dépassé n'est
 * pas une inélégance : c'est une histoire qui ne sera pas racontée.
 */
const RECORD_LINK = `/r/${'demo-budget-link'.padEnd(43, 'x')}`;

const BUDGET_BYTES = 150 * 1024;

/*
 * Mesurable uniquement sur les assets **construits**.
 *
 * Le serveur de développement sert chaque module séparément et sans
 * minification : la page pèse alors huit fois le budget (1 200 Ko mesurés le
 * 3 septembre 2026), et le test échoue sur une propriété de l'outillage, pas
 * du produit. Un rouge qui veut dire « tu es en mode développement » fait
 * perdre le temps de celui qui le lit — il vaut mieux le dire.
 *
 * Pour le jouer en local : `npm run build`, retirer `public/hot`, lancer, puis
 * remettre `public/hot`. L'intégration continue construit toujours, donc le
 * budget y est vérifié à chaque poussée.
 */
test.skip(
    () => existsSync('public/hot'),
    'Le serveur de développement de Vite tourne : le poids mesuré serait celui des modules non regroupés.',
);

test('la page d’enregistrement tient dans 150 Ko de JavaScript', async ({
    page,
}) => {
    const bodies: Buffer[] = [];

    page.on('response', (response) => {
        if (response.request().resourceType() !== 'script') {
            return;
        }

        void response
            .body()
            .then((body) => bodies.push(body))
            .catch(() => {
                // Une réponse annulée n'a pas de corps : elle ne compte pas.
            });
    });

    await page.goto(RECORD_LINK, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);

    // Le serveur local ne compresse pas : on mesure nous-mêmes le gzip, qui
    // est ce que paiera réellement un narrateur en 4G.
    const gzipped = bodies.reduce(
        (total, body) => total + gzipSync(body).byteLength,
        0,
    );

    expect(
        gzipped,
        `JavaScript gzip : ${Math.round(gzipped / 1024)} Ko pour un budget de ${BUDGET_BYTES / 1024} Ko`,
    ).toBeLessThanOrEqual(BUDGET_BYTES);
});
