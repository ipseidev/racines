import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import OptIn from './OptIn';

const catalogue = {
    common: {},
    narrator: {
        optin: {
            greeting: 'Bonjour :name,',
            title: ':inviter vous offre quelque chose',
            from: 'Un message de :inviter',
            listen_message: 'Écouter son message',
            overture: {
                greeting: 'Bonjour,',
                offered: ':inviter vous a offert quelque chose.',
                promise: 'Vos souvenirs, de votre voix, pour les vôtres.',
            },
            means: {
                title: 'Ce que cela veut dire pour vous',
                one: 'Une question par semaine.',
                two: 'Vous relisez avant que quiconque le voie.',
                three: 'Vous pouvez arrêter à tout moment.',
            },
            consents: {
                title: 'Vos accords',
                summary: 'Les textes complets des cinq accords.',
                before_accept:
                    'En appuyant sur « J’accepte », vous donnez les cinq accords décrits plus bas.',
                intro: 'Touchez un accord pour lire son texte.',
                version: 'Version :version',
            },
            advanced: {
                title: 'Paramètres avancés',
                summary:
                    'Après vous : vos histoires pourront être transmises à votre famille, sauf choix contraire.',
                wishes_title: 'Vos souhaits pour plus tard',
                wishes_body: 'Ce qu’il faudra faire de vos histoires.',
                referent: 'La personne à qui nous nous adresserons',
                referent_contact: 'Comment la joindre',
            },
            settings: {
                title: 'Comment nous vous joignons',
                hint: 'Tout est déjà réglé.',
                channel: 'Par quel moyen ?',
                phone: 'Votre numéro de téléphone',
                phone_hint: 'Au format international.',
                email: 'Votre adresse de courriel',
                email_hint: 'Nous y enverrons chaque question.',
                cadence: 'À quelle fréquence ?',
                day: 'Quel jour ?',
                slot: 'À quel moment ?',
                address_form: 'Vous ou tu ?',
            },
            days: {
                1: 'Lundi',
                2: 'Mardi',
                3: 'Mercredi',
                4: 'Jeudi',
                5: 'Vendredi',
                6: 'Samedi',
                7: 'Dimanche',
            },
            accept: 'J’accepte',
            refuse: 'Non merci',
            already_answered: 'Vous avez déjà répondu. Écrivez à :email.',
            no_password: 'Cette page ne demandera jamais de mot de passe.',
            refusal: {
                title: 'Vous préférez ne pas',
                body: 'C’est votre choix.',
                no_reason: 'Je préfère ne rien dire',
                confirm: 'Confirmer mon refus',
                back: 'Revenir en arrière',
            },
        },
    },
};

const post = vi.fn();

/*
 * Ce que la page a donné à `useForm`, et ce que le serveur lui répond : les
 * deux sont sous la main du test, parce que la page réagit aux deux.
 */
const server = vi.hoisted(() => ({
    initial: null as Record<string, unknown> | null,
    errors: {} as Record<string, string>,
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    useForm: (initial: Record<string, unknown>) => {
        // Les cinq accords sont les seuls champs `consent_*` : c'est ainsi
        // qu'on reconnaît le formulaire d'acceptation de celui du refus.
        if ('consent_voice_recording' in initial) {
            server.initial = initial;
        }

        return {
            data: initial,
            errors: server.errors,
            processing: false,
            setData: vi.fn(),
            post: (...args: unknown[]) => post(...args),
        };
    },
    usePage: () => ({
        props: {
            i18n: catalogue,
            brand: { name: 'P', support_email: 'aide@example.test' },
        },
    }),
}));

const props = {
    inviterName: 'Camille',
    firstName: 'Jeanne',
    personalMessage: 'J’aimerais garder tes histoires, maman.',
    giftAudioUrl: null,
    phoneMasked: '+336•• •• •• 78',
    phone: '+33612345678',
    email: 'jeanne@example.test',
    preferredChannel: 'email',
    addressForm: 'vous',
    cadence: 'weekly',
    promptDay: 1,
    promptSlot: 'morning',
    consents: [
        {
            kind: 'voice_recording',
            label: 'Enregistrement de la voix',
            version: '1.0',
            body: 'Votre voix est enregistrée.',
        },
        {
            kind: 'transcription',
            label: 'Transcription',
            version: '1.0',
            body: 'Votre enregistrement est transcrit.',
        },
        {
            kind: 'ai_rendering',
            label: 'Mise en forme',
            version: '1.0',
            body: 'Un outil met le texte en forme.',
        },
        {
            kind: 'family_sharing',
            label: 'Partage aux proches',
            version: '1.0',
            body: 'Vos histoires validées sont visibles.',
        },
        {
            kind: 'sensitive_categories',
            label: 'Sujets sensibles',
            version: '1.0',
            body: 'Vos récits peuvent aborder votre santé.',
        },
    ],
    channels: [
        { value: 'sms', label: 'SMS' },
        { value: 'email', label: 'Courriel' },
    ],
    cadences: [
        { value: 'weekly', label: 'Une question par semaine' },
        { value: 'biweekly', label: 'Une question tous les quinze jours' },
    ],
    slots: [
        { value: 'morning', label: 'Matin' },
        { value: 'evening', label: 'Soir' },
    ],
    addressForms: [
        { value: 'vous', label: 'Vouvoiement' },
        { value: 'tu', label: 'Tutoiement' },
    ],
    refusalReasons: [
        { value: 'not_the_right_time', label: 'Ce n’est pas le bon moment' },
        { value: 'prefer_not_to', label: 'Je préfère ne pas' },
    ],
    wishes: [
        { value: 'transfer_to_family', label: 'Transmettre à ma famille' },
        { value: 'freeze', label: 'Geler, sans rien transmettre' },
        { value: 'delete', label: 'Tout supprimer' },
    ],
    defaultWish: 'transfer_to_family',
    answered: false,
    acceptAction: '/i/jeton/accepter',
    refuseAction: '/i/jeton/refuser',
};

