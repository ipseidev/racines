import type { PropsWithChildren } from 'react';

/*
 * Une pastille d'état : un point de couleur et un mot. Sauge pour ce qui va,
 * or pour ce qui attend un geste, marque pour ce qui est acquis, sable pour
 * ce qui dort. Jamais la couleur d'action : une pastille ne se presse pas.
 */
export type PillTone = 'sage' | 'gold' | 'brand' | 'muted';

const DOT: Record<PillTone, string> = {
    sage: 'bg-brand-sage',
    gold: 'bg-brand-gold',
    brand: 'bg-brand',
    muted: 'bg-brand-sand',
};

type Props = PropsWithChildren<{ tone?: PillTone; className?: string }>;

export function Pill({ tone = 'sage', className = '', children }: Props) {
    return (
        <span
            className={`border-brand-sand bg-brand-surface text-brand inline-flex items-center gap-2 rounded-full border-[1.5px] px-3 py-1 text-[0.85rem] font-medium ${className}`}
        >
            <span
                aria-hidden="true"
                className={`size-2 flex-none rounded-full ${DOT[tone]}`}
            />
            {children}
        </span>
    );
}
