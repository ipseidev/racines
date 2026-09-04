import { useId, type InputHTMLAttributes, type ReactNode } from 'react';

import { describedBy, Field } from './Field';

type Props = Omit<InputHTMLAttributes<HTMLInputElement>, 'id'> & {
    id?: string;
    label: ReactNode;
    hint?: ReactNode;
    error?: string;
};

/** Un champ texte, courriel, téléphone ou date, dans la charte. */
export function TextField({
    id,
    label,
    hint,
    error,
    className = '',
    ...input
}: Props) {
    const fallback = useId();
    const controlId = id ?? fallback;

    return (
        <Field id={controlId} label={label} hint={hint} error={error}>
            <input
                id={controlId}
                aria-invalid={error !== undefined || undefined}
                aria-describedby={describedBy(controlId, hint, error)}
                className={`input ${className}`}
                {...input}
            />
        </Field>
    );
}
