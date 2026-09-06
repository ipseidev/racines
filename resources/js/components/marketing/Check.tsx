/**
 * Une coche dans la couleur de marque, jamais dans celle de l'action.
 *
 * Décalée d'un cran vers le bas par défaut, pour s'aligner sur la première
 * ligne d'un texte ; dans une pastille, on lui retire ce décalage, sinon elle
 * n'est plus au centre.
 */
export function Check({
    light = false,
    className = 'mt-1',
}: {
    light?: boolean;
    className?: string;
}) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            aria-hidden="true"
            className={`${className} size-[22px] flex-none ${light ? 'text-brand-gold' : 'text-brand'}`}
        >
            <circle cx="12" cy="12" r="10" />
            <path d="m8 12 3 3 5-6" />
        </svg>
    );
}

/** Le petit cadenas des lignes de réassurance. */
export function Lock() {
    return (
        <svg
            viewBox="0 0 16 16"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            aria-hidden="true"
            className="size-4 flex-none"
        >
            <rect x="3" y="7" width="10" height="7" rx="1.5" />
            <path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" />
        </svg>
    );
}
