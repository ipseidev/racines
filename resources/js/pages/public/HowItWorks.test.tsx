import { render, screen, within } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import HowItWorks from './HowItWorks';

/*
 * Un catalogue réduit à ce que les tests lisent. Les valeurs n'ont pas à être
 * celles de `lang/fr/public.php` : on vérifie la mécanique de la page, pas
 * sa rédaction, qui a ses propres tests côté serveur.
 */
function step(gift: [string, string], self: [string, string], link: string) {
    return {
        gift: { title: gift[0], body: gift[1], link },
        self: { title: self[0], body: self[1], link },
        alt: 'Une photo.',
    };
}

const catalogue = {
    public: {
        landing: {
            cta: 'J’offre ce livre',
            hero: {
                card: {
                    aria: 'Exemple de question de la semaine',
                    label: 'Question de la semaine',
                    name: 'Odette',
                    question: 'Quelle odeur vous ramène à votre enfance ?',
                },
            },
            proof: {
                verbatim: 'Mot à mot',
                fluide: 'Texte mis au propre',
                sample_verbatim: 'alors euh… ma grand-mère',
                sample_fluide: 'Ma grand-mère, elle habitait',
                // eslint-disable-next-line unicorn/no-thenable -- clé du catalogue, jamais attendue
                then: 'Puis elle choisit :',
                share: 'Partager',
                keep: 'Garder pour moi',
                later: 'Décider plus tard',
            },
            story: { p3: 'C’est de là que vient ce livre.' },
            faq: {
                title: 'Questions fréquentes',
                no_smartphone: {
                    q: 'Et sans smartphone ?',
                    a: 'L’option téléphone.',
                },
                refuses: { q: 'Et si elle refuse ?', a: 'Remboursé.' },
                edit: { q: 'Peut-on corriger ?', a: 'Oui.' },
                privacy: {
                    q: 'Qui peut écouter ?',
                    a: 'Les proches autorisés.',
                },
            },
        },
        welcome_offer: {
            teaser: 'Laissez-nous votre adresse : un code de :amount.',
            email_label: 'Votre adresse de courriel',
            email_placeholder: 'prenom@exemple.fr',
            news: 'Je souhaite aussi recevoir vos nouvelles.',
            send: 'Recevoir mon code',
            waiting: 'Un instant…',
            fine_print: 'Votre adresse sert à vous envoyer le code.',
            sent_title: 'C’est envoyé',
            sent_body: 'Votre code part vers :email.',
        },
        how_it_works: {
            seo_title: 'Comment ça marche',
            hero: {
                title: 'Comment ça marche',
                question: 'Vous offrez ce livre, ou vous racontez vous-même ?',
                toggle_label: 'Pour qui est le livre ?',
                gift: 'Pour un proche',
                self: 'Pour moi',
            },
            intro: {
                gift: {
                    headline: 'Ses souvenirs, racontés avec sa voix.',
                    lede: 'Rien à écrire.',
                },
                self: {
                    headline: 'Votre vie, racontée avec votre voix.',
                    lede: 'Rien à écrire.',
                },
                listen: 'Écoutez',
                caption: 'Deux minutes avec Odette.',
            },
            steps: {
                title: 'Les six étapes',
                label: 'Étape :n',
                badge: ':n sur 6',
                questions: {
                    ...step(
                        [
                            'Vous choisissez les questions',
                            'Soixante questions.',
                        ],
                        [
                            'Vous choisissez ce que vous voulez raconter',
                            'Soixante questions.',
                        ],
                        'Voir quelques questions',
                    ),
                    samples: {
                        first_memory: 'Quel est votre tout premier souvenir ?',
                        dish: 'Quel plat de votre enfance ?',
                        meeting: 'Comment avez-vous rencontré ?',
                        value: 'Quelle valeur ?',
                    },
                },
                record: step(
                    ['Une question arrive. Elle parle.', 'Chaque semaine.'],
                    [
                        'Chaque semaine, une question. Vous parlez.',
                        'Chaque semaine.',
                    ],
                    'Essayer l’écran qu’elle verra',
                ),
                text: step(
                    ['Ses mots deviennent un texte', 'En quelques minutes.'],
                    ['Vos mots deviennent un texte', 'En quelques minutes.'],
                    'Voir la différence',
                ),
                decide: step(
                    ['Elle relit, puis elle décide', 'Avant qui que ce soit.'],
                    [
                        'Vous relisez, puis vous décidez',
                        'Avant qui que ce soit.',
                    ],
                    'Lire nos engagements',
                ),
                family: step(
                    [
                        'La famille écoute et lui répond',
                        'Chaque histoire partagée.',
                    ],
                    [
                        'Vos proches écoutent et vous répondent',
                        'Chaque histoire partagée.',
                    ],
                    'Qui peut écouter ?',
                ),
                book: step(
                    [
                        'Le livre relié, avec sa voix à chaque page',
                        'Au fil de l’année.',
                    ],
                    [
                        'Le livre relié, avec votre voix à chaque page',
                        'Au fil de l’année.',
                    ],
                    'Voir le livre',
                ),
            },
            questions: {
                title: 'Encore des questions ?',
                all: 'Toutes les questions',
            },
            cta: {
                gift: {
                    headline: 'Offrez-lui le livre de sa vie.',
                    body: 'Une année de questions.',
                },
                self: {
                    headline: 'Votre histoire, dans vos propres mots.',
                    body: 'Une année de questions.',
                    button: 'Je commence mon livre',
                },
            },
            rendering: {
                eyebrow: 'Le texte',
                headline: 'Le même enregistrement, deux textes.',
                lede: 'Le premier est ce qu’elle a dit.',
                tabs_label: 'Les deux versions du texte',
                question_label: 'La question d’Odette',
            },
            voice: {
                title: 'Pourquoi ce livre existe',
                author: 'Le fondateur de :brand',
                cta: 'Lire notre histoire',
            },
            more: {
                faq: { body: 'Ce qui est compris.', cta: 'Lire les réponses' },
                try: {
                    title: 'Essayez en 60 secondes',
                    body: 'L’écran de la personne qui raconte.',
                    cta: 'Faire l’essai',
                },
            },
            together: {
                gift: {
                    headline: 'Un livre qui se fait à plusieurs.',
                    body: 'Vous lancez le projet.',
                },
                self: {
                    headline: 'Un livre qui se fait avec les vôtres.',
                    body: 'Vous racontez.',
                    button: 'Je commence mon livre',
                },
                points: {
                    questions: 'Choisir les questions',
                    photos: 'Ajouter des photos',
                    listen: 'Écouter chaque histoire',
                    reply: 'Répondre d’un mot',
                },
                alt: 'Une femme âgée et sa fille.',
            },
            newsletter: { title: ':amount offerts pour commencer' },
        },
    },
};

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        Head: ({ title }: { title: string }) => <title>{title}</title>,
        usePage: () => ({ props: { i18n: catalogue } }),
        // Un formulaire qui retient ce qu'on y tape : le champ est requis, et
        // un envoi sur une valeur vide serait refusé avant d'atteindre `post`.
        useForm: <T extends Record<string, unknown>>(initial: T) => {
            const [data, setData] = useState<T>(initial);

            return {
                data,
                setData: (key: keyof T, value: T[keyof T]) =>
                    setData((current) => ({ ...current, [key]: value })),
                post,
                processing: false,
                errors: {} as Record<string, string>,
            };
        },
        // Marqué, pour distinguer une navigation Inertia d'un lien ordinaire.
        Link: ({
            href,
            children,
        }: {
            href: string;
            children: React.ReactNode;
        }) => (
            <a href={href} data-inertia="true">
                {children}
            </a>
        ),
    };
});

