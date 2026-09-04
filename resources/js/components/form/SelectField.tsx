import { useId, type ReactNode } from 'react';

import { describedBy, Field } from './Field';

export type Option = { value: string; label: string };

type Props = {
    id?: string;
    label: ReactNode;
    hint?: ReactNode;
    error?: string;
    options: Option[];
    value: string;
    onChange: (value: string) => void;
    name?: string;
};

/**
 * Une liste déroulante native, habillée.
 *
 * Native, parce que le sélecteur du téléphone est le meilleur qui existe pour
 * une liste courte ; habillée, parce que la flèche grise du navigateur n'est
 * pas dans la charte.
 */
export function SelectField({
    id,
    label,
    hint,
    error,
    options,
    value,
    onChange,
    name,
}: Props) {
    const fallback = useId();
    const controlId = id ?? fallback;

    return (
        <Field id={controlId} label={label} hint={hint} error={error}>
            <select
                id={controlId}
                name={name}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                aria-invalid={error !== undefined || undefined}
                aria-describedby={describedBy(controlId, hint, error)}
                className="select"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </Field>
    );
}
