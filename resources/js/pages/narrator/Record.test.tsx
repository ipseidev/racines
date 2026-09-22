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
            greeting: 'Une question pour vous, :name.',
            greeting_tu: 'Une question pour toi, :name.',
            mic_notice:
                'Votre téléphone demandera l’autorisation d’utiliser le micro.',
            mic_notice_tu:
                'Ton téléphone demandera l’autorisation d’utiliser le micro.',
            open_camera: 'Ouvrir la caméra',
            requesting: 'Choisissez « Autoriser ».',
            start: 'Commencer',
            written_link: 'Répondre par écrit',
            storage_low: 'Il reste peu de place.',
            draft_title: 'Vous avez un enregistrement en cours',
            draft_body: 'Nous avons retrouvé votre début.',
            draft_resume: 'Reprendre mon enregistrement',
            draft_discard: 'Recommencer',
            photo_open: 'Agrandir : :alt',
            photo_enlarge: 'Voir en grand',
            photo_from: 'Envoyée par :name.',
            photos_from: 'Envoyées par :name.',
            photo_from_family: 'Envoyée par votre famille.',
            photos_from_family: 'Envoyées par votre famille.',
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
    firstRunSeconds: 15,
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
    questionPhotos: [],
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
    // Ces suites portent sur la capture : le tour de chauffe du premier lien
    // a la sienne, et s’interposerait ici entre le test et son sujet.
    firstTime: false,
    // Sans déclaration d’avance : ces suites portent sur la capture, et
    // l’écran de fin y pose encore sa question.
    declaredSharing: false,
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
                    name: 'Une question pour vous, Odette.',
                }),
            ).toBeTruthy();
        });

        unmount();

        render(<Record {...props} addressForm="tu" />);

        await waitFor(() => {
            expect(
                screen.getByRole('heading', {
                    name: 'Une question pour toi, Odette.',
                }),
            ).toBeTruthy();
        });
    });

    it('ne demande le micro qu’après le grand bouton, jamais au choix', async () => {
        render(<Record {...props} />);

        await choose('Avec votre voix');

        const button = await screen.findByRole('button', {
            name: 'Commencer',
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
            await screen.findByRole('button', { name: 'Commencer' }),
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

        // La réponse écrite arrive en morceau séparé depuis qu'elle ne pèse
        // plus à l'ouverture : on l'attend, comme le fait un navigateur.
        expect(
            await screen.findByRole('heading', { name: 'Répondre par écrit' }),
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

        await choose('Ouvrir la caméra');

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
        await choose('Ouvrir la caméra');

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
        await choose('Ouvrir la caméra');

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
        await choose('Ouvrir la caméra');
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
        await choose('Ouvrir la caméra');
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
            name: 'Commencer',
        });

        /*
         * Le bouton principal est le cadran depuis T-248, et sa taille vient
         * de `.record-dial` — 10,5 rem, 7,5 rem sur un écran bas — et non
         * d'utilitaires. Jsdom ne lit pas la feuille de style : on vérifie
         * donc la classe qui porte la garantie, faute de pouvoir mesurer.
         * Les 44 px de la convention sont largement dépassés dans les deux
         * cas, et c'est le test bout en bout d'accessibilité qui le mesure
         * pour de vrai.
         */
        expect(button.className).toContain('record-dial');
    });

    /*
     * Une question posée **avec** une image (T-251).
     *
     * La famille tend la photo et demande. Ce qui se vérifie ici n'est pas la
     * mise en page mais le lien entre les deux : l'image porte un texte de
     * remplacement, et la légende se lit — une photo sans alternative est une
     * question muette pour qui n'y voit pas.
     */
    it('montre les photos qui posent la question, avec leur texte de remplacement', async () => {
        render(
            <Record
                {...props}
                questionPhotos={[
                    {
                        id: 1,
                        url: 'https://exemple.test/web-1.jpg',
                        thumbUrl: 'https://exemple.test/thumb-1.jpg',
                        alt: 'Photo jointe par Claire',
                        caption: 'La maison de Saint-Léon, été 1951',
                        from: 'Claire',
                    },
                    {
                        id: 2,
                        url: 'https://exemple.test/web-2.jpg',
                        thumbUrl: 'https://exemple.test/thumb-2.jpg',
                        alt: 'Photo jointe par Claire',
                        caption: null,
                        from: 'Claire',
                    },
                ]}
            />,
        );

        await waitFor(() => {
            expect(
                screen.getAllByRole('img', { name: 'Photo jointe par Claire' }),
            ).toHaveLength(2);
        });

        /*
         * La légende ne s'affiche sous la vignette que lorsqu'il n'y en a
         * qu'une : trois légendes sous trois vignettes de 88 px ne se lisent
         * pas, et elles vivent alors dans la vue agrandie, à côté de l'image
         * qu'elles décrivent.
         */
        expect(
            screen.queryByText('La maison de Saint-Léon, été 1951'),
        ).toBeNull();

        // Le prénom de qui l'a envoyée, et non un collectif : c'est
        // quelqu'un qui demande, pas un service.
        expect(screen.getByText('Envoyées par Claire.')).toBeTruthy();

        // La question reste lisible : l'image l'accompagne, elle ne la remplace pas.
        expect(
            screen.getByText('Quel est votre premier souvenir d’école ?'),
        ).toBeTruthy();
    });

    it('affiche la légende sous l’image quand la question n’en porte qu’une', async () => {
        render(
            <Record
                {...props}
                questionPhotos={[
                    {
                        id: 1,
                        url: 'https://exemple.test/web-1.jpg',
                        thumbUrl: 'https://exemple.test/thumb-1.jpg',
                        alt: 'Photo jointe par Claire',
                        caption: 'La maison de Saint-Léon, été 1951',
                        from: 'Claire',
                    },
                ]}
            />,
        );

        await waitFor(() => {
            expect(
                screen.getByText('La maison de Saint-Léon, été 1951'),
            ).toBeTruthy();
        });
    });

    it('n’affiche aucune image quand la question n’en porte pas', async () => {
        render(<Record {...props} />);

        await waitFor(() => {
            expect(
                screen.getByText('Quel est votre premier souvenir d’école ?'),
            ).toBeTruthy();
        });

        expect(screen.queryAllByRole('img')).toHaveLength(0);
    });
});
