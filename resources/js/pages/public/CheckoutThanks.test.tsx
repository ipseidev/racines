import { act, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import CheckoutThanks from './CheckoutThanks';

const celebrateOnce = vi.hoisted(() => vi.fn());

vi.mock('@/lib/celebrate', () => ({ celebrateOnce }));

vi.mock('./Checkout', () => ({
    formatDate: (value: string) => value,
    formatTime: (value: string) => value,
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        children,
        href,
    }: {
        children: React.ReactNode;
        href?: string;
    }) => <a href={href}>{children}</a>,
    usePage: () => ({ props: { i18n: {}, brand: { name: 'P' } } }),
}));

const props = {
    sessionId: 'cs_test_123',
    forSelf: false,
    giftNow: false,
    narratorFirstName: 'Odette',
    giftSendAt: '2026-10-01',
    giftSendTime: '09:00',
};

beforeEach(() => {
    vi.useFakeTimers();
    celebrateOnce.mockClear();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('la page de merci', () => {
    it('lance les confettis quand la couverture bascule, une fois par commande', () => {
        render(<CheckoutThanks {...props} />);

        // Pas avant : la couverture n'a pas encore pivoté.
        act(() => {
            vi.advanceTimersByTime(1000);
        });
        expect(celebrateOnce).not.toHaveBeenCalled();

        act(() => {
            vi.advanceTimersByTime(600);
        });
        expect(celebrateOnce).toHaveBeenCalledTimes(1);
        expect(celebrateOnce).toHaveBeenCalledWith('cs_test_123', 'generous');
    });

    it('n’a rien à tirer si la page est quittée avant', () => {
        const { unmount } = render(<CheckoutThanks {...props} />);

        unmount();

        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(celebrateOnce).not.toHaveBeenCalled();
    });
    /*
     * Deux gestes, et des adresses **sans identifiant de projet**.
     *
     * Stripe ramène le navigateur ici avant que le webhook n'arrive : une
     * adresse `/espace/projets/{id}/questions` répondrait 404 dans les
     * secondes qui suivent le paiement. `/espace/questions` et
     * `/espace/proches` résolvent le projet du compte, et servent une page qui
     * explique quand il n'existe pas encore (T-199).
     */
    it('invite à choisir ses questions et à inviter ses proches', () => {
        const { container } = render(<CheckoutThanks {...props} />);

        const liens = Array.from(container.querySelectorAll('a')).map((a) =>
            a.getAttribute('href'),
        );

        expect(liens).toContain('/espace/questions');
        expect(liens).toContain('/espace/proches');
        expect(liens.some((href) => href?.includes('/projets/'))).toBe(false);
    });
});
