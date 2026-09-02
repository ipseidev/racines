import { usePage } from '@inertiajs/react';

export type Brand = {
    name: string;
    short_name: string;
    tagline: string;
    links_domain: string;
    support_email: string;
    support_phone: string | null;
    logo_url: string | null;
};

const fallback: Brand = {
    name: '',
    short_name: '',
    tagline: '',
    links_domain: '',
    support_email: '',
    support_phone: null,
    logo_url: null,
};

/**
 * La marque vient des réglages, partagés par le serveur à chaque page.
 * Aucun composant n'écrit le nom, le domaine ou une couleur en dur.
 */
export function useBrand(): Brand {
    return (usePage().props.brand ?? fallback) as Brand;
}

export function BrandLogo({ className }: { className?: string }) {
    const brand = useBrand();

    if (brand.logo_url === null) {
        return <span className={className}>{brand.name}</span>;
    }

    return <img src={brand.logo_url} alt={brand.name} className={className} />;
}
