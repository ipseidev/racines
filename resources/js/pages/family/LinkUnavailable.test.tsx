import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import LinkUnavailable from './LinkUnavailable';

const catalogue = {
    common: {},
    family: {
        link_unavailable: {
            expired: {
                title: 'Ce lien a expiré',
                body: 'Demandez un nouveau lien à la personne qui vous a invité·e.',
            },
            revoked: {
                title: 'Ce lien n’est plus valable',
                body: 'La famille a retiré cet accès.',
            },
            help: 'Besoin d’aide ? Écrivez-nous à :email.',
        },
    },
};

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    usePage: () => ({
        props: {
            i18n: catalogue,
            brand: { name: 'Produit', support_email: 'aide@example.test' },
        },
    }),
}));

describe('LinkUnavailable (proches)', () => {
    it('renvoie vers la personne qui a invité, sans bouton', () => {
        render(<LinkUnavailable reason="expired" />);

        expect(
            screen.getByRole('heading', { name: 'Ce lien a expiré' }),
        ).toBeTruthy();

        expect(
            screen.getByText(
                'Demandez un nouveau lien à la personne qui vous a invité·e.',
            ),
        ).toBeTruthy();

        // Un proche ne redemande jamais un accès au produit lui-même.
        expect(screen.queryByRole('button')).toBeNull();
    });

    it('explique un accès retiré par la famille', () => {
        render(<LinkUnavailable reason="revoked" />);

        expect(screen.getByText('La famille a retiré cet accès.')).toBeTruthy();
    });
});
