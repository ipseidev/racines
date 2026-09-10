import { act, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import {
    OVERTURE_ENTER_MS,
    OVERTURE_EXIT_MS,
    OVERTURE_LEAVE_MS,
    holdFor,
} from '@/lib/overture';

import GiftOverture from './GiftOverture';

const beats = {
    title: 'Marque',
    lines: ['Bonjour Odette,', 'Camille vous a offert quelque chose.'],
};

const advance = (ms: number): void => {
    act(() => {
        vi.advanceTimersByTime(ms);
    });
};

/**
 * Un temps entier de l'ouverture : la phrase monte et reste, puis s'efface.
 *
 * En deux pas et non un : le minuteur de la sortie n'est posé qu'une fois
 * l'entrée finie et le nouvel état rendu, et React ne rend qu'à la fin d'un
 * `act`. Avancer d'un seul coup laisserait la sortie en attente.
 */
const wholeBeat = (text: string): void => {
    advance(OVERTURE_ENTER_MS + holdFor(text));
    advance(OVERTURE_EXIT_MS);
};

beforeEach(() => {
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    document.documentElement.classList.remove('overture-open');
});

describe('l’ouverture du cadeau', () => {
    it('commence par le nom de marque, seul', () => {
        render(<GiftOverture {...beats} />);

        expect(screen.getByText('Marque')).toBeTruthy();
        expect(screen.queryByText('Bonjour Odette,')).toBeNull();
    });

    it('fait défiler les phrases une à une, puis s’efface', () => {
        const onDone = vi.fn();

        render(<GiftOverture {...beats} onDone={onDone} />);

        wholeBeat('Marque');
        expect(screen.getByText('Bonjour Odette,')).toBeTruthy();
        expect(screen.queryByText('Marque')).toBeNull();

        wholeBeat('Bonjour Odette,');
        expect(
            screen.getByText('Camille vous a offert quelque chose.'),
        ).toBeTruthy();
        expect(onDone).not.toHaveBeenCalled();

        // La dernière phrase effacée, le rideau tombe : la page dessous est
        // prévenue à cet instant, pour monter pendant qu'il s'efface.
        wholeBeat('Camille vous a offert quelque chose.');
        expect(onDone).toHaveBeenCalledTimes(1);
        expect(document.querySelector('[data-overture]')).not.toBeNull();

        advance(OVERTURE_LEAVE_MS);
        expect(document.querySelector('[data-overture]')).toBeNull();
        expect(onDone).toHaveBeenCalledTimes(1);
    });

    it('reste invisible aux lecteurs d’écran, qui ont la page entière dessous', () => {
        render(<GiftOverture {...beats} />);

        const overture = document.querySelector('[data-overture]');

        expect(overture?.getAttribute('aria-hidden')).toBe('true');
        // Rien à atteindre au clavier dans un décor : un élément focalisable
        // sous `aria-hidden` est une violation sérieuse, et un piège.
        expect(overture?.querySelector('button, a, [tabindex]')).toBeNull();
    });

    it('passe à la phrase suivante quand on touche l’écran', () => {
        render(<GiftOverture {...beats} />);

        const overture = document.querySelector('[data-overture]');

        if (overture === null) {
            throw new Error('ouverture absente');
        }

        // Le nom vient d'apparaître ; un tap l'écourte sans sauter le reste.
        advance(OVERTURE_ENTER_MS);
        fireEvent.pointerDown(overture);
        advance(OVERTURE_EXIT_MS);

        expect(screen.getByText('Bonjour Odette,')).toBeTruthy();
    });

    it('s’efface d’un coup dès que le clavier cherche la page', () => {
        const onDone = vi.fn();

        render(
            <>
                <GiftOverture {...beats} onDone={onDone} />
                <button type="button">J’accepte</button>
            </>,
        );

        // Un Tab pendant l'ouverture veut dire « je veux la page » : le focus
        // ne doit pas se poser sur un élément caché derrière un rideau.
        act(() => {
            screen.getByRole('button', { name: 'J’accepte' }).focus();
        });

        expect(onDone).toHaveBeenCalledTimes(1);

        advance(OVERTURE_LEAVE_MS);

        expect(document.querySelector('[data-overture]')).toBeNull();
        expect(onDone).toHaveBeenCalledTimes(1);
    });

    it('présente la page par le haut quand le rideau se lève, puis quand il tombe', () => {
        const scrollTo = vi
            .spyOn(window, 'scrollTo')
            .mockImplementation(() => {});

        render(<GiftOverture {...beats} />);

        // Inertia remet la page où elle était à un rechargement ; derrière le
        // rideau, personne ne le voit, et la page apparaissait au milieu.
        expect(scrollTo).toHaveBeenCalledWith(
            expect.objectContaining({ top: 0 }),
        );

        scrollTo.mockClear();
        wholeBeat('Marque');
        wholeBeat('Bonjour Odette,');
        wholeBeat('Camille vous a offert quelque chose.');

        expect(scrollTo).toHaveBeenCalledWith(
            expect.objectContaining({ top: 0 }),
        );
    });

    it('bloque le défilement de la page tant qu’elle joue', () => {
        const { unmount } = render(<GiftOverture {...beats} />);

        expect(
            document.documentElement.classList.contains('overture-open'),
        ).toBe(true);

        unmount();

        expect(
            document.documentElement.classList.contains('overture-open'),
        ).toBe(false);
    });

    it('ne joue pas pour qui a demandé qu’on ne bouge pas', () => {
        vi.stubGlobal('matchMedia', vi.fn().mockReturnValue({ matches: true }));
        const onDone = vi.fn();

        render(<GiftOverture {...beats} onDone={onDone} />);

        expect(document.querySelector('[data-overture]')).toBeNull();
        expect(onDone).toHaveBeenCalledTimes(1);
    });
});
