import 'fake-indexeddb/auto';

import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';

import Record from './Record';

/*
 * Micro refusé, puis autorisé — le scénario S9 du spike navigateur.
 *
 * Trouvé sur un vrai iPhone : « je refuse le micro, l'aide s'affiche, je
 * clique sur réessayer, j'accepte cette fois — l'aide reste et je ne passe
 * pas à l'étape d'après ».
 *
 * La cause est une couture, pas une logique. La machine a l'événement qu'il
 * faut, `RETRY_PERMISSION`, qui mène de `permission_denied` à
 * `requesting_permission` en comptant l'essai. Mais la page envoyait `READY`,
 * qui n'est valable que depuis `explaining` : depuis l'état refusé il ne fait
 * rien, et le `PERMISSION_GRANTED` qui suit est ignoré à son tour puisqu'on
 * n'est jamais entré en demande. La machine reste sur l'aide, pour toujours.
 *
 * Aucun test ne pouvait le voir : celui de la machine passait — elle est
 * correcte — et le bout en bout accorde le micro d'emblée, le navigateur de
 * test étant lancé avec une permission déjà donnée (T-178).
 */
const catalogue = {
    common: { player: {} },
    narrator: {
        record: {
            greeting: ':name, voici votre question de la semaine',
            mic_notice: 'Votre téléphone demandera le micro.',
            ready: 'Je suis prêt·e',
            requesting: 'Votre téléphone va vous demander l’autorisation.',
            start: 'Commencer',
            tap_hint: 'Appuyez, puis parlez.',
            written_link: 'Répondre par écrit',
        },
        mic_help: {
            title: 'Le micro n’est pas autorisé',
            retry: 'Réessayer',
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

/** Le micro refuse la première fois, accepte ensuite. */
function micRefusePuisAccepte() {
    let premierAppel = true;

    return vi.fn().mockImplementation(() => {
        if (premierAppel) {
            premierAppel = false;

            return Promise.reject(
                new DOMException('refusé', 'NotAllowedError'),
            );
        }

        return Promise.resolve({ getTracks: () => [] });
    });
}

beforeEach(() => {
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
        value: { getUserMedia: micRefusePuisAccepte() },
        configurable: true,
    });
});

afterEach(() => {
    vi.restoreAllMocks();
});

it('repart quand le micro est autorisé au second essai', async () => {
    const user = userEvent.setup();

    render(<Record {...props} />);

    await user.click(
        await screen.findByRole('button', { name: /je suis prêt/i }),
    );

    // L'aide s'affiche : c'est le comportement attendu du premier refus.
    expect(
        await screen.findByRole('button', { name: /réessayer/i }),
    ).toBeVisible();

    await user.click(screen.getByRole('button', { name: /réessayer/i }));

    // Et cette fois on passe à la suite : le bouton d'enregistrement apparaît,
    // l'aide disparaît.
    expect(
        await screen.findByRole('button', { name: /commencer/i }),
    ).toBeVisible();
    expect(screen.queryByRole('button', { name: /réessayer/i })).toBeNull();
});
