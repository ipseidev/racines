import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Props = {
    tokenType: string;
    subjectType: string | null;
    subjectId: string | null;
    scope: string[];
    expiresAt: string | null;
};

/**
 * Page de vérification du bloc 03 : elle prouve qu'un lien résolu arrive au
 * contrôleur avec son sujet. Le bloc 04 la remplace par la page
 * d'enregistrement.
 *
 * Elle n'affiche aucune donnée personnelle : un lien porteur ne doit pas
 * révéler à qui il a été envoyé.
 */
export default function TokenProbe({
    tokenType,
    subjectType,
    subjectId,
    expiresAt,
}: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('narrator.probe.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.probe.title')}
            </h1>

            <p className="mt-6">{t('narrator.probe.body')}</p>

            <dl className="mt-8 space-y-4">
                <div>
                    <dt className="text-brand-muted text-base">
                        {t('narrator.probe.token_type')}
                    </dt>
                    <dd className="font-medium">{tokenType}</dd>
                </div>

                <div>
                    <dt className="text-brand-muted text-base">
                        {t('narrator.probe.subject')}
                    </dt>
                    <dd className="font-medium">
                        {subjectType} {subjectId}
                    </dd>
                </div>

                {expiresAt !== null ? (
                    <div>
                        <dt className="text-brand-muted text-base">
                            {t('narrator.probe.expires_at')}
                        </dt>
                        <dd className="font-medium">{expiresAt}</dd>
                    </div>
                ) : null}
            </dl>
        </>
    );
}
