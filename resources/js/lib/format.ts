/**
 * Durée lisible pour un narrateur : « 9 s », « 1 min 05 s ».
 * Les pages narrateur n'affichent jamais de compte à rebours (PRD US-06),
 * seulement du temps écoulé.
 */
export function formatDuration(seconds: number): string {
    const total = Math.max(0, Math.floor(seconds));

    if (total < 60) {
        return `${total} s`;
    }

    const minutes = Math.floor(total / 60);
    const rest = String(total % 60).padStart(2, '0');

    return `${minutes} min ${rest} s`;
}
