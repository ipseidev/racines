import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import FirstRun from './FirstRun';

const recorder = vi.hoisted(() => ({
    phase: 'idle' as string,
    seconds: 0,
    levels: [] as number[],
    playbackUrl: null as string | null,
    start: vi.fn(),
    stop: vi.fn(),
    again: vi.fn(),
}));

vi.mock('@/recorder/useLocalRecorder', () => ({
    useLocalRecorder: () => recorder,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            i18n: {
                narrator: {
                    first_run: {
                        title: 'On essaie une fois, :name ?',
                        why: 'Rien ne sera envoyé, rien ne sera gardé. C’est juste pour prendre la main.',
                        start: 'Essayer',
                        skip: 'Passer et répondre tout de suite',
                        recording: 'Parlez, je vous écoute',
                        stop: 'J’ai fini',
                        listen_title: 'Écoutez-vous',
                        listen_body:
                            'C’est exactement ce que votre famille entendra.',
                        again: 'Recommencer',
                        done: 'C’est bon',
                        over_title: 'Voilà, c’est tout.',
                        over_body:
                            'Cet essai n’a été gardé nulle part — votre vraie question vous attend.',
                        over_button: 'Voir ma question',
                        refused_title: 'Le micro n’a pas répondu',
                        refused_body:
                            'Ce n’est pas grave, et rien n’est perdu.',
                        refused_button: 'Aller à ma question',
                    },
                },
            },
            brand: { name: 'P' },
        },
    }),
}));

const onDone = vi.fn();
const onStart = vi.fn();

const props = {
    firstName: 'Odette',
    tu: false,
    seconds: 15,
    segmentMilliseconds: 5000,
    onDone,
    onStart,
};

beforeEach(() => {
    onDone.mockClear();
    onStart.mockClear();
    recorder.start.mockClear();
    recorder.phase = 'idle';
    recorder.playbackUrl = null;
});

describe('le tour de chauffe du premier lien', () => {
    it('promet que rien ne part **avant** de proposer d’appuyer', () => {
        render(<FirstRun {...props} />);

        /*
         * L'ordre est le sujet. C'est la promesse qui autorise à appuyer :
         * placée sous le bouton, elle rassurerait quelqu'un qui a déjà parlé.
         */
        const texte = document.body.textContent ?? '';

        expect(texte.indexOf('Rien ne sera envoyé')).toBeLessThan(
            texte.indexOf('Essayer'),
        );
    });

    it('laisse passer sans jamais demander le micro', () => {
        render(<FirstRun {...props} />);

        fireEvent.click(screen.getByRole('button', { name: /Passer/ }));

        // Une personne pressée ne traverse pas un tutoriel pour répondre.
        expect(onDone).toHaveBeenCalledWith(false);
        expect(recorder.start).not.toHaveBeenCalled();
    });

    it('signale le départ, pour qu’on sache s’il aide ou s’il retarde', () => {
        render(<FirstRun {...props} />);

        fireEvent.click(screen.getByRole('button', { name: 'Essayer' }));

        expect(onStart).toHaveBeenCalledTimes(1);
        expect(recorder.start).toHaveBeenCalledTimes(1);
    });

    it('dit que l’essai n’a rien gardé avant d’emmener à la question', () => {
        recorder.phase = 'ready';
        recorder.playbackUrl = 'blob:essai';

        render(<FirstRun {...props} />);
        fireEvent.click(screen.getByRole('button', { name: 'C’est bon' }));

        // Le seul risque que cet écran ajoute : croire qu'on a répondu. La
        // phrase qui le retire est lue avant le bouton qui emmène.
        expect(screen.getByText(/n’a été gardé nulle part/)).toBeTruthy();
        expect(onDone).not.toHaveBeenCalled();

        fireEvent.click(
            screen.getByRole('button', { name: 'Voir ma question' }),
        );

        expect(onDone).toHaveBeenCalledWith(true);
    });

    it('ne piège personne quand le micro est refusé', () => {
        recorder.phase = 'refused';

        render(<FirstRun {...props} />);

        // L'aide propre au téléphone vit sur l'écran de la question : on y
        // emmène, on ne laisse pas devant un mur.
        fireEvent.click(
            screen.getByRole('button', { name: 'Aller à ma question' }),
        );

        expect(onDone).toHaveBeenCalledWith(false);
    });
});
