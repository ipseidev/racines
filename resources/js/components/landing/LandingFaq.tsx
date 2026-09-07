import { Link } from '@inertiajs/react';
import { useEffect, type ReactNode } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { BAND, H2, Section, SHELL } from '@/components/landing/primitives';
import { track } from '@/components/landing/track';
import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';

/*
 * L'ordre est celui des questions qu'on se pose avant d'offrir : ce que ça
 * comprend, ce que ça engage, ce que le proche aura à faire, ce qu'on peut
 * corriger, qui écoute, ce qui reste après. Les deux dernières — la fin de
 * service et la garantie — ferment la page sur nos engagements.
 */
const QUESTIONS = [
    'included',
    'subscription',
    'no_app',
    'questions',
    'edit',
    'privacy',
    'after_year',
    'no_smartphone',
    'refuses',
    'date',
    'protection',
    'shutdown',
    'guarantee',
] as const;

/**
 * S20 — Les questions fréquentes.
 *
 * Des `<details>` natifs, et non un composant d'accordéon : ils s'ouvrent au
 * clavier sans une ligne de JavaScript, leur contenu est **dans le HTML** donc
 * lisible par un moteur et par qui n'a pas de JavaScript, et plusieurs
 * réponses peuvent rester ouvertes en même temps.
 *
 * Les liens profonds ouvrent la réponse visée : `#faq-shutdown` depuis S08 doit
 * afficher l'engagement de fin de service, pas seulement faire défiler jusqu'à
 * son titre replié. C'est le seul JavaScript de la section.
 */
export default function LandingFaq({
    variant,
    price,
}: {
    variant: string;
    price: number;
}) {
    const t = useT();
    const brand = useBrand();

    useEffect(() => {
        const open = () => {
            const hash = window.location.hash;

            if (!hash.startsWith('#faq-')) {
                return;
            }

            const target = document.getElementById(hash.slice(1));

            if (target instanceof HTMLDetailsElement) {
                target.open = true;
                target.scrollIntoView({ block: 'start' });
            }
        };

        open();
        window.addEventListener('hashchange', open);

        return () => window.removeEventListener('hashchange', open);
    }, []);

    // Les liens que certaines réponses portent : la page dédiée existe, et la
    // réponse ne la remplace pas.
    const EXTRA: Partial<Record<(typeof QUESTIONS)[number], ReactNode>> = {
        no_smartphone: (
            <a
                href={`mailto:${brand.support_email}`}
                className="text-brand inline-flex min-h-[2.75rem] items-center font-semibold underline decoration-2 underline-offset-4"
            >
                {t('public.lp.faq.no_smartphone.link')}
            </a>
        ),
        protection: (
            <>
                <Link
                    href="/confidentialite"
                    className="text-brand inline-flex min-h-[2.75rem] items-center font-semibold underline decoration-2 underline-offset-4"
                >
                    {t('public.lp.faq.protection.privacy_link')}
                </Link>
                {' · '}
                <Link
                    href="/consentements"
                    className="text-brand inline-flex min-h-[2.75rem] items-center font-semibold underline decoration-2 underline-offset-4"
                >
                    {t('public.lp.faq.protection.consents_link')}
                </Link>
            </>
        ),
        guarantee: (
            <Link
                href="/cgv"
                className="text-brand inline-flex min-h-[2.75rem] items-center font-semibold underline decoration-2 underline-offset-4"
            >
                {t('public.lp.faq.guarantee.link')}
            </Link>
        ),
    };

    return (
        <Section
            id="questions-frequentes"
            tone="white"
            labelledBy="lp-faq"
            className={BAND}
        >
            <div
                className={`${SHELL} grid gap-8 lg:grid-cols-[4fr_8fr] lg:gap-14`}
            >
                <h2 id="lp-faq" className={`${H2} max-w-[18em]`}>
                    {t('public.lp.faq.title')}
                </h2>

                <div className="border-brand-sand divide-brand-sand divide-y border-y">
                    {QUESTIONS.map((key) => (
                        <details
                            key={key}
                            id={`faq-${key}`}
                            onToggle={(nativeEvent) => {
                                if (
                                    nativeEvent.currentTarget instanceof
                                        HTMLDetailsElement &&
                                    nativeEvent.currentTarget.open
                                ) {
                                    track('lp_faq_open', {
                                        variant,
                                        question: key,
                                    });
                                }
                            }}
                            className="group scroll-mt-6"
                        >
                            <summary className="text-brand flex min-h-[3.25rem] cursor-pointer items-center justify-between gap-4 py-4 text-[1.1rem] font-semibold">
                                {t(`public.lp.faq.${key}.q`, {
                                    price: formatPrice(price),
                                    brand: brand.name,
                                })}
                                <span
                                    aria-hidden="true"
                                    className="text-brand-muted flex-none text-xl transition-transform group-open:rotate-45"
                                >
                                    +
                                </span>
                            </summary>
                            <div className="flex flex-col gap-3 pr-8 pb-5">
                                <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                    {t(`public.lp.faq.${key}.a`, {
                                        brand: brand.name,
                                    })}
                                </p>
                                {EXTRA[key] !== undefined && (
                                    <p className="text-[1rem]">{EXTRA[key]}</p>
                                )}
                            </div>
                        </details>
                    ))}
                </div>
            </div>
        </Section>
    );
}
