import { act, render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Demo from './Demo';

const catalogue = {
    public: {
        demo: {
            eyebrow: 'L’essai',
            title: 'Essayez en 60 secondes',
            body: 'Répondez à une vraie question de la semaine.',
            nothing_sent: 'Cet essai reste sur votre téléphone.',
            question_label: 'Question de la semaine',
            question: 'Quelle odeur vous ramène à votre enfance ?',
            start: 'Commencer l’essai',
            start_hint: 'Touchez le bouton, puis parlez.',
            recording: 'Ça tourne. Parlez, on vous écoute.',
            elapsed: 'Vous parlez depuis :time.',
            stop: 'J’ai terminé',
            ready: 'Réécoutez-vous.',
            playback: 'Votre essai',
            again: 'Recommencer',
            result_title: 'Et voici ce que ça devient.',
            result_body: 'Votre voix n’a pas quitté votre téléphone.',
            unsupported: 'Ce navigateur ne sait pas enregistrer.',
            refused: 'Le micro n’a pas été autorisé.',
            cta: 'Offrir à un proche',
        },
        landing: {
            proof: {
                aria: 'Exemple',
                verbatim: 'Mot à mot',
                fluide: 'Texte mis au propre',
                sample_verbatim: 'alors euh… ma grand-mère',
                sample_fluide: 'Ma grand-mère, elle habitait',
                // eslint-disable-next-line unicorn/no-thenable -- clé du catalogue, jamais attendue
                then: 'Puis elle choisit :',
                share: 'Partager',
                keep: 'Garder pour moi',
                later: 'Décider plus tard',
            },
        },
    },
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
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    usePage: () => ({ props: { i18n: catalogue } }),
    Link: ({ href, children }: { href: string; children: React.ReactNode }) => (
        <a href={href}>{children}</a>
    ),
}));

vi.mock('@/recorder/mime', () => ({
    isRecordingSupported: () => true,
    pickMimeType: () => 'audio/webm',
    baseMimeType: (mime: string) => mime,
}));

const limits = {
    demoSeconds: 60,
    segmentMilliseconds: 5_000,
    acceptedMimes: ['audio/webm'],
};

/*
 * jsdom n'a ni micro ni `MediaRecorder`. On lui donne le strict nécessaire :
 * un flux dont on peut couper les pistes, et un enregistreur qui rend une
 * tranche puis se déclare arrêté. Le vu-mètre, lui, est neutralisé — son
 * `AudioContext` n'existe pas ici, et le module rend alors des barres à zéro,
 * ce qui est exactement le repli qu'on veut vérifier accessoirement.
 */
const tracks = { stop: vi.fn() };

class FakeRecorder {
    state = 'recording';
    mimeType = 'audio/webm';
    ondataavailable: ((event: { data: Blob }) => void) | null = null;
    onstop: (() => void) | null = null;

    start() {
        this.ondataavailable?.({
            data: new Blob(['a'], { type: 'audio/webm' }),
        });
    }

    stop() {
        this.state = 'inactive';
        this.onstop?.();
    }
}

beforeEach(() => {
    tracks.stop.mockReset();

    Object.defineProperty(navigator, 'mediaDevices', {
        configurable: true,
        value: {
            getUserMedia: vi.fn().mockResolvedValue({
                getTracks: () => [tracks],
            }),
        },
    });

    vi.stubGlobal('MediaRecorder', FakeRecorder);
    URL.createObjectURL = vi.fn(() => 'blob:essai');
    URL.revokeObjectURL = vi.fn();
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('Demo', () => {
    it('pose la question de la semaine avant de proposer de parler', () => {
        render(<Demo limits={limits} />);

        // L'acheteur doit reconnaître l'écran qu'il décrira au téléphone à sa
        // mère : la question d'abord, le bouton ensuite.
        expect(
            screen.getByText('Quelle odeur vous ramène à votre enfance ?'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: /Commencer l’essai/ }),
        ).toBeInTheDocument();
    });

    it('montre le temps écoulé pendant qu’on parle, jamais un décompte', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup();

        render(<Demo limits={limits} />);
        await user.click(screen.getByRole('button', { name: /Commencer/ }));

        expect(await screen.findByText(/Ça tourne/)).toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(3_000);
        });

        // Trois secondes écoulées, et non cinquante-sept restantes : voir le
        // compte tomber coupe la parole de qui cherche ses mots (PRD US-06).
        expect(screen.getByText('0:03')).toBeInTheDocument();
        expect(screen.queryByText(/0:57/)).not.toBeInTheDocument();

        vi.useRealTimers();
    });

    it('rend l’essai réécoutable, puis montre ce que devient une voix', async () => {
        const user = userEvent.setup();

        render(<Demo limits={limits} />);
        await user.click(screen.getByRole('button', { name: /Commencer/ }));
        await user.click(
            await screen.findByRole('button', { name: /terminé/ }),
        );

        expect(await screen.findByText('Réécoutez-vous.')).toBeInTheDocument();

        // La démonstration n'arrive qu'après : on ne montre ce que devient une
        // voix qu'à qui vient d'en donner une.
        expect(
            screen.getByText('Et voici ce que ça devient.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('alors euh… ma grand-mère'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Ma grand-mère, elle habitait'),
        ).toBeInTheDocument();

        // Et le micro est rendu : une page qui garde la main dessus laisse la
        // pastille rouge allumée dans l'onglet.
        expect(tracks.stop).toHaveBeenCalled();
    });

    it('n’affiche la démonstration qu’une fois l’essai fait', () => {
        render(<Demo limits={limits} />);

        expect(
            screen.queryByText('Et voici ce que ça devient.'),
        ).not.toBeInTheDocument();
    });
});
