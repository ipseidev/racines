import { act, render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { beforeAll, describe, expect, it, vi } from 'vitest';

import HeroSample from './HeroSample';

const catalogue = {
    common: {
        player: { play: 'Écouter', pause: 'Mettre en pause' },
    },
    public: {
        landing: {
            hero: {
                card: {
                    answers: 'Elle répond en parlant.',
                    duration: '2 min 14',
                    synthetic:
                        'Exemple : voix de synthèse. Les vraies histoires sont dites par de vraies voix.',
                    transcript_label: 'Ce qu’Odette raconte dans cet extrait',
                    transcript: 'Oh… l’odeur du pain.',
                },
            },
        },
    },
};

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { i18n: catalogue } }),
}));

/*
 * jsdom ne lit aucun son : `play()` et `pause()` n'y existent pas. On les
 * remplace par ce que le composant attend d'eux — l'état `paused` et les
 * événements — parce que c'est sur les événements, et non sur le clic, qu'il
 * décide de ce qu'affiche le bouton.
 */
beforeAll(() => {
    Object.defineProperty(HTMLMediaElement.prototype, 'paused', {
        configurable: true,
        get(this: HTMLMediaElement) {
            return this.dataset.playing !== 'true';
        },
    });

    HTMLMediaElement.prototype.play = function play(this: HTMLMediaElement) {
        this.dataset.playing = 'true';
        this.dispatchEvent(new Event('play'));

        return Promise.resolve();
    };

    HTMLMediaElement.prototype.pause = function pause(this: HTMLMediaElement) {
        this.dataset.playing = 'false';
        this.dispatchEvent(new Event('pause'));
    };
});

describe('HeroSample', () => {
    it('reprend la frise décorative quand la page est servie sans extrait', () => {
        const { container } = render(<HeroSample sample={null} />);

        expect(screen.getByText('Elle répond en parlant.')).toBeInTheDocument();
        expect(screen.getByText(/2 min 14/)).toBeInTheDocument();
        // Pas de bouton au-dessus d'un fichier absent, et rien à écouter.
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(container.querySelector('audio')).toBeNull();
    });

    it('propose d’écouter l’extrait, et ne le charge pas avant qu’on le demande', () => {
        const { container } = render(
            <HeroSample
                sample={{ src: '/audio/landing/hero.mp3', disclosed: true }}
            />,
        );

        const audio = container.querySelector('audio');

        expect(audio).toHaveAttribute('src', '/audio/landing/hero.mp3');
        // Les entêtes suffisent : le fichier ne part qu'au clic.
        expect(audio).toHaveAttribute('preload', 'metadata');
        expect(
            screen.getByRole('button', { name: 'Écouter' }),
        ).toBeInTheDocument();
    });

    it('affiche la mention quand la page la demande', () => {
        render(
            <HeroSample
                sample={{ src: '/audio/landing/hero.mp3', disclosed: true }}
            />,
        );

        expect(screen.getByText(/voix de synthèse/)).toBeInTheDocument();
    });

    it('se tait quand la page ne la demande pas', () => {
        render(
            <HeroSample
                sample={{ src: '/audio/landing/hero.mp3', disclosed: false }}
            />,
        );

        expect(screen.queryByText(/voix de synthèse/)).not.toBeInTheDocument();
    });

    it('porte la transcription, l’équivalent textuel exigé par WCAG 1.2.1', () => {
        render(
            <HeroSample
                sample={{ src: '/audio/landing/hero.mp3', disclosed: true }}
            />,
        );

        expect(
            screen.getByText('Ce qu’Odette raconte dans cet extrait'),
        ).toBeInTheDocument();
        expect(screen.getByText('Oh… l’odeur du pain.')).toBeInTheDocument();
    });

    it('bascule entre écouter et mettre en pause', async () => {
        const user = userEvent.setup();

        render(
            <HeroSample
                sample={{ src: '/audio/landing/hero.mp3', disclosed: true }}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Écouter' }));

        const pause = screen.getByRole('button', { name: 'Mettre en pause' });

        expect(pause).toHaveAttribute('aria-pressed', 'true');

        await user.click(pause);

        expect(screen.getByRole('button', { name: 'Écouter' })).toHaveAttribute(
            'aria-pressed',
            'false',
        );
    });

    it('rend son bouton quand l’extrait se termine tout seul', async () => {
        const user = userEvent.setup();

        const { container } = render(
            <HeroSample
                sample={{ src: '/audio/landing/hero.mp3', disclosed: true }}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Écouter' }));

        const audio = container.querySelector('audio') as HTMLAudioElement;

        act(() => {
            audio.dispatchEvent(new Event('ended'));
        });

        expect(
            screen.getByRole('button', { name: 'Écouter' }),
        ).toBeInTheDocument();
    });
});
