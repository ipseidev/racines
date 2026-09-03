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
            means: {
                title: 'Ce que cela veut dire pour vous',
                one: 'Une question par semaine.',
                two: 'Vous relisez avant que quiconque le voie.',
                three: 'Vous pouvez arrêter à tout moment.',
            },
            consents: {
                title: 'Vos accords',
                intro: 'Chaque accord est séparé.',
                read: 'Lire le texte',
                hide: 'Masquer le texte',
                version: 'Version :version',
            },
            settings: {
                title: 'Comment nous vous joignons',
                channel: 'Par quel moyen ?',
                phone: 'Votre numéro',
                phone_hint: 'Au format international.',
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

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    useForm: (initial: Record<string, unknown>) => ({
        data: initial,
        errors: {},
        processing: false,
        setData: vi.fn(),
        post: (...args: unknown[]) => post(...args),
    }),
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
    answered: false,
    acceptAction: '/i/jeton/accepter',
    refuseAction: '/i/jeton/refuser',
};

beforeEach(() => {
    post.mockClear();
});

describe('la page d’opt-in', () => {
    it('propose cinq accords séparés', () => {
        render(<OptIn {...props} />);

        // Cinq cases, cinq acceptations. Un « j'accepte tout » rendrait la
        // révocation d'un seul consentement impossible.
        expect(screen.getAllByRole('checkbox')).toHaveLength(5);
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

    it('déplie le texte d’un accord à la demande', async () => {
        render(<OptIn {...props} />);

        expect(screen.queryByText('Votre voix est enregistrée.')).toBeNull();

        await userEvent.click(screen.getAllByText('Lire le texte')[0]);

        expect(screen.getByText('Votre voix est enregistrée.')).toBeTruthy();
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
});
