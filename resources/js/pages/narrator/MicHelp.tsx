import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';
import type { Platform } from '@/recorder/platform';

type Props = {
    platform: Platform;
    /** Faux quand le navigateur ne sait pas enregistrer du tout. */
    canRetry: boolean;
    onRetry?: () => void;
    onWrite?: () => void;
};

/**
 * Le micro a été refusé.
 *
 * Le dossier prévient : le refus du micro par des seniors est un risque
 * identifié. On ne renvoie donc pas vers une page d'aide générique — on montre
 * le chemin exact sur *son* téléphone, on propose un seul nouvel essai, et on
 * offre toujours l'écrit comme porte de sortie.
 */
export default function MicHelp({
    platform,
    canRetry,
    onRetry,
    onWrite,
}: Props) {
    const t = useT();
    const brand = useBrand();
    const [retried, setRetried] = useState(false);

    return (
        <>
            <Head title={t('narrator.mic_help.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.mic_help.title')}
            </h1>

            <p className="mt-6">
                {canRetry
                    ? t('narrator.mic_help.body')
                    : t('narrator.mic_help.unsupported')}
            </p>

            {canRetry ? (
                <p className="bg-brand-accent text-brand-accent-foreground mt-6 rounded-md px-4 py-4">
                    {t(`narrator.mic_help.${platform}`)}
                </p>
            ) : null}

            {canRetry && !retried ? (
                <button
                    type="button"
                    onClick={() => {
                        setRetried(true);
                        onRetry?.();
                    }}
                    className="bg-brand text-brand-foreground mt-8 min-h-[2.75rem] w-full rounded-md px-6 py-3 text-lg font-medium"
                >
                    {t('narrator.mic_help.retry')}
                </button>
            ) : null}

            <button
                type="button"
                onClick={() => onWrite?.()}
                className="border-brand-muted/40 mt-4 min-h-[2.75rem] w-full rounded-md border px-6 py-3 text-lg font-medium"
            >
                {t('narrator.record.written_link')}
            </button>

            <p className="text-brand-muted mt-10 text-base">
                {t('narrator.link_unavailable.help', {
                    email: brand.support_email,
                })}
            </p>
        </>
    );
}
