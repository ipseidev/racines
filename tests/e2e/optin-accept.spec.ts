import { expect, test } from '@playwright/test';

import { openInvitation } from './support/optin';

/**
 * Le moment H0, accepté.
 *
 * Le parcours le plus important du produit après l'enregistrement : un cadeau
 * devient un consentement. Ce que le test vérifie en creux compte autant —
 * aucun micro, aucune question, aucun aperçu avant que la personne ait dit
 * oui.
 */
const OPTIN = `/i/${'demo-optin-accept-link'.padEnd(43, 'x')}`;

/*
 * Un lien par test, et ici la règle est plus stricte qu'ailleurs : l'opt-in
 * est définitif. Le test du dessus accepte pour de bon ; celui du dessous a
 * besoin d'une page encore intacte, donc de son propre lien.
 */
const INTACT = `/i/${'demo-optin-accept-partial-link'.padEnd(43, 'x')}`;

test('accepte le cadeau et voit sa première question annoncée', async ({
    page,
}) => {
    await page.goto(OPTIN);

    // L'ouverture la salue par son prénom avant de montrer quoi que ce soit ;
    // puis le rideau tombe, et la lettre est là. On attend que le rideau soit
    // monté avant d'y chercher le prénom : la page est rendue par le
    // navigateur, et le prénom ne reste que deux secondes.
    const overture = page.locator('[data-overture]');
    await overture.waitFor({ state: 'attached', timeout: 20_000 });
    await expect(overture).toContainText('Bonjour Odette,', { timeout: 8_000 });
    await expect(overture).toHaveCount(0, { timeout: 20_000 });

    await expect(
        page.getByText('J’aimerais garder tes histoires, maman.'),
    ).toBeVisible();

    // Rien qui ressemble à un enregistrement avant l'acceptation : le verbe,
    // pas le nom — « Enregistrement de la voix » est le titre d'un accord, et
    // depuis T-233 c'est un bouton qui ouvre son texte.
    await expect(page.locator('audio')).toHaveCount(0);
    await expect(
        page.getByRole('button', { name: /enregistrer/i }),
    ).toHaveCount(0);

    // Les cinq accords sont donnés par le bouton, sans case à cocher (T-233),
    // et la phrase juste au-dessus dit ce qu'il donne.
    await expect(page.getByRole('checkbox')).toHaveCount(0);
    await expect(
        page.getByText(/vous donnez les cinq accords décrits plus bas/),
    ).toBeVisible();

    await page.getByRole('button', { name: 'J’accepte' }).click();

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'Bienvenue',
    );

    // La fiche contact d'abord : un message qui n'arrive pas de ce contact
    // est un faux.
    await expect(
        page.getByRole('link', { name: 'Ajouter le contact' }),
    ).toBeVisible();

    // Et les souhaits pour plus tard, avec « Plus tard » proposé aussi
    // visiblement que l'autre choix.
    const later = page.getByRole('button', { name: 'Plus tard' });
    const now = page.getByRole('button', {
        name: 'Dire mes souhaits maintenant',
    });

    await expect(later).toBeVisible();
    await expect(now).toBeVisible();

    await later.click();

    // « Plus tard » ne poste rien : la section se replie, et la personne sait
    // qu'elle pourra y revenir.
    await expect(page.getByText(/quand vous voudrez/)).toBeVisible();
});

test('montre les réglages avant les boutons, et replie les accords dessous', async ({
    page,
}) => {
    await openInvitation(page, INTACT);

    const accept = page.getByRole('button', { name: 'J’accepte' });
    const day = page.getByLabel('Quel jour ?');
    const consents = page.locator('details');
    const transcription = page.getByRole('button', { name: /Transcription/ });

    // Les réglages se lisent avant de dire oui ; les accords attendent
    // dessous, fermés, sous leur vrai nom.
    await expect(day).toBeVisible();
    await expect(transcription).toBeHidden();
    await expect(consents).toContainText('Vos accords');

    const dayBox = await day.boundingBox();
    const acceptBox = await accept.boundingBox();
    const consentsBox = await consents.boundingBox();

    expect(dayBox?.y ?? 0).toBeLessThan(acceptBox?.y ?? 0);
    expect(consentsBox?.y ?? 0).toBeGreaterThan(acceptBox?.y ?? 0);

    // Le champ de contact suit le canal, déjà rempli avec ce qu'on sait : ici
    // le courriel de l'invitation. Passer au SMS montre le numéro à la place.
    // Et le téléphone opéré (D-9) n'est pas proposé.
    const channel = page.getByLabel('Par quel moyen ?');
    const email = page.getByLabel('Votre adresse de courriel');
    const phone = page.getByLabel('Votre numéro de téléphone');

    await expect(channel).toHaveValue('email');
    await expect(channel.locator('option')).toHaveCount(3);
    await expect(email).toHaveValue(/odette\+optin-accept-partial@/);
    await expect(phone).toHaveCount(0);

    await channel.selectOption('sms');

    await expect(phone).toBeVisible();
    await expect(email).toHaveCount(0);

    await channel.selectOption('email');

    // Ouvrir l'accordéon, puis un accord : son texte et sa version.
    await consents.locator('summary').click();
    await expect(transcription).toBeVisible();

    await transcription.click();
    await expect(page.getByText(/^Version /)).toBeVisible();

    // Rien n'a été posté : la page est intacte pour la prochaine fois.
    await expect(accept).toBeVisible();
});
