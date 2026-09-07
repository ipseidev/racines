import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';
import type { RecordingKind } from '@/recorder/mime';
import type { Platform } from '@/recorder/platform';

type Props = {
    platform: Platform;
    canRetry: boolean;
    /** Voix ou vidéo : le réglage à ouvrir n'est pas le même (T-210). */
    kind?: RecordingKind;
    onRetry?: () => void;
    onWrite?: () => void;
};

/**
 * Quand l'autorisation est refusée, ou que le navigateur ne sait pas
 * enregistrer.
 *
 * Le chemin propre au téléphone est écrit en toutes lettres, et l'écrit
 * est toujours là comme issue : personne ne reste bloqué devant un réglage.
 * Envoyer quelqu'un dans « Réglages › Safari › Micro » alors que c'est la
 * caméra qu'il a refusée le laisserait tourner en rond, d'où les deux jeux
 * de textes.
 */
export default function MicHelp({
    platform,
    canRetry,
    kind = 'audio',
    onRetry,
    onWrite,
}: Props) {
    const t = useT();
    const brand = useBrand();
    const [retried, setRetried] = useState(false);
    const prefix =
        kind === 'video' ? 'narrator.camera_help' : 'narrator.mic_help';

    return (
        <>
            <Head title={t(`${prefix}.title`)} />

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {t(`${prefix}.title`)}
            </h1>

            <p className="mt-5">
                {canRetry ? t(`${prefix}.body`) : t(`${prefix}.unsupported`)}
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
                        <span>{t(`${prefix}.${platform}`)}</span>
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
                        {t(`${prefix}.retry`)}
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
