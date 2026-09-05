import { useEffect, useId, useRef } from 'react';

/*
 * Une confirmation avant un geste qui engage : retirer un accès, se rétracter.
 * Le focus va d'abord sur « Annuler » : on ne confirme pas par réflexe. Échap
 * ferme, un clic hors de la carte aussi.
 */
type Props = {
    open: boolean;
    title: string;
    body: string;
    confirmLabel: string;
    cancelLabel: string;
    onConfirm: () => void;
    onCancel: () => void;
    processing?: boolean;
};

export function ConfirmDialog({
    open,
    title,
    body,
    confirmLabel,
    cancelLabel,
    onConfirm,
    onCancel,
    processing = false,
}: Props) {
    const id = useId();
    const cancelRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        cancelRef.current?.focus();

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onCancel();
            }
        };

        document.addEventListener('keydown', onKey);

        return () => document.removeEventListener('keydown', onKey);
    }, [open, onCancel]);

    if (!open) {
        return null;
    }

    return (
        <div
            className="bg-brand-deep/40 fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
            onClick={onCancel}
        >
            <div
                role="alertdialog"
                aria-modal="true"
                aria-labelledby={`${id}-title`}
                aria-describedby={`${id}-body`}
                onClick={(event) => event.stopPropagation()}
                className="card enter w-full max-w-md p-6 shadow-xl"
            >
                <h2
                    id={`${id}-title`}
                    className="font-display text-brand text-xl leading-snug font-medium"
                >
                    {title}
                </h2>

                <p id={`${id}-body`} className="mt-3">
                    {body}
                </p>

                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        ref={cancelRef}
                        type="button"
                        onClick={onCancel}
                        className="btn-secondary press"
                    >
                        {cancelLabel}
                    </button>

                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        aria-busy={processing || undefined}
                        className="btn-primary press disabled:opacity-70"
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
