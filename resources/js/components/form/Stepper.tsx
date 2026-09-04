import { Link } from '@inertiajs/react';

export type Step = {
    label: string;
    /** Où mène l'étape quand elle est déjà franchie. Sans lien, elle ne se rejoint pas. */
    href?: string;
};

type Props = {
    steps: Step[];
    /** L'étape en cours, à partir de 1. */
    current: number;
    /** Le nom de la progression pour les technologies d'assistance. */
    ariaLabel: string;
    /** « Étape :step sur :total », déjà mis en forme pour l'étape en cours. */
    ofLabel: string;
};

function Check() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.5"
            aria-hidden="true"
            className="size-4"
        >
            <path d="m6 12 4 4 8-9" />
        </svg>
    );
}

/**
 * La progression d'un parcours en étapes.
 *
 * Sur téléphone : « Étape 2 sur 6 », le nom de l'étape, une barre qui avance.
 * Sur bureau : les six noms, les étapes franchies cochées et cliquables, la
 * courante marquée. On ne saute jamais en avant : une étape qu'on n'a pas
 * franchie n'est pas un lien.
 */
export function Stepper({ steps, current, ariaLabel, ofLabel }: Props) {
    const percent = Math.round((current / steps.length) * 100);

    return (
        <nav aria-label={ariaLabel} className="flex flex-col gap-3">
            <p className="text-brand-muted flex items-baseline justify-between text-base lg:hidden">
                <span>{ofLabel}</span>
                <span className="text-brand-text font-medium">
                    {steps[current - 1]?.label}
                </span>
            </p>

            <ol className="hidden items-center gap-2 lg:flex">
                {steps.map((step, index) => {
                    const number = index + 1;
                    const done = number < current;
                    const active = number === current;

                    const marker = (
                        <span
                            className={`flex size-7 flex-none items-center justify-center rounded-full text-[0.85rem] font-semibold tabular-nums transition-colors duration-300 ${
                                done || active
                                    ? 'bg-brand text-brand-foreground'
                                    : 'border-brand-sand text-brand-muted border-[1.5px]'
                            }`}
                        >
                            {done ? <Check /> : number}
                        </span>
                    );

                    return (
                        <li
                            key={step.label}
                            aria-current={active ? 'step' : undefined}
                            className="flex items-center gap-2"
                        >
                            {done && step.href !== undefined ? (
                                <Link
                                    href={step.href}
                                    className="hover:text-brand flex items-center gap-2 text-[0.95rem]"
                                >
                                    {marker}
                                    <span className="whitespace-nowrap">
                                        {step.label}
                                    </span>
                                </Link>
                            ) : (
                                <span
                                    className={`flex items-center gap-2 text-[0.95rem] ${
                                        active
                                            ? 'text-brand-text font-semibold'
                                            : 'text-brand-muted'
                                    }`}
                                >
                                    {marker}
                                    <span className="whitespace-nowrap">
                                        {step.label}
                                    </span>
                                </span>
                            )}
                            {number < steps.length && (
                                <span
                                    aria-hidden="true"
                                    className={`h-px w-6 ${done ? 'bg-brand' : 'bg-brand-sand'}`}
                                />
                            )}
                        </li>
                    );
                })}
            </ol>

            <div
                className="bg-brand-sand h-1 w-full overflow-hidden rounded-full"
                aria-hidden="true"
            >
                <div
                    data-testid="stepper-progress"
                    className="bg-brand ease-soft h-full rounded-full transition-[width] duration-700"
                    style={{ width: `${percent}%` }}
                />
            </div>
        </nav>
    );
}
