import { expect, test } from '@playwright/test';

/**
 * Le tunnel d'achat, de la première étape au récapitulatif.
 *
 * Le clic final part chez Stripe, et Stripe n'est pas à nous : la suite
 * s'arrête au récapitulatif, où le total est calculé et affiché. Le paiement
 * lui-même se joue au checkpoint §7, avec la carte de test et le
 * `stripe listen` du runbook — un test bout en bout qui dépendrait de clés
 * réelles ne tournerait ni en CI ni chez un nouveau venu.
 */
const UNIQUE = Date.now();

test('remplit le tunnel jusqu’au récapitulatif', async ({ page }) => {
    await page.goto('/acheter');

    // Étape 1 — pour qui.
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'Pour qui',
    );
    await page.getByRole('radio', { name: 'Un proche' }).check();
    await page.getByRole('button', { name: 'Continuer' }).click();

    // Étape 2 — le narrateur.
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'narrateur',
    );
    await page.getByLabel('Son prénom').fill('Odette');
    await page.getByLabel('Votre lien avec elle').fill('ma mère');
    await page.getByLabel('Son courriel').fill(`odette+${UNIQUE}@example.test`);
    // À quel point elle est à l'aise avec un téléphone : la réponse change
    // ce qu'on propose ensuite (T-136).
    await page.getByRole('radio', { name: /Peu à l’aise/ }).check();
    await page.getByRole('button', { name: 'Continuer' }).click();

    // Étape 3 — le cadeau. La date, l'heure et le message sont préremplis :
    // c'est le point du défaut, on ne doit rien avoir à saisir pour avancer.
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'cadeau',
    );
    await expect(page.getByLabel('Votre message personnel')).not.toBeEmpty();
    await page.getByRole('button', { name: 'Continuer' }).click();

    // Étape 4 — le compte, créé sans quitter le tunnel (T-135). Sans
    // connexion, il n'y a pas de « Continuer » : on crée le compte ou on se
    // connecte, et Fortify ramène à l'étape suivante.
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'compte',
    );
    await expect(
        page.getByRole('button', { name: 'Continuer', exact: true }),
    ).toHaveCount(0);
    await page.getByLabel('Votre nom').fill('Camille');
    await page
        .getByLabel('Votre courriel')
        .fill(`camille+${UNIQUE}@example.test`);
    await page.getByLabel('Un mot de passe').fill('un-mot-de-passe-solide-12');
    await page
        .getByRole('button', { name: 'Créer mon compte et continuer' })
        .click();

    // Étape 5 — options et accords.
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'accords',
    );

    // La case marketing est décochée, et distincte des conditions.
    const marketing = page.getByRole('checkbox', {
        name: /recevoir des nouvelles/,
    });
    await expect(marketing).not.toBeChecked();

    const terms = page.getByRole('checkbox', { name: /conditions générales/ });
    await expect(terms).not.toBeChecked();

    // Sans les conditions, on n'avance pas.
    await page.getByRole('button', { name: 'Continuer' }).click();
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'accords',
    );

    await terms.check();

    // Les options sont des cartes qu'on ajoute. Un exemplaire de plus, et
    // l'option téléphone est recommandée pour quelqu'un peu à l'aise.
    await page
        .getByRole('button', { name: /Ajouter : Exemplaires supplémentaires/ })
        .click();
    await expect(page.getByLabel('Nombre d’exemplaires')).toHaveValue('1');
    // La recommandation de l'option téléphone n'apparaît que si l'option est
    // ouverte, ce que le décor local ne garantit pas : on ne l'affirme pas ici.
    await page.getByRole('button', { name: 'Continuer' }).click();

    // Étape 6 — récapitulatif, avec le total.
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'Récapitulatif',
    );
    // Exact : la colonne de droite dit aussi « Pour Odette ».
    await expect(page.getByText('Odette', { exact: true })).toBeVisible();
    await expect(page.getByText('Total à payer')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Payer' })).toBeVisible();

    // Le paiement se fait sur la page de notre prestataire : on le dit avant
    // le clic, pas après.
    await expect(page.getByText(/numéro de carte/)).toBeVisible();
});

test('garde la saisie quand on revient corriger un champ', async ({ page }) => {
    await page.goto('/acheter');

    await page.getByRole('radio', { name: 'Un proche' }).check();
    await page.getByRole('button', { name: 'Continuer' }).click();

    await page.getByLabel('Son prénom').fill('Odette');
    await page
        .getByLabel('Son courriel')
        .fill(`odette+r${UNIQUE}@example.test`);
    await page.getByRole('button', { name: 'Continuer' }).click();

    await page.getByRole('link', { name: 'Revenir' }).click();

    // Le brouillon vit sept jours côté serveur : quelqu'un qui corrige un
    // champ ne doit pas tout ressaisir.
    await expect(page.getByLabel('Son prénom')).toHaveValue('Odette');
});

test('l’essai n’envoie rien sur le réseau', async ({ page }) => {
    const uploads: string[] = [];

    page.on('request', (request) => {
        if (/\/recordings|\/r\//.test(request.url())) {
            uploads.push(request.url());
        }
    });

    await page.goto('/essai');

    await expect(
        page.getByRole('button', { name: 'Commencer l’essai' }),
    ).toBeVisible();
    await expect(page.getByText(/reste sur votre téléphone/)).toBeVisible();

    expect(uploads).toEqual([]);
});
