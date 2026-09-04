import {
    useId,
    useState,
    type InputHTMLAttributes,
    type ReactNode,
} from 'react';

import { describedBy, Field } from './Field';

type Props = Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'type'> & {
    id?: string;
    label: ReactNode;
    hint?: ReactNode;
    error?: string;
    showLabel: string;
    hideLabel: string;
};

/**
 * Un mot de passe qu'on peut afficher.
 *
 * Un seul champ et un bouton « Afficher », plutôt qu'une confirmation : voir ce
 * qu'on a tapé attrape la faute de frappe aussi bien, et ne fait pas saisir
 * deux fois la même chose sur un clavier de téléphone.
 */
export function PasswordField({
    id,
    label,
    hint,
    error,
    showLabel,
    hideLabel,
    className = '',
    ...input
}: Props) {
    const fallback = useId();
    const controlId = id ?? fallback;
    const [visible, setVisible] = useState(false);

    return (
        <Field id={controlId} label={label} hint={hint} error={error}>
            <div className="relative">
                <input
                    id={controlId}
                    type={visible ? 'text' : 'password'}
                    aria-invalid={error !== undefined || undefined}
                    aria-describedby={describedBy(controlId, hint, error)}
                    className={`input pr-24 ${className}`}
                    {...input}
                />
                <button
                    type="button"
                    onClick={() => setVisible((value) => !value)}
                    aria-pressed={visible}
                    className="text-brand hover:bg-brand/5 absolute top-1/2 right-1.5 min-h-[2.25rem] -translate-y-1/2 rounded px-3 text-[0.95rem] font-medium transition-colors"
                >
                    {visible ? hideLabel : showLabel}
                </button>
            </div>
        </Field>
    );
}
