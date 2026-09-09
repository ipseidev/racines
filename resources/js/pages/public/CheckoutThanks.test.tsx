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
    Link: ({ children }: { children: React.ReactNode }) => <a>{children}</a>,
    usePage: () => ({ props: { i18n: {}, brand: { name: 'P' } } }),
}));

const props = {
    sessionId: 'cs_test_123',
    forSelf: false,
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
});
