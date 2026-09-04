import type { PropsWithChildren } from 'react';

type Props = {
    processing: boolean;
    /** Ce que dit le bouton pendant l'envoi : « Un instant… ». */
    waitingLabel: string;
    className?: string;
    disabled?: boolean;
};

/**
 * Le bouton qui envoie un formulaire, et qui le dit.
 *
 * Pendant l'envoi, il se désactive, montre une roue et change de texte : un
 * bouton qui reste identique après le clic fait cliquer une deuxième fois,
 * et une deuxième soumission est la première cause de doublons.
 */
export function SubmitButton({
    processing,
    waitingLabel,
    className = '',
    disabled = false,
    children,
}: PropsWithChildren<Props>) {
    return (
        <button
            type="submit"
            disabled={processing || disabled}
            aria-busy={processing || undefined}
            className={`btn-primary press disabled:opacity-70 ${className}`}
        >
            {processing ? (
                <>
                    <span className="spinner" aria-hidden="true" />
                    {waitingLabel}
                </>
            ) : (
                children
            )}
        </button>
    );
}
