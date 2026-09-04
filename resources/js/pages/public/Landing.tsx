import { Head, Link } from '@inertiajs/react';

import { useBrand } from '@/brand/BrandProvider';
import WelcomeOffer from '@/components/WelcomeOffer';
import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';

type Props = {
    /** `pilot`, `prevente` ou `core`. */
    mode: string;
    /** Le prix vu par ce visiteur, en centimes. */
    price: number;
    phoneOptionPrice: number;
    extraCopyPrice: number;
    /** La fenêtre de bienvenue (T-141) : proposée ou non, et son pourcentage. */
    welcomeOffer: { enabled: boolean; discountPercent: number };
};

/*
 * La page d'accueil suit la structure de Remento, le leader, adaptée à notre
 * univers (décision du fondateur, 4 septembre 2026, T-134). Section par
 * section : bandeau, héros, trois engagements en bandeau sombre, « qu'est-ce
 * que », comment ça marche, notre histoire, la fiche produit, ce que comprend
 * l'achat et le prix, la bande de confiance, la garantie, « pensé pour les
 * grands-parents » et l'essai, la double page, la relecture, le cadeau, les
 * options, les questions.
 *
 * Ce qui n'existe pas chez nous n'y est pas : ni presse, ni avis, ni vidéo de
 * clients. Les emplacements viendront avec les premières familles. Les sept
 * engagements gardent leur formulation canonique.
 *
 * Direction artistique du 3 septembre (docs/design/README.md) : une seule
 * couleur d'action, la terracotta ; des sections pleine largeur qui alternent
 * crème, lin et forêt.
 *
 * Le héros suit celui du leader dans l'ordre et le choix des informations
 * (T-142, premier retour d'un prospect : « on ne comprend pas ce que le site
 * fait avant Comment ça marche ») : la photo d'abord sur téléphone, le titre,
 * un texte qui dit qui parle, qui fait quoi, et ce qu'on reçoit, l'action,
 * « Comment ça marche », puis quatre repères. La carte « question de la
 * semaine » a quitté le héros : elle décrivait un rituel avant qu'on ait
 * compris le produit.
 */
const STEPS = ['one', 'two', 'three', 'four'] as const;

const PROMISES = ['validation', 'ai_arranges', 'withdrawal'] as const;

const INCLUDES = [
    'questions',
    'device',
    'download',
    'book',
    'qr',
    'family',
] as const;

const CHECKS = ['voice', 'no_app', 'kept_words', 'she_decides'] as const;

const QUESTIONS = [
    'included',
    'subscription',
    'edit',
    'no_smartphone',
    'refuses',
    'writing',
    'privacy',
    'refund',
    'shutdown',
] as const;

const H2 =
    'font-display text-[2rem] leading-[1.1] font-medium sm:text-4xl lg:text-5xl';
const LEDE = 'text-brand-muted text-xl leading-snug';
const PRIMARY =
    'bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep inline-flex min-h-[3.5rem] items-center justify-center rounded-md px-7 text-[1.05rem] font-semibold';
const SECONDARY =
    'border-brand text-brand hover:bg-brand/5 inline-flex min-h-[3.5rem] items-center justify-center rounded-md border-2 px-7 text-[1.05rem] font-semibold';

/**
 * Une coche dans la couleur de marque, jamais dans celle de l'action.
 *
 * Décalée d'un cran vers le bas par défaut, pour s'aligner sur la première
 * ligne d'un texte ; dans une pastille, on lui retire ce décalage, sinon elle
 * n'est plus au centre.
 */
function Check({
    light = false,
    className = 'mt-1',
}: {
    light?: boolean;
    className?: string;
}) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            aria-hidden="true"
            className={`${className} size-[22px] flex-none ${light ? 'text-brand-gold' : 'text-brand'}`}
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
function Wave({ bars = 22 }: { bars?: number }) {
    return (
        <div className="flex h-8 items-center gap-[3px]" aria-hidden="true">
            {Array.from({ length: bars }, (_, i) => (
                <i
                    key={i}
                    className="wave-bar"
                    style={{ animationDelay: `${(i % 5) * -0.3}s` }}
                />
            ))}
        </div>
    );
}

/*
 * Un motif façon code à scanner, déterministe et décoratif : il ne mène nulle
 * part. Le vrai code viendra avec le livre (bloc 13).
 */
