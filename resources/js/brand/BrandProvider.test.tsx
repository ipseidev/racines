import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { type Brand, BrandLogo, useBrand } from './BrandProvider';

const brand: Brand = {
    name: 'Essai',
    short_name: 'Essai',
    tagline: 'Une promesse',
    links_domain: 'exemple.test',
    support_email: 'support@exemple.test',
    support_phone: null,
    mark_url: null,
    logo_url: null,
};

const page = { props: { brand } as { brand: Brand } };

vi.mock('@inertiajs/react', () => ({
    usePage: () => page,
}));

function Nom() {
    return <p>{useBrand().name}</p>;
}

describe('useBrand', () => {
    it('lit la marque dans les propriétés partagées par le serveur', () => {
        page.props.brand = brand;
        render(<Nom />);

        expect(screen.getByText('Essai')).toBeInTheDocument();
    });
});

describe('BrandLogo', () => {
    it('affiche le nom en texte quand aucun logo n’est défini', () => {
        page.props.brand = brand;
        render(<BrandLogo />);

        expect(screen.getByText('Essai')).toBeInTheDocument();
        expect(screen.queryByRole('img')).not.toBeInTheDocument();
    });

    it('pose le pictogramme à côté du nom, sans le répéter à la voix', () => {
        page.props.brand = { ...brand, mark_url: '/img/brand/mark.svg' };
        const { container } = render(<BrandLogo />);

        const mark = container.querySelector('img');

        expect(mark).toHaveAttribute('src', '/img/brand/mark.svg');
        expect(mark).toHaveAttribute('aria-hidden', 'true');
        expect(screen.getByText('Essai')).toBeInTheDocument();
        expect(screen.queryByRole('img')).not.toBeInTheDocument();
    });

    it('efface le pictogramme quand un logo complet remplace le nom', () => {
        page.props.brand = {
            ...brand,
            mark_url: '/img/brand/mark.svg',
            logo_url: '/storage/logo.svg',
        };
        render(<BrandLogo />);

        expect(screen.getAllByRole('img')).toHaveLength(1);
        expect(screen.getByRole('img')).toHaveAttribute(
            'src',
            '/storage/logo.svg',
        );
    });

    it('affiche le logo avec le nom en texte alternatif', () => {
        page.props.brand = { ...brand, logo_url: '/storage/logo.svg' };
        render(<BrandLogo />);

        const logo = screen.getByRole('img');

        expect(logo).toHaveAttribute('src', '/storage/logo.svg');
        expect(logo).toHaveAccessibleName('Essai');
    });
});
