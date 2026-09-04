import { useId, type ReactNode } from 'react';

type Props = {
    name: string;
    value: string;
    checked: boolean;
    onChange: (value: string) => void;
    title: ReactNode;
    hint?: ReactNode;
};

/**
 * Un choix parmi quelques-uns, présenté comme une carte.
 *
 * Le bouton radio reste un vrai bouton radio, dessiné dans la charte : le
 * clavier, le lecteur d'écran et les tests le voient comme tel. La carte
 * entière est cliquable, parce qu'un rond de 24 px est une cible trop petite
 * pour un pouce.
 */
export function ChoiceCard({
    name,
    value,
    checked,
    onChange,
    title,
    hint,
}: Props) {
    const id = useId();

    return (
        <label
            htmlFor={id}
            className={`card press flex cursor-pointer items-start gap-4 px-5 py-4 transition-[border-color,box-shadow] duration-200 ${
                checked
                    ? 'border-brand shadow-[0_0_0_1px_var(--color-brand)]'
                    : 'hover:border-brand/50'
            }`}
        >
            <input
                id={id}
                type="radio"
                name={name}
                value={value}
                checked={checked}
                onChange={() => onChange(value)}
                className="radio mt-0.5"
            />
            <span className="flex flex-col gap-0.5">
                <span className="font-semibold">{title}</span>
                {hint !== undefined && (
                    <span className="text-brand-muted text-base">{hint}</span>
                )}
            </span>
        </label>
    );
}