const props = {
    mode: 'pilot',
    price: 8_900,
    welcomeOffer: { enabled: true, discountPercent: 10 },
    heroSample: null,
};

describe('HowItWorks', () => {
    it('ouvre sur un titre, une question et deux onglets, sans rien vendre', () => {
        render(<HowItWorks {...props} />);

        // Le bandeau de titre est celui du leader : le titre, la question,
        // les deux onglets. Ni photo, ni prix, ni bouton d'achat avant
        // l'accroche.
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
            'Comment ça marche',
        );
        expect(
            screen.getByText(
                'Vous offrez ce livre, ou vous racontez vous-même ?',
            ),
        ).toBeInTheDocument();

        const group = screen.getByRole('group', {
            name: 'Pour qui est le livre ?',
        });
        expect(
            within(group).getByRole('button', { name: 'Pour un proche' }),
        ).toHaveAttribute('aria-pressed', 'true');
        expect(
            within(group).getByRole('button', { name: 'Pour moi' }),
        ).toHaveAttribute('aria-pressed', 'false');
    });

    it('déroule six étapes numérotées, dans l’ordre du parcours', () => {
        render(<HowItWorks {...props} />);

        const steps = within(
            screen.getByRole('list', { name: 'Les six étapes' }),
        )
            .getAllByRole('heading', { level: 3 })
            .map((heading) => heading.textContent);

        expect(steps).toEqual([
            'Vous choisissez les questions',
            'Une question arrive. Elle parle.',
            'Ses mots deviennent un texte',
            'Elle relit, puis elle décide',
            'La famille écoute et lui répond',
            'Le livre relié, avec sa voix à chaque page',
        ]);

        // L'étiquette et le badge de chaque étape viennent du catalogue.
        expect(screen.getByText('Étape 1')).toBeInTheDocument();
        expect(screen.getByText('6 sur 6')).toBeInTheDocument();

        // Et les questions d'exemple sont là, derrière le lien de l'étape 1.
        expect(
            screen.getByText('Quel est votre tout premier souvenir ?'),
        ).toBeInTheDocument();
    });

    it('bascule tout le parcours quand on raconte soi-même', async () => {
        const user = userEvent.setup();
        render(<HowItWorks {...props} />);

        expect(
            screen.getByText('Ses souvenirs, racontés avec sa voix.'),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: /J’offre ce livre/ }),
        ).toHaveLength(2);

        await user.click(screen.getByRole('button', { name: 'Pour moi' }));

        // L'accroche, les six étapes et les deux appels suivent ; le titre de
        // la page, lui, ne bouge pas.
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
            'Comment ça marche',
        );
        expect(
            screen.getByText('Votre vie, racontée avec votre voix.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Vous choisissez ce que vous voulez raconter'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Vous relisez, puis vous décidez'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Votre histoire, dans vos propres mots.'),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: /Je commence mon livre/ }),
        ).toHaveLength(2);
        expect(
            screen.queryByRole('link', { name: /J’offre ce livre/ }),
        ).not.toBeInTheDocument();
    });

    it('met le prix dans le bouton du bandeau d’appel', () => {
        render(<HowItWorks {...props} />);

        const buy = screen.getAllByRole('link', { name: /J’offre ce livre/ });

        for (const link of buy) {
            expect(link).toHaveAttribute('href', '/acheter');
        }

        // 8 900 centimes, mis en forme sans décimales inutiles. L'espace
        // devant le symbole est celle d'`Intl`, insécable : on la lit avec `\s`.
        expect(buy.some((link) => /89\s€/u.test(link.textContent ?? ''))).toBe(
            true,
        );
    });

    it('montre le mot à mot d’abord, puis le texte mis au propre', async () => {
        const user = userEvent.setup();
        render(<HowItWorks {...props} />);

        const verbatim = screen.getByRole('tab', { name: 'Mot à mot' });
        const fluide = screen.getByRole('tab', { name: 'Texte mis au propre' });

        expect(verbatim).toHaveAttribute('aria-selected', 'true');
        expect(
            screen.getByText('alors euh… ma grand-mère'),
        ).toBeInTheDocument();
        expect(
            screen.queryByText('Ma grand-mère, elle habitait'),
        ).not.toBeInTheDocument();

        await user.click(fluide);

        expect(fluide).toHaveAttribute('aria-selected', 'true');
        expect(
            screen.getByText('Ma grand-mère, elle habitait'),
        ).toBeInTheDocument();
        expect(
            screen.queryByText('alors euh… ma grand-mère'),
        ).not.toBeInTheDocument();

        // Et les flèches passent d'un onglet à l'autre.
        fluide.focus();
        await user.keyboard('{ArrowLeft}');
        expect(verbatim).toHaveAttribute('aria-selected', 'true');
    });

    it('mène à l’essai par des liens ordinaires, jamais par Inertia', () => {
        render(<HowItWorks {...props} />);

        const toDemo = screen
            .getAllByRole('link')
            .filter((link) => link.getAttribute('href') === '/essai');

        // Trois chemins vers l'essai : l'étape où elle parle, puis le texte et
        // la vignette de la carte du bas. Une navigation Inertia garderait la
        // politique de permissions de cette page, où le micro est interdit, et
        // Safari refuserait l'essai sans demander l'autorisation (T-151).
        expect(toDemo).toHaveLength(3);

        for (const link of toDemo) {
            expect(link).not.toHaveAttribute('data-inertia');
        }

        // Les achats, eux, passent bien par Inertia.
        for (const link of screen.getAllByRole('link', {
            name: /J’offre ce livre/,
        })) {
            expect(link).toHaveAttribute('data-inertia', 'true');
        }
    });

    it('propose la réduction contre une adresse, la case des nouvelles décochée', async () => {
        const user = userEvent.setup();
        render(<HowItWorks {...props} />);

        expect(
            screen.getByRole('heading', {
                name: /10\s% offerts pour commencer/u,
            }),
        ).toBeInTheDocument();

        const news = screen.getByRole('checkbox', { name: /vos nouvelles/ });
        expect(news).not.toBeChecked();
        expect(news).not.toBeRequired();

        await user.type(
            screen.getByLabelText('Votre adresse de courriel'),
            'a@b.fr',
        );
        await user.click(
            screen.getByRole('button', { name: 'Recevoir mon code' }),
        );

        expect(post).toHaveBeenCalledWith(
            '/offre-de-bienvenue',
            expect.anything(),
        );
    });

    it('se tait sur la réduction quand elle n’est pas proposée', () => {
        render(
            <HowItWorks
                {...props}
                welcomeOffer={{ enabled: false, discountPercent: 10 }}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Recevoir mon code' }),
        ).not.toBeInTheDocument();
    });
});
