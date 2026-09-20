import { expect, test } from '@playwright/test';

import { blockingViolations } from './support/a11y';

/**
 * Le tunnel de découverte, du premier écran au plan.
 *
 * Ce qu'il éprouve et que les tests unitaires ne peuvent pas : les treize
 * écrans s'enchaînent **dans un vrai navigateur**, un tap avance, le retour
 * garde la réponse, et le plan affiché à la fin contient une question qui
 * vient réellement de la base. Puis le passage au tunnel d'achat, qui est le
 * seul endroit où les deux parcours se touchent : les réponses du quiz
 * doivent y être, déjà remplies.
 */

/** Tape un choix et attend que l'écran suivant ait pris le focus. */
async function choose(
    page: import('@playwright/test').Page,
    label: string | RegExp,
) {
    await page.getByRole('button', { name: label }).click();
}

async function heading(page: import('@playwright/test').Page) {
    return page.getByRole('heading', { level: 1 });
}

test('mène du premier écran au plan, puis au tunnel', async ({ page }) => {
    await page.goto('/commencer');

    // 1 — le lien de parenté.
    await expect(await heading(page)).toContainText('De qui');
    await choose(page, /Ma mère/);

    // 2 — le miroir de la voix. Il ne demande rien.
    await expect(await heading(page)).toContainText('La voix compte');
    await page.getByRole('button', { name: 'Continuer' }).click();

    // 3 — l'âge. Le sujet suit la réponse du premier écran.
    await expect(await heading(page)).toContainText('votre mère');
    await choose(page, /70 et 79/);

    // 4 — la distance, puis 5 — le miroir qui la commente.
    await expect(await heading(page)).toContainText('distance');
    await choose(page, /autre bout du pays/);
    await expect(await heading(page)).toContainText('raccroche');
    await page.getByRole('button', { name: 'Continuer' }).click();

    // 6 — les thèmes. Le bouton reste éteint sous trois.
    await expect(await heading(page)).toContainText('raconte');
    const next = page.getByRole('button', { name: 'Continuer' });
    await expect(next).toBeDisabled();

    await page.getByText('Son enfance').click();
    await page.getByText('Son métier').click();
    await expect(next).toBeDisabled();

    await page.getByText('Ses conseils').click();
    await expect(next).toBeEnabled();
    await next.click();

    // 7 — comment la personne raconte, puis 8 — la réponse à l'objection.
    await choose(page, /Intarissable/);
    await expect(await heading(page)).toContainText('rien à raconter');
    await page.getByRole('button', { name: 'Continuer' }).click();

    // 9 — l'aisance, puis 10 — « rien à installer ».
    await expect(await heading(page)).toContainText('téléphone');
    await choose(page, /le téléphone fait partie/);
    await expect(await heading(page)).toContainText('rien à installer');
    await page.getByRole('button', { name: 'Continuer' }).click();

    // 11 — le canal.
    await expect(await heading(page)).toContainText('joindre');
    await choose(page, /^Choisir : SMS$/);

    // 12 — le prénom et le petit nom.
    await expect(await heading(page)).toContainText('s’appelle');
    await page.getByLabel('Son prénom').fill('Jeanne');
    await page.getByLabel('comment l’appelez-vous').fill('Mamie');
    await page.getByRole('button', { name: 'Continuer' }).click();

    /*
     * 13 — le livre. Le premier écran où l'objet se voit : le prénom saisi à
     * l'écran précédent est sur la couverture, et la teinte tapée ici part à
     * l'impression.
     */
    await expect(await heading(page)).toContainText('Voici le livre de Jeanne');

    // Le prénom seul par défaut, comme la couverture le faisait avant ce
    // choix ; puis la formule, composée avec le prénom saisi à l'écran d'avant.
    await expect(page.getByTestId('quiz-book')).toContainText('Jeanne');
    await page.getByRole('button', { name: 'Les histoires de…' }).click();
    await expect(page.getByTestId('quiz-book')).toContainText(
        'Les histoires de Jeanne',
    );

    // Le titre libre ouvre son champ, et la couverture suit la frappe.
    await page.getByRole('button', { name: 'Un titre à moi' }).click();
    await page.getByLabel('Votre titre').fill('Les dimanches chez Mamie');
    await expect(page.getByTestId('quiz-book')).toContainText(
        'Les dimanches chez Mamie',
    );

    await page.getByRole('button', { name: 'Vert forêt' }).click();
    await expect(page.getByText('Vert forêt')).toBeVisible();
    await page.getByRole('button', { name: 'Continuer' }).click();

    // 14 — l'occasion, puis la date, préremplie.
    await expect(await heading(page)).toContainText('occasion');
    await choose(page, /anniversaire/);
    await expect(page.getByLabel('Quel jour')).not.toHaveValue('');
    await page.getByRole('button', { name: 'Continuer' }).click();

    /*
     * Le plan. La première question vient du corpus en base — on ne la cite
     * pas mot pour mot, le corpus peut être réordonné —, mais elle est là,
     * elle finit par un point d'interrogation, et le petit nom a remplacé le
     * prénom dans le message.
     */
    await expect(await heading(page)).toContainText('Mamie');

    // Le plan ouvre sur le livre, dans la teinte et sous le titre choisis.
    await expect(page.getByTestId('quiz-book')).toContainText(
        'Les dimanches chez Mamie',
    );

    await expect(page.getByText(/première question/)).toBeVisible();

    await expect(page.getByTestId('quiz-question')).toContainText('?');

    // Le message d'invitation porte l'expéditeur réel et le petit nom, et
    // marque ce qui manque encore plutôt que de l'inventer.
    await expect(page.getByTestId('quiz-invitation')).toContainText(
        '[votre prénom]',
    );

    // Le passage au tunnel : les réponses y sont déjà.
    await page.getByRole('link', { name: /Offrir ce livre à Mamie/ }).click();

    await expect(page).toHaveURL(/\/acheter\?step=2/);
    await expect(page.getByLabel('Son prénom')).toHaveValue('Jeanne');
    await expect(page.getByLabel('Votre lien avec elle')).toHaveValue(
        'Ma mère',
    );
});

