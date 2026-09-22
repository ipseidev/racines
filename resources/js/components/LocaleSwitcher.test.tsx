import { fireEvent, render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import LocaleSwitcher from './LocaleSwitcher';

const post = vi.fn();

let props: Record<string, unknown> = {};

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props }),
    router: {
        post: (...args: unknown[]) => post(...args),
    },
    Link: ({
        href,
        children,
        ...rest
    }: {
        href: string;
        children: React.ReactNode;
    }) => (
        <a href={href} {...rest}>
            {children}
        </a>
    ),
}));

const catalogue = { common: { locale: { label: 'Choisir la langue' } } };

const locales = (withUrls: boolean) => [
    {
        value: 'fr',
        name: 'Français',
        url: withUrls ? 'https://exemple.test/cgv' : null,
    },
    {
        value: 'it',
        name: 'Italiano',
        url: withUrls ? 'https://exemple.test/it/condizioni-di-vendita' : null,
    },
];

describe('sélecteur de langue', () => {
    it('propose des liens sur une page qui a une adresse par langue', () => {
        props = {
            i18n: catalogue,
            locale: { current: 'fr', language: 'fr', locales: locales(true) },
        };

        render(<LocaleSwitcher />);

        const italian = screen.getByRole('link', { name: 'Italiano' });

        // Un vrai lien : c'est ce que suit un moteur, et ce qui s'ouvre dans
        // un nouvel onglet.
        expect(italian).toHaveAttribute(
            'href',
            'https://exemple.test/it/condizioni-di-vendita',
        );
        expect(italian).toHaveAttribute('hreflang', 'it');
    });

    it('n’offre pas de lien vers la langue déjà servie', () => {
        props = {
            i18n: catalogue,
            locale: { current: 'fr', language: 'fr', locales: locales(true) },
        };

        render(<LocaleSwitcher />);

        // Un `<span>` et non un lien désactivé : un lecteur d'écran annonce un
        // lien comme un lien, même s'il ne mène nulle part. La liste est le
        // périmètre : le nom de la langue courante s'affiche aussi sur le
        // dépliant, et c'est une étiquette, pas un choix.
        const list = within(screen.getByRole('list'));

        expect(list.queryByRole('link', { name: 'Français' })).toBeNull();
        expect(list.getByText('Français')).toHaveAttribute(
            'aria-current',
            'true',
        );
    });

    it('montre la langue lue sur le dépliant, et cache les autres jusqu’au toucher', () => {
        props = {
            i18n: catalogue,
            locale: { current: 'fr', language: 'fr', locales: locales(true) },
        };

        const { container } = render(<LocaleSwitcher />);

        // Cinq langues tenaient deux rangs en bas de chaque page ; il n'en
        // reste qu'une à l'écran, celle qu'on est en train de lire.
        const box = container.querySelector('details');
        const summary = container.querySelector('summary');

        expect(box?.open).toBe(false);
        expect(summary?.textContent).toContain('Français');
    });

    it('se referme sur Échap', () => {
        props = {
            i18n: catalogue,
            locale: { current: 'fr', language: 'fr', locales: locales(true) },
        };

        const { container } = render(<LocaleSwitcher />);
        const box = container.querySelector('details');

        box!.open = true;
        fireEvent.keyDown(document, { key: 'Escape' });

        // Ce que `<details>` ne sait pas faire seul, et sans quoi le panneau
        // reste ouvert derrière la page suivante.
        expect(box!.open).toBe(false);
    });

    it('poste le choix sur une page qui n’a qu’une adresse', () => {
        props = {
            i18n: catalogue,
            locale: { current: 'fr', language: 'fr', locales: locales(false) },
        };

        const { container } = render(<LocaleSwitcher />);
        const box = container.querySelector('details');

        box!.open = true;
        screen.getByRole('button', { name: 'Italiano' }).click();

        expect(post).toHaveBeenCalledWith(
            '/langue',
            { locale: 'it' },
            { preserveScroll: true },
        );
        expect(box!.open).toBe(false);
    });

    it('disparaît quand il n’y a rien à choisir', () => {
        props = { i18n: catalogue, locale: { current: 'fr', locales: [] } };

        const { container } = render(<LocaleSwitcher />);

        expect(container).toBeEmptyDOMElement();
    });
});
