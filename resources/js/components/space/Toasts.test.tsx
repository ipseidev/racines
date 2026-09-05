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

        // La région de statut disparaît avec son dernier message : deux
        // régions de statut sur un même écran est une ambiguïté, et certaines
        // pages en ont déjà une à elles.
        expect(screen.queryByRole('status')).toBeNull();
        expect(
            screen.queryByText('L’ordre est enregistré.'),
        ).not.toBeInTheDocument();
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