function FauxQr({ className = '' }: { className?: string }) {
    const n = 21;
    let seed = 7;
    const rnd = () => (seed = (seed * 9301 + 49297) % 233280) / 233280;
    const cells: string[] = [];
    for (let y = 0; y < n; y++) {
        for (let x = 0; x < n; x++) {
            const finder =
                (x < 7 && y < 7) || (x > 13 && y < 7) || (x < 7 && y > 13);
            if (!finder && rnd() > 0.55) cells.push(`${x},${y}`);
        }
    }
    const finders = [
        [0, 0],
        [14, 0],
        [0, 14],
    ];
    return (
        <svg
            viewBox={`0 0 ${n} ${n}`}
            className={className}
            aria-hidden="true"
            shapeRendering="crispEdges"
        >
            <rect width={n} height={n} fill="#fff" />
            {cells.map((c) => {
                const [x, y] = c.split(',').map(Number);
                return (
                    <rect
                        key={c}
                        x={x}
                        y={y}
                        width="1"
                        height="1"
                        fill="#26211C"
                    />
                );
            })}
            {finders.map(([x, y]) => (
                <g key={`${x}-${y}`} fill="#26211C">
                    <rect x={x} y={y} width="7" height="7" />
                    <rect
                        x={x + 1}
                        y={y + 1}
                        width="5"
                        height="5"
                        fill="#fff"
                    />
                    <rect x={x + 2} y={y + 2} width="3" height="3" />
                </g>
            ))}
        </svg>
    );
}

/**
 * La maquette du livre : une couverture reliée et la page d'écoute, en CSS.
 *
 * Nous n'avons pas encore de livre imprimé ; le premier viendra du bloc 13.
 * D'ici là, on ne montre pas une photo d'un livre qui n'existe pas : on
 * dessine celui qu'on fabrique, avec un vrai titre d'histoire du corpus.
 */
function BookMockup() {
    const t = useT();

    return (
        <figure
            aria-label={t('public.landing.product.mockup.aria')}
            className="relative mx-auto flex w-full max-w-[520px] flex-col items-center py-6 sm:flex-row sm:items-end sm:justify-center sm:gap-6"
        >
            <div className="bg-brand-deep relative aspect-[3/4] w-[68%] rounded-l-sm rounded-r-md shadow-[0_30px_60px_rgba(38,33,28,0.28)] sm:w-[62%]">
                <div className="bg-brand absolute top-0 bottom-0 left-0 w-[5%] rounded-l-sm" />
                <div className="absolute inset-x-[16%] top-[14%] flex flex-col items-center gap-3 text-center">
                    <span className="bg-brand-gold h-px w-10" />
                    <span className="font-display text-[clamp(1rem,2.4vw,1.5rem)] leading-tight font-medium text-[#F7F1E6]">
                        {t('public.landing.product.mockup.cover_title')}
                    </span>
                    <span className="text-[0.7rem] tracking-[0.14em] text-[#C9C0B2] uppercase">
                        {t('public.landing.product.mockup.cover_sub')}
                    </span>
                    <span className="bg-brand-gold h-px w-10" />
                </div>
                <div className="absolute inset-x-[22%] bottom-[16%] aspect-[4/3] overflow-hidden rounded-sm">
                    <img
                        src="/img/landing/etape-1.jpg"
                        alt=""
                        width="1400"
                        height="930"
                        loading="lazy"
                        className="size-full object-cover"
                    />
                </div>
            </div>

            {/*
             * Sur téléphone, la page d'écoute est posée sous le livre et le
             * chevauche un peu, comme une carte glissée dans la couverture ;
             * côte à côte, elle n'avait plus la place de ses mots (T-142).
             */}
            <div className="bg-brand-surface relative z-10 -mt-12 flex w-[76%] flex-col gap-3 self-end rounded-2xl p-4 shadow-[0_24px_60px_rgba(38,33,28,0.18)] sm:z-auto sm:mt-0 sm:w-[34%] sm:self-auto">
                <span className="font-display text-brand text-[clamp(1rem,1.8vw,1.05rem)] leading-tight font-medium">
                    {t('public.landing.product.mockup.chapter')}
                </span>
                <Wave bars={14} />
                <div className="flex items-center gap-2">
                    <FauxQr className="border-brand-sand size-12 flex-none rounded-sm border p-0.5" />
                    <span className="text-brand-muted text-[0.7rem] leading-snug">
                        {t('public.landing.product.mockup.scan')}
                    </span>
                </div>
            </div>
        </figure>
    );
}