beforeEach(() => {
    post.mockClear();
    server.initial = null;
    server.errors = {};
});

describe('la page d’opt-in', () => {
    it('énonce les cinq accords, sans case à cocher, et les donne avec J’accepte', () => {
        render(<OptIn {...props} />);

        // Cinq lignes lisibles, aucune case : c'est le bouton qui est l'acte
        // (T-233). Rien n'est pré-coché, parce qu'il n'y a rien à cocher. La
        // phrase au-dessus du bouton dit ce qu'il donne ; les textes sont
        // repliés dessous.
        expect(screen.queryByRole('checkbox')).toBeNull();
        expect(
            screen.getByText(/vous donnez les cinq accords décrits plus bas/),
        ).toBeTruthy();

        for (const consent of props.consents) {
            expect(screen.getByText(consent.label)).toBeTruthy();
        }

        // Et le formulaire part avec les cinq : le serveur les exige un par
        // un, et les journalise un par un — chacun reste révocable seul.
        expect(server.initial).toMatchObject({
            consent_voice_recording: true,
            consent_transcription: true,
            consent_ai_rendering: true,
            consent_family_sharing: true,
            consent_sensitive_categories: true,
        });
    });

    it('montre les réglages avant les boutons, et replie les accords dessous', () => {
        render(<OptIn {...props} />);

        const details = document.querySelector('details');
        const accept = screen.getByRole('button', { name: 'J’accepte' });
        const day = screen.getByLabelText('Quel jour ?');

        if (details === null) {
            throw new Error('accordéon absent');
        }

        const follows = (first: Element, second: Element): boolean =>
            (first.compareDocumentPosition(second) &
                Node.DOCUMENT_POSITION_FOLLOWING) !==
            0;

        // Les réglages se lisent avant de dire oui ; les textes des accords
        // attendent dessous, fermés, sous leur vrai nom.
        expect(follows(day, accept)).toBe(true);
        expect(follows(accept, details)).toBe(true);
        expect(details.open).toBe(false);
        expect(details.querySelector('summary')?.textContent).toContain(
            'Vos accords',
        );
    });

    it('montre le champ de contact du canal choisi, déjà rempli', () => {
        // Courriel : l'adresse, et pas le numéro.
        const byEmail = render(<OptIn {...props} preferredChannel="email" />);

        expect(screen.getByLabelText('Votre adresse de courriel')).toHaveValue(
            'jeanne@example.test',
        );
        expect(screen.queryByLabelText('Votre numéro de téléphone')).toBeNull();

        byEmail.unmount();

        // SMS : le numéro, écrit comme on l'écrit en France, et pas l'adresse.
        const bySms = render(<OptIn {...props} preferredChannel="sms" />);

        expect(screen.getByLabelText('Votre numéro de téléphone')).toHaveValue(
            '06 12 34 56 78',
        );
        expect(screen.queryByLabelText('Votre adresse de courriel')).toBeNull();

        bySms.unmount();

        // Les deux : les deux.
        render(<OptIn {...props} preferredChannel="both" />);

        expect(screen.getByLabelText('Votre numéro de téléphone')).toBeTruthy();
        expect(screen.getByLabelText('Votre adresse de courriel')).toBeTruthy();
    });

    it('replie les souhaits pour plus tard sous les accords, « transmettre » proposé d’avance', () => {
        render(<OptIn {...props} />);

        const [accords, advanced] = Array.from(
            document.querySelectorAll('details'),
        );

        if (accords === undefined || advanced === undefined) {
            throw new Error('deux accordéons attendus');
        }

        // Fermé, sous les accords, et la ligne sous le titre dit ce qui vaut
        // sans qu'il faille ouvrir.
        expect(advanced.open).toBe(false);
        expect(
            (accords.compareDocumentPosition(advanced) &
                Node.DOCUMENT_POSITION_FOLLOWING) !==
                0,
        ).toBe(true);
        expect(advanced.querySelector('summary')?.textContent).toContain(
            'Paramètres avancés',
        );
        expect(advanced.querySelector('summary')?.textContent).toContain(
            'transmises à votre famille',
        );

        // « Transmettre à ma famille » est coché d'avance : c'est ce qui
        // arrivera sans directive, et la personne n'a rien à gérer. Le
        // serveur, lui, n'écrit rien tant qu'elle ne choisit pas autre chose.
        expect(
            screen.getByRole('radio', { name: 'Transmettre à ma famille' }),
        ).toBeChecked();
        expect(server.initial).toMatchObject({
            wishes: 'transfer_to_family',
            referent_name: '',
        });
    });

    it('ouvre les accords quand le serveur en refuse un', () => {
        server.errors = {
            consent_voice_recording: 'Cet accord est nécessaire.',
        };

        render(<OptIn {...props} />);

        // Une erreur derrière un accordéon fermé serait invisible : personne
        // n'irait la chercher là.
        expect(document.querySelector('details')?.open).toBe(true);
        expect(screen.getByRole('alert').textContent).toBe(
            'Cet accord est nécessaire.',
        );
    });

    it('ne propose aucun enregistrement avant l’acceptation', () => {
        render(<OptIn {...props} />);

        // Pas de micro, pas de question, pas d'aperçu : quelqu'un qui
        // découvre le service par un cadeau doit pouvoir comprendre de quoi
        // il s'agit sans être déjà en train de faire quelque chose.
        expect(screen.queryByText(/enregistrer/i)).toBeNull();
        expect(document.querySelector('audio')).toBeNull();
    });

    it('donne aux deux boutons exactement le même poids visuel', () => {
        render(<OptIn {...props} />);

        const accept = screen.getByRole('button', { name: 'J’accepte' });
        const refuse = screen.getByRole('button', { name: 'Non merci' });

        // Rendre le refus discret ne produit pas des oui : ça produit des
        // gens qui ne répondent pas. Le test le vérifie sur les classes,
        // parce que c'est là que la tentation se logerait.
        //
        // Les variantes `disabled:` sont écartées de la comparaison : elles
        // n'agissent que pendant l'envoi du formulaire, et seul le bouton qui
        // envoie en a besoin.
        const weight = (element: HTMLElement): string =>
            element.className
                .split(' ')
                .filter((name) => !name.startsWith('disabled:'))
                .join(' ');

        expect(weight(refuse)).toBe(weight(accept));
        expect(accept.parentElement).toBe(refuse.parentElement);
    });

    it('montre le message personnel de la personne qui offre', () => {
        render(<OptIn {...props} />);

        expect(
            screen.getByText('J’aimerais garder tes histoires, maman.'),
        ).toBeTruthy();
    });

    it('déplie le texte d’un accord quand on touche son titre', async () => {
        render(<OptIn {...props} />);

        expect(screen.queryByText('Votre voix est enregistrée.')).toBeNull();

        // Le titre est le bouton : pas de « lire » à côté, rien à chercher.
        const title = screen.getByRole('button', {
            name: 'Enregistrement de la voix',
        });

        expect(title.getAttribute('aria-expanded')).toBe('false');

        await userEvent.click(title);

        expect(title.getAttribute('aria-expanded')).toBe('true');
        expect(screen.getByText('Votre voix est enregistrée.')).toBeTruthy();

        // Et le refermer : un texte qui reste ouvert allonge la page pour rien.
        await userEvent.click(title);

        expect(screen.queryByText('Votre voix est enregistrée.')).toBeNull();
    });

    it('poste le refus sur l’URL donnée par le serveur', async () => {
        render(<OptIn {...props} />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Non merci' }),
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Confirmer mon refus' }),
        );

        expect(post).toHaveBeenCalledWith('/i/jeton/refuser');
    });

    it('laisse revenir en arrière depuis l’écran de refus', async () => {
        render(<OptIn {...props} />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Non merci' }),
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Revenir en arrière' }),
        );

        // Un refus n'est pas un piège : on peut changer d'avis avant de le
        // confirmer.
        expect(screen.getByRole('button', { name: 'J’accepte' })).toBeTruthy();
        expect(post).not.toHaveBeenCalled();
    });

    it('ne redemande rien à quelqu’un qui a déjà répondu', () => {
        render(<OptIn {...props} answered={true} />);

        expect(screen.queryByRole('checkbox')).toBeNull();
        expect(screen.getByText(/Vous avez déjà répondu/)).toBeTruthy();
    });

    it('s’ouvre sur le nom de marque, seul, la page entière déjà dessous', () => {
        render(<OptIn {...props} />);

        const overture = document.querySelector('[data-overture]');

        // Le rideau montre le nom ; la page, elle, est là dès le départ pour
        // qui ne voit pas le rideau — et le prénom y est repris.
        expect(overture?.textContent).toBe('P');
        expect(overture?.getAttribute('aria-hidden')).toBe('true');
        expect(screen.getByText('Bonjour Jeanne,')).toBeTruthy();
        expect(screen.getByRole('button', { name: 'J’accepte' })).toBeTruthy();
    });

    it('ne rejoue pas l’ouverture à qui a déjà répondu', () => {
        render(<OptIn {...props} answered={true} />);

        expect(document.querySelector('[data-overture]')).toBeNull();
    });
});
