/** Les cinq locales servies. Le miroir exact de `App\Enums\Locale`. */
export const LOCALES = ['fr', 'it', 'es', 'fr-CH', 'it-CH'] as const;

export type LocaleTag = (typeof LOCALES)[number];

export type LocaleOption = {
    value: LocaleTag;
    /** Le nom de la langue dans la langue elle-même. */
    name: string;
    /** La même page dans cette langue, ou `null` si elle n'a qu'une adresse. */
    url: string | null;
};

/**
 * Les adresses des pages publiques dans la langue courante, envoyées par le
 * serveur : sans elles, le front écrirait `/acheter` en dur et enverrait un
 * visiteur italien sur la page française.
 */
export type LocalizedUrls = {
    home: string;
    how_it_works: string;
    books: string;
    faq: string;
    demo: string;
    legal_terms: string;
    legal_privacy: string;
    legal_imprint: string;
    legal_consents: string;
    checkout_show: string;
    checkout_thanks: string;
};

export type LocaleProp = {
    current: LocaleTag;
    language: string;
    tag: string;
    currency: 'EUR' | 'CHF';
    locales: LocaleOption[];
    urls: LocalizedUrls;
};
