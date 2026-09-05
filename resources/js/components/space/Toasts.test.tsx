import { act, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { toast, Toasts } from './Toasts';

describe('Toasts', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('montre un message puis le retire', () => {
        render(<Toasts duration={1000} />);

        act(() => toast('L’ordre est enregistré.'));

        expect(screen.getByRole('status')).toHaveTextContent(
            'L’ordre est enregistré.',
        );

        act(() => {
            vi.advanceTimersByTime(1100);
        });

        expect(screen.getByRole('status')).toHaveTextContent('');
    });

    it('remplace un message identique au lieu de l’empiler', () => {
        render(<Toasts duration={1000} />);

        act(() => toast('C’est enregistré.'));
        act(() => toast('C’est enregistré.'));
        act(() => toast('Autre chose.'));

        expect(screen.getAllByText('C’est enregistré.')).toHaveLength(1);
        expect(screen.getByText('Autre chose.')).toBeVisible();
    });
});
