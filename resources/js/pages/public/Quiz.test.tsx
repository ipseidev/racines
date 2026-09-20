import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Quiz from './Quiz';

const post = vi.hoisted(() => vi.fn());
const track = vi.hoisted(() => vi.fn());

vi.mock('@/components/landing/track', () => ({ track }));

vi.mock('@/components/landing/SampleAudio', () => ({
    default: () => <div data-testid="sample" />,
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => (
        <a href={href}>{children}</a>
    ),
    router: { post, delete: vi.fn() },
    useForm: () => ({
        data: { email: '', news: false, website: '' },
        errors: {},
        processing: false,
        setData: vi.fn(),
        post: vi.fn(),
    }),
    usePage: () => ({ props: { i18n: CATALOGUE, brand: { name: 'P' } } }),
}));

/*
 * Un catalogue réduit aux deux clés qui **portent un paramètre dont le test a
 * besoin** : le nom accessible d'un choix, et l'expéditeur de la bulle. Tout
 * le reste rend sa clé, ce qui est voulu — on éprouve l'enchaînement, pas la
 * rédaction, et une assertion sur une clé ne casse pas à la prochaine
 * retouche de texte.
 */
const CATALOGUE = {
    public: {
        quiz: {
            choose: ':label',
            preview: { from: 'De : :sender' },
        },
    },
};

/*
 * Le catalogue n'est pas chargé : `useT()` rend la clé. C'est exactement ce
 * qu'on veut ici — on éprouve l'enchaînement, pas la rédaction, et une
 * assertion sur une clé ne casse pas à la prochaine retouche de texte.
 */
function option(value: string, label: string) {
    return { value, label };
}

const props = {
    preview: null,
    checkoutUrl: '/acheter?step=2',
    emailSaved: false,
    welcomeOffer: true,
    minThemes: 3,
    relationships: [
        option('mother', 'Ma mère'),
        option('father', 'Mon père'),
        option('myself', 'Pour moi'),
    ],
    subjects: { mother: 'votre mère', father: 'votre père', myself: 'vous' },
    ageBands: [option('seventies', '70 à 79 ans')],
    distances: [
        option('same_town', 'Même ville'),
        option('abroad', 'Autre pays'),
    ],
    themes: [
        option('childhood', 'Son enfance'),
        option('work', 'Son métier'),
        option('legacy', 'Ce qui doit rester'),
        option('love', 'L’amour'),
    ],
    storytellers: [option('endless', 'Intarissable')],
    techComforts: [option('daily', 'Très à l’aise'), option('rarely', 'Peu')],
    channels: [option('sms', 'SMS')],
    occasions: [option('birthday', 'Son anniversaire')],
    covers: [
        {
            value: 'ivory',
            label: 'Ivoire',
            background: '#faf7f2',
            ink: '#2a231e',
            mutedInk: '#5a5049',
            dark: false,
        },
        {
            value: 'forest',
            label: 'Vert forêt',
            background: '#24392f',
            ink: '#f7f1e6',
            mutedInk: '#c9c0b2',
            dark: true,
        },
    ],
    titles: [
        { value: 'first_name', label: 'Son prénom, seul', pattern: ':name' },
        { value: 'story', label: 'L’histoire de…', pattern: 'L’histoire :of' },
        { value: 'custom', label: 'Un titre à moi', pattern: null },
    ],
    bookSubtitle: 'Récits recueillis en 2026',
    sample: null,
};

/**
 * Tape un choix, et laisse passer le délai qui montre la sélection.
 *
 * Sur l'horloge réelle, et non sur une horloge figée : `userEvent` attend
 * lui-même entre deux évènements, et sous `vi.useFakeTimers()` le clic ne
 * rend jamais la main. Le délai du quiz étant d'un quart de seconde, la
 * vraie horloge coûte moins cher que la mécanique pour s'en passer.
 */
async function choose(user: ReturnType<typeof userEvent.setup>, label: string) {
    await user.click(screen.getByRole('button', { name: new RegExp(label) }));

    await act(
        async () =>
            void (await new Promise((resolve) => setTimeout(resolve, 340))),
    );
}

function setup() {
    return userEvent.setup();
}

beforeEach(() => {
    post.mockClear();
    track.mockClear();
    window.localStorage.clear();
});

