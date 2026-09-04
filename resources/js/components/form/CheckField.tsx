import { useId, type ReactNode } from 'react';

type Props = {
    checked: boolean;
    onChange: (checked: boolean) => void;
    label: ReactNode;
    hint?: ReactNode;
    error?: string;
    required?: boolean;
    name?: string;
};

/**
 * Une case à cocher et son texte, d'un seul tenant.
 *
 * Toute la ligne est cliquable. L'aide vient sous le libellé, en retrait ;
 * l'erreur, sous la case, là où l'œil est déjà.
 */
export function CheckField({
    checked,
    onChange,
    label,
    hint,
    error,
    required,
    name,
}: Props) {
    const id = useId();

    return (
        <div className="flex flex-col gap-1.5">
            <label
                htmlFor={id}
                className="flex cursor-pointer items-start gap-3"
            >
                <input
                    id={id}
                    type="checkbox"
                    name={name}
                    checked={checked}
                    required={required}
                    onChange={(event) => onChange(event.target.checked)}
                    aria-invalid={error !== undefined || undefined}
                    aria-describedby={
                        error !== undefined ? `${id}-error` : undefined
                    }
                    className="check mt-0.5"
                />
                <span>
                    {label}
                    {hint !== undefined && (
                        <span className="text-brand-muted mt-1 block text-base">
                            {hint}
                        </span>
                    )}
                </span>
            </label>

            {error !== undefined && (
                <p
                    id={`${id}-error`}
                    role="alert"
                    className="field-error enter pl-9"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
