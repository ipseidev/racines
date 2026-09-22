import { expect, test, type Page } from '@playwright/test';

/**
 * Un proche pose sa question (R-1, dossier v3.1).
 *
 * Ce fichier gardait le dépôt d'une photo sur une histoire déjà racontée.
 * Cette voie n'existe plus : une photo collée sur un récit clos est une
 * décoration, alors que jointe à la question elle appelle le récit. Le droit
 * qu'un proche reçoit est donc celui de **demander**, avec ses photos.
 *
 * Deux choses s'y jouent, et la seconde est celle qu'on perdrait le plus
 * facilement : un proche **sans** ce droit ne voit pas le bouton, et reçoit un
 * refus s'il poste quand même. Un bouton n'est pas une autorisation, et le
 * serveur ne le croit pas sur parole.
 */
const AVEC = `/l/${'demo-listen-photo-link'.padEnd(43, 'x')}`;
const SANS = `/l/${'demo-listen-link'.padEnd(43, 'x')}`;

/*
 * La première histoire de la liste, prise par son lien à elle.
 *
 * `getByRole('link').first()` visait le premier lien du document, ce qui a
 * cessé d'être une histoire le jour où la mise en page a reçu son évitement
 * clavier (T-158) : « Aller au contenu » est en tête, invisible mais premier.
 * Un sélecteur qui dépend de l'ordre du document décrit la page d'hier.
 */
async function openFirstStory(page: Page): Promise<void> {
    await page.getByRole('listitem').first().getByRole('link').click();
}

test('un proche sans ce droit ne voit pas le bouton', async ({ page }) => {
    await page.goto(SANS);

    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    // Un bouton grisé invite à demander pourquoi ; un bouton absent non.
    await expect(page.getByText('Posez-lui votre question')).toHaveCount(0);
});

test('plus personne ne dépose de photo sur une histoire déjà racontée', async ({
    page,
}) => {
    await page.goto(AVEC);
    await openFirstStory(page);

    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    // Même pour qui a le droit de poser des questions : la photo appartient à
    // la question, pas au récit qu'elle a produit.
    await expect(page.getByText('Ajouter une photo')).toHaveCount(0);
});

test('un proche autorisé pose sa question, avec une photo', async ({
    page,
}) => {
    await page.goto(AVEC);

    await page
        .getByRole('button', { name: 'Posez-lui votre question' })
        .click();

    // Le champ, pas son étiquette : « Votre question » nomme aussi le titre
    // de la section qui l'entoure.
    await page
        .getByRole('textbox', { name: 'Votre question' })
        .fill('Comment vous êtes-vous rencontrés, mamie ?');

    // Un JPEG minimal fabriqué dans le test : rien de binaire dans le dépôt.
    await page.locator('input[type="file"]').setInputFiles({
        name: 'souvenir.jpg',
        mimeType: 'image/jpeg',
        buffer: Buffer.from(
            // Un vrai JPEG de 8×8, et non l'entête tronqué d'avant : le
            // serveur refuse ce qui n'est pas une image lisible, et un test
            // qui lui donne un leurre n'éprouve que le refus.
            'ffd8ffe000104a46494600010100000100010000ffdb00430006040506050406060506070706080a100a0a09090a140e0f0c1017141818171416161a1d251f1a1b231c1616202c20232627292a29191f2d302d283025282928ffdb0043010707070a080a130a0a13281a161a2828282828282828282828282828282828282828282828282828282828282828282828282828282828282828282828282828ffc00011080008000803012200021101031101ffc4001f0000010501010101010100000000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a434445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae1e2e3e4e5e6e7e8e9eaf1f2f3f4f5f6f7f8f9faffc4001f0100030101010101010101010000000000000102030405060708090a0bffc400b51100020102040403040705040400010277000102031104052131061241510761711322328108144291a1b1c109233352f0156272d10a162434e125f11718191a262728292a35363738393a434445464748494a535455565758595a636465666768696a737475767778797a82838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f00ea68a28af993e84fffd9',
            'hex',
        ),
    });

    /*
     * La confirmation nomme la narratrice et dit ce qui va se passer : la
     * question part à son tour, derrière celles qui attendent. Rien ne promet
     * qu'elle y répondra — c'est son veto, et il prévaut (R-1).
     *
     * L'attente s'arme **avant** le clic : le toast vit trois secondes et
     * demie, et une assertion posée après coup peut arriver quand il n'est
     * déjà plus là. Attendre ce qui a disparu, c'est échouer sur un produit
     * qui marche.
     */
    const confirmation = page
        .getByRole('status')
        .filter({ hasText: /votre question est dans la file/i })
        .waitFor({ timeout: 15_000 });

    await page.getByRole('button', { name: 'Envoyer ma question' }).click();
    await confirmation;

    // Et le formulaire se referme : la page redevient une page d'écoute.
    await expect(
        page.getByRole('button', { name: 'Posez-lui votre question' }),
    ).toBeVisible();
});
