/**
 * Une durée en minutes et secondes, pour l'afficher.
 *
 * Partagée par le compteur de la page d'enregistrement et par celui de
 * l'écran caméra : deux copies auraient fini par diverger d'une seconde, et
 * c'est le genre d'écart qu'on ne remarque qu'en capture d'écran.
 */
export function formatDuration(seconds: number): string {
    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;

    return `${minutes}:${String(rest).padStart(2, '0')}`;
}
