import type { ButtonHTMLAttributes, PropsWithChildren } from 'react';

/*
 * Un bouton rond de 44 px pour un seul geste : monter, descendre, retirer. Le
 * libellé est lu par les lecteurs d'écran et montré au survol ; l'icône seule
 * ne suffit jamais.
 */
type Props = PropsWithChildren<
    Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'type'> & { label: string }
>;

export function IconButton({
    label,
    className = '',
    children,
    ...rest
}: Props) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            className={`press border-brand-sand bg-brand-surface text-brand hover:bg-brand/5 inline-flex size-11 flex-none items-center justify-center rounded-full border transition-colors disabled:cursor-not-allowed disabled:opacity-40 ${className}`}
            {...rest}
        >
            {children}
        </button>
    );
}