export default function Landing({
    mode,
    price,
    phoneOptionPrice,
    extraCopyPrice,
    welcomeOffer,
}: Props) {
    const t = useT();
    const brand = useBrand();

    return (
        <>
            <Head title={t('public.landing.promise')} />

            {/* La réduction de bienvenue, après un délai (T-141) ============== */}
            <WelcomeOffer
                enabled={welcomeOffer.enabled}
                discountPercent={welcomeOffer.discountPercent}
            />

            {/* Héros ============================================================ */}
            <section className="mx-auto grid w-full max-w-6xl gap-8 px-6 pt-6 pb-20 lg:grid-cols-2 lg:items-center lg:gap-16 lg:pt-16 lg:pb-24">
                <div className="flex flex-col gap-7">
                    <h1 className="font-display text-[2.5rem] leading-[1.05] font-medium sm:text-5xl lg:text-[4rem]">
                        {t('public.landing.promise')}
                    </h1>

                    <p className={`${LEDE} max-w-[34em]`}>
                        {t('public.landing.hero.lede', { brand: brand.name })}
                    </p>

                    <div className="flex flex-wrap items-center gap-3.5">
                        <Link
                            href="/acheter"
                            className={`${PRIMARY} w-full sm:w-auto`}
                        >
                            {t('public.landing.cta')}
                        </Link>
                        <a
                            href="#comment"
                            className={`${SECONDARY} w-full sm:w-auto`}
                        >
                            {t('public.landing.cta_how')} ↓
                        </a>
                    </div>

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

                    <p className="text-brand-muted flex items-center gap-2 text-base">
                        <Lock />
                        {t('public.landing.hero.note')}
                    </p>
                </div>

                {/*
                 * La photo d'abord sur téléphone, à droite sur bureau : ce qu'on
                 * voit avant de lire doit déjà dire « une personne, sa voix ».
                 */}
                <img
                    src="/img/landing/hero.jpg"
                    alt={t('public.landing.hero.photo_alt')}
                    width="1400"
                    height="933"
                    className="order-first aspect-[4/3] w-full rounded-2xl object-cover object-[60%_25%] lg:order-none lg:aspect-[5/4]"
                />
            </section>

            {/* Trois engagements, en bandeau sombre ============================ */}
            <section
                aria-labelledby="promises"
                className="bg-brand-deep text-[#F7F1E6]"
            >
                <div className="mx-auto w-full max-w-6xl px-6 py-14 lg:py-16">
                    <h2 id="promises" className="sr-only">
                        {t('public.landing.promises.title')}
                    </h2>
                    <ul className="grid gap-10 text-center sm:grid-cols-3 sm:gap-8">
                        {PROMISES.map((promise) => (
                            <li key={promise} className="flex flex-col gap-3">
                                <p className="font-display text-[1.6rem] leading-tight font-medium text-[#F7F1E6] lg:text-[2rem]">
                                    {t(`public.landing.promises.${promise}`)}
                                </p>
                                <p className="text-[0.95rem] leading-relaxed text-[#C9C0B2]">
                                    {t(`public.landing.commitments.${promise}`)}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* Qu'est-ce que ==================================================== */}
            <section
                aria-labelledby="what"
                className="mx-auto grid w-full max-w-6xl gap-8 px-6 py-16 lg:grid-cols-2 lg:items-start lg:gap-20 lg:py-24"
            >
                <div className="flex flex-col gap-5">
                    <span className="eyebrow">
                        {t('public.landing.what.title', { brand: brand.name })}
                    </span>
                    <h2 id="what" className={H2}>
                        {t('public.landing.what.headline')}
                    </h2>
                </div>
                <p className="text-brand-text text-xl leading-relaxed lg:pt-12">
                    {t('public.landing.what.body', { brand: brand.name })}
                </p>
            </section>

            {/* Comment ça marche ================================================ */}
            <section
                id="comment"
                aria-labelledby="how"
                className="border-brand-sand mx-auto w-full max-w-6xl border-t px-6 py-16 lg:py-24"
            >
                {/* À gauche sur téléphone, comme les étapes qui suivent ; centré sur bureau, au-dessus des quatre colonnes. */}
                <div className="mb-12 flex flex-col items-start gap-4 text-left lg:items-center lg:text-center">
                    <span className="eyebrow">
                        {t('public.landing.how.title')}
                    </span>
                    <h2 id="how" className={`${H2} max-w-[24em]`}>
                        {t('public.landing.how.headline')}
                    </h2>
                    <p className={`${LEDE} max-w-[36em]`}>
                        {t('public.landing.how.lede')}
                    </p>
                </div>

                <ol className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
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
                            <span className="text-brand-muted text-[0.78rem] font-semibold tracking-[0.12em] uppercase">
                                Étape {index + 1}
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

            {/* Notre histoire =================================================== */}
            <section
                id="histoire"
                aria-labelledby="story"
                className="bg-brand-linen"
            >
                <div className="mx-auto grid w-full max-w-6xl gap-10 px-6 py-16 lg:grid-cols-[5fr_7fr] lg:gap-20 lg:py-24">
                    <div className="flex flex-col gap-5">
                        <h2 id="story" className={H2}>
                            {t('public.landing.story.title')}
                        </h2>
                    </div>
                    <div className="flex flex-col gap-6 text-xl leading-relaxed">
                        <p>{t('public.landing.story.p1')}</p>
                        <p>{t('public.landing.story.p2')}</p>
                        <p className="font-display text-brand text-[1.45rem] leading-snug">
                            {t('public.landing.story.p3')}
                        </p>
                    </div>
                </div>
            </section>

            {/* La fiche produit ================================================= */}
            <section
                id="livre"
                aria-labelledby="product"
                className="mx-auto grid w-full max-w-6xl gap-12 px-6 py-16 lg:grid-cols-2 lg:items-center lg:gap-16 lg:py-24"
            >
                <BookMockup />

                <div className="flex flex-col gap-6">
                    <h2 id="product" className={H2}>
                        {t('public.landing.product.title')}
                    </h2>
                    <p className={LEDE}>{t('public.landing.product.lede')}</p>

                    <dl className="border-brand-sand divide-brand-sand flex flex-col divide-y border-y">
                        {(['read', 'hear', 'bound'] as const).map((key) => (
                            <div key={key} className="flex flex-col gap-1 py-5">
                                <dt className="font-display text-brand text-[1.35rem] font-medium">
                                    {t(`public.landing.product.${key}.title`)}
                                </dt>
                                <dd className="text-brand-muted text-[1.02rem]">
                                    {t(`public.landing.product.${key}.body`)}
                                </dd>
                            </div>
                        ))}
                    </dl>

                    <ul className="grid gap-2.5 text-[1rem] sm:grid-cols-2">
                        {INCLUDES.map((item) => (
                            <li key={item} className="flex gap-2.5">
                                <Check />
                                <span>
                                    {t(
                                        `public.landing.product.includes.${item}`,
                                    )}
                                </span>
                            </li>
                        ))}
                    </ul>

                    <Link href="/acheter" className={PRIMARY}>
                        {t('public.landing.cta')} · {formatPrice(price)}
                    </Link>

                    <ul className="text-brand-muted flex flex-wrap gap-x-6 gap-y-2 text-[0.95rem]">
                        {(['refund', 'yours', 'download'] as const).map((g) => (
                            <li key={g} className="flex items-center gap-2">
                                <Lock />
                                {t(`public.landing.product.guarantees.${g}`)}
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* Pour toujours, et le prix ======================================== */}
            <section aria-labelledby="forever" className="bg-brand-linen">
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-12 px-6 py-16 lg:py-24">
                    <div className="flex max-w-[40em] flex-col items-start gap-4 text-left lg:mx-auto lg:items-center lg:text-center">
                        <h2 id="forever" className={H2}>
                            {t('public.landing.forever.headline')}
                        </h2>
                        <p className={LEDE}>
                            {t('public.landing.forever.lede', {
                                brand: brand.name,
                            })}
                        </p>
                    </div>

                    <div className="card flex flex-col gap-10 p-7 lg:p-12">
                        <span className="eyebrow self-center">
                            {t('public.landing.forever.title')}
                        </span>

                        <div className="grid gap-8 lg:grid-cols-3">
                            {(['access', 'download', 'no_sub'] as const).map(
                                (item) => (
                                    <div
                                        key={item}
                                        className="flex flex-col gap-2"
                                    >
                                        <span className="bg-brand text-brand-foreground flex size-10 items-center justify-center rounded-full">
                                            <Check light className="" />
                                        </span>
                                        <h3 className="font-display text-[1.35rem] leading-tight font-medium">
                                            {t(
                                                `public.landing.forever.${item}.title`,
                                            )}
                                        </h3>
                                        <p className="text-brand-muted text-[1rem]">
                                            {t(
                                                `public.landing.forever.${item}.body`,
                                            )}
                                        </p>
                                    </div>
                                ),
                            )}
                        </div>

                        <p className="font-display border-brand-gold/40 bg-brand-linen text-brand rounded-md border px-6 py-4 text-center text-[1.15rem] italic">
                            {t('public.landing.forever.banner')}
                        </p>

                        <div className="grid items-center gap-10 lg:grid-cols-2">
                            <div className="flex flex-col items-center gap-5 text-center">
                                <span className="font-display text-brand text-6xl leading-none font-medium tabular-nums">
                                    {formatPrice(price)}
                                </span>
                                <span className="text-brand-muted text-base">
                                    {t('public.landing.forever.per')}
                                </span>
                                <Link href="/acheter" className={PRIMARY}>
                                    {t('public.landing.cta_start')} →
                                </Link>
                                <p className="text-brand-muted flex items-center gap-2 text-[0.95rem]">
                                    <Lock />
                                    {t('public.landing.price.reassurance')}
                                </p>
                                {mode === 'prevente' && (
                                    <p className="text-brand-muted text-[0.95rem]">
                                        {t(
                                            'public.landing.price.prevente_body',
                                        )}
                                    </p>
                                )}
                            </div>
                            <img
                                src="/img/landing/livre.jpg"
                                alt={t('public.landing.book.photo_alt')}
                                width="1400"
                                height="875"
                                loading="lazy"
                                className="border-brand-gold aspect-[4/3] w-full rounded-xl border-2 object-cover"
                            />
                        </div>
                    </div>
                </div>
            </section>

            {/* La bande de confiance ============================================ */}
            <section
                aria-label={t('public.landing.commitments.title')}
                className="border-brand-sand border-y"
            >
                <ul className="mx-auto flex w-full max-w-6xl flex-col gap-x-10 gap-y-3 px-6 py-6 sm:flex-row sm:flex-wrap sm:items-center sm:justify-center">
                    {(['eu', 'no_training', 'refund'] as const).map((k) => (
                        <li
                            key={k}
                            className="font-display text-brand flex items-center gap-2.5 text-[1.15rem]"
                        >
                            <Check />
                            {t(`public.landing.trust.${k}`)}
                        </li>
                    ))}
                </ul>
            </section>

            {/* La garantie ====================================================== */}
            <section
                aria-labelledby="guarantee"
                className="mx-auto flex w-full max-w-4xl flex-col items-center gap-4 px-6 py-16 text-center lg:py-20"
            >
                <h2
                    id="guarantee"
                    className="font-display text-[1.75rem] leading-[1.25] font-medium sm:text-3xl lg:text-4xl"
                >
                    {t('public.landing.guarantee.headline')}
                </h2>
                <p className={LEDE}>{t('public.landing.guarantee.body')}</p>
            </section>

            {/* Pensé pour les grands-parents, et l'essai ======================== */}
            <section
                aria-labelledby="tested"
                className="mx-auto grid w-full max-w-6xl overflow-hidden rounded-2xl lg:grid-cols-2"
            >
                <img
                    src="/img/landing/etape-2.jpg"
                    alt={t('public.landing.tested.photo_alt')}
                    width="1400"
                    height="933"
                    loading="lazy"
                    className="aspect-[4/3] h-full w-full object-cover lg:aspect-auto"
                />
                <div className="bg-brand-deep flex flex-col gap-7 px-7 py-12 text-[#F7F1E6] lg:px-14 lg:py-16">
                    <h2
                        id="tested"
                        className="font-display text-[2rem] leading-[1.1] font-medium text-[#F7F1E6] sm:text-4xl"
                    >
                        {t('public.landing.tested.title')}
                    </h2>
                    <p className="text-lg text-[#C9C0B2]">
                        {t('public.landing.tested.lede')}
                    </p>
                    <ul className="grid gap-3 sm:grid-cols-3">
                        {(['no_writing', 'no_app', 'no_password'] as const).map(
                            (k) => (
                                <li
                                    key={k}
                                    className="rounded-md bg-white/8 px-4 py-5 text-center text-[1.05rem] font-medium"
                                >
                                    {t(`public.landing.tested.${k}`)}
                                </li>
                            ),
                        )}
                    </ul>
                    <Link
                        href="/essai"
                        className="bg-brand-surface text-brand hover:bg-brand-linen inline-flex min-h-[3.5rem] items-center justify-center gap-2 rounded-md px-7 text-[1.05rem] font-semibold"
                    >
                        <span className="bg-brand-accent size-2.5 rounded-full" />
                        {t('public.landing.tested.cta')}
                    </Link>
                </div>
            </section>

            {/* La double page ================================================== */}
            <section
                aria-labelledby="book"
                className="mx-auto w-full max-w-6xl px-6 py-16 lg:py-24"
            >
                <div className="bg-brand-linen flex flex-col items-start gap-8 rounded-2xl px-6 py-14 text-left lg:items-center lg:px-16 lg:text-center">
                    <span className="eyebrow">
                        {t('public.landing.book.title')}
                    </span>
                    <h2 id="book" className={`${H2} max-w-[22em]`}>
                        {t('public.landing.book.headline')}
                    </h2>
                    <p className={`${LEDE} max-w-[36em]`}>
                        {t('public.landing.book.body')}
                    </p>
                    <Link href="/acheter" className={PRIMARY}>
                        {t('public.landing.cta')}
                    </Link>

                    <figure className="bg-brand-surface grid w-full max-w-4xl overflow-hidden rounded-xl shadow-[0_30px_70px_rgba(38,33,28,0.22)] sm:grid-cols-2">
                        <img
                            src="/img/landing/livre.jpg"
                            alt=""
                            width="1400"
                            height="875"
                            loading="lazy"
                            className="aspect-[4/3] w-full object-cover sm:aspect-auto sm:h-full"
                        />
                        <div className="flex flex-col gap-4 p-7 text-left">
                            <span className="text-brand-muted text-[0.75rem] font-semibold tracking-[0.12em] uppercase">
                                Chapitre 3
                            </span>
                            <span className="font-display text-brand text-[1.5rem] leading-tight font-medium">
                                {t('public.landing.product.mockup.chapter')}
                            </span>
                            <p className="text-brand-muted font-display text-[1.05rem] leading-relaxed italic">
                                {t('public.landing.proof.sample_fluide')}
                            </p>
                            <div className="mt-auto flex items-center gap-3 pt-2">
                                <FauxQr className="border-brand-sand size-16 flex-none rounded-sm border p-1" />
                                <span className="text-brand-muted text-[0.85rem] leading-snug">
                                    {t('public.landing.product.mockup.scan')}
                                </span>
                            </div>
                        </div>
                    </figure>

                    <p className="text-brand-muted max-w-[40em] text-[0.95rem]">
                        {t('public.landing.book.qr')}
                    </p>
                </div>
            </section>

            {/* La relecture ===================================================== */}
            <section
                aria-labelledby="review"
                className="mx-auto grid w-full max-w-6xl gap-10 px-6 pb-16 lg:grid-cols-[6fr_6fr] lg:items-center lg:gap-20 lg:pb-24"
            >
                <div className="flex flex-col gap-6">
                    <h2 id="review" className={H2}>
                        {t('public.landing.review.headline')}
                    </h2>
                    <p className={LEDE}>{t('public.landing.review.body')}</p>

                    <figure
                        aria-label={t('public.landing.proof.aria')}
                        className="card overflow-hidden"
                    >
                        <div className="border-brand-sand grid border-b sm:grid-cols-2">
                            <div className="text-brand-muted px-5 py-3 text-[0.75rem] font-semibold tracking-[0.08em] uppercase">
                                {t('public.landing.proof.verbatim')}
                            </div>
                            <div className="border-brand-sand text-brand border-t px-5 py-3 text-[0.75rem] font-semibold tracking-[0.08em] uppercase sm:border-t-0 sm:border-l">
                                {t('public.landing.proof.fluide')}
                            </div>
                        </div>
                        <div className="grid sm:grid-cols-2">
                            <p className="bg-brand-linen text-brand-muted px-5 py-5 text-[0.95rem] leading-relaxed italic">
                                {t('public.landing.proof.sample_verbatim')}
                            </p>
                            <p className="border-brand-sand font-display border-t px-5 py-5 text-[1.05rem] leading-relaxed sm:border-t-0 sm:border-l">
                                {t('public.landing.proof.sample_fluide')}
                            </p>
                        </div>
                        <div className="border-brand-sand text-brand-muted flex flex-wrap items-center gap-2 border-t px-5 py-3.5 text-[0.9rem]">
                            <span>{t('public.landing.proof.then')}</span>
                            {(['share', 'keep', 'later'] as const).map((c) => (
                                <span key={c} className="chip">
                                    {t(`public.landing.proof.${c}`)}
                                </span>
                            ))}
                        </div>
                    </figure>
                </div>

                <div className="mx-auto w-full max-w-[380px]">
                    <div className="bg-brand-deep rounded-[2.2rem] p-3 shadow-[0_30px_70px_rgba(38,33,28,0.28)]">
                        <img
                            src="/img/landing/relecture.png"
                            alt={t('public.landing.review.screenshot_alt')}
                            width="780"
                            height="1600"
                            loading="lazy"
                            className="w-full rounded-[1.6rem]"
                        />
                    </div>
                </div>
            </section>

            {/* Le cadeau ======================================================== */}
            <section
                aria-labelledby="gift"
                className="mx-auto w-full max-w-6xl px-6 pb-8"
            >
                <div className="bg-brand-linen grid gap-8 rounded-2xl px-7 py-12 lg:grid-cols-[7fr_5fr] lg:items-center lg:px-14">
                    <div className="flex flex-col gap-5">
                        <h2 id="gift" className={H2}>
                            {t('public.landing.gift.headline')}
                        </h2>
                        <p className="text-brand-text text-lg leading-relaxed">
                            {t('public.landing.gift.body')}
                        </p>
                        <Link href="/acheter" className={`${PRIMARY} w-fit`}>
                            {t('public.landing.cta')}
                        </Link>
                    </div>
                    <div className="bg-brand-surface mx-auto flex w-full max-w-[320px] rotate-[-3deg] flex-col gap-4 rounded-md px-7 py-9 shadow-[0_20px_50px_rgba(38,33,28,0.18)]">
                        <span className="font-display text-brand text-2xl leading-tight font-medium italic">
                            {t('public.landing.gift.card_name')},
                        </span>
                        <span className="bg-brand-sand h-1.5 w-full rounded-full" />
                        <span className="bg-brand-sand h-1.5 w-[86%] rounded-full" />
                        <span className="bg-brand-sand h-1.5 w-[70%] rounded-full" />
                        <div className="flex items-center gap-3 pt-2">
                            <FauxQr className="border-brand-sand size-12 flex-none rounded-sm border p-0.5" />
                            <span className="text-brand-muted text-[0.75rem] leading-snug">
                                {t('public.landing.product.mockup.scan')}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            {/* Les options ====================================================== */}
            <section
                aria-label={t('public.landing.price.title')}
                className="mx-auto grid w-full max-w-6xl gap-4 px-6 pb-20 lg:grid-cols-2 lg:pb-24"
            >
                <div className="bg-brand-deep flex flex-col gap-3 rounded-2xl px-8 py-10 text-[#F7F1E6]">
                    <h3 className="font-display text-[1.75rem] leading-tight font-medium text-[#F7F1E6]">
                        {t('public.landing.tiles.phone.title')}
                    </h3>
                    <p className="text-[#C9C0B2]">
                        {t('public.landing.tiles.phone.body')}
                    </p>
                    <p className="font-display text-brand-gold mt-auto pt-4 text-3xl tabular-nums">
                        +{formatPrice(phoneOptionPrice)}
                    </p>
                </div>
                <div className="card flex flex-col gap-3 px-8 py-10">
                    <h3 className="font-display text-[1.75rem] leading-tight font-medium">
                        {t('public.landing.tiles.copies.title')}
                    </h3>
                    <p className="text-brand-muted">
                        {t('public.landing.tiles.copies.body')}
                    </p>
                    <p className="font-display text-brand mt-auto pt-4 text-3xl tabular-nums">
                        {formatPrice(extraCopyPrice)}{' '}
                        <span className="text-brand-muted text-base">
                            {t('public.landing.tiles.copies.each')}
                        </span>
                    </p>
                </div>
            </section>

            {/* Questions fréquentes ============================================= */}
            <section
                id="questions"
                aria-labelledby="faq"
                className="border-brand-sand mx-auto grid w-full max-w-6xl gap-10 border-t px-6 py-16 lg:grid-cols-[4fr_8fr] lg:gap-16 lg:py-24"
            >
                <h2 id="faq" className={H2}>
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
