import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import VerifyEmail from './verify-email';

const catalogue = {
    common: {},
    auth: {
        pages: { verify_email: { title: 'Vérifiez votre courriel' } },
        actions: {
            resend: 'Renvoyer le courriel',
            waiting: 'Un instant…',
            logout: 'Se déconnecter',
        },
        verify: {
            sent: 'Un nouveau lien vient de partir vers votre adresse.',
            expired:
                'Ce lien avait expiré. Un nouveau vient de partir vers votre adresse.',
            mismatch:
                'Ce lien confirme une autre adresse que celle du compte ouvert ici.',
        },
    },
};

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Form: ({
        children,
    }: {
        children: (state: { processing: boolean }) => React.ReactNode;
    }) => <form>{children({ processing: false })}</form>,
    usePage: () => ({ props: { i18n: catalogue, brand: { name: 'P' } } }),
}));

vi.mock('@/components/text-link', () => ({
    default: ({ children }: { children: React.ReactNode }) => <a>{children}</a>,
}));

describe('la page de confirmation d’adresse', () => {
    it('ne dit rien quand on y arrive de soi-même', () => {
        render(<VerifyEmail />);

        expect(screen.queryByRole('status')).toBeNull();
    });

    it('dit qu’un lien vient de partir après une demande', () => {
        render(<VerifyEmail status="verification-link-sent" />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Un nouveau lien vient de partir',
        );
    });

    /*
     * Les deux motifs de T-240 : la personne n'a rien demandé, elle a cliqué
     * un lien qui ne marchait plus. La page doit expliquer lequel des deux,
     * parce que le remède diffère — attendre le nouveau courriel, ou changer
     * de compte.
     */
    it('explique un lien périmé, et dit que le remplaçant est parti', () => {
        render(<VerifyEmail status="verification-link-expired" />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Ce lien avait expiré',
        );
    });

    it('explique un lien qui vise un autre compte', () => {
        render(<VerifyEmail status="verification-link-mismatch" />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'une autre adresse que celle du compte ouvert ici',
        );
    });

    it('ignore un état qu’elle ne connaît pas', () => {
        render(<VerifyEmail status="quelque-chose-dautre" />);

        expect(screen.queryByRole('status')).toBeNull();
    });
});
