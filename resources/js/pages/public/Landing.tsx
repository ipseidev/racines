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
 *
 * La direction artistique est celle validée le 3 septembre 2026
 * (docs/design/README.md) : une seule couleur d'action, la terracotta, sur les
 * boutons qui font quelque chose et nulle part ailleurs ; des sections pleine
 * largeur qui alternent crème, lin et forêt ; la question de la semaine comme
 * objet du héros, parce que c'est le rituel qu'on vend.
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

const INCLUDES = ['questions', 'text', 'family', 'book', 'download'] as const;

const CHECKS = ['no_app', 'kept_words', 'she_decides', 'book'] as const;

/** Une coche dans la couleur de marque, jamais dans celle de l'action. */
function Check() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            aria-hidden="true"
            className="text-brand mt-1 size-[22px] flex-none"
        >
            <circle cx="12" cy="12" r="10" />
            <path d="m8 12 3 3 5-6" />
        </svg>
    );
}

function Lock() {
    return (
        <svg
            viewBox="0 0 16 16"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            aria-hidden="true"
            className="size-4 flex-none"
        >
            <rect x="3" y="7" width="10" height="7" rx="1.5" />
            <path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" />
        </svg>
    );
}

/** L'onde d'une voix : le seul mouvement de la page, et il s'arrête pour qui le demande. */
function Wave() {
    return (
        <div className="flex h-8 items-center gap-[3px]" aria-hidden="true">
            {Array.from({ length: 22 }, (_, i) => (
                <i
                    key={i}
                    className="wave-bar"
                    style={{ animationDelay: `${(i % 5) * -0.3}s` }}
                />
            ))}
        </div>
    );
}

