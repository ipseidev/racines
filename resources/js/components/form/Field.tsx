import type { PropsWithChildren, ReactNode } from 'react';

export type FieldProps = {
    /** L'identifiant du contrôle : le libellé, l'aide et l'erreur s'y rattachent. */
    id: string;
    label: ReactNode;
    hint?: ReactNode;
    error?: string;
};

/** Les identifiants à donner en `aria-describedby` au contrôle d'un champ. */
export function describedBy(
    id: string,
    hint: unknown,
    error: unknown,
): string | undefined {
    const ids = [
        hint !== undefined && hint !== null ? `${id}-hint` : null,
        error !== undefined && error !== null ? `${id}-error` : null,
    ].filter((value): value is string => value !== null);

    return ids.length > 0 ? ids.join(' ') : undefined;
}

/**
 * Un champ de formulaire : libellé, contrôle, aide, erreur, dans cet ordre.
 *
 * Le libellé est au-dessus et non flottant : une personne de soixante ans qui
 * remplit le tunnel sur son téléphone ne doit pas deviner ce qu'un champ
 * attendait une fois qu'elle a commencé à écrire. L'erreur entre en fondu, à
 * l'endroit du champ, jamais dans une liste en haut de page.
 */
export function Field({
    id,
    label,
    hint,
    error,
    children,
}: PropsWithChildren<FieldProps>) {
    return (
        <div className="flex flex-col gap-1.5">
            <label htmlFor={id} className="font-medium">
                {label}
            </label>

            {children}

            {hint !== undefined && (
                <p id={`${id}-hint`} className="text-brand-muted text-base">
                    {hint}
                </p>
            )}

            {error !== undefined && (
                <p
                    id={`${id}-error`}
                    role="alert"
                    className="field-error enter"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
