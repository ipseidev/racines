import { act, render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import WelcomeOffer from './WelcomeOffer';
import { WELCOME_OFFER_STORAGE_KEY } from '@/lib/welcomeOffer';

const catalogue = {
    common: { actions: { close: 'Fermer' } },
    public: {
        welcome_offer: {
            aria: 'Une réduction de bienvenue',
            eyebrow: 'Pour commencer',
            title: ':amount offerts',
            subtitle: 'sur le livre de ses souvenirs',
            teaser: 'Laissez-nous votre adresse : :amount de réduction.',
            claim: 'Je prends ma réduction',
            no_thanks: 'Non merci',
            email_label: 'Votre adresse de courriel',
            email_placeholder: 'prenom@exemple.fr',
            news: 'Je souhaite aussi recevoir vos nouvelles.',
            send: 'Recevoir mon code',
            waiting: 'Un instant…',
            fine_print: 'Votre adresse sert à vous envoyer le code.',
            sent_title: 'C’est envoyé',
            sent_body: 'Votre code part vers :email.',
            sent_auto: 'La réduction s’appliquera toute seule.',
            sent_cta: 'Je commence son livre',
        },
    },
};

const post = vi.fn();
const setData = vi.fn();
const formState = {
    errors: {} as Record<string, string>,
};

/*
 * Le formulaire d'Inertia, réduit à ce que le composant en lit : des valeurs
 * qui suivent la saisie (un vrai état React, sinon le champ contrôlé reste
 * vide et le navigateur refuse d'envoyer un courriel requis), les erreurs du
 * serveur, et `post`, observé.
 */
vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        usePage: () => ({ props: { i18n: catalogue } }),
        useForm: function useFormMock(initial: Record<string, unknown>) {
            const [data, setState] = useState(initial);

            return {
                data,
                errors: formState.errors,
                processing: false,
                setData: (key: string, value: unknown) => {
                    setData(key, value);
                    setState((current) => ({ ...current, [key]: value }));
                },
                post,
            };
        },
        Link: ({
            href,
            children,
            className,
        }: {
            href: string;
            children: React.ReactNode;
            className?: string;
        }) => (
            <a href={href} className={className}>
                {children}
            </a>
        ),
    };
});

/*
 * jsdom ne sait pas ouvrir un `<dialog>` : on lui apprend le strict
 * nécessaire, l'attribut `open` et l'événement `close`.
 */
beforeEach(() => {
    localStorage.clear();
    formState.errors = {};
    post.mockReset();

    if (!('showModal' in HTMLDialogElement.prototype)) {
        Object.defineProperty(HTMLDialogElement.prototype, 'showModal', {
            configurable: true,
            value(this: HTMLDialogElement) {
                this.setAttribute('open', '');
            },
        });
        Object.defineProperty(HTMLDialogElement.prototype, 'close', {
            configurable: true,
            value(this: HTMLDialogElement) {
                this.removeAttribute('open');
                this.dispatchEvent(new Event('close'));
            },
        });
    }
});

afterEach(() => {
    vi.useRealTimers();
});

/*
 * Les faux minuteurs servent aux tests du délai, et à eux seuls : sous de
 * faux minuteurs, React ne rend plus rien après un clic simulé. Les tests
 * d'interaction ouvrent la fenêtre sans délai et attendent en temps réel.
 */
async function opened() {
    return await screen.findByRole('button', {
        name: 'Je prends ma réduction',
    });
}

describe('WelcomeOffer', () => {
    it('attend le délai avant de se montrer, puis dit le montant', async () => {
        vi.useFakeTimers();
        render(<WelcomeOffer enabled={true} discountPercent={10} />);

        // Le composant écrit une espace fine insécable avant le signe ;
        // Testing Library la ramène à une espace ordinaire avant de comparer.
        expect(screen.queryByText('10 % offerts')).not.toBeInTheDocument();

        await act(async () => {
            vi.advanceTimersByTime(6_000);
        });

        expect(screen.getByText('10 % offerts')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Je prends ma réduction' }),
        ).toBeInTheDocument();
        // Le champ n'est pas encore là : d'abord la promesse, puis le champ.
        expect(
            screen.queryByLabelText('Votre adresse de courriel'),
        ).not.toBeInTheDocument();
    });

    it('ne se montre pas quand l’offre est coupée', async () => {
        vi.useFakeTimers();
        render(<WelcomeOffer enabled={false} discountPercent={10} />);

        await act(async () => {
            vi.advanceTimersByTime(10_000);
        });

        expect(screen.queryByText('10 % offerts')).not.toBeInTheDocument();
    });

    it('se tait après une fermeture récente', async () => {
        vi.useFakeTimers();
        localStorage.setItem(
            WELCOME_OFFER_STORAGE_KEY,
            JSON.stringify({ status: 'dismissed', at: Date.now() }),
        );

        render(<WelcomeOffer enabled={true} discountPercent={10} />);

        await act(async () => {
            vi.advanceTimersByTime(10_000);
        });

        expect(screen.queryByText('10 % offerts')).not.toBeInTheDocument();
    });

    it('retient la fermeture', async () => {
        render(
            <WelcomeOffer enabled={true} discountPercent={10} delayMs={0} />,
        );
        await opened();

        await userEvent.click(screen.getByRole('button', { name: 'Fermer' }));

        expect(screen.queryByText('10 % offerts')).not.toBeInTheDocument();
        expect(
            JSON.parse(localStorage.getItem(WELCOME_OFFER_STORAGE_KEY) ?? '{}'),
        ).toMatchObject({ status: 'dismissed' });
    });

    it('ouvre le champ, envoie, puis dit où le code est parti', async () => {
        render(
            <WelcomeOffer enabled={true} discountPercent={10} delayMs={0} />,
        );

        await userEvent.click(await opened());

        const email = screen.getByLabelText('Votre adresse de courriel');
        expect(email).toHaveFocus();

        await userEvent.type(email, 'camille@exemple.fr');
        expect(setData).toHaveBeenCalledWith('email', expect.any(String));

        // La case des nouvelles est là, décochée, et n'est pas requise.
        const news = screen.getByRole('checkbox', {
            name: 'Je souhaite aussi recevoir vos nouvelles.',
        });
        expect(news).not.toBeChecked();
        expect(news).not.toBeRequired();

        post.mockImplementation(
            (_url: string, options: { onSuccess: () => void }) =>
                options.onSuccess(),
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Recevoir mon code' }),
        );

        expect(post).toHaveBeenCalledWith(
            '/offre-de-bienvenue',
            expect.objectContaining({ preserveState: true }),
        );
        expect(screen.getByText('C’est envoyé')).toBeInTheDocument();
        expect(
            screen.getByText('Votre code part vers camille@exemple.fr.'),
        ).toBeInTheDocument();
        expect(
            JSON.parse(localStorage.getItem(WELCOME_OFFER_STORAGE_KEY) ?? '{}'),
        ).toMatchObject({ status: 'claimed' });
    });

    it('montre l’erreur du serveur à l’endroit du champ', async () => {
        formState.errors = {
            email: 'Nous n’avons pas réussi à envoyer le code.',
        };

        render(
            <WelcomeOffer enabled={true} discountPercent={10} delayMs={0} />,
        );
        await userEvent.click(await opened());

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Nous n’avons pas réussi à envoyer le code.',
        );
    });
});
