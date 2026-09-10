import { render, screen } from '@testing-library/react';
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
        // lien comme un lien, même s'il ne mène nulle part.
        expect(screen.queryByRole('link', { name: 'Français' })).toBeNull();
        expect(screen.getByText('Français')).toHaveAttribute(
            'aria-current',
            'true',
        );
    });

    it('poste le choix sur une page qui n’a qu’une adresse', () => {
        props = {
            i18n: catalogue,
            locale: { current: 'fr', language: 'fr', locales: locales(false) },
        };

        render(<LocaleSwitcher />);
        screen.getByRole('button', { name: 'Italiano' }).click();

        expect(post).toHaveBeenCalledWith(
            '/langue',
            { locale: 'it' },
            { preserveScroll: true },
        );
    });

    it('disparaît quand il n’y a rien à choisir', () => {
        props = { i18n: catalogue, locale: { current: 'fr', locales: [] } };

        const { container } = render(<LocaleSwitcher />);

        expect(container).toBeEmptyDOMElement();
    });
});
