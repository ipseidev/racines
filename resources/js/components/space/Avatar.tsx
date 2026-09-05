/*
 * Le médaillon d'un proche : ses initiales sur lin. Pas de photo à ce stade,
 * et un médaillon vide se remarque plus qu'un médaillon absent.
 */
export function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .filter((part) => part !== '')
        .slice(0, 2)
        .map((part) => part[0].toLocaleUpperCase('fr-FR'))
        .join('');
}

export function Avatar({ name }: { name: string }) {
    return (
        <span
            aria-hidden="true"
            className="bg-brand-linen text-brand font-display inline-flex size-11 flex-none items-center justify-center rounded-full text-lg font-semibold"
        >
            {initials(name)}
        </span>
    );
}
