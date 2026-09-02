import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ShareDecision from './ShareDecision';

const catalogue = {
    common: {},
    narrator: {
        share_decision: {
            title: 'Que souhaitez-vous faire de cette histoire ?',
            body: 'C’est votre récit. Vous décidez.',
            share: {
                label: 'Partager avec mes proches',
                hint: 'Vos proches pourront l’écouter et lire le texte.',
            },
            keep_private: {
                label: 'Garder pour moi',
                hint: 'Personne d’autre que vous ne l’entendra.',
            },
            decide_later: {
                label: 'Décider plus tard',
                hint: 'Nous vous le redemanderons, sans insister.',
            },
        },
    },
};

const post = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: {
        post: (...args: unknown[]) => post(...args),
    },
    usePage: () => ({
        props: {
            i18n: catalogue,
            brand: { name: 'P', support_email: 'aide@example.test' },
        },
    }),
}));

beforeEach(() => {
    post.mockReset();
});

describe('les trois choix de fin d’enregistrement', () => {
    it('présente les trois choix, dans l’ordre du dossier', () => {
        render(<ShareDecision action="/r/jeton/share-decision" />);

        const labels = screen
            .getAllByRole('button')
            .map((button) => button.textContent ?? '');

        expect(labels).toHaveLength(3);
        expect(labels[0]).toContain('Partager avec mes proches');
        expect(labels[1]).toContain('Garder pour moi');
        expect(labels[2]).toContain('Décider plus tard');
    });

    it('dit sous chaque choix ce qu’il entraîne', () => {
        render(<ShareDecision action="/r/jeton/share-decision" />);

        expect(
            screen.getByText(
                'Vos proches pourront l’écouter et lire le texte.',
            ),
        ).toBeTruthy();
        expect(
            screen.getByText('Personne d’autre que vous ne l’entendra.'),
        ).toBeTruthy();
        expect(
            screen.getByText('Nous vous le redemanderons, sans insister.'),
        ).toBeTruthy();
    });

    it('ne présélectionne aucun choix', () => {
        render(<ShareDecision action="/r/jeton/share-decision" />);

        for (const button of screen.getAllByRole('button')) {
            // Ni `aria-pressed`, ni `aria-selected`, ni `disabled` : rien qui
            // désigne un choix par défaut. L'absence de réaction ne vaut
            // jamais accord (doc 04 §1).
            expect(button.getAttribute('aria-pressed')).toBeNull();
            expect(button.getAttribute('aria-selected')).toBeNull();
            expect(button.hasAttribute('disabled')).toBe(false);
        }
    });

    it('n’affiche aucun compte à rebours', () => {
        const { container } = render(
            <ShareDecision action="/r/jeton/share-decision" />,
        );

        // Un minuteur transformerait l'hésitation en consentement.
        expect(container.querySelector('time')).toBeNull();
        expect(container.querySelector('progress')).toBeNull();
        expect(container.textContent).not.toMatch(/seconde|minute|reste/i);
    });

    it('donne à chaque bouton au moins 44 px de haut', () => {
        render(<ShareDecision action="/r/jeton/share-decision" />);

        for (const button of screen.getAllByRole('button')) {
            // 2,75 rem = 44 px : la cible minimale pour un doigt de personne
            // âgée (convention §4).
            expect(button.className).toContain('min-h-[2.75rem]');
        }
    });

    it('poste la décision choisie', async () => {
        const user = userEvent.setup();
        render(<ShareDecision action="/r/jeton/share-decision" />);

        await user.click(screen.getByText('Garder pour moi'));

        expect(post).toHaveBeenCalledWith(
            '/r/jeton/share-decision',
            { decision: 'keep_private' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
