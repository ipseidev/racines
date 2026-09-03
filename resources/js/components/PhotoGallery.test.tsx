import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import PhotoGallery, { type Photo } from './PhotoGallery';

const catalogue = {
    common: {
        actions: { close: 'Fermer' },
        // Dans `common` : les quatre espaces affichent le même dépôt, et
        // seul `common` voyage avec toutes les pages.
        photos: {
            title: 'Les photos',
            remove: 'Retirer cette photo',
        },
    },
};

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { i18n: catalogue } }),
}));

const photos: Photo[] = [
    {
        id: 1,
        caption: 'Le mariage de ma sœur',
        thumbUrl: 'https://exemple.test/thumb-1.jpg',
        url: 'https://exemple.test/web-1.jpg',
        alt: 'Le mariage de ma sœur',
    },
    {
        id: 2,
        caption: null,
        thumbUrl: 'https://exemple.test/thumb-2.jpg',
        url: 'https://exemple.test/web-2.jpg',
        alt: 'Photo jointe par Claire',
    },
];

/**
 * La galerie des photos.
 *
 * Trois contraintes du dossier, et aucune n'est cosmétique : la cible
 * tactile, la navigation au clavier, et le texte alternatif. Ce sont les
 * trois choses qu'on perd en premier quand on retouche une grille d'images.
 */
describe('PhotoGallery', () => {
    it('donne à chaque photo un texte alternatif utile', () => {
        render(<PhotoGallery photos={photos} />);

        // « Photo jointe par Claire » plutôt que « Photo » : un lecteur
        // d'écran qui annonce dix fois « Photo » ne dit rien.
        expect(screen.getByAltText('Le mariage de ma sœur')).toBeTruthy();
        expect(screen.getByAltText('Photo jointe par Claire')).toBeTruthy();
    });

    it('offre une cible tactile d’au moins 88 pixels', () => {
        render(<PhotoGallery photos={photos} />);

        // La cible d'un doigt imprécis : une grille de vignettes de 44 px se
        // touche de travers.
        for (const button of screen.getAllByRole('button')) {
            expect(button.className).toContain('size-[88px]');
        }
    });

    it('ouvre le plein écran au clavier', async () => {
        render(<PhotoGallery photos={photos} />);

        await userEvent.tab();
        await userEvent.keyboard('{Enter}');

        const dialog = screen.getByRole('dialog');

        expect(dialog.getAttribute('aria-label')).toBe('Le mariage de ma sœur');
        expect(dialog.getAttribute('aria-modal')).toBe('true');
    });

    it('ferme le plein écran avec Échap', async () => {
        render(<PhotoGallery photos={photos} />);

        await userEvent.click(screen.getAllByRole('button')[0]);
        expect(screen.getByRole('dialog')).toBeTruthy();

        await userEvent.keyboard('{Escape}');

        // C'est le réflexe : sans lui, la seule sortie serait un bouton
        // qu'il faut trouver.
        expect(screen.queryByRole('dialog')).toBeNull();
    });

    it('n’offre le retrait que si on en a le droit', async () => {
        const { unmount } = render(<PhotoGallery photos={photos} />);

        await userEvent.click(screen.getAllByRole('button')[0]);
        expect(screen.queryByText('Retirer cette photo')).toBeNull();
        unmount();

        const onRemove = vi.fn();
        render(<PhotoGallery photos={photos} onRemove={onRemove} />);

        await userEvent.click(screen.getAllByRole('button')[0]);
        await userEvent.click(screen.getByText('Retirer cette photo'));

        expect(onRemove).toHaveBeenCalledWith(1);
    });

    it('ne rend rien quand il n’y a pas de photo', () => {
        render(<PhotoGallery photos={[]} />);

        // Un titre « Les photos » au-dessus du vide fait croire à une perte.
        expect(screen.queryByText('Les photos')).toBeNull();
    });
});
