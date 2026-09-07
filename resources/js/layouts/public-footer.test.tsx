import { render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import PublicFooter from './public-footer';

const catalogue = {
    public: {
        landing: {
            nav: {
                how: 'Comment ça marche',
                book: 'Le livre',
                story: 'Notre histoire',
            },
            faq: { title: 'Questions fréquentes' },
        },
        legal: {
            terms: 'Conditions générales de vente',
            privacy: 'Politique de confidentialité',
            imprint: 'Mentions légales',
            consents: 'Vos accords, dans leur version en vigueur',
        },
        footer: {
            discover: 'Découvrir',
            home: 'Accueil',
            try: 'Essayer en 60 secondes',
            information: 'Informations',
            contact: 'Nous joindre',
            copyright: '© :year :brand',
            hosting: 'Hébergé dans l’Union européenne',
        },
    },
};

const brand = {
    name: 'Exemple',
    short_name: 'Exemple',
    tagline: 'Le livre de leurs souvenirs.',
    links_domain: 'exemple.fr',
    support_email: 'bonjour@exemple.fr',
    support_phone: '01 23 45 67 89',
    mark_url: null,
    logo_url: null,
};

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { i18n: catalogue, brand } }),
    // Marqué, pour distinguer une navigation Inertia d'un lien ordinaire.
    Link: ({
        href,
        children,
        ...rest
    }: {
        href: string;
        children: React.ReactNode;
        [key: string]: unknown;
    }) => (
        <a href={href} data-inertia="true" {...rest}>
            {children}
        </a>
    ),
}));

describe('PublicFooter', () => {
    it('porte la marque, les pages du site, les informations et le contact', () => {
        render(<PublicFooter />);

        const footer = screen.getByRole('contentinfo');

        expect(
            within(footer).getByText('Le livre de leurs souvenirs.'),
        ).toBeInTheDocument();

        const discover = within(footer).getByRole('navigation', {
            name: 'Découvrir',
        });
        expect(
            within(discover)
                .getAllByRole('link')
                .map((link) => link.getAttribute('href')),
        ).toEqual([
            '/',
            '/comment-ca-marche',
            '/#livre',
            '/#histoire',
            '/#questions',
            '/essai',
        ]);

        const information = within(footer).getByRole('navigation', {
            name: 'Informations',
        });
        expect(within(information).getAllByRole('link')).toHaveLength(4);

        expect(
            within(footer).getByRole('link', { name: 'bonjour@exemple.fr' }),
        ).toHaveAttribute('href', 'mailto:bonjour@exemple.fr');
        expect(
            within(footer).getByRole('link', { name: '01 23 45 67 89' }),
        ).toHaveAttribute('href', 'tel:0123456789');

        expect(
            within(footer).getByText(`© ${new Date().getFullYear()} Exemple`),
        ).toBeInTheDocument();
        expect(
            within(footer).getByText('Hébergé dans l’Union européenne'),
        ).toBeInTheDocument();
    });

    it('mène à l’essai par un lien ordinaire, et aux pages par Inertia', () => {
        render(<PublicFooter />);

        // Le micro doit pouvoir être demandé sur l'essai : une navigation
        // Inertia garderait la politique de permissions de la page d'où l'on
        // vient (T-151).
        expect(
            screen.getByRole('link', { name: 'Essayer en 60 secondes' }),
        ).not.toHaveAttribute('data-inertia');
        expect(
            screen.getByRole('link', { name: 'Comment ça marche' }),
        ).toHaveAttribute('data-inertia', 'true');
    });

    it('se fait discret dans le tunnel : sans les pages du site, mais avec le légal', () => {
        render(<PublicFooter variant="compact" />);

        // T-135 : rien qui concurrence « Continuer ». Les conditions, elles,
        // restent lisibles sans perdre sa saisie.
        expect(
            screen.queryByRole('navigation', { name: 'Découvrir' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('navigation', { name: 'Informations' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Conditions générales de vente' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'bonjour@exemple.fr' }),
        ).toBeInTheDocument();
    });

    it('ne montre le téléphone que s’il existe', () => {
        brand.support_phone = null as unknown as string;
        render(<PublicFooter />);

        expect(
            screen.queryByRole('link', { name: /01 23/ }),
        ).not.toBeInTheDocument();
    });
});
