import type { ReactNode } from 'react';

type Props = {
    image: string;
    imageAlt: string;
    title: string;
    /** Le prix, déjà mis en forme : « 45 € l’exemplaire ». */
    price: string;
    /** Un prix barré à côté du prix : « au lieu de 45 € ». */
    regularPrice?: string;
    /** `contain` pour une capture d'écran qu'il ne faut pas rogner. */
    imageFit?: 'cover' | 'contain';
    body: string;
    added: boolean;
    onAdd: () => void;
    onRemove: () => void;
    addLabel: string;
    removeLabel: string;
    addedLabel: string;
    /** Une pastille au-dessus du titre : « Recommandé pour elle ». */
    recommended?: string;
    /** Fermée : le bouton est remplacé par ce texte. */
    closed?: string;
    /** Ce qui s'affiche une fois l'option ajoutée (un compteur, par exemple). */
    children?: ReactNode;
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
 * Une option à ajouter à la commande, comme chez le leader : une image, un
 * titre, un prix, ce que ça apporte, et un seul bouton.
 *
 * Ajoutée, la carte le dit et prend le contour de la marque ; le bouton
 * devient « Retirer », discret : on ne vend pas deux fois. Fermée, elle le dit
 * aussi, plutôt que de disparaître et de laisser croire qu'elle n'a jamais
 * existé.
 */
export function OptionCard({
    image,
    imageAlt,
    title,
    price,
    regularPrice,
    imageFit = 'cover',
    body,
    added,
    onAdd,
    onRemove,
    addLabel,
    removeLabel,
    addedLabel,
    recommended,
    closed,
    children,
}: Props) {
    return (
        <article
            aria-label={title}
            className={`card flex flex-col gap-5 p-5 transition-[border-color,box-shadow] duration-200 sm:flex-row ${
                added
                    ? 'border-brand shadow-[0_0_0_1px_var(--color-brand)]'
                    : ''
            }`}
        >
            <img
                src={image}
                alt={imageAlt}
                width="600"
                height="600"
                loading="lazy"
                className={`aspect-square w-full flex-none rounded-lg sm:w-32 ${
                    imageFit === 'contain'
                        ? 'bg-brand-linen object-contain p-3'
                        : 'object-cover'
                }`}
            />

            <div className="flex min-w-0 flex-1 flex-col gap-3">
                {recommended !== undefined && closed === undefined && (
                    <span className="chip w-fit">{recommended}</span>
                )}

                <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                    <h3 className="font-display text-brand text-2xl leading-tight font-medium">
                        {title}
                    </h3>
                    <p className="font-semibold tabular-nums sm:flex-none sm:text-right">
                        {price}
                        {regularPrice !== undefined && (
                            <span className="text-brand-muted block text-[0.9rem] font-normal">
                                <s>{regularPrice}</s>
                            </span>
                        )}
                    </p>
                </div>

                <p className="text-brand-muted text-base">{body}</p>

                {closed !== undefined ? (
                    <p className="text-brand-muted text-base font-medium">
                        {closed}
                    </p>
                ) : added ? (
                    <div className="enter flex flex-wrap items-center gap-4">
                        <span className="bg-brand text-brand-foreground inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[0.9rem] font-semibold">
                            <Check />
                            {addedLabel}
                        </span>
                        {children}
                        <button
                            type="button"
                            onClick={onRemove}
                            aria-label={`${removeLabel} : ${title}`}
                            className="text-brand-muted hover:text-brand min-h-[2.75rem] text-base underline underline-offset-4 transition-colors"
                        >
                            {removeLabel}
                        </button>
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={onAdd}
                        aria-label={`${addLabel} : ${title}`}
                        className="btn-secondary press min-h-[2.75rem] w-fit px-5 text-base"
                    >
                        {addLabel}
                    </button>
                )}
            </div>
        </article>
    );
}
