import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type ConsentText = {
    kind: string;
    label: string;
    version: string;
    effectiveFrom: string | null;
    body: string;
};

type Props = {
    texts: ConsentText[];
};

/**
 * Les accords, dans leur version **en vigueur**.
 *
 * Rendus depuis la base et non depuis un fichier : c'est la version que les
 * gens ont réellement acceptée qui doit s'afficher, et elle est datée. Un
 * texte de consentement qu'on réécrit sans versionner rend inopposable tout
 * ce qui a été accepté avant.
 */
export default function Consents({ texts }: Props) {
    const t = useT();

    return (
        <div className="mx-auto w-full max-w-3xl px-6 py-8 text-[1.125rem] leading-relaxed">
            <Head title={t('public.legal.consents')} />

            <h1 className="font-display text-3xl leading-tight font-semibold">
                {t('public.legal.consents')}
            </h1>

            <dl className="mt-8 flex flex-col gap-8">
                {texts.map((text) => (
                    <div key={text.kind}>
                        <dt className="font-medium">{text.label}</dt>
                        <dd className="mt-2">{text.body}</dd>
                        <dd className="text-brand-muted mt-1 text-base">
                            {t('public.legal.version', {
                                version: text.version,
                                date: text.effectiveFrom?.slice(0, 10) ?? '',
                            })}
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
