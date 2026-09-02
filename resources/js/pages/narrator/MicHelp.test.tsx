import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import MicHelp from './MicHelp';

const catalogue = {
    common: {},
    narrator: {
        mic_help: {
            title: 'Le micro n’est pas autorisé',
            body: 'Voici comment l’autoriser.',
            retry: 'Réessayer',
            ios: 'Sur iPhone : ouvrez Réglages, Safari, Micro.',
            android: 'Sur Android : touchez le cadenas.',
            samsung: 'Sur Samsung Internet : touchez le cadenas.',
            other: 'Cherchez l’icône de cadenas.',
            unsupported: 'Votre navigateur ne sait pas enregistrer de son.',
        },
        record: { written_link: 'Répondre par écrit' },
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
}));

describe('aide au micro', () => {
    it('montre le chemin propre à chaque plateforme', () => {
        const expectations = [
            ['ios', catalogue.narrator.mic_help.ios],
            ['android', catalogue.narrator.mic_help.android],
            ['samsung', catalogue.narrator.mic_help.samsung],
            ['other', catalogue.narrator.mic_help.other],
        ] as const;

        for (const [platform, text] of expectations) {
            const { unmount } = render(
                <MicHelp platform={platform} canRetry={true} />,
            );

            expect(screen.getByText(text)).toBeTruthy();

            unmount();
        }
    });

    it('ne propose qu’un seul nouvel essai', async () => {
        const onRetry = vi.fn();

        render(<MicHelp platform="ios" canRetry={true} onRetry={onRetry} />);

        const button = screen.getByRole('button', { name: 'Réessayer' });
        await userEvent.click(button);

        expect(onRetry).toHaveBeenCalledOnce();
        expect(screen.queryByRole('button', { name: 'Réessayer' })).toBeNull();
    });

    it('offre toujours l’écrit comme porte de sortie', async () => {
        const onWrite = vi.fn();

        render(<MicHelp platform="ios" canRetry={true} onWrite={onWrite} />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Répondre par écrit' }),
        );

        expect(onWrite).toHaveBeenCalledOnce();
    });

    it('n’invite pas à réessayer quand le navigateur ne sait pas enregistrer', () => {
        render(<MicHelp platform="other" canRetry={false} />);

        expect(
            screen.getByText(
                'Votre navigateur ne sait pas enregistrer de son.',
            ),
        ).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Réessayer' })).toBeNull();
        expect(
            screen.getByRole('button', { name: 'Répondre par écrit' }),
        ).toBeTruthy();
    });
});
