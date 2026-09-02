import 'fake-indexeddb/auto';

import { render, screen, waitFor } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Record from './Record';

const catalogue = {
    common: { actions: { back: 'Retour' } },
    narrator: {
        record: {
            greeting: ':name, voici votre question de la semaine',
            greeting_tu: ':name, voici ta question de la semaine',
            mic_notice:
                'Votre téléphone demandera l’autorisation d’utiliser le micro.',
            mic_notice_tu:
                'Ton téléphone demandera l’autorisation d’utiliser le micro.',
            ready: 'Je suis prêt·e',
            requesting: 'Choisissez « Autoriser ».',
            start: 'Commencer',
            written_link: 'Répondre par écrit',
            storage_low: 'Il reste peu de place.',
            draft_title: 'Vous avez un enregistrement en cours',
            draft_body: 'Nous avons retrouvé votre début.',
            draft_resume: 'Reprendre mon enregistrement',
            draft_discard: 'Recommencer',
        },
        mic_help: {
            title: 'Le micro n’est pas autorisé',
            body: 'Voici comment l’autoriser.',
            retry: 'Réessayer',
            ios: 'Sur iPhone…',
            android: 'Sur Android…',
            samsung: 'Sur Samsung…',
            other: 'Cherchez le cadenas.',
            unsupported: 'Votre navigateur ne sait pas enregistrer de son.',
        },
        written_answer: {
            title: 'Répondre par écrit',
            body: 'Écrivez ce que vous auriez raconté.',
            label: 'Votre réponse',
            counter: ':count sur :max',
            send: 'Envoyer',
        },
        link_unavailable: { help: 'Écrivez-nous à :email.' },
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

const limits = {
    softWarningSeconds: 600,
    hardStopSeconds: 1200,
    maxBytes: 200_000_000,
    segmentMilliseconds: 5000,
    partSizeBytes: 5 * 1024 * 1024,
    acceptedMimes: ['audio/webm'],
};

const props = {
    firstName: 'Odette',
    addressForm: 'vous' as const,
    question: 'Quel est votre premier souvenir d’école ?',
    storyRef: 'b'.repeat(32),
    state: 'proposed',
    limits,
    writtenAnswerMaxChars: 20000,
};

const getUserMedia = vi.fn();

beforeEach(() => {
    getUserMedia.mockReset();
    getUserMedia.mockResolvedValue({ getTracks: () => [] });

    vi.stubGlobal(
        'MediaRecorder',
        class {
            static isTypeSupported = () => true;
            state = 'inactive';
            start = vi.fn();
            stop = vi.fn();
            pause = vi.fn();
            resume = vi.fn();
        },
    );

    Object.defineProperty(globalThis.navigator, 'mediaDevices', {
        value: { getUserMedia },
        configurable: true,
    });
});

describe('page d’enregistrement', () => {
    it('affiche la question et l’explication avant toute demande de micro', async () => {
        render(<Record {...props} />);

        await waitFor(() => {
            expect(
                screen.getByText(
                    'Votre téléphone demandera l’autorisation d’utiliser le micro.',
                ),
            ).toBeTruthy();
        });

        expect(screen.getByText(props.question)).toBeTruthy();

        // Le micro n'a pas été demandé : c'est tout l'enjeu de cet écran.
        expect(getUserMedia).not.toHaveBeenCalled();
    });

    it('salue le narrateur par son prénom, et le tutoie si le projet le veut', async () => {
        const { unmount } = render(<Record {...props} />);

        await waitFor(() => {
            expect(
                screen.getByRole('heading', {
                    name: 'Odette, voici votre question de la semaine',
                }),
            ).toBeTruthy();
        });

        unmount();

        render(<Record {...props} addressForm="tu" />);

        await waitFor(() => {
            expect(
                screen.getByRole('heading', {
                    name: 'Odette, voici ta question de la semaine',
                }),
            ).toBeTruthy();
        });
    });

    it('ne demande le micro qu’après le bouton « Je suis prêt·e »', async () => {
        render(<Record {...props} />);

        const button = await screen.findByRole('button', {
            name: 'Je suis prêt·e',
        });

        expect(getUserMedia).not.toHaveBeenCalled();

        await userEvent.click(button);

        await waitFor(() => {
            expect(getUserMedia).toHaveBeenCalledOnce();
        });

        expect(getUserMedia).toHaveBeenCalledWith({
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            },
        });
    });

    it('mène à l’écran d’aide quand le micro est refusé', async () => {
        getUserMedia.mockRejectedValue(new Error('NotAllowedError'));

        render(<Record {...props} />);

        await userEvent.click(
            await screen.findByRole('button', { name: 'Je suis prêt·e' }),
        );

        await waitFor(() => {
            expect(
                screen.getByRole('heading', {
                    name: 'Le micro n’est pas autorisé',
                }),
            ).toBeTruthy();
        });

        expect(
            screen.getByRole('button', { name: 'Répondre par écrit' }),
        ).toBeTruthy();
    });

    it('mène à l’écran d’aide quand le navigateur ne sait pas enregistrer', async () => {
        vi.stubGlobal('MediaRecorder', undefined);

        render(<Record {...props} />);

        await waitFor(() => {
            expect(
                screen.getByText(
                    'Votre navigateur ne sait pas enregistrer de son.',
                ),
            ).toBeTruthy();
        });

        expect(screen.queryByRole('button', { name: 'Réessayer' })).toBeNull();
    });

    it('propose l’écrit dès l’écran d’explication', async () => {
        render(<Record {...props} />);

        await userEvent.click(
            await screen.findByRole('button', { name: 'Répondre par écrit' }),
        );

        expect(
            screen.getByRole('heading', { name: 'Répondre par écrit' }),
        ).toBeTruthy();
        expect(screen.getByLabelText('Votre réponse')).toBeTruthy();
    });

    it('donne au bouton principal une hauteur d’au moins 44 px', async () => {
        render(<Record {...props} />);

        const button = await screen.findByRole('button', {
            name: 'Je suis prêt·e',
        });

        // Les zones tactiles sont exprimées en rem : 2.75rem = 44 px.
        expect(button.className).toContain('min-h-[2.75rem]');
        expect(button.className).toContain('py-4');
    });
});