describe('le tunnel de découverte', () => {
    it('ouvre sur le lien de parenté, sans le choix « pour moi » dans la liste', () => {
        render(<Quiz {...props} />);

        expect(
            screen.getByRole('heading', {
                name: 'public.quiz.relationship.title',
            }),
        ).toBeInTheDocument();

        // « Pour moi » existe, mais comme un lien qui quitte le quiz : le
        // mettre dans la liste ferait entrer dans douze écrans écrits pour
        // quelqu'un qui offre.
        expect(
            screen.queryByRole('button', { name: /Pour moi/ }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Pour moi' })).toHaveAttribute(
            'href',
            '/acheter',
        );
    });

    it('avance au miroir après une réponse, et reprend le sujet choisi', async () => {
        const user = setup();
        render(<Quiz {...props} />);

        await choose(user, 'Ma mère');

        expect(
            screen.getByRole('heading', { name: 'public.quiz.voice.title' }),
        ).toBeInTheDocument();
        expect(screen.getByTestId('sample')).toBeInTheDocument();
    });

    it('laisse revenir en arrière, et garde la réponse donnée', async () => {
        const user = setup();
        render(<Quiz {...props} />);

        await choose(user, 'Ma mère');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.back' }),
        );

        // La carte choisie est encore marquée : revenir corriger une réponse
        // ne doit pas donner l'impression d'avoir tout perdu.
        expect(screen.getByRole('button', { name: /Ma mère/ })).toHaveClass(
            'border-brand',
        );
    });

    it('éteint le retour au premier écran', () => {
        render(<Quiz {...props} />);

        expect(
            screen.getByRole('button', { name: 'public.quiz.back' }),
        ).toBeDisabled();
    });

    it('choisit le miroir d’éloignement d’après la distance', async () => {
        const user = setup();
        render(<Quiz {...props} />);

        await choose(user, 'Ma mère');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
        await choose(user, '70 à 79 ans');
        await choose(user, 'Autre pays');

        expect(
            screen.getByRole('heading', {
                name: 'public.quiz.closeness.far_title',
            }),
        ).toBeInTheDocument();
    });

    it('retient le « Continuer » des thèmes sous le compte demandé', async () => {
        const user = setup();
        render(<Quiz {...props} />);

        await choose(user, 'Ma mère');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
        await choose(user, '70 à 79 ans');
        await choose(user, 'Même ville');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );

        const next = screen.getByRole('button', { name: 'public.quiz.next' });
        expect(next).toBeDisabled();

        await user.click(screen.getByLabelText('Son enfance'));
        await user.click(screen.getByLabelText('Son métier'));
        expect(next).toBeDisabled();

        await user.click(screen.getByLabelText('Ce qui doit rester'));
        expect(next).toBeEnabled();
    });

    it('numérote les thèmes dans l’ordre des clics', async () => {
        const user = setup();
        render(<Quiz {...props} />);

        await choose(user, 'Ma mère');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
        await choose(user, '70 à 79 ans');
        await choose(user, 'Même ville');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );

        await user.click(screen.getByLabelText('Son métier'));
        await user.click(screen.getByLabelText('Son enfance'));

        /*
         * L'ordre décide des premières semaines : le premier coché donne la
         * première question. La pastille fait partie du libellé accessible
         * dès qu'elle apparaît — d'où la recherche partielle.
         */
        expect(
            within(
                screen.getByLabelText(/Son métier/).closest('label')!,
            ).getByText('1'),
        ).toBeInTheDocument();
        expect(
            within(
                screen.getByLabelText(/Son enfance/).closest('label')!,
            ).getByText('2'),
        ).toBeInTheDocument();
    });

    it('reprend les réponses gardées sur l’appareil', () => {
        window.localStorage.setItem(
            'quiz.answers',
            JSON.stringify({ relationship: 'father' }),
        );

        render(<Quiz {...props} />);

        expect(screen.getByRole('button', { name: /Mon père/ })).toHaveClass(
            'border-brand',
        );
        expect(track).toHaveBeenCalledWith('quiz_started', { resumed: true });
    });

    it('mesure chaque écran vu et chaque réponse donnée', async () => {
        const user = setup();
        render(<Quiz {...props} />);

        expect(track).toHaveBeenCalledWith('quiz_screen', {
            index: 1,
            screen: 'relationship',
        });

        await choose(user, 'Ma mère');

        expect(track).toHaveBeenCalledWith('quiz_answer', {
            screen: 'relationship',
            value: 'mother',
        });
        expect(track).toHaveBeenCalledWith('quiz_screen', {
            index: 2,
            screen: 'voice',
        });
    });
});

