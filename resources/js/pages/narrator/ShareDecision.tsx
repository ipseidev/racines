import { router } from '@inertiajs/react';
import { useState } from 'react';

import { useT } from '@/hooks/useT';

type Decision = 'share' | 'keep_private' | 'decide_later';

type Props = {
    /** Où poster la décision : la page d'enregistrement connaît son jeton. */
    action: string;
    onDecided?: (decision: Decision) => void;
};

/*
 * Toujours dans cet ordre, jamais présélectionnés, sans minuteur.
 *
 * Le dossier est formel : l'absence de réaction ne vaut jamais accord. Le
 * troisième choix existe pour que le narrateur puisse ne pas choisir sans
 * que son silence soit interprété — le retirer transformerait une hésitation
 * en consentement.
 */
const DECISIONS: readonly Decision[] = [
    'share',
    'keep_private',
    'decide_later',
];

/**
 * Les trois choix de fin d'enregistrement (variante A).
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
        <section aria-labelledby="share-decision-title" className="mt-8">
            <h2
                id="share-decision-title"
                className="font-display text-2xl leading-tight font-semibold"
            >
                {t('narrator.share_decision.title')}
            </h2>

            <p className="mt-4">{t('narrator.share_decision.body')}</p>

            <div className="mt-8 flex flex-col gap-4">
                {DECISIONS.map((decision) => (
                    <button
                        key={decision}
                        type="button"
                        disabled={processing}
                        onClick={() => decide(decision)}
                        className="border-brand-muted/40 min-h-[2.75rem] rounded-md border px-6 py-4 text-left disabled:opacity-60"
                    >
                        <span className="block text-lg font-medium">
                            {t(`narrator.share_decision.${decision}.label`)}
                        </span>
                        <span className="text-brand-muted mt-1 block text-base">
                            {t(`narrator.share_decision.${decision}.hint`)}
                        </span>
                    </button>
                ))}
            </div>
        </section>
    );
}
