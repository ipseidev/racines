import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';
import type { Platform } from '@/recorder/platform';

type Props = {
    platform: Platform;
    canRetry: boolean;
    onRetry?: () => void;
    onWrite?: () => void;
};

/**
 * Quand le micro est refusé, ou que le navigateur ne sait pas enregistrer.
 *
 * Le chemin propre au téléphone est écrit en toutes lettres, et l'écrit
 * est toujours là comme issue : personne ne reste bloqué devant un réglage.
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

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {t('narrator.mic_help.title')}
            </h1>

            <p className="mt-5">
                {canRetry
                    ? t('narrator.mic_help.body')
                    : t('narrator.mic_help.unsupported')}
            </p>

            {canRetry ? (
                <ol className="panel mt-6 list-none">
                    <li className="flex items-start gap-3">
                        <span
                            aria-hidden="true"
                            className="bg-brand text-brand-foreground mt-0.5 flex size-7 flex-none items-center justify-center rounded-full text-[0.9rem] font-semibold"
                        >
                            1
                        </span>
                        <span>{t(`narrator.mic_help.${platform}`)}</span>
                    </li>
                </ol>
            ) : null}

            <div className="mt-8 flex flex-col gap-3">
                {canRetry && !retried ? (
                    <button
                        type="button"
                        onClick={() => {
                            setRetried(true);
                            onRetry?.();
                        }}
                        className="btn-primary press min-h-[2.75rem] w-full py-4 text-xl"
                    >
                        {t('narrator.mic_help.retry')}
                    </button>
                ) : null}

                <button
                    type="button"
                    onClick={() => onWrite?.()}
                    className="btn-secondary press w-full"
                >
                    {t('narrator.record.written_link')}
                </button>
            </div>

            <p className="text-brand-muted mt-10 text-base">
                {t('narrator.link_unavailable.help', {
                    email: brand.support_email,
                })}
            </p>
        </>
    );
}
