import { router } from '@inertiajs/react';
import { useState } from 'react';

import { useT } from '@/hooks/useT';

type Decision = 'share' | 'keep_private' | 'decide_later';

type Props = {
    action: string;
    onDecided?: (decision: Decision) => void;
};

/*
 * Toujours dans cet ordre, jamais présélectionnés, sans minuteur : le
 * dossier est formel, l'absence de réaction ne vaut jamais accord.
 */
const DECISIONS: readonly Decision[] = [
    'share',
    'keep_private',
    'decide_later',
];

/**
 * Les trois choix de fin d'enregistrement (bloc 07), en trois cartes.
 *
 * Trois boutons de même taille et de même couleur : aucun n'est « le bon ».
 * Chacun dit sa conséquence en une phrase, au présent.
 */
export default function ShareDecision({ action, onDecided }: Props) {
    const t = useT();
    const [processing, setProcessing] = useState(false);

    const decide = (decision: Decision) => {
        setProcessing(true);
        router.post(
            action,
            { decision },
            {
                preserveScroll: true,
                onSuccess: () => onDecided?.(decision),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <section aria-labelledby="share-decision-title" className="mt-6">
            <h2
                id="share-decision-title"
                className="font-display text-[1.4rem] leading-tight font-medium"
            >
                {t('narrator.share_decision.title')}
            </h2>
            <p className="text-brand-muted record-optional mt-2 text-base">
                {t('narrator.share_decision.body')}
            </p>

            <div className="record-stack mt-4 flex flex-col gap-3">
                {DECISIONS.map((decision) => (
                    <DecisionButton
                        key={decision}
                        label={t(`narrator.share_decision.${decision}.label`)}
                        hint={t(`narrator.share_decision.${decision}.hint`)}
                        disabled={processing}
                        onClick={() => decide(decision)}
                    />
                ))}
            </div>
        </section>
    );
}

/** Une carte-bouton : le geste en gros, sa conséquence en dessous. */
export function DecisionButton({
    label,
    hint,
    disabled,
    onClick,
}: {
    label: string;
    hint: string;
    disabled?: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className="card press hover:border-brand min-h-[2.75rem] w-full px-5 py-3 text-left transition-[border-color,box-shadow] duration-200 disabled:opacity-60"
        >
            <span className="text-brand block text-lg font-semibold">
                {label}
            </span>
            <span className="text-brand-muted mt-0.5 block text-[0.95rem] leading-snug">
                {hint}
            </span>
        </button>
    );
}
