import { useId } from 'react';

/**
 * Un thème à cocher, à l'écran du plan.
 *
 * Le seul écran du quiz où l'on coche plutôt que de taper, et le seul qui
 * garde un « Continuer ». C'est voulu : trois gestes au lieu d'un font de cet
 * écran le moment où l'on s'engage, et l'ordre des clics décide ensuite de
 * l'ordre des premières questions.
 */
export function QuizCheck({
    label,
    checked,
    rank,
    onToggle,
}: {
    label: string;
    checked: boolean;
    /** Le rang du clic, affiché pour montrer que l'ordre compte. */
    rank: number | null;
    onToggle: () => void;
}) {
    const id = useId();

    return (
        <label
            htmlFor={id}
            className={`card press flex min-h-[3.5rem] cursor-pointer items-center gap-4 px-5 py-3.5 transition-[border-color,box-shadow] duration-200 ${
                checked
                    ? 'border-brand shadow-[0_0_0_1px_var(--color-brand)]'
                    : 'hover:border-brand/50'
            }`}
        >
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={onToggle}
                className="check flex-none"
            />

            <span className="min-w-0 flex-1 font-medium">{label}</span>

            {rank !== null && (
                <span
                    aria-hidden="true"
                    className="bg-brand text-brand-foreground flex size-6 flex-none items-center justify-center rounded-full text-[0.8rem] font-semibold tabular-nums"
                >
                    {rank}
                </span>
            )}
        </label>
    );
}
