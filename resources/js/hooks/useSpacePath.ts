import { usePage } from '@inertiajs/react';

/** L'espace tel que le serveur le partage : le projet regardé, et les autres. */
export type SpaceShared = {
    /** Le préfixe des adresses du projet : `/espace/projets/{id}`. */
    base: string;
    current: { id: string; narrator: string };
    projects: { id: string; narrator: string; href: string }[];
};

/**
 * Le chemin d'une page de l'espace, préfixé par le projet regardé.
 *
 * Les pages écrivaient `/espace/reglages` en dur — vingt-trois fois. Depuis
 * que les adresses portent le projet (`/espace/projets/{id}/reglages`), y
 * recoller l'identifiant à la main aurait voulu dire vingt-trois occasions de
 * l'oublier, et une vingt-quatrième écrite demain. Le préfixe vient du
 * serveur, qui seul sait quel projet est ouvert.
 *
 * Le repli sur `/espace` n'est pas décoratif : la prop est nulle hors des
 * routes du projet, et un composant partagé rendu ailleurs doit produire une
 * adresse valide plutôt qu'`undefined/reglages`.
 */
export function useSpacePath(): (chemin: string) => string {
    const space = usePage().props.space as SpaceShared | null | undefined;
    const base = space?.base ?? '/espace';

    return (chemin: string) => (chemin === '/' ? base : `${base}${chemin}`);
}

/** Les projets du compte, pour le sélecteur. Vide hors de l'espace. */
export function useSpace(): SpaceShared | null {
    return (usePage().props.space as SpaceShared | null | undefined) ?? null;
}
