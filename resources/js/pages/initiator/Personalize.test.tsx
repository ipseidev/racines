import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Personalize from './Personalize';

const router = vi.hoisted(() => ({ post: vi.fn(), visit: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        children,
        href,
        className,
    }: {
        children: React.ReactNode;
        href?: string;
        className?: string;
    }) => (
        <a href={href} className={className}>
            {children}
        </a>
    ),
    router,
    usePage: () => ({
        props: { errors: {}, i18n: {}, space: { base: '/espace/projets/p1' } },
    }),
}));

const theme = (value: string) => ({ value, label: `theme:${value}` });

const props = {
    narratorFirstName: 'Jeanne',
    step: null,
    skipped: false,
    profile: null,
    themes: [
        'childhood',
        'family_origins',
        'youth',
        'work',
        'love',
        'places',
        'joys',
        'hardships',
        'beliefs_values',
        'legacy',
    ].map(theme),
    facts: [
        'partner',
        'children',
        'grandchildren',
        'career',
        'siblings',
        'migration',
        'rural',
    ],
    topics: [
        { value: 'war', label: 'La guerre' },
        { value: 'illness', label: 'La maladie' },
    ],
    deck: [
        {
            text: 'Quelle chanson connais-tu encore par cœur ?',
            theme: 'joys',
            themeLabel: 'Joies',
        },
    ],
    previews: {
        child: {
            feminine: {
                feminine: 'Raconte le jour où {{prénom}} est née.',
                masculine: 'Raconte le jour où {{prénom}} est né.',
                unknown: 'Raconte le jour où {{prénom}} est né·e.',
            },
            masculine: {
                feminine: 'Raconte le jour où {{prénom}} est née.',
                masculine: 'Raconte le jour où {{prénom}} est né.',
                unknown: 'Raconte le jour où {{prénom}} est né·e.',
            },
        },
    },
    firstQuestions: [],
    dashboardUrl: '/espace/projets/p1',
};

const key = (k: string) => `initiator.personalize.${k}`;

beforeEach(() => {
    router.post.mockClear();
    router.visit.mockClear();
});

