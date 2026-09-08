import type { ComponentType, ReactNode } from 'react';

/** Une mise en page : un composant qui enveloppe la page qu'on lui donne. */
export type Layout = ComponentType<{ children: ReactNode }>;

export type LayoutKey =
    | 'app'
    | 'auth'
    | 'checkout'
    | 'family'
    | 'initiator'
    | 'lp'
    | 'narrator'
    | 'public'
    | 'settings';

/**
 * La ou les mises en page d'une page, d'après le nom de son composant.
 *
 * Une seule table pour le client et le serveur : les deux doivent poser
 * **exactement** la même enveloppe autour de la même page, sinon l'hydratation
 * compare deux arbres différents et jette le rendu serveur. Le client charge
 * les modules à la demande, le serveur les importe une fois ; ce qui leur est
 * commun, c'est ce choix — et il ne l'était pas : le serveur ignorait le
 * tunnel, les pages QR et les exports.
 */
export function layoutKeysFor(name: string): LayoutKey[] {
    switch (true) {
        case name.startsWith('auth/'):
            return ['auth'];
        // Espaces sans compte : mise en page sobre, texte large, aucune
        // dépendance lourde (convention §4, budget 150 Ko par page).
        case name.startsWith('narrator/'):
            return ['narrator'];
        case name.startsWith('family/'):
        // Les pages ouvertes par un QR imprimé, et celles d'export, ouvertes
        // depuis un courriel : même sobriété que l'espace famille, et personne
        // n'est identifié derrière.
        case name.startsWith('qr/'):
        case name.startsWith('exports/'):
        // Les pages d'action en un tap s'ouvrent depuis un SMS, sans compte :
        // surtout pas la navigation d'un espace où l'on n'est pas connecté.
        case name === 'initiator/OneTapConfirm':
            return ['family'];
        // Le tunnel d'achat a sa propre mise en page : sans la navigation ni
        // le bouton d'achat de l'accueil, qui concurrenceraient « Continuer »
        // (T-135).
        case name.startsWith('public/Checkout'):
            return ['checkout'];
        // L'accueil et ses pages sœurs ont leur propre barre : ses ancres
        // pointent dans la page (T-219, T-220). Le témoin retombe sur
        // `public` par le cas suivant, comme il l'a toujours fait.
        case name === 'public/Landing':
        case name === 'public/Faq':
        case name === 'public/Books':
        case name === 'public/HowItWorks':
            return ['lp'];
        // Les pages publiques portent le pied de page légal partout : on doit
        // pouvoir lire les conditions sans revenir en arrière et perdre sa
        // saisie.
        case name.startsWith('public/'):
            return ['public'];
        case name.startsWith('initiator/'):
            return ['initiator'];
        case name.startsWith('settings/'):
            return ['app', 'settings'];
        default:
            return ['app'];
    }
}
