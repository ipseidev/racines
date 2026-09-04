import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Props = {
    message: string | null;
};

/**
 * Le merci qui clôt un geste : partager, garder, décider plus tard.
 *
 * Une coche qui apparaît, une phrase, et rien à faire. On ne renvoie pas
 * ailleurs : la personne a fini, elle peut fermer.
 */
export default function Thanks({ message }: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('narrator.thanks.title')} />

            <div className="flex flex-col items-center pt-6 text-center">
                <span
                    aria-hidden="true"
                    className="bg-brand text-brand-foreground animate-pop-in flex size-16 items-center justify-center rounded-full"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        className="size-8"
                    >
                        <path d="m6 12 4 4 8-9" />
                    </svg>
                </span>

                <h1 className="font-display mt-6 text-3xl leading-tight font-medium">
                    {t('narrator.thanks.title')}
                </h1>

                <p role="status" className="mt-4 text-[1.25rem] leading-snug">
                    {message ?? t('narrator.thanks.body')}
                </p>
            </div>
        </>
    );
}
