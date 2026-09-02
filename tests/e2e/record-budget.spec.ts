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
