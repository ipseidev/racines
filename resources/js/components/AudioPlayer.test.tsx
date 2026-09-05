import { act, render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import AudioPlayer from './AudioPlayer';

const catalogue = {
    common: {
        player: {
            play: 'Écouter',
            pause: 'Mettre en pause',
            back15: 'Reculer de 15 secondes',
            forward15: 'Avancer de 15 secondes',
            slower: 'Ralentir un peu',
            normal: 'Vitesse normale',
            remaining: 'Il reste :time',
            elapsed: ':time',
            progress: 'Progression de l’écoute',
        },
    },
};

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            i18n: catalogue,
            brand: { name: 'P', support_email: 'aide@example.test' },
        },
    }),
}));

/**
 * jsdom n'implémente ni `play()` ni `pause()` : on les remplace, et on pilote
 * `currentTime` à la main pour simuler une lecture.
 */
function stubAudio(): void {
    Object.defineProperty(HTMLMediaElement.prototype, 'play', {
        configurable: true,
        value: vi.fn().mockImplementation(function (this: HTMLMediaElement) {
            Object.defineProperty(this, 'paused', {
                configurable: true,
                value: false,
            });

            return Promise.resolve();
        }),
    });

    Object.defineProperty(HTMLMediaElement.prototype, 'pause', {
        configurable: true,
        value: vi.fn().mockImplementation(function (this: HTMLMediaElement) {
            Object.defineProperty(this, 'paused', {
                configurable: true,
                value: true,
            });
        }),
    });
}

beforeEach(() => {
    stubAudio();
});

describe('lecteur audio de l’espace famille', () => {
    it('donne à chaque commande une cible touchable', () => {
        render(<AudioPlayer src="/audio.mp3" />);

        for (const button of screen.getAllByRole('button')) {
            // 2,75 rem = 44 px. Le bouton principal est plus grand encore :
            // c'est celui qu'on cherche du doigt en premier.
            expect(button.className).toMatch(/min-h-\[(2\.75|3\.5)rem\]/);
        }
    });

    it('nomme chaque commande en mots, pas en pictogrammes', () => {
        render(<AudioPlayer src="/audio.mp3" />);

        expect(screen.getByRole('button', { name: 'Écouter' })).toBeTruthy();
        expect(
            screen.getByRole('button', { name: 'Reculer de 15 secondes' }),
        ).toBeTruthy();
        expect(
            screen.getByRole('button', { name: 'Avancer de 15 secondes' }),
        ).toBeTruthy();
    });

    it('propose de ralentir un peu, pour les voix âgées', async () => {
        const user = userEvent.setup();
        render(<AudioPlayer src="/audio.mp3" />);

        const slower = screen.getByRole('button', { name: 'Ralentir un peu' });

        expect(slower.getAttribute('aria-pressed')).toBe('false');

        await user.click(slower);

        expect(
            screen
                .getByRole('button', { name: 'Vitesse normale' })
                .getAttribute('aria-pressed'),
        ).toBe('true');
    });

    it('annonce le temps restant', () => {
        const { container } = render(<AudioPlayer src="/audio.mp3" />);
        const audio = container.querySelector('audio');

        Object.defineProperty(audio, 'duration', {
            configurable: true,
            value: 95,
        });

        // `act` : l'événement part hors du cycle de React, et sans lui on
        // lirait le rendu d'avant la mise à jour d'état.
        act(() => {
            audio?.dispatchEvent(new Event('loadedmetadata'));
        });

        const remaining = screen.getByText(/Il reste/);

        expect(remaining.textContent).toBe('Il reste 1:35');
        // Annoncé, pas seulement affiché : une personne qui n'y voit pas doit
        // savoir où elle en est.
        expect(remaining.getAttribute('aria-live')).toBe('polite');
    });

    it('lit la durée déjà connue au moment où il s’installe', () => {
        // Fichier en cache, retour en arrière dans l'historique : les entêtes
        // sont là avant que React ne s'abonne, et l'événement ne repassera
        // pas. Sans relecture au montage, une histoire de trois minutes
        // s'annonçait « Il reste 0:00 » jusqu'au premier clic.
        Object.defineProperty(HTMLMediaElement.prototype, 'readyState', {
            configurable: true,
            get: () => HTMLMediaElement.prototype.HAVE_METADATA,
        });
        Object.defineProperty(HTMLMediaElement.prototype, 'duration', {
            configurable: true,
            get: () => 95,
        });

        render(<AudioPlayer src="/audio.mp3" />);

        expect(screen.getByText(/Il reste/).textContent).toBe('Il reste 1:35');

        // @ts-expect-error -- on rend à jsdom ses propriétés d'origine.
        delete HTMLMediaElement.prototype.readyState;
        // @ts-expect-error -- idem.
        delete HTMLMediaElement.prototype.duration;
    });

    it('rapporte les secondes jouées, par tranches', () => {
        const onProgress = vi.fn();
        const { container } = render(
            <AudioPlayer
                src="/audio.mp3"
                onProgress={onProgress}
                reportEverySeconds={10}
            />,
        );

        const audio = container.querySelector('audio');

        // Neuf avances d'une seconde : rien n'est encore rapporté.
        for (let second = 1; second <= 9; second++) {
            Object.defineProperty(audio, 'currentTime', {
                configurable: true,
                value: second,
            });
            audio?.dispatchEvent(new Event('timeupdate'));
        }

        expect(onProgress).not.toHaveBeenCalled();

        Object.defineProperty(audio, 'currentTime', {
            configurable: true,
            value: 10,
        });
        audio?.dispatchEvent(new Event('timeupdate'));

        expect(onProgress).toHaveBeenCalledWith(10);
    });

    it('ne compte pas un saut du curseur comme de l’écoute', () => {
        const onProgress = vi.fn();
        const { container } = render(
            <AudioPlayer src="/audio.mp3" onProgress={onProgress} />,
        );

        const audio = container.querySelector('audio');

        // Un bond de deux minutes : c'est un déplacement, pas une écoute.
        Object.defineProperty(audio, 'currentTime', {
            configurable: true,
            value: 120,
        });
        audio?.dispatchEvent(new Event('timeupdate'));

        expect(onProgress).not.toHaveBeenCalled();
    });

    it('rapporte ce qui reste à la mise en pause', async () => {
        const user = userEvent.setup();
        const onProgress = vi.fn();
        const { container } = render(
            <AudioPlayer src="/audio.mp3" onProgress={onProgress} />,
        );

        const audio = container.querySelector('audio');

        await user.click(screen.getByRole('button', { name: 'Écouter' }));

        for (let second = 1; second <= 4; second++) {
            Object.defineProperty(audio, 'currentTime', {
                configurable: true,
                value: second,
            });
            audio?.dispatchEvent(new Event('timeupdate'));
        }

        await user.click(
            screen.getByRole('button', { name: 'Mettre en pause' }),
        );

        // Quatre secondes écoutées ne doivent pas être perdues parce qu'on
        // n'a pas atteint la tranche de dix.
        expect(onProgress).toHaveBeenCalledWith(4);
    });

    it('n’utilise jamais les contrôles natifs du navigateur', () => {
        const { container } = render(<AudioPlayer src="/audio.mp3" />);

        // Ils font 20 px de haut sur un téléphone, et le public de ce produit
        // est celui qui a le plus de mal à les toucher.
        expect(container.querySelector('audio')?.hasAttribute('controls')).toBe(
            false,
        );
    });
});
