import { useId, type ReactNode } from 'react';

import { describedBy, Field } from './Field';

type Props = {
    id?: string;
    label: ReactNode;
    hint?: ReactNode;
    error?: string;
    value: number;
    min?: number;
    max?: number;
    onChange: (value: number) => void;
    decrementLabel: string;
    incrementLabel: string;
    name?: string;
};

function clamp(value: number, min: number, max: number): number {
    if (Number.isNaN(value)) {
        return min;
    }

    return Math.min(max, Math.max(min, Math.trunc(value)));
}

/**
 * Un nombre entier borné : moins, la valeur, plus.
 *
 * Le champ reste un vrai champ numérique, qu'on peut remplir au clavier ; les
 * deux boutons sont là pour le pouce. Aux bornes, le bouton qui ne mènerait
 * nulle part se désactive plutôt que d'ignorer le clic en silence.
 */
export function Counter({
    id,
    label,
    hint,
    error,
    value,
    min = 0,
    max = Number.MAX_SAFE_INTEGER,
    onChange,
    decrementLabel,
    incrementLabel,
    name,
}: Props) {
    const fallback = useId();
    const controlId = id ?? fallback;
    const set = (next: number) => onChange(clamp(next, min, max));

    const button =
        'border-brand-sand bg-brand-surface text-brand hover:border-brand hover:bg-brand/5 press flex size-11 flex-none items-center justify-center rounded-md border text-2xl leading-none font-medium disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-brand-sand disabled:hover:bg-brand-surface';

    return (
        <Field id={controlId} label={label} hint={hint} error={error}>
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => set(value - 1)}
                    disabled={value <= min}
                    aria-label={decrementLabel}
                    className={button}
                >
                    −
                </button>
                <input
                    id={controlId}
                    name={name}
                    type="number"
                    inputMode="numeric"
                    min={min}
                    max={max}
                    value={value}
                    onChange={(event) => set(Number(event.target.value))}
                    aria-invalid={error !== undefined || undefined}
                    aria-describedby={describedBy(controlId, hint, error)}
                    className="input w-20 text-center text-xl tabular-nums"
                />
                <button
                    type="button"
                    onClick={() => set(value + 1)}
                    disabled={value >= max}
                    aria-label={incrementLabel}
                    className={button}
                >
                    +
                </button>
            </div>
        </Field>
    );
}
