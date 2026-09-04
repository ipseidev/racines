import 'fake-indexeddb/auto';

import { act, render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Record from './Record';

/*
 * Le compteur de l'enregistrement, pendant et après une pause (T-140).
 *
 * Trouvé au test humain sur téléphone : « quand je mets en pause et que je
 * reprends, le compteur ajoute d'un coup toutes les secondes réellement
 * écoulées ». Le point de départ du compteur ne s'oubliait pas à la pause.
 */
const catalogue = {
    common: { player: {} },
    narrator: {
        record: {
            greeting: ':name, voici votre question de la semaine',
            mic_notice:
                'Quand vous appuierez sur le bouton, votre téléphone demandera le micro.',
            ready: 'Je suis prêt·e',
            requesting: 'Votre téléphone va vous demander l’autorisation.',
            start: 'Commencer',
            tap_hint: 'Appuyez, puis parlez.',
            pause: 'Pause',
            resume: 'Reprendre',
            finish: 'Terminer',
            recording: 'Enregistrement en cours',
            paused: 'En pause',
            elapsed: 'Durée : :time',
            written_link: 'Répondre par écrit',
        },
    },
};

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    usePage: () => ({
        props: {
            i18n: catalogue,
            brand: { name: 'P', support_email: 'aide@example.test' },
        },
    }),
    useForm: () => ({
        data: { written_answer: '' },
        setData: vi.fn(),
        post: vi.fn(),
        processing: false,
        errors: {},
    }),
}));

vi.mock('@/recorder/clientEvents', () => ({ reportClientEvent: vi.fn() }));
vi.mock('@/recorder/wakeLock', () => ({
    requestWakeLock: async () => ({ release: () => undefined }),
}));

const props = {
    firstName: 'Odette',
    addressForm: 'vous' as const,
    question: 'Quel est votre premier souvenir d’école ?',
    storyRef: 'c'.repeat(32),
    state: 'proposed',
    limits: {
        softWarningSeconds: 600,
        hardStopSeconds: 1200,
        maxBytes: 200_000_000,
        segmentMilliseconds: 5000,
        partSizeBytes: 5 * 1024 * 1024,
        acceptedMimes: ['audio/webm'],
    },
    writtenAnswerMaxChars: 20000,
    validationVariant: 'deferred' as const,
    shareDecisionAction: '/r/jeton/share-decision',
    shareDecision: null,
    techComfort: null,
};

beforeEach(() => {
    vi.useFakeTimers({ shouldAdvanceTime: true });

    vi.stubGlobal(
        'MediaRecorder',
        class {
            static isTypeSupported = () => true;
            state = 'inactive';
            start = vi.fn();
            stop = vi.fn();
            pause = vi.fn();
            resume = vi.fn();
            requestData = vi.fn();
        },
    );

    Object.defineProperty(globalThis.navigator, 'mediaDevices', {
        value: {
            getUserMedia: vi.fn().mockResolvedValue({ getTracks: () => [] }),
        },
        configurable: true,
    });
});

afterEach(() => {
    vi.useRealTimers();
});

describe('le compteur de l’enregistrement', () => {
    it('ne compte pas le temps passé en pause', async () => {
        const user = userEvent.setup({
            advanceTimers: (ms) => vi.advanceTimersByTime(ms),
        });

        render(<Record {...props} />);

        await user.click(
            await screen.findByRole('button', { name: 'Je suis prêt·e' }),
        );
        await user.click(
            await screen.findByRole('button', { name: 'Commencer' }),
        );
        // L'enregistrement démarre après l'ouverture du brouillon, qui est
        // asynchrone : on attend d'être vraiment en train d'enregistrer.
        await screen.findByRole('button', { name: 'Pause' });

        // Trois secondes de parole.
        await act(async () => {
            await vi.advanceTimersByTimeAsync(3_100);
        });
        expect(screen.getByText('0:03')).toBeInTheDocument();

        // Une pause de dix secondes : le compteur ne bouge pas.
        await user.click(screen.getByRole('button', { name: 'Pause' }));
        await act(async () => {
            await vi.advanceTimersByTimeAsync(10_000);
        });
        expect(screen.getByText('0:03')).toBeInTheDocument();

        // On reprend deux secondes : cinq, et non quinze.
        await user.click(screen.getByRole('button', { name: 'Reprendre' }));
        await act(async () => {
            await vi.advanceTimersByTimeAsync(2_100);
        });
        expect(screen.getByText('0:05')).toBeInTheDocument();
        expect(screen.queryByText('0:15')).not.toBeInTheDocument();
    });
});
