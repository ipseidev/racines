import { usePage } from '@inertiajs/react';

export type Brand = {
    name: string;
    short_name: string;
    tagline: string;
    links_domain: string;
    support_email: string;
    support_phone: string | null;
    mark_url: string | null;
    logo_url: string | null;
};

const fallback: Brand = {
    name: '',
    short_name: '',
    tagline: '',
    links_domain: '',
    support_email: '',
    support_phone: null,
    mark_url: null,
    logo_url: null,
};

/**
 * La marque vient des réglages, partagés par le serveur à chaque page.
 * Aucun composant n'écrit le nom, le domaine ou une couleur en dur.
 */
export function useBrand(): Brand {
    return (usePage().props.brand ?? fallback) as Brand;
}

/**
 * La signature de la marque en en-tête : le pictogramme, puis le nom.
 *
 * Les deux cohabitent, et c'est voulu — le dessin se reconnaît d'un coup d'œil
 * dans un onglet ou sur un téléphone tenu à bout de bras, le mot reste ce qui
 * se prononce et se retient. Le pictogramme est décoratif au sens des lecteurs
 * d'écran : le nom est juste à côté, en texte, et l'annoncer deux fois ne
 * renseignerait personne.
 *
 * `logo_path` reste l'échappatoire : un logo complet, lui, remplace le nom
 * plutôt que de l'accompagner. Les deux viennent des réglages, jamais du code.
 */
export function BrandLogo({ className }: { className?: string }) {
    const brand = useBrand();

    if (brand.logo_url !== null) {
        return (
            <img src={brand.logo_url} alt={brand.name} className={className} />
        );
    }

    if (brand.mark_url === null) {
        return <span className={className}>{brand.name}</span>;
    }

    return (
        <span
            className={`inline-flex items-center gap-[0.4em] ${className ?? ''}`}
        >
            <img
                src={brand.mark_url}
                alt=""
                aria-hidden="true"
                className="h-[0.95em] w-auto flex-none"
            />
            {brand.name}
        </span>
    );
}
