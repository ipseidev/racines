/**
 * Les jours d'envoi d'un rythme, à partir du jour choisi.
 *
 * Le narrateur choisit **un** jour ; une cadence de deux ou trois questions
 * par semaine en déduit les autres par un écartement régulier, décidé côté
 * serveur par `App\Enums\Cadence::dayOffsets()` et poussé avec la page. Le
 * front ne connaît donc pas la règle — il l'applique — et ajouter un rythme
 * ne demande pas de toucher deux fichiers dans deux langages.
 */
export function promptDays(offsets: number[], firstDay: number): number[] {
    return offsets.map((offset) => ((firstDay - 1 + offset) % 7) + 1);
}

/**
 * Une initiale en minuscule, pour qu'un jour entre dans une phrase.
 *
 * Les libellés des jours sont capitalisés : ils habillent une liste
 * déroulante, où « Lundi » commence une ligne. Dans « vos questions
 * arriveront chaque semaine : mardi et vendredi », la majuscule serait une
 * faute. Aucune des trois langues servies n'impose la capitale au milieu
 * d'une phrase.
 */
export function uncapitalize(label: string): string {
    return label.charAt(0).toLocaleLowerCase() + label.slice(1);
}
