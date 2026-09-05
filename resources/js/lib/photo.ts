/*
 * Les photos de la page d'accueil, en WebP et en six largeurs (T-150).
 *
 * Le JPEG d'origine pesait le double à qualité égale, et la page en charge
 * sept : 1,03 Mo avant, moins de la moitié après, sans perte visible. Les
 * cibles du dossier — Safari iOS N-2, Chrome Android N-2, Samsung Internet —
 * lisent toutes le WebP.
 *
 * Le JPEG reste dans public/img/landing : il sert l'aperçu Open Graph, que
 * plusieurs robots sociaux ne savent pas lire en WebP.
 *
 * L'échelle des largeurs est serrée là où la page en a besoin. Aucune photo
 * n'occupe la pleine largeur d'un écran de bureau : les emplacements font de
 * 17 à 36 rem, soit 272 à 576 px, le double en densité 2. Avec les trois
 * largeurs d'origine — 700, 1000, natif — le navigateur n'avait jamais de
 * barreau à sa mesure : il prenait 700 pour un emplacement de 272 en densité
 * 1, et le fichier natif pour un emplacement de 544 en densité 2. Lighthouse
 * comptait 442 Ko de trop le 5 septembre 2026. Les barreaux du bas (400, 550)
 * servent la densité 1, ceux du haut (900, 1100) la densité 2 ; un seul
 * fichier est téléchargé, les autres ne coûtent que leur place sur le disque.
 *
 * Les largeurs au-delà du natif de la photo sont écartées : annoncer une
 * largeur qu'on ne peut pas servir ferait charger un fichier plus petit que
 * l'emplacement. La capture de relecture, portrait et native en 780, passe
 * donc sa largeur en second argument.
 */
const WIDTHS = [400, 550, 700, 900, 1100] as const;

const NATIVE = 1400;

/**
 * Les attributs `src` et `srcSet` d'une photo de la page d'accueil.
 *
 * @param name Le nom du fichier, sans largeur ni extension.
 * @param native La largeur native du fichier, en pixels.
 */
export function photo(
    name: string,
    native: number = NATIVE,
): { src: string; srcSet: string } {
    const base = `/img/landing/${name}`;

    return {
        src: `${base}.webp`,
        srcSet: [
            ...WIDTHS.filter((width) => width < native).map(
                (width) => `${base}-${width}.webp ${width}w`,
            ),
            `${base}.webp ${native}w`,
        ].join(', '),
    };
}
