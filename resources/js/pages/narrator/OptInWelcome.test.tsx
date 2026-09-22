import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import OptInWelcome from './OptInWelcome';

const celebrate = vi.hoisted(() => vi.fn());
const page = vi.hoisted(() => ({ status: null as string | null }));

vi.mock('@/lib/celebrate', () => ({ celebrate }));

const i18n = {
    narrator: {
        optin_welcome: {
            title: 'Bienvenue, :name',
            first_question: 'Votre première question arrive',
            when_unknown: 'très bientôt',
            nothing_to_do: 'Vous n’avez rien à installer, rien à préparer.',
            leave: 'Vous pouvez fermer cette page sans crainte. Nous vous écrirons le moment venu.',
        },
    },
};

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    usePage: () => ({
        props: {
            i18n,
            brand: { name: 'P' },
            flash: { status: page.status },
        },
    }),
}));

const props = {
    firstName: 'Jeanne',
    nextPromptAt: null,
};

beforeEach(() => {
    celebrate.mockClear();
    page.status = null;
});

describe('l’écran de bienvenue', () => {
    it('fête le oui d’une pluie légère, à l’arrivée depuis l’acceptation', () => {
        page.status = 'Votre accord est enregistré.';

        render(<OptInWelcome {...props} />);

        // Légère : la personne a quatre-vingts ans et vient de donner cinq
        // accords. On la félicite, on ne l'assourdit pas.
        expect(celebrate).toHaveBeenCalledTimes(1);
        expect(celebrate).toHaveBeenCalledWith('soft');
    });

    it('ne refait pas la fête à qui revient ou recharge la page', () => {
        // Sans message flash, on n'arrive pas de l'acceptation.
        render(<OptInWelcome {...props} />);

        expect(celebrate).not.toHaveBeenCalled();
    });

    it('ne demande plus rien : ni contact à enregistrer, ni souhaits pour plus tard', () => {
        render(<OptInWelcome {...props} />);

        /*
         * Le cœur de la décision du 20 septembre 2026. Les deux sections
         * posaient une tâche de plus à quelqu'un qui venait d'accepter de
         * raconter sa vie, et la seconde lui parlait de sa mort à la minute
         * où on la félicitait. Aucun bouton, aucun lien, aucune question.
         */
        expect(screen.queryByRole('button')).toBeNull();
        expect(screen.queryByRole('link')).toBeNull();
        expect(screen.queryByRole('radio')).toBeNull();
        expect(screen.queryByRole('heading', { level: 2 })).toBeNull();
    });

    it('dit quand arrive la première question, et qu’on peut partir', () => {
        render(<OptInWelcome {...props} />);

        expect(screen.getByRole('heading', { level: 1 }).textContent).toBe(
            'Bienvenue, Jeanne',
        );

        // Sans échéance posée, la page ne promet pas une heure qu'elle ignore.
        expect(screen.getByText('très bientôt')).toBeTruthy();

        // La phrase qui autorise à fermer la page : c'est elle qui évite
        // qu'on reste devant l'écran à attendre quelque chose.
        expect(screen.getByText(/fermer cette page sans crainte/)).toBeTruthy();
    });

    it('n’annonce le message d’accord que lorsqu’il existe', () => {
        render(<OptInWelcome {...props} />);
        expect(screen.queryByRole('status')).toBeNull();

        page.status = 'Votre accord est enregistré.';
        render(<OptInWelcome {...props} />);
        expect(screen.getAllByRole('status')[0].textContent).toBe(
            'Votre accord est enregistré.',
        );
    });
});
