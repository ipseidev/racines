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

/**
 * « +33612345678 » redevient « 06 12 34 56 78 » à l'écran.
 *
 * Le serveur garde le format international ; la personne, elle, a tapé un
 * numéro français, et doit le retrouver tel qu'elle l'écrit quand elle revient
 * corriger un champ. Tout autre numéro est rendu tel quel.
 */
export function nationalPhone(e164: string): string {
    const match = /^\+33([1-9])(\d{2})(\d{2})(\d{2})(\d{2})$/.exec(e164.trim());

    if (match === null) {
        return e164;
    }

    const [, first, ...rest] = match;

    return [`0${first}`, ...rest].join(' ');
}
