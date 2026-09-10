import { usePage } from '@inertiajs/react';

import type { LocaleProp, LocaleTag, LocalizedUrls } from '@/types/locale';

/*
 * La langue de la page, telle que le serveur l'a décidée.
 *
 * Le serveur décide, jamais le navigateur : une adresse préfixée
 * (`/it/come-funziona`) sert toujours l'italien, et une page sans préfixe
 * suit le témoin, le compte, puis `Accept-Language` — dans cet ordre, et une
 * seule fois, dans `SetLocale`. Le front n'a qu'à lire.
 */
const FALLBACK: LocaleProp = {
    current: 'fr',
    language: 'fr',
    tag: 'fr-FR',
    currency: 'EUR',
    locales: [],
    urls: {
        home: '/',
        how_it_works: '/comment-ca-marche',
        books: '/nos-livres',
        faq: '/questions-frequentes',
        demo: '/essai',
        legal_terms: '/cgv',
        legal_privacy: '/confidentialite',
        legal_imprint: '/mentions-legales',
        legal_consents: '/consentements',
        checkout_show: '/acheter',
        checkout_thanks: '/acheter/merci',
    },
};

export function useLocaleProp(): LocaleProp {
    return {
        ...FALLBACK,
        ...((usePage().props.locale ?? {}) as Partial<LocaleProp>),
    };
}

/** La locale courante : `fr`, `it`, `es`, `fr-CH` ou `it-CH`. */
export function useLocale(): LocaleTag {
    return useLocaleProp().current;
}

/**
 * Les adresses des pages publiques dans la langue courante.
 *
 * À utiliser partout où l'on écrivait `/acheter` : un lien en dur renverrait
 * un visiteur italien sur la page française, et lui ferait perdre sa langue
 * au milieu du parcours.
 */
export function useUrls(): LocalizedUrls {
    return useLocaleProp().urls;
}
