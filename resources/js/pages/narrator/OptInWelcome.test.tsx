import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import OptInWelcome from './OptInWelcome';

const celebrate = vi.hoisted(() => vi.fn());
const page = vi.hoisted(() => ({ status: null as string | null }));

vi.mock('@/lib/celebrate', () => ({ celebrate }));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    usePage: () => ({
        props: {
            i18n: {},
            brand: { name: 'P' },
            flash: { status: page.status },
        },
    }),
}));

const props = {
    firstName: 'Jeanne',
    nextPromptAt: null,
    vcardUrl: '/vcard',
    directivesRecorded: false,
};

beforeEach(() => {
    celebrate.mockClear();
    page.status = null;
});

describe('l’écran de bienvenue', () => {
    it('fête le oui d’une pluie légère, à l’arrivée depuis l’acceptation', () => {
        page.status = 'C’est noté. Bienvenue.';

        render(<OptInWelcome {...props} />);

        // Légère : la personne a quatre-vingts ans et vient de donner cinq
        // accords. On la félicite, on ne l'assourdit pas.
        expect(celebrate).toHaveBeenCalledTimes(1);
        expect(celebrate).toHaveBeenCalledWith('soft');
    });

    it('ne redemande pas ses souhaits à qui vient d’accepter', () => {
        render(<OptInWelcome {...props} />);

        // Les souhaits se choisissent sur la page d'acceptation (T-236) : ici,
        // aucun bouton, aucune question — on dit ce qui vaut.
        expect(screen.queryByRole('button')).toBeNull();
        expect(screen.queryByRole('radio')).toBeNull();
        expect(screen.getByRole('status')).toBeTruthy();
    });

    it('ne refait pas la fête à qui revient ou recharge la page', () => {
        // Sans message flash, on n'arrive pas de l'acceptation.
        render(<OptInWelcome {...props} />);

        expect(celebrate).not.toHaveBeenCalled();
    });
});
