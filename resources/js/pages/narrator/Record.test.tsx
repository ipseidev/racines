import 'fake-indexeddb/auto';

import { render, screen, waitFor } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Record from './Record';

const catalogue = {
    common: {
        actions: { back: 'Retour' },
        video: { label: 'Le récit filmé', self_view: 'Ce que voit la caméra' },
    },
    narrator: {
        record: {
            mode_title: 'Comment voulez-vous répondre, :name ?',
            mode_audio: 'Avec votre voix',
            mode_video: 'En vous filmant',
            mode_help: 'La voix suffit.',
            camera_notice:
                'Votre téléphone demandera l’autorisation d’utiliser le micro et la caméra.',
            video_stage: 'Vous filmer',
            mode_exit: 'Sortir',
            recording: 'Enregistrement en cours',
            paused: 'En pause',
            elapsed: 'Durée : :time',
            pause: 'Pause',
            resume: 'Reprendre',
            finish: 'Terminer',
            soft_warning: 'Vous parlez depuis dix minutes.',
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
        camera_help: {
            title: 'La caméra n’est pas autorisée',
            body: 'Voici comment l’autoriser.',
            retry: 'Réessayer',
            ios: 'Sur iPhone…',
            android: 'Sur Android…',
            samsung: 'Sur Samsung…',
            other: 'Cherchez le cadenas.',
            unsupported: 'Votre navigateur ne sait pas filmer.',
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
    video: {
        maxBytes: 400_000_000,
        bitsPerSecond: 1_500_000,
        audioBitsPerSecond: 128_000,
        height: 720,
        acceptedMimes: ['video/webm'],
    },
};

const props = {
    firstName: 'Odette',
    addressForm: 'vous' as const,
    question: 'Quel est votre premier souvenir d’école ?',
    storyRef: 'b'.repeat(32),
    state: 'proposed',
    limits,
    writtenAnswerMaxChars: 20000,
    // Variante B par défaut dans ces tests : ils portent sur la capture, pas
    // sur la décision de partage, qui a sa propre suite.
    validationVariant: 'deferred' as const,
    shareDecisionAction: '/r/jeton/share-decision',
    shareDecision: null,
    techComfort: null,
};

const getUserMedia = vi.fn();

/**
 * L'écran du choix précède tout (T-210) : chaque parcours le traverse, comme
 * un vrai narrateur le traverse.
 */
const choose = async (label: string) =>
    userEvent.click(await screen.findByRole('button', { name: label }));

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

        await choose('Avec votre voix');

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

        await choose('Avec votre voix');

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

        await choose('Avec votre voix');
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

    it('demande la caméra, bornée, quand la personne choisit de se filmer', async () => {
        render(<Record {...props} />);

        await choose('En vous filmant');

        // Toujours rien : le choix ne déclenche aucune autorisation.
        expect(getUserMedia).not.toHaveBeenCalled();

        expect(
            await screen.findByText(
                'Votre téléphone demandera l’autorisation d’utiliser le micro et la caméra.',
            ),
        ).toBeTruthy();

        await choose('Je suis prêt·e');

        await waitFor(() => {
            expect(getUserMedia).toHaveBeenCalledOnce();
        });

        expect(getUserMedia).toHaveBeenCalledWith({
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            },
            video: {
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 },
                frameRate: { ideal: 30, max: 30 },
            },
        });
    });

    it('mène à l’aide de la caméra, pas à celle du micro, quand la caméra est refusée', async () => {
        getUserMedia.mockRejectedValue(new Error('NotAllowedError'));

        render(<Record {...props} />);

        await choose('En vous filmant');
        await choose('Je suis prêt·e');

        await waitFor(() => {
            expect(
                screen.getByRole('heading', {
                    name: 'La caméra n’est pas autorisée',
                }),
            ).toBeTruthy();
        });

        // Envoyer quelqu'un dans le réglage du micro le ferait tourner en rond.
        expect(
            screen.queryByRole('heading', {
                name: 'Le micro n’est pas autorisé',
            }),
        ).toBeNull();
    });

    it('n’offre pas de se filmer quand le navigateur ne sait pas le faire', async () => {
        vi.stubGlobal(
            'MediaRecorder',
            class {
                static isTypeSupported = (mime: string) =>
                    !mime.startsWith('video/');
                state = 'inactive';
                start = vi.fn();
                stop = vi.fn();
                pause = vi.fn();
                resume = vi.fn();
            },
        );

        render(<Record {...props} />);

        // La voix reste offerte : un bouton absent vaut mieux qu'un bouton
        // qui mène à un écran d'aide.
        expect(
            await screen.findByRole('button', { name: 'Avec votre voix' }),
        ).toBeTruthy();
        expect(
            screen.queryByRole('button', { name: 'En vous filmant' }),
        ).toBeNull();
    });

    it('montre la caméra en plein écran, la question posée dessus (T-212)', async () => {
        render(<Record {...props} />);

        await choose('En vous filmant');
        await choose('Je suis prêt·e');

        // L'aperçu occupe l'écran, et la question se lit par-dessus.
        await expect
            .poll(() => screen.queryByLabelText('Ce que voit la caméra'))
            .not.toBeNull();

        expect(screen.getByText(props.question)).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Sortir' })).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Commencer' })).toBeTruthy();
    });

    it('efface la question et la sortie dès que ça tourne (T-212)', async () => {
        render(<Record {...props} />);

        await choose('En vous filmant');
        await choose('Je suis prêt·e');
        await choose('Commencer');

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Pause' })).toBeTruthy();
        });

        // La question recouvrirait le visage qu'on est en train de cadrer.
        expect(screen.queryByText(props.question)).toBeNull();

        // Et sortir ici jetterait un récit : la porte est « Terminer ».
        expect(screen.queryByRole('button', { name: 'Sortir' })).toBeNull();
        expect(screen.getByRole('button', { name: 'Terminer' })).toBeTruthy();
    });

    it('ramène au choix quand on sort de la caméra, sans rien enregistrer', async () => {
        render(<Record {...props} />);

        await choose('En vous filmant');
        await choose('Je suis prêt·e');
        await choose('Sortir');

        await waitFor(() => {
            expect(
                screen.getByRole('button', { name: 'Avec votre voix' }),
            ).toBeTruthy();
        });
    });

    it('donne au bouton principal une hauteur d’au moins 44 px', async () => {
        render(<Record {...props} />);

        await choose('Avec votre voix');

        const button = await screen.findByRole('button', {
            name: 'Je suis prêt·e',
        });

        // Les zones tactiles sont exprimées en rem : 2.75rem = 44 px.
        expect(button.className).toContain('min-h-[2.75rem]');
        expect(button.className).toContain('py-4');
    });
});