export default function Landing({ mode, price, phoneOptionPrice }: Props) {
    const t = useT();
    const offer = mode === 'prevente' ? 'prevente' : 'pilot';

    return (
        <>
            <Head title={t('public.landing.promise')} />

            {/* Promesse ------------------------------------------------ */}
            <section className="mx-auto grid w-full max-w-6xl gap-10 px-6 pt-10 pb-20 lg:grid-cols-2 lg:items-center lg:gap-16 lg:pt-16 lg:pb-28">
                <div className="flex flex-col gap-7">
                    <h1 className="font-display text-[2.5rem] leading-[1.05] font-medium sm:text-5xl lg:text-[4.25rem]">
                        {t('public.landing.promise')}
                    </h1>

                    <p className="text-brand-muted max-w-[34em] text-xl leading-snug">
                        {t('public.landing.hero.lede')}
                    </p>

                    <div className="flex flex-wrap items-center gap-3.5">
                        <Link
                            href="/acheter"
                            className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep inline-flex min-h-[3.5rem] items-center justify-center rounded-md px-7 text-[1.05rem] font-semibold"
                        >
                            {t('public.landing.cta')} — {formatPrice(price)}
                        </Link>

                        <a
                            href="#comment"
                            className="border-brand text-brand hover:bg-brand/5 inline-flex min-h-[3.5rem] items-center justify-center rounded-md border-2 px-7 text-[1.05rem] font-semibold"
                        >
                            {t('public.landing.cta_how')}
                        </a>
                    </div>

                    <p className="text-brand-muted flex items-center gap-2 text-base">
                        <Lock />
                        {t('public.landing.hero.note')}
                    </p>

                    <ul className="flex flex-col gap-3">
                        {CHECKS.map((check) => (
                            <li
                                key={check}
                                className="flex gap-3 text-[1.02rem]"
                            >
                                <Check />
                                <span>
                                    {t(`public.landing.hero.checks.${check}`)}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="relative mb-11 lg:mb-0">
                    <img
                        src="/img/landing/hero.jpg"
                        alt={t('public.landing.hero.photo_alt')}
                        width="1400"
                        height="933"
                        className="aspect-[5/4] w-full rounded-2xl object-cover object-[60%_25%]"
                    />

                    {/*
                     * La carte « question de la semaine » : l'objet du rituel,
                     * posé sur la photo. Un exemple, dit comme tel.
                     */}
                    <figure
                        aria-label={t('public.landing.hero.card.aria')}
                        className="bg-brand-surface absolute bottom-[-2.25rem] left-3 flex w-[min(380px,calc(100%-1.5rem))] flex-col gap-3.5 rounded-2xl px-6 py-5 shadow-[0_24px_60px_rgba(38,33,28,0.18),0_2px_6px_rgba(38,33,28,0.08)] lg:bottom-[-1.75rem] lg:-left-6"
                    >
                        <div className="text-brand-muted flex justify-between text-[0.78rem] font-semibold tracking-[0.08em] uppercase">
                            <span>{t('public.landing.hero.card.label')}</span>
                            <span>{t('public.landing.hero.card.name')}</span>
                        </div>
                        <p className="font-display text-[1.35rem] leading-[1.3] font-medium">
                            {t('public.landing.hero.card.question')}
                        </p>
                        <div className="text-brand-muted flex items-center gap-3.5 text-[0.9rem]">
                            <Wave />
                            <span>
                                <b className="text-brand font-semibold">
                                    {t('public.landing.hero.card.answers')}
                                </b>{' '}
                                {t('public.landing.hero.card.duration')}
                            </span>
                        </div>
                    </figure>
                </div>
            </section>

            {/* Comment ça marche ---------------------------------------- */}
            <section
                id="comment"
                aria-labelledby="how"
                className="mx-auto w-full max-w-6xl px-6 py-16 lg:py-28"
            >
                <div className="mb-12 flex max-w-[42em] flex-col gap-5">
                    <span className="eyebrow">
                        {t('public.landing.how.title')}
                    </span>
                    <h2
                        id="how"
                        className="font-display text-[2rem] leading-[1.1] font-medium sm:text-4xl lg:text-5xl"
                    >
                        {t('public.landing.how.headline')}
                    </h2>
                    <p className="text-brand-muted text-xl leading-snug">
                        {t('public.landing.how.lede')}
                    </p>
                </div>

                <ol className="grid gap-7 sm:grid-cols-2 lg:grid-cols-4">
                    {STEPS.map((step, index) => (
                        <li key={step} className="flex flex-col gap-4">
                            <img
                                src={`/img/landing/etape-${index + 1}.jpg`}
                                alt={t(`public.landing.how.${step}.alt`)}
                                width="1400"
                                height="933"
                                loading="lazy"
                                className="aspect-[4/3] w-full rounded-lg object-cover"
                            />
                            <span
                                aria-hidden="true"
                                className="font-display text-brand-gold text-[2.6rem] leading-none"
                            >
                                {index + 1}
                            </span>
                            <h3 className="font-display text-[1.55rem] leading-[1.22] font-medium">
                                {t(`public.landing.how.${step}.title`)}
                            </h3>
                            <p className="text-brand-muted text-[1.02rem]">
                                {t(`public.landing.how.${step}.body`)}
                            </p>
                        </li>
                    ))}
                </ol>
            </section>

            {/* L'essai ------------------------------------------------- */}
            <section
                aria-labelledby="try"
                className="mx-auto w-full max-w-6xl px-6 pb-16 lg:pb-28"
            >
                <div className="card flex flex-col gap-5 px-7 py-8 lg:flex-row lg:items-center lg:justify-between lg:gap-10 lg:px-10">
                    <div className="flex max-w-[38em] flex-col gap-3">
                        <h2
                            id="try"
                            className="font-display text-[1.75rem] leading-[1.15] font-medium sm:text-3xl"
                        >
                            {t('public.landing.try.title')}
                        </h2>
                        <p className="text-brand-muted">
                            {t('public.landing.try.body')}
                        </p>
                    </div>

                    <Link
                        href="/essai"
                        className="border-brand text-brand hover:bg-brand/5 inline-flex min-h-[3.5rem] flex-none items-center justify-center rounded-md border-2 px-7 text-[1.05rem] font-semibold"
                    >
                        {t('public.landing.cta_try')}
                    </Link>
                </div>
            </section>

            {/* Le livre ------------------------------------------------ */}
            <section aria-labelledby="book" className="bg-brand-linen">
                <div className="mx-auto grid w-full max-w-6xl gap-10 px-6 py-16 lg:grid-cols-2 lg:items-center lg:gap-24 lg:py-28">
                    <img
                        src="/img/landing/livre.jpg"
                        alt={t('public.landing.book.photo_alt')}
                        width="1400"
                        height="875"
                        loading="lazy"
                        className="aspect-[4/3] w-full rounded-2xl object-cover"
                    />

                    <div className="flex flex-col gap-6">
                        <span className="eyebrow">
                            {t('public.landing.book.title')}
                        </span>
                        <h2
                            id="book"
                            className="font-display text-[2rem] leading-[1.1] font-medium sm:text-4xl lg:text-5xl"
                        >
                            {t('public.landing.book.headline')}
                        </h2>
                        <p className="text-brand-muted text-xl leading-snug">
                            {t('public.landing.book.body')}
                        </p>
                        <ul className="flex flex-col gap-3">
                            {(['photos', 'proof', 'lasting'] as const).map(
                                (point) => (
                                    <li key={point} className="flex gap-3">
                                        <Check />
                                        <span>
                                            {t(
                                                `public.landing.book.points.${point}`,
                                            )}
                                        </span>
                                    </li>
                                ),
                            )}
                        </ul>
                        <p className="text-brand-muted text-base">
                            {t('public.landing.book.qr')}
                        </p>
                    </div>
                </div>
            </section>

            {/* Les engagements ------------------------------------------ */}
            <section
                aria-labelledby="commitments"
                className="bg-brand-deep text-[#F7F1E6]"
            >
                <div className="mx-auto grid w-full max-w-6xl gap-12 px-6 py-16 lg:grid-cols-[5fr_7fr] lg:items-start lg:gap-24 lg:py-28">
                    <div className="flex flex-col gap-10">
                        <div className="flex flex-col gap-5">
                            <span className="eyebrow text-[#C9C0B2]">
                                {t('public.landing.commitments.title')}
                            </span>
                            <h2
                                id="commitments"
                                className="font-display text-[2rem] leading-[1.1] font-medium text-[#F7F1E6] sm:text-4xl lg:text-5xl"
                            >
                                {t('public.landing.commitments.headline')}
                            </h2>
                            <p className="text-xl leading-snug text-[#C9C0B2]">
                                {t('public.landing.commitments.lede')}
                            </p>
                        </div>

                        <ul className="divide-brand-gold/45 border-brand-gold/45 divide-y border-y">
                            {COMMITMENTS.map((commitment) => (
                                <li
                                    key={commitment}
                                    className="py-5 text-[1.02rem] text-[#F7F1E6]"
                                >
                                    {t(
                                        `public.landing.commitments.${commitment}`,
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/*
                     * La preuve à côté de la promesse : un mot à mot du corpus
                     * et son rendu, tels qu'ils sortent. Pas une reformulation
                     * pour la page d'accueil.
                     */}
                    <figure
                        aria-label={t('public.landing.proof.aria')}
                        className="bg-brand-surface text-brand-text overflow-hidden rounded-2xl shadow-[0_30px_70px_rgba(0,0,0,0.25)]"
                    >
                        <div className="border-brand-sand grid border-b sm:grid-cols-2">
                            <div className="text-brand-muted px-6 py-3.5 text-[0.78rem] font-semibold tracking-[0.08em] uppercase">
                                {t('public.landing.proof.verbatim')}
                            </div>
                            <div className="border-brand-sand text-brand border-t px-6 py-3.5 text-[0.78rem] font-semibold tracking-[0.08em] uppercase sm:border-t-0 sm:border-l">
                                {t('public.landing.proof.fluide')}
                            </div>
                        </div>
                        <div className="grid sm:grid-cols-2">
                            <p className="bg-brand-linen text-brand-muted px-6 py-6 text-base leading-relaxed italic">
                                {t('public.landing.proof.sample_verbatim')}
                            </p>
                            <p className="border-brand-sand font-display border-t px-6 py-6 text-[1.12rem] leading-relaxed sm:border-t-0 sm:border-l">
                                {t('public.landing.proof.sample_fluide')}
                            </p>
                        </div>
                        <div className="border-brand-sand text-brand-muted flex flex-wrap items-center gap-2.5 border-t px-6 py-4 text-[0.92rem]">
                            <span>{t('public.landing.proof.then')}</span>
                            {(['share', 'keep', 'later'] as const).map(
                                (choice) => (
                                    <span key={choice} className="chip">
                                        {t(`public.landing.proof.${choice}`)}
                                    </span>
                                ),
                            )}
                        </div>
                    </figure>
                </div>
            </section>

            {/* Le prix ------------------------------------------------- */}
            <section
                aria-labelledby="price"
                className="mx-auto grid w-full max-w-6xl gap-10 px-6 py-16 lg:grid-cols-[7fr_5fr] lg:items-start lg:gap-16 lg:py-28"
            >
                <div className="flex flex-col gap-10">
                    <div className="flex max-w-[42em] flex-col gap-5">
                        <span className="eyebrow">
                            {t('public.landing.price.title')}
                        </span>
                        <h2
                            id="price"
                            className="font-display text-[2rem] leading-[1.1] font-medium sm:text-4xl lg:text-5xl"
                        >
                            {t('public.landing.price.headline')}
                        </h2>
                        <p className="text-brand-muted text-xl leading-snug">
                            {t('public.landing.price.lede')}
                        </p>
                    </div>

                    <ul className="divide-brand-sand border-brand-sand divide-y border-y">
                        <li className="flex justify-between gap-4 py-5">
                            <div>
                                <span className="text-brand block font-semibold">
                                    {t(`public.landing.price.${offer}`)}
                                </span>
                                <span className="text-brand-muted block text-base">
                                    {t(`public.landing.price.${offer}_body`)}
                                </span>
                            </div>
                            <span className="font-display text-brand text-2xl whitespace-nowrap tabular-nums">
                                {formatPrice(price)}
                            </span>
                        </li>
                        <li className="flex justify-between gap-4 py-5">
                            <div>
                                <span className="text-brand block font-semibold">
                                    {t('public.landing.price.phone_option')}
                                </span>
                                <span className="text-brand-muted block text-base">
                                    {t(
                                        'public.landing.price.phone_option_body',
                                    )}
                                </span>
                            </div>
                            <span className="font-display text-brand text-2xl whitespace-nowrap tabular-nums">
                                +{formatPrice(phoneOptionPrice)}
                            </span>
                        </li>
                    </ul>
                </div>

                <div className="card flex flex-col gap-6 p-7 lg:p-11">
                    <div className="flex items-baseline gap-3.5">
                        <span className="font-display text-brand text-6xl leading-none font-medium tabular-nums">
                            {formatPrice(price)}
                        </span>
                        <span className="text-brand-muted max-w-[14em] text-base leading-snug">
                            {t('public.landing.price.per')}
                        </span>
                    </div>

                    <ul className="flex flex-col gap-3">
                        {INCLUDES.map((item) => (
                            <li key={item} className="flex gap-3">
                                <Check />
                                <span>
                                    {t(`public.landing.price.includes.${item}`)}
                                </span>
                            </li>
                        ))}
                    </ul>

                    <div className="flex flex-col gap-3">
                        <Link
                            href="/acheter"
                            className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep inline-flex min-h-[3.5rem] items-center justify-center rounded-md px-7 text-[1.05rem] font-semibold"
                        >
                            {t('public.landing.price.cta_start')}
                        </Link>
                        <p className="text-brand-muted flex items-center gap-2 text-base">
                            <Lock />
                            {t('public.landing.price.reassurance')}
                        </p>
                    </div>
                </div>
            </section>

            {/* Questions fréquentes ------------------------------------- */}
            <section
                aria-labelledby="faq"
                className="mx-auto grid w-full max-w-6xl gap-10 px-6 pb-20 lg:grid-cols-[4fr_8fr] lg:gap-16 lg:pb-28"
            >
                <h2
                    id="faq"
                    className="font-display text-[2rem] leading-[1.1] font-medium sm:text-4xl"
                >
                    {t('public.landing.faq.title')}
                </h2>

                <dl className="divide-brand-sand border-brand-sand divide-y border-y">
                    {QUESTIONS.map((question) => (
                        <div
                            key={question}
                            className="flex flex-col gap-2 py-6"
                        >
                            <dt className="text-brand text-xl font-semibold">
                                {t(`public.landing.faq.${question}.q`)}
                            </dt>
                            <dd className="text-brand-muted">
                                {t(`public.landing.faq.${question}.a`)}
                            </dd>
                        </div>
                    ))}
                </dl>
            </section>
        </>
    );
}
