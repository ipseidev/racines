import { useId, type ReactNode, type TextareaHTMLAttributes } from 'react';

import { describedBy, Field } from './Field';

type Props = Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'id'> & {
    id?: string;
    label: ReactNode;
    hint?: ReactNode;
    error?: string;
    /** Le texte du compteur, déjà mis en forme : « 120 caractères sur 600 ». */
    counter?: string;
};

/** Une zone de texte, avec son compteur quand la longueur est bornée. */
export function TextAreaField({
    id,
    label,
    hint,
    error,
    counter,
    className = '',
    ...textarea
}: Props) {
    const fallback = useId();
    const controlId = id ?? fallback;

    return (
        <Field id={controlId} label={label} hint={hint} error={error}>
            <textarea
                id={controlId}
                aria-invalid={error !== undefined || undefined}
                aria-describedby={describedBy(controlId, hint, error)}
                className={`input min-h-[8rem] resize-y leading-relaxed ${className}`}
                {...textarea}
            />
            {counter !== undefined && (
                <p
                    className="text-brand-muted -mt-0.5 text-right text-[0.9rem] tabular-nums"
                    aria-live="polite"
                >
                    {counter}
                </p>
            )}
        </Field>
    );
}