describe('le tunnel de personnalisation', () => {
    it('va de l’accueil aux thèmes et envoie exactement ce qu’on a répondu', async () => {
        const user = userEvent.setup();
        render(<Personalize {...props} />);

        await user.click(
            screen.getByRole('button', { name: key('welcome.start') }),
        );

        // Le lien d'abord : rien ne continue sans lui.
        const next = () =>
            screen.getByRole('button', { name: key('continue') });
        expect(next()).toBeDisabled();
        await user.click(
            screen.getByRole('button', { name: key('relation.mother') }),
        );
        await user.click(next());

        // « Sur moi » demande un prénom et un genre, et montre la question.
        await user.click(
            screen.getByRole('button', { name: key('about.buyer') }),
        );
        expect(next()).toBeDisabled();
        await user.type(
            screen.getByLabelText(key('about.name_label_feminine')),
            'Claire',
        );
        await user.click(
            screen.getByRole('button', { name: key('about.child_feminine') }),
        );
        expect(screen.getByText('Claire')).toBeInTheDocument();
        await user.click(next());

        // Sa vie : un oui, un non ; le reste reste « on ne sait pas ».
        const yes = screen.getAllByRole('button', { name: key('life.yes') });
        const no = screen.getAllByRole('button', { name: key('life.no') });
        await user.click(yes[0]);
        await user.click(no[1]);
        await user.click(next());

        await user.click(screen.getByRole('button', { name: 'La guerre' }));
        await user.click(next());

        await user.click(screen.getByRole('button', { name: /theme:joys/ }));
        await user.click(screen.getByRole('button', { name: /theme:places/ }));
        await user.click(
            screen.getByRole('button', { name: key('themes.cta') }),
        );

        expect(router.post).toHaveBeenCalledWith(
            '/espace/projets/p1/personnaliser',
            {
                relation: 'child',
                narrator_gender: 'feminine',
                buyer_focus: 'buyer',
                buyer_first_name: 'Claire',
                buyer_gender: 'feminine',
                facts: { partner: true, children: false },
                avoided_topics: ['war'],
                favored_themes: ['joys', 'places'],
            },
            expect.anything(),
        );
    });

    it('demande le genre quand le lien ne le dit pas, et saute « Et vous ? » pour un conjoint', async () => {
        const user = userEvent.setup();
        render(<Personalize {...props} />);

        await user.click(
            screen.getByRole('button', { name: key('welcome.start') }),
        );
        await user.click(
            screen.getByRole('button', { name: key('relation.partner') }),
        );

        const next = screen.getByRole('button', { name: key('continue') });
        expect(next).toBeDisabled();
        await user.click(
            screen.getByRole('button', { name: key('relation.masculine') }),
        );
        await user.click(next);

        expect(screen.getByText(key('life.title'))).toBeInTheDocument();
    });

    it('ne laisse pas choisir plus de trois thèmes', async () => {
        const user = userEvent.setup();
        render(<Personalize {...props} />);

        await user.click(
            screen.getByRole('button', { name: key('welcome.start') }),
        );
        await user.click(
            screen.getByRole('button', { name: key('relation.grandmother') }),
        );
        await user.click(screen.getByRole('button', { name: key('continue') }));
        await user.click(screen.getByRole('button', { name: key('skip') }));
        await user.click(screen.getByRole('button', { name: key('skip') }));
        await user.click(screen.getByRole('button', { name: key('skip') }));

        for (const value of ['joys', 'places', 'work', 'love']) {
            await user.click(
                screen.getByRole('button', {
                    name: new RegExp(`theme:${value}`),
                }),
            );
        }

        expect(
            screen.getByRole('button', { name: /theme:love/ }),
        ).toHaveAttribute('aria-pressed', 'false');
        expect(
            screen.getByRole('button', { name: /theme:work/ }),
        ).toHaveAttribute('aria-pressed', 'true');
    });

    it('fait partir la question choisie parmi les trois premières', async () => {
        const user = userEvent.setup();
        const firstQuestions = [
            {
                id: 'q1',
                text: 'Sais-tu où et comment tu es née ?',
                theme: 'childhood',
                themeLabel: 'Enfance',
            },
            {
                id: 'q2',
                text: 'Quel est ton tout premier souvenir ?',
                theme: 'childhood',
                themeLabel: 'Enfance',
            },
            {
                id: 'q3',
                text: 'Quelle chanson connais-tu par cœur ?',
                theme: 'joys',
                themeLabel: 'Joies',
            },
        ];
        render(
            <Personalize
                {...props}
                step="premieres"
                firstQuestions={firstQuestions}
            />,
        );

        expect(
            screen.getByRole('button', { name: /Sais-tu où/ }),
        ).toHaveAttribute('aria-pressed', 'true');

        await user.click(
            screen.getByRole('button', { name: /premier souvenir/ }),
        );
        await user.click(
            screen.getByRole('button', { name: key('first.cta') }),
        );

        expect(router.post).toHaveBeenCalledWith(
            '/espace/projets/p1/personnaliser/premiere',
            { question_id: 'q2' },
            expect.anything(),
        );
    });

    it('se passe en entier depuis l’accueil', async () => {
        const user = userEvent.setup();
        render(<Personalize {...props} />);

        await user.click(
            screen.getByRole('button', { name: key('welcome.skip_all') }),
        );

        expect(router.post).toHaveBeenCalledWith(
            '/espace/projets/p1/personnaliser/passer',
            {},
            expect.anything(),
        );
    });

    it('dit, à la fin, si le tunnel a été passé', () => {
        render(<Personalize {...props} step="fin" skipped />);

        expect(screen.getByText(key('done.skipped_title'))).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: key('done.cta') }),
        ).toHaveAttribute('href', '/espace/projets/p1');
    });
});
