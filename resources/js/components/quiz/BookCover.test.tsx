import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { BookCover, marking, titleSize, type Cover } from './BookCover';

const IVOIRE: Cover = {
    value: 'ivory',
    label: 'Ivoire',
    background: '#faf7f2',
    ink: '#2a231e',
    mutedInk: '#5a5049',
    dark: false,
};

const FORET: Cover = {
    value: 'forest',
    label: 'Vert forêt',
    background: '#24392f',
    ink: '#f7f1e6',
    mutedInk: '#c9c0b2',
    dark: true,
};

describe('le marquage à froid', () => {
    it('creuse fort et éclaire peu sur une toile sombre', () => {
        const { emboss, recess } = marking(true);

        // Un bord trop clair sur une toile sombre donne un titre boursouflé,
        // pas un titre marqué.
        expect(emboss).toContain('255,255,255,0.16');
        expect(recess).toContain('0,0,0,0.55');
    });

    it('inverse le rapport sur une toile claire', () => {
        const { emboss, recess } = marking(false);

        expect(emboss).toContain('255,255,255,0.75');
        expect(recess).toContain('0,0,0,0.20');
    });
});

describe('le corps du titre', () => {
    it('descend d’un palier à mesure que le titre s’allonge', () => {
        // Ce qu'un imprimeur fait : il descend jusqu'à ce que la ligne tienne
        // sur un plat de quinze centimètres.
        expect(titleSize('Jeanne'.length)).toBe('text-[1.7rem]');
        expect(titleSize('L’histoire de Jeanne'.length)).toBe('text-[1.4rem]');
        expect(
            titleSize('Les dimanches chez Mamie, à La Rochelle'.length),
        ).toBe('text-[1.15rem]');
        expect(titleSize(80)).toBe('text-[0.95rem]');
    });

    it('ne glisse pas à chaque lettre tapée', () => {
        // Un corps continu ferait vibrer la couverture pendant la frappe.
        expect(titleSize(10)).toBe(titleSize(14));
        expect(titleSize(15)).toBe(titleSize(26));
    });
});

describe('le livre', () => {
    it('porte le contenu de la vraie couverture', () => {
        render(
            <BookCover
                cover={FORET}
                title="Jeanne"
                subtitle="Recueilli en 2026"
                brand="Marque"
            />,
        );

        // Le prénom en titre, l'année en sous-titre, la marque au pied :
        // exactement ce que `RenderBookHtml` compose.
        expect(screen.getByText('Jeanne')).toBeInTheDocument();
        expect(screen.getByText('Recueilli en 2026')).toBeInTheDocument();
        expect(screen.getByText('Marque')).toBeInTheDocument();
    });

    it('tire ses teintes de la palette du serveur, jamais d’une constante', () => {
        const { container } = render(
            <BookCover
                cover={IVOIRE}
                title="Jeanne"
                subtitle="Recueilli en 2026"
                brand="Marque"
            />,
        );

        const scene = container.querySelector<HTMLElement>('[data-testid]');

        // La même valeur que celle que l'imprimeur recevra : l'écran et le
        // BAT lisent la même énumération.
        expect(scene?.style.getPropertyValue('--case-tint')).toBe('#faf7f2');
        expect(scene?.style.getPropertyValue('--case-recess')).toContain(
            '0,0,0,0.20',
        );
    });

    it('cache à la lecture d’écran ce qui n’est que de la matière', () => {
        const { container } = render(
            <BookCover
                cover={FORET}
                title="Jeanne"
                subtitle="Recueilli en 2026"
                brand="Marque"
            />,
        );

        // La tranche, le filet et l'ombre portée ne se disent pas : ils se
        // voient. Les annoncer encombrerait la page de trois éléments vides.
        for (const selector of ['.case-spine', '.case-rule', '.case-shadow']) {
            expect(container.querySelector(selector)).toHaveAttribute(
                'aria-hidden',
                'true',
            );
        }
    });
});
