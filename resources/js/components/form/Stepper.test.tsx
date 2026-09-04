import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { Stepper } from './Stepper';

vi.mock('@inertiajs/react', () => ({
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
}));

const steps = [
    { label: 'Pour qui', href: '/acheter?step=1' },
    { label: 'Le narrateur', href: '/acheter?step=2' },
    { label: 'Le cadeau', href: '/acheter?step=3' },
    { label: 'Récapitulatif', href: '/acheter?step=4' },
];

describe('Stepper', () => {
    it('marque l’étape en cours et n’offre de lien que vers les étapes franchies', () => {
        render(
            <Stepper
                steps={steps}
                current={3}
                ariaLabel="Progression"
                ofLabel="Étape 3 sur 4"
            />,
        );

        const items = screen.getAllByRole('listitem');
        expect(items[2]).toHaveAttribute('aria-current', 'step');

        // Franchies : on peut y revenir corriger.
        expect(screen.getByRole('link', { name: /Pour qui/ })).toHaveAttribute(
            'href',
            '/acheter?step=1',
        );
        expect(
            screen.getByRole('link', { name: /Le narrateur/ }),
        ).toBeVisible();

        // En cours et à venir : jamais un lien, on ne saute pas en avant.
        expect(
            screen.queryByRole('link', { name: /Le cadeau/ }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: /Récapitulatif/ }),
        ).not.toBeInTheDocument();
    });

    it('avance la barre au prorata des étapes', () => {
        render(
            <Stepper
                steps={steps}
                current={2}
                ariaLabel="Progression"
                ofLabel="Étape 2 sur 4"
            />,
        );

        expect(screen.getByTestId('stepper-progress')).toHaveStyle({
            width: '50%',
        });
        expect(screen.getByText('Étape 2 sur 4')).toBeInTheDocument();
    });
});
