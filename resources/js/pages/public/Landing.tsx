import { Head, Link } from '@inertiajs/react';

import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';

type Props = {
    /** `pilot`, `prevente` ou `core`. */
    mode: string;
    /** Le prix vu par ce visiteur, en centimes. */
    price: number;
    phoneOptionPrice: number;
};

/*
 * L'ordre des sections vient du dossier 01 §4 et n'est pas négociable.
 *
 * Promesse, puis « comment ça marche », puis l'essai, puis le livre, puis les
 * engagements, puis le prix, puis les questions. On explique avant de
 * demander — la même règle que sur la page d'enregistrement, où l'on explique
 * avant de demander le micro. Mettre le prix plus haut ferait vendre un
 * abonnement ; ici on demande à quelqu'un de confier la voix de sa mère.
 */
const STEPS = ['one', 'two', 'three', 'four'] as const;

/*
 * Les engagements R-10, en formulation **canonique**. Ce sont des phrases
 * qu'on peut nous opposer, et elles sont identiques ici, dans les CGV et dans
 * les courriels. Les réécrire « pour la page d'accueil » serait créer une
 * seconde promesse, plus jolie et moins vraie.
 */
const COMMITMENTS = [
    'validation',
    'no_cloning',
    'ai_arranges',
    'source_audio',
    'no_training',
    'eu_hosting',
    'withdrawal',
] as const;

const QUESTIONS = [
    'no_smartphone',
    'refuses',
    'writing',
    'privacy',
    'shutdown',
] as const;

export default function Landing({ mode, price, phoneOptionPrice }: Props) {
    const t = useT();
    const offer = mode === 'prevente' ? 'prevente' : 'pilot';

    return (
        <>
            <Head title={t('public.landing.promise')} />

            <h1 className="font-display text-3xl leading-tight font-semibold sm:text-4xl">
                {t('public.landing.promise')}
            </h1>

            <p className="text-brand-muted mt-4 text-xl">
                {t('public.landing.subtitle')}
            </p>

            <div className="mt-8 flex flex-wrap gap-4">
                <Link
                    href="/acheter"
                    className="bg-brand text-brand-foreground min-h-[2.75rem] rounded-md px-6 py-3 font-medium"
                >
                    {t('public.landing.cta')}
                </Link>

                <Link
                    href="/essai"
                    className="border-brand-muted/40 min-h-[2.75rem] rounded-md border px-6 py-3"
                >
                    {t('public.landing.cta_try')}
                </Link>
            </div>

            <section aria-labelledby="how" className="mt-16">
                <h2
                    id="how"
                    className="font-display text-2xl leading-tight font-semibold"
                >
                    {t('public.landing.how.title')}
                </h2>

                <ol className="mt-6 flex flex-col gap-6">
                    {STEPS.map((step, index) => (
                        <li key={step} className="flex gap-4">
                            <span
                                aria-hidden="true"
                                className="text-brand-muted font-display text-2xl"
                            >
                                {index + 1}
                            </span>

                            <span>
                                <strong className="block font-medium">
                                    {t(`public.landing.how.${step}.title`)}
                                </strong>
                                <span className="text-brand-muted">
                                    {t(`public.landing.how.${step}.body`)}
                                </span>
                            </span>
                        </li>
                    ))}
                </ol>
            </section>

            <section aria-labelledby="try" className="mt-16">
                <h2
                    id="try"
                    className="font-display text-2xl leading-tight font-semibold"
                >
                    {t('public.landing.try.title')}
                </h2>

                <p className="mt-4">{t('public.landing.try.body')}</p>

                <Link
                    href="/essai"
                    className="border-brand-muted/40 mt-6 inline-block min-h-[2.75rem] rounded-md border px-6 py-3"
                >
                    {t('public.landing.cta_try')}
                </Link>
            </section>

            <section aria-labelledby="book" className="mt-16">
                <h2
                    id="book"
                    className="font-display text-2xl leading-tight font-semibold"
                >
                    {t('public.landing.book.title')}
                </h2>

                <p className="mt-4">{t('public.landing.book.body')}</p>
                <p className="text-brand-muted mt-4 text-base">
                    {t('public.landing.book.qr')}
                </p>
            </section>

            <section aria-labelledby="commitments" className="mt-16">
                <h2
                    id="commitments"
                    className="font-display text-2xl leading-tight font-semibold"
                >
                    {t('public.landing.commitments.title')}
                </h2>

                <ul className="mt-6 flex flex-col gap-4">
                    {COMMITMENTS.map((commitment) => (
                        <li key={commitment}>
                            {t(`public.landing.commitments.${commitment}`)}
                        </li>
                    ))}
                </ul>
            </section>

            <section aria-labelledby="price" className="mt-16">
                <h2
                    id="price"
                    className="font-display text-2xl leading-tight font-semibold"
                >
                    {t('public.landing.price.title')}
                </h2>

                <p className="mt-6 text-xl font-medium">
                    {t(`public.landing.price.${offer}`)} — {formatPrice(price)}
                </p>

                <p className="text-brand-muted mt-2">
                    {t(`public.landing.price.${offer}_body`)}
                </p>

                <p className="mt-6 font-medium">
                    {t('public.landing.price.phone_option')} —{' '}
                    {formatPrice(phoneOptionPrice)}
                </p>

                <p className="text-brand-muted mt-2">
                    {t('public.landing.price.phone_option_body')}
                </p>

                <Link
                    href="/acheter"
                    className="bg-brand text-brand-foreground mt-8 inline-block min-h-[2.75rem] rounded-md px-6 py-3 font-medium"
                >
                    {t('public.landing.cta')}
                </Link>
            </section>

            <section aria-labelledby="faq" className="mt-16">
                <h2
                    id="faq"
                    className="font-display text-2xl leading-tight font-semibold"
                >
                    {t('public.landing.faq.title')}
                </h2>

                <dl className="mt-6 flex flex-col gap-6">
                    {QUESTIONS.map((question) => (
                        <div key={question}>
                            <dt className="font-medium">
                                {t(`public.landing.faq.${question}.q`)}
                            </dt>
                            <dd className="text-brand-muted mt-1">
                                {t(`public.landing.faq.${question}.a`)}
                            </dd>
                        </div>
                    ))}
                </dl>
            </section>
        </>
    );
}
