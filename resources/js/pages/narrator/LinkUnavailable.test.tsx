import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import LinkUnavailable, { type LinkUnavailableReason } from './LinkUnavailable';

const post = vi.fn();

const catalogue = {
    common: {},
    narrator: {
        link_unavailable: {
            not_found: {
                title: 'Ce lien ne fonctionne pas',
                body: 'Le lien est peut-être incomplet.',
            },
            expired: {
                title: 'Ce lien a expiré',
                body: 'Vous pouvez en demander un nouveau.',
            },
            revoked: {
                title: 'Ce lien n’est plus valable',
                body: 'Il a été remplacé.',
            },
            used: {
                title: 'Ce lien a déjà servi',
                body: 'Il ne fonctionnait qu’une fois.',
            },
            type_mismatch: {
                title: 'Ce lien ne mène pas ici',
                body: 'Il correspond à une autre page.',
            },
            request_new_link: 'Demander un nouveau lien',
            request_sent: 'C’est noté.',
            help: 'Besoin d’aide ? Écrivez-nous à :email.',
        },
    },
};

let flash: { status?: string } = {};

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    usePage: () => ({
        props: {
            i18n: catalogue,
            flash,
            brand: { name: 'Produit', support_email: 'aide@example.test' },
        },
    }),
    useForm: () => ({ post, processing: false }),
}));

describe('LinkUnavailable (narrateur)', () => {
    it('affiche le message correspondant à chaque raison', () => {
        const reasons: LinkUnavailableReason[] = [
            'not_found',
            'expired',
            'revoked',
            'used',
            'type_mismatch',
        ];

        for (const reason of reasons) {
            const { unmount } = render(
                <LinkUnavailable reason={reason} canRequestNewLink={false} />,
            );

            expect(
                screen.getByRole('heading', {
                    name: catalogue.narrator.link_unavailable[reason].title,
                }),
            ).toBeTruthy();

            expect(
                screen.getByText(
                    catalogue.narrator.link_unavailable[reason].body,
                ),
            ).toBeTruthy();

            unmount();
        }
    });

    it('propose de demander un nouveau lien quand c’est possible', () => {
        render(<LinkUnavailable reason="expired" canRequestNewLink={true} />);

        const button = screen.getByRole('button', {
            name: 'Demander un nouveau lien',
        });

        expect(button).toBeTruthy();
    });

    it('ne propose rien quand le lien n’est pas remplaçable', () => {
        render(
            <LinkUnavailable reason="not_found" canRequestNewLink={false} />,
        );

        expect(screen.queryByRole('button')).toBeNull();
    });

    it('affiche la confirmation et retire le bouton après la demande', () => {
        flash = { status: 'C’est noté.' };

        render(<LinkUnavailable reason="expired" canRequestNewLink={true} />);

        expect(screen.getByRole('status').textContent).toBe('C’est noté.');
        expect(screen.queryByRole('button')).toBeNull();

        flash = {};
    });

    it('donne toujours une adresse de secours', () => {
        render(<LinkUnavailable reason="expired" canRequestNewLink={true} />);

        expect(
            screen.getByText(
                'Besoin d’aide ? Écrivez-nous à aide@example.test.',
            ),
        ).toBeTruthy();
    });
});
