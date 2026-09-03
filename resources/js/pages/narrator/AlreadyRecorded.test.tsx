import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import AlreadyRecorded from './AlreadyRecorded';

const post = vi.fn();

const catalogue = {
    common: { actions: { cancel: 'Annuler' } },
    narrator: {
        already_recorded: {
            title: 'Vous avez déjà répondu à cette question',
            title_with_date: 'Vous avez déjà répondu le :date',
            body: 'Vous pouvez recommencer si vous préférez une autre version.',
            restart: 'Recommencer',
        },
        withdrawals: {
            hide: 'Masquer cette histoire',
            hide_confirm: 'Elle disparaîtra pour vos proches. Continuer ?',
        },
    },
};

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    router: { post: (...args: unknown[]) => post(...args) },
    usePage: () => ({ props: { i18n: catalogue, flash: { status: null } } }),
}));

const props = {
    firstName: 'Odette',
    question: 'Quel jeu aimiez-vous enfant ?',
    recordedAt: '2026-09-03T10:00:00+02:00',
    answerType: 'audio',
    restartAction: '/r/jeton/restart',
};

beforeEach(() => {
    post.mockReset();
});

describe('page « vous avez déjà répondu »', () => {
    /**
     * Le défaut que ce test aurait attrapé : le bouton appelait une prop
     * `onRestart` qu'une page rendue par le serveur ne peut pas recevoir — une
     * fonction ne traverse pas Inertia. Il ne faisait donc rien, dans tous les
     * cas, et la page était le cul-de-sac que son propre commentaire disait
     * qu'elle n'était pas.
     */
    it('recommence pour de vrai : le bouton appelle le serveur', async () => {
        render(<AlreadyRecorded {...props} canRestart />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Recommencer' }),
        );

        expect(post).toHaveBeenCalledOnce();
        expect(post.mock.calls[0][0]).toBe('/r/jeton/restart');
    });

    it('n’affiche pas le bouton quand recommencer n’est plus possible', () => {
        render(<AlreadyRecorded {...props} canRestart={false} />);

        expect(
            screen.queryByRole('button', { name: 'Recommencer' }),
        ).toBeNull();
    });

    it('propose le masquage seulement quand le lien le permet', async () => {
        const { unmount } = render(<AlreadyRecorded {...props} canRestart />);

        expect(
            screen.queryByRole('button', { name: 'Masquer cette histoire' }),
        ).toBeNull();

        unmount();

        render(<AlreadyRecorded {...props} canRestart canHide />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Masquer cette histoire' }),
        );

        expect(
            screen.getByText('Elle disparaîtra pour vos proches. Continuer ?'),
        ).toBeTruthy();
    });
});