describe('le plan', () => {
    const preview = {
        firstName: 'Jeanne',
        nickname: 'Mamie',
        sender: 'EXPEDITEUR',
        channel: 'sms',
        sendAt: '2026-12-01',
        sendTime: '09:00',
        cover: 'forest',
        title: 'story',
        titleCustom: '',
        first: 'Quel est votre tout premier souvenir ?',
        next: ['Racontez votre premier jour de travail.'],
        themes: ['childhood', 'work'],
    };

    it('montre le message réel puis les vraies questions', () => {
        render(<Quiz {...props} preview={preview} />);

        expect(
            screen.getByText('EXPEDITEUR', { exact: false }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Quel est votre tout premier souvenir ?'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Racontez votre premier jour de travail.'),
        ).toBeInTheDocument();
    });

    it('nomme la personne par son petit nom, pas par son prénom', () => {
        render(<Quiz {...props} preview={preview} />);

        // « Mamie » est le mot par lequel elle est appelée depuis quarante
        // ans ; c'est lui qui sera dans le message.
        const cta = screen.getByRole('link', {
            name: /public.quiz.preview.cta/,
        });

        expect(cta).toHaveAttribute('href', '/acheter?step=2');
    });

    it('se rabat sur le prénom quand aucun petit nom n’a été donné', () => {
        render(<Quiz {...props} preview={{ ...preview, nickname: null }} />);

        expect(
            screen.getByRole('link', { name: /public.quiz.preview.cta/ }),
        ).toBeInTheDocument();
    });

    it('dit que le corpus manque plutôt que d’inventer une question', () => {
        render(
            <Quiz {...props} preview={{ ...preview, first: null, next: [] }} />,
        );

        expect(
            screen.getByText('public.quiz.preview.empty'),
        ).toBeInTheDocument();
    });

    it('n’offre pas l’adresse quand l’offre de bienvenue est fermée', () => {
        render(<Quiz {...props} preview={preview} welcomeOffer={false} />);

        expect(
            screen.queryByText('public.quiz.preview.email.title'),
        ).not.toBeInTheDocument();
    });
});

describe('la couverture du livre', () => {
    /** Mène jusqu'à l'écran du livre, le prénom saisi. */
    async function untilCover(user: ReturnType<typeof userEvent.setup>) {
        await choose(user, 'Ma mère');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
        await choose(user, '70 à 79 ans');
        await choose(user, 'Même ville');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
        await user.click(screen.getByLabelText('Son enfance'));
        await user.click(screen.getByLabelText('Son métier'));
        await user.click(screen.getByLabelText('Ce qui doit rester'));
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
        await choose(user, 'Intarissable');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
        await choose(user, 'Très à l’aise');
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
        await choose(user, 'SMS');
        await user.type(
            screen.getByLabelText('public.quiz.name.first_name'),
            'Jeanne',
        );
        await user.click(
            screen.getByRole('button', { name: 'public.quiz.next' }),
        );
    }

    it('porte le prénom saisi deux écrans plus tôt', async () => {
        const user = setup();
        render(<Quiz {...props} />);

        await untilCover(user);

        // Le contenu de la vraie couverture : le prénom en titre, et le
        // sous-titre composé par le serveur depuis le catalogue du livre
        // imprimé — mot pour mot celui qui partira à l'impression.
        expect(screen.getByText('Jeanne')).toBeInTheDocument();
        expect(
            screen.getByText('Récits recueillis en 2026'),
        ).toBeInTheDocument();
    });

    it('change de teinte au tap, et dit laquelle', async () => {
        const user = setup();
        render(<Quiz {...props} />);

        await untilCover(user);

        // La première de la palette est posée d'avance : l'écran montre un
        // livre avant qu'on ait touché à quoi que ce soit.
        expect(screen.getByText('Ivoire')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Vert forêt' }));

        expect(screen.getByText('Vert forêt')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Vert forêt' }),
        ).toHaveAttribute('aria-pressed', 'true');
    });

    it('ouvre le plan sur le livre, dans la teinte choisie', () => {
        render(
            <Quiz
                {...props}
                preview={{
                    firstName: 'Jeanne',
                    nickname: 'Mamie',
                    sender: 'EXPEDITEUR',
                    channel: 'sms',
                    sendAt: '2026-12-01',
                    sendTime: '09:00',
                    cover: 'forest',
                    title: 'first_name',
                    titleCustom: '',
                    first: 'Quel est votre tout premier souvenir ?',
                    next: [],
                    themes: ['childhood'],
                }}
            />,
        );

        // Le prénom sur la couverture, pas le petit nom : c'est le livre de
        // Jeanne, même si tout le monde l'appelle Mamie.
        expect(screen.getByText('Jeanne')).toBeInTheDocument();
        expect(
            screen.getByText('public.quiz.preview.book_label'),
        ).toBeInTheDocument();
    });
});
