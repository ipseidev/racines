import type { PropsWithChildren, ReactNode } from 'react';

/*
 * L'en-tête d'une page de l'espace : l'œillet qui dit où l'on est, le titre en
 * Fraunces, une ligne qui dit ce qu'on va y faire. Le même sur les cinq pages,
 * pour que l'espace se lise comme une seule maison.
 */
type Props = PropsWithChildren<{
    eyebrow: string;
    title: string;
    intro?: ReactNode;
}>;

export function PageHeader({ eyebrow, title, intro, children }: Props) {
    return (
        <header>
            <p className="eyebrow">{eyebrow}</p>

            <h1 className="font-display mt-3 text-[2rem] leading-[1.1] font-semibold sm:text-[2.375rem]">
                {title}
            </h1>

            {intro !== undefined && (
                <div className="text-brand-muted mt-3 text-base">{intro}</div>
            )}

            {children}
        </header>
    );
}