test('garde la réponse quand on revient en arrière', async ({ page }) => {
    await page.goto('/commencer');

    await choose(page, /Mon père/);
    await expect(await heading(page)).toContainText('La voix compte');

    await page.getByRole('button', { name: 'Revenir' }).click();

    // Le choix est encore marqué : un questionnaire dont une correction perd
    // la réponse se quitte à la première hésitation.
    await expect(page.getByRole('button', { name: /Mon père/ })).toHaveClass(
        /border-brand/,
    );
});

test('reprend là où on en était après un rechargement', async ({ page }) => {
    await page.goto('/commencer');

    await choose(page, /Ma grand-mère/);
    await page.getByRole('button', { name: 'Continuer' }).click();
    await choose(page, /80 et 89/);

    await page.reload();

    /*
     * La reprise passe par le stockage de l'appareil : on revient au premier
     * écran, mais les réponses sont là. C'est ce qui compte — Safari purge
     * les onglets, et recommencer dix questions ne se fait pas deux fois.
     */
    await expect(
        page.getByRole('button', { name: /Ma grand-mère/ }),
    ).toHaveClass(/border-brand/);
});

test('envoie vers le tunnel qui raconte soi-même sans passer par le quiz', async ({
    page,
}) => {
    await page.goto('/commencer');

    await page.getByRole('link', { name: /Pour moi/ }).click();

    await expect(page).toHaveURL(/\/acheter$/);
});

test('aucune violation grave d’accessibilité', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/commencer');

    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    const violations = await blockingViolations(page, ['wcag2a', 'wcag2aa']);

    expect(
        violations.map((one) => `${one.id}: ${one.help}`),
        JSON.stringify(violations, null, 2),
    ).toEqual([]);
});
