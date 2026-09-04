/**
 * « d’Odette », « de Marie » : l'élision devant une voyelle ou un h.
 *
 * Un prénom arrive de la base ; la phrase qui l'accueille ne peut pas savoir
 * à l'avance s'il commence par une voyelle. Sans cela, la page de merci
 * écrivait « le livre de Odette », ce qu'aucun francophone n'écrit.
 */
export function ofName(name: string): string {
    const trimmed = name.trim();

    return /^[aeiouyhàâäéèêëîïôöùûüœ]/i.test(trimmed)
        ? `d’${trimmed}`
        : `de ${trimmed}`;
}
