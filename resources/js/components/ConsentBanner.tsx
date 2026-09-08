import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import { useT } from '@/hooks/useT';
import {
    CONSENT_OPEN,
    readConsent,
    rememberConsent,
    type Consent,
} from '@/lib/consent';

type Shared = {
    analytics: { key: string; host: string } | null;
    googleAnalytics: { measurementId: string } | null;
    metaPixel: { pixelId: string } | null;
};

/**
 * Le bandeau de consentement aux cookies (T-227).
 *
 * Ce qu'exige la CNIL, et pourquoi chaque point est là :
 *  - **deux boutons de même poids**, refuser aussi visible qu'accepter — un
 *    « refuser » en petit gris n'est pas un choix, c'est une manœuvre ;
 *  - **rien de pré-coché**, rien qui parte avant la réponse ;
 *  - **le choix se retire aussi simplement qu'il se donne** : le pied de page
 *    rouvre ce bandeau ;
 *  - un bandeau, pas un mur : la page reste lisible et utilisable derrière.
 *
 * Il n'apparaît que là où le serveur a donné une mesure à démarrer : les
 * mêmes props que `useAnalytics`. Une page à jeton ne mesure rien et ne
 * demande donc rien — un bandeau sur la page d'un narrateur de quatre-vingt-
 * cinq ans qui n'a rien à consentir serait un obstacle, pas une protection.
 *
 * L'ouverture se décide dans un effet, jamais au rendu : le serveur ne
 * connaît pas le cookie, et un bandeau rendu côté serveur puis retiré au
 * montage ferait un écart d'hydratation.
 */
export default function ConsentBanner() {
    const t = useT();
    const { analytics, googleAnalytics, metaPixel } = usePage<Shared>().props;
    const [open, setOpen] = useState(false);
    const heading = useRef<HTMLHeadingElement>(null);

    const measured =
        analytics !== null || googleAnalytics !== null || metaPixel !== null;

    useEffect(() => {
        if (!measured) {
            return;
        }

        if (readConsent() === null) {
            setOpen(true);
        }

        const reopen = () => {
            setOpen(true);
            // Le focus va au titre : qui rouvre le bandeau depuis le pied de
            // page doit savoir où il est arrivé, sans chercher.
            requestAnimationFrame(() => heading.current?.focus());
        };

        window.addEventListener(CONSENT_OPEN, reopen);

        return () => window.removeEventListener(CONSENT_OPEN, reopen);
    }, [measured]);

    if (!open) {
        return null;
    }

    const choose = (choice: Consent) => {
        rememberConsent(choice);
        setOpen(false);
    };

    const button =
        'border-brand text-brand hover:bg-brand/5 inline-flex min-h-[2.75rem] flex-1 items-center justify-center rounded-md border-2 px-5 text-[1rem] font-semibold sm:flex-none';

    return (
        <section
            role="region"
            aria-labelledby="consent-title"
            className="border-brand-sand bg-brand-surface text-brand-text fixed inset-x-0 bottom-0 z-40 border-t shadow-[0_-8px_30px_rgba(38,33,28,0.12)]"
        >
            <div className="mx-auto flex w-full max-w-[74rem] flex-col gap-4 px-5 py-5 sm:px-8 lg:flex-row lg:items-center lg:gap-8 lg:px-10">
                <div className="flex flex-1 flex-col gap-1.5">
                    <h2
                        id="consent-title"
                        ref={heading}
                        tabIndex={-1}
                        className="text-brand text-[1.05rem] font-semibold outline-none"
                    >
                        {t('public.consent.title')}
                    </h2>
                    <p className="text-brand-muted text-[0.95rem] leading-relaxed">
                        {t('public.consent.body')}{' '}
                        <Link
                            href="/confidentialite"
                            className="text-brand font-semibold underline decoration-2 underline-offset-4"
                        >
                            {t('public.consent.more')}
                        </Link>
                    </p>
                </div>

                <div className="flex gap-3">
                    <button
                        type="button"
                        onClick={() => choose('denied')}
                        className={button}
                    >
                        {t('public.consent.refuse')}
                    </button>
                    <button
                        type="button"
                        onClick={() => choose('granted')}
                        className={button}
                    >
                        {t('public.consent.accept')}
                    </button>
                </div>
            </div>
        </section>
    );
}
