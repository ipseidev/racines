import { Head, Link } from '@inertiajs/react';
import { useState, type KeyboardEvent } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import HeroSample from '@/components/HeroSample';
import { Check } from '@/components/marketing/Check';
import Newsletter from '@/components/marketing/Newsletter';
import { H2, LEDE, PRIMARY } from '@/components/marketing/styles';
import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

type Props = {
    /** `pilot`, `prevente` ou `core`. */
    mode: string;
    /** Le prix vu par ce visiteur, en centimes. */
    price: number;
    /** La réduction contre une adresse (T-141) : proposée ou non, et son pourcentage. */
    welcomeOffer: { enabled: boolean; discountPercent: number };
    /** L'extrait écoutable d'Odette (T-149), absent tant que le fichier l'est. */
    heroSample: { src: string; disclosed: boolean } | null;
};

/** À qui est le livre : on l'offre, ou on raconte soi-même. */
type Audience = 'gift' | 'self';

const AUDIENCES: readonly Audience[] = ['gift', 'self'];

/** Les deux versions du texte, dans l'ordre où elles naissent. */
type Rendering = 'verbatim' | 'fluide';

const RENDERINGS: readonly Rendering[] = ['verbatim', 'fluide'];

/*
 * Les six étapes, dans l'ordre où elles se vivent (doc 03 §5.1). « Elle
 * décide » précède « la famille écoute » : inverser les deux décrirait un
 * produit où la famille lit avant l'accord, et un test du serveur garde
 * l'ordre du catalogue.
 *
 * Chaque étape porte sa photo et son lien secondaire, comme chez le leader.
 * La troisième montre l'écran lui-même, la page de relecture : c'est là que
 * les deux textes apparaissent. Les liens vers l'essai sont des `<a>`
 * ordinaires (T-151), les ancres de l'accueil aussi.
 */
const STEPS = [
    { key: 'questions', photo: 'etape-1', href: null },
    { key: 'record', photo: 'etape-2', href: '/essai' },
    { key: 'text', photo: 'relecture', href: '#texte' },
    { key: 'decide', photo: 'etape-3', href: '/#commitments' },
    { key: 'family', photo: 'etape-4', href: '/#questions' },
    { key: 'book', photo: 'livre', href: '/#livre' },
] as const;

const SAMPLES = ['first_memory', 'dish', 'meeting', 'value'] as const;

const CHOICES = ['share', 'keep', 'later'] as const;

/* Quatre réponses de l'accueil, celles qu'on se pose ici. */
const QUESTIONS = ['no_smartphone', 'refuses', 'edit', 'privacy'] as const;

const POINTS = ['questions', 'photos', 'listen', 'reply'] as const;

/** L'étiquette en petites capitales, comme sur l'accueil. */
const LABEL =
    'text-brand-muted text-[0.78rem] font-semibold tracking-[0.12em] uppercase';

/** Le lien secondaire d'une étape : souligné, une flèche, rien de plus. */
const TEXT_LINK =
    'text-brand inline-flex items-center gap-2 text-[1.05rem] font-semibold underline underline-offset-4';

/**
 * « Comment ça marche », la page (T-208).
 *
 * La structure est celle de la page du leader, relevée sur son HTML et une
 * capture : un bandeau de titre avec deux onglets, une accroche et un média,
 * six étapes sur une frise, « Encore des questions ? », un bandeau d'appel,
 * la technologie du texte, une section sombre avec une citation et deux
 * cartes, « réunir les générations », l'adresse contre une réduction. Les
 * onglets remplacent tout le contenu en dessous : chaque variante a ses six
 * étapes et ses appels.
 *
 * Les mots sont les nôtres, et ce qui n'existe pas chez nous n'y est pas : ni
 * avis de clients, ni vidéo, ni troisième rendu.
 *
 * Les liens vers l'essai sont des `<a>` ordinaires et non des `<Link>`
 * Inertia : la politique de permissions vaut pour le document, et une
 * navigation Inertia garderait celle de cette page, où le micro est
 * interdit ; Safari refuserait alors l'essai sans demander l'autorisation
 * (T-151).
 */
export default function HowItWorks({ price, welcomeOffer, heroSample }: Props) {
    const t = useT();
    const brand = useBrand();
    const [audience, setAudience] = useState<Audience>('gift');
    const [rendering, setRendering] = useState<Rendering>('verbatim');

    const buy =
        audience === 'gift'
            ? t('public.landing.cta')
            : t('public.how_it_works.cta.self.button');

    // Les flèches passent d'un onglet à l'autre ; il n'y en a que deux.
    const onTabKey = (event: KeyboardEvent<HTMLButtonElement>) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        event.preventDefault();
        setRendering(rendering === 'verbatim' ? 'fluide' : 'verbatim');
    };

    return (
        <>
            <Head title={t('public.how_it_works.seo_title')} />

            {/* Le bandeau de titre, la question, les deux onglets ============= */}
            <section className="mx-auto flex w-full max-w-3xl flex-col items-center gap-6 px-6 pt-14 pb-10 text-center lg:pt-20 lg:pb-14">
                <h1 className="font-display text-[2.5rem] leading-[1.05] font-medium sm:text-5xl lg:text-[3.5rem]">
                    {t('public.how_it_works.hero.title')}
                </h1>
                <p className="text-brand-muted text-lg leading-snug">
                    {t('public.how_it_works.hero.question')}
                </p>
                <div
                    role="group"
                    aria-label={t('public.how_it_works.hero.toggle_label')}
                    className="border-brand-sand bg-brand-surface inline-flex rounded-full border p-1"
                >
                    {AUDIENCES.map((one) => (
                        <button
                            key={one}
                            type="button"
                            aria-pressed={audience === one}
                            onClick={() => setAudience(one)}
                            className={`min-h-[2.75rem] rounded-full px-6 text-base font-semibold transition-colors ${
                                audience === one
                                    ? 'bg-brand text-brand-foreground'
                                    : 'text-brand-muted hover:text-brand'
                            }`}
                        >
                            {t(`public.how_it_works.hero.${one}`)}
                        </button>
                    ))}
                </div>
            </section>

            {/* L'accroche, et l'extrait à écouter ============================== */}
            <section
                aria-labelledby="intro"
                className="mx-auto grid w-full max-w-6xl gap-10 px-6 py-12 lg:grid-cols-[7fr_5fr] lg:items-center lg:gap-16 lg:py-16"
            >
                <div className="flex flex-col gap-5">
                    <h2 id="intro" className={H2}>
                        {t(`public.how_it_works.intro.${audience}.headline`)}
                    </h2>
                    <p className={LEDE}>
                        {t(`public.how_it_works.intro.${audience}.lede`)}
                    </p>
                </div>

                <figure
                    aria-label={t('public.landing.hero.card.aria')}
                    className="card flex flex-col gap-3.5 px-6 py-5 shadow-[0_24px_60px_rgba(38,33,28,0.14)]"
                >
                    <div className="text-brand-muted flex justify-between text-[0.78rem] font-semibold tracking-[0.08em] uppercase">
                        <span>{t('public.landing.hero.card.label')}</span>
                        <span>{t('public.landing.hero.card.name')}</span>
                    </div>
                    <p className="font-display text-[1.35rem] leading-[1.3] font-medium">
                        {t('public.landing.hero.card.question')}
                    </p>
                    <HeroSample sample={heroSample} />
                    <figcaption className="text-brand-muted flex items-center gap-2 text-[0.95rem]">
                        <span className="bg-brand-accent size-2.5 flex-none rounded-full" />
                        <span>
                            <span className="text-brand font-semibold">
                                {t('public.how_it_works.intro.listen')}
                            </span>{' '}
                            · {t('public.how_it_works.intro.caption')}
                        </span>
                    </figcaption>
                </figure>
            </section>

            {/* Les six étapes, sur une frise ==================================== */}
            <section aria-labelledby="steps" className="bg-brand-linen">
                <div className="mx-auto w-full max-w-6xl px-6 py-16 lg:py-24">
                    <h2 id="steps" className="sr-only">
                        {t('public.how_it_works.steps.title')}
                    </h2>

                    <ol
                        aria-labelledby="steps"
                        className="timeline-rail relative flex flex-col gap-16 lg:gap-24"
                    >
                        {STEPS.map((step, index) => {
                            const base = `public.how_it_works.steps.${step.key}`;
                            const link = t(`${base}.${audience}.link`);

                            return (
                                <li
                                    key={step.key}
                                    className="relative grid gap-6 pl-10 lg:grid-cols-2 lg:grid-rows-[auto_1fr] lg:gap-x-16 lg:gap-y-5"
                                >
                                    {/* Le point sur la frise. */}
                                    <span
                                        aria-hidden="true"
                                        className="bg-brand-gold border-brand-linen absolute top-1.5 left-0 size-6 rounded-full border-4"
                                    />

                                    <div className="flex flex-col gap-3 lg:col-start-1 lg:row-start-1">
                                        <span className={LABEL}>
                                            {t(
                                                'public.how_it_works.steps.label',
                                                { n: index + 1 },
                                            )}
                                        </span>
                                        <h3 className="font-display text-[1.75rem] leading-[1.2] font-medium sm:text-3xl">
                                            {t(`${base}.${audience}.title`)}
                                        </h3>
                                    </div>

                                    {/*
                                     * Sur téléphone, l'image vient sous le titre et
                                     * avant le texte ; sur bureau, elle tient la
                                     * colonne de droite sur toute la hauteur.
                                     */}
                                    <div className="relative lg:col-start-2 lg:row-span-2 lg:row-start-1">
                                        {step.photo === 'relecture' ? (
                                            <div className="bg-brand-surface flex justify-center rounded-2xl px-6 py-8">
                                                <div className="bg-brand-deep w-full max-w-[300px] rounded-[2rem] p-2.5 shadow-[0_30px_70px_rgba(38,33,28,0.28)]">
                                                    <img
                                                        {...photo(
                                                            'relecture',
                                                            780,
                                                        )}
                                                        sizes="280px"
                                                        alt={t(`${base}.alt`)}
                                                        width="780"
                                                        height="1600"
                                                        loading="lazy"
                                                        className="w-full rounded-[1.5rem]"
                                                    />
                                                </div>
                                            </div>
                                        ) : (
                                            <img
                                                {...photo(step.photo)}
                                                sizes="(min-width: 1024px) 34rem, 100vw"
                                                alt={t(`${base}.alt`)}
                                                width="1400"
                                                height="933"
                                                loading="lazy"
                                                className="aspect-[4/3] w-full rounded-2xl object-cover"
                                            />
                                        )}
                                        <span className="bg-brand-surface text-brand absolute top-4 left-4 rounded-full px-3 py-1.5 text-[0.8rem] font-semibold tabular-nums shadow-[0_2px_8px_rgba(38,33,28,0.18)]">
                                            {t(
                                                'public.how_it_works.steps.badge',
                                                { n: index + 1 },
                                            )}
                                        </span>
                                    </div>

                                    <div className="flex flex-col gap-5 lg:col-start-1 lg:row-start-2">
                                        <p className="text-brand-muted text-[1.05rem] leading-relaxed">
                                            {t(`${base}.${audience}.body`)}
                                        </p>

                                        {step.key === 'questions' && (
                                            <details className="group">
                                                <summary
                                                    className={`${TEXT_LINK} cursor-pointer list-none`}
                                                >
                                                    {link}
                                                    <span
                                                        aria-hidden="true"
                                                        className="transition-transform group-open:rotate-90"
                                                    >
                                                        →
                                                    </span>
                                                </summary>
                                                <ul className="card mt-4 flex flex-col gap-2.5 p-5">
                                                    {SAMPLES.map((sample) => (
                                                        <li
                                                            key={sample}
                                                            className="font-display text-brand flex gap-3 text-[1.1rem] leading-snug"
                                                        >
                                                            <span
                                                                aria-hidden="true"
                                                                className="bg-brand-gold mt-3 h-0.5 w-4 flex-none"
                                                            />
                                                            {t(
                                                                `${base}.samples.${sample}`,
                                                            )}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </details>
                                        )}

                                        {step.href !== null && (
                                            <a
                                                href={step.href}
                                                className={TEXT_LINK}
                                            >
                                                {link}
                                                <span aria-hidden="true">
                                                    →
                                                </span>
                                            </a>
                                        )}
                                    </div>
                                </li>
                            );
                        })}
                    </ol>
                </div>
            </section>

            {/* Encore des questions ? =========================================== */}
            <section
                aria-labelledby="questions"
                className="mx-auto flex w-full max-w-5xl flex-col items-center gap-10 px-6 py-16 lg:py-24"
            >
                <h2 id="questions" className={`${H2} text-center`}>
                    {t('public.how_it_works.questions.title')}
                </h2>
                <dl className="grid w-full gap-x-12 gap-y-8 lg:grid-cols-2">
                    {QUESTIONS.map((question) => (
                        <div key={question} className="flex flex-col gap-2">
                            <dt className="text-brand text-xl font-semibold">
                                {t(`public.landing.faq.${question}.q`)}
                            </dt>
                            <dd className="text-brand-muted leading-relaxed">
                                {t(`public.landing.faq.${question}.a`)}
                            </dd>
                        </div>
                    ))}
                </dl>
                <a href="/#questions" className={TEXT_LINK}>
                    {t('public.how_it_works.questions.all')}
                    <span aria-hidden="true">→</span>
                </a>
            </section>

            {/* Le bandeau d'appel =============================================== */}
            <section
                aria-labelledby="cta"
                className="mx-auto w-full max-w-6xl px-6 pb-16 lg:pb-24"
            >
                <div className="flex flex-col items-center gap-6 rounded-2xl bg-[linear-gradient(135deg,var(--color-brand),var(--color-brand-deep))] px-7 py-14 text-center text-[#F7F1E6] lg:px-16 lg:py-20">
                    <h2
                        id="cta"
                        className="font-display max-w-[22em] text-[2rem] leading-[1.1] font-medium text-[#F7F1E6] sm:text-4xl"
                    >
                        {t(`public.how_it_works.cta.${audience}.headline`)}
                    </h2>
                    <p className="max-w-[36em] text-lg text-[#C9C0B2]">
                        {t(`public.how_it_works.cta.${audience}.body`)}
                    </p>
                    <Link
                        href="/acheter"
                        className={`${PRIMARY} w-full sm:w-auto`}
                    >
                        {buy} · {formatPrice(price)}
                    </Link>
                </div>
            </section>

            {/* Le texte, en deux versions ======================================= */}
            <section
                id="texte"
                aria-labelledby="rendering"
                className="mx-auto flex w-full max-w-6xl flex-col items-center gap-10 px-6 py-16 lg:py-24"
            >
                <div className="flex max-w-[40em] flex-col items-center gap-4 text-center">
                    <span className="eyebrow">
                        {t('public.how_it_works.rendering.eyebrow')}
                    </span>
                    <h2 id="rendering" className={H2}>
                        {t('public.how_it_works.rendering.headline')}
                    </h2>
                    <p className={LEDE}>
                        {t('public.how_it_works.rendering.lede')}
                    </p>
                </div>

                <div className="card grid w-full overflow-hidden lg:grid-cols-[5fr_7fr]">
                    <div className="bg-brand-linen flex flex-col gap-4 p-6 lg:p-8">
                        <img
                            {...photo('etape-2')}
                            sizes="(min-width: 1024px) 24rem, 100vw"
                            alt=""
                            width="1400"
                            height="933"
                            loading="lazy"
                            className="aspect-[4/3] w-full rounded-xl object-cover"
                        />
                        <span className={LABEL}>
                            {t('public.how_it_works.rendering.question_label')}
                        </span>
                        <p className="font-display text-brand text-[1.25rem] leading-snug font-medium">
                            {t('public.landing.hero.card.question')}
                        </p>
                        <HeroSample sample={heroSample} />
                    </div>

                    <div className="flex flex-col">
                        <div
                            role="tablist"
                            aria-label={t(
                                'public.how_it_works.rendering.tabs_label',
                            )}
                            className="border-brand-sand flex gap-2 border-b px-4"
                        >
                            {RENDERINGS.map((one) => (
                                <button
                                    key={one}
                                    type="button"
                                    role="tab"
                                    id={`tab-${one}`}
                                    aria-selected={rendering === one}
                                    aria-controls={`panel-${one}`}
                                    onClick={() => setRendering(one)}
                                    onKeyDown={onTabKey}
                                    className={`tab ${rendering === one ? 'tab-current' : ''}`}
                                >
                                    {t(`public.landing.proof.${one}`)}
                                </button>
                            ))}
                        </div>

                        <div
                            role="tabpanel"
                            id={`panel-${rendering}`}
                            aria-labelledby={`tab-${rendering}`}
                            className="flex-1 px-6 py-6 lg:px-8"
                        >
                            {rendering === 'verbatim' ? (
                                <p className="text-brand-muted text-[1.05rem] leading-relaxed italic">
                                    {t('public.landing.proof.sample_verbatim')}
                                </p>
                            ) : (
                                <p className="font-display text-[1.15rem] leading-relaxed">
                                    {t('public.landing.proof.sample_fluide')}
                                </p>
                            )}
                        </div>

                        <div className="border-brand-sand text-brand-muted flex flex-wrap items-center gap-2 border-t px-6 py-4 text-[0.9rem] lg:px-8">
                            <span>{t('public.landing.proof.then')}</span>
                            {CHOICES.map((choice) => (
                                <span key={choice} className="chip">
                                    {t(`public.landing.proof.${choice}`)}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* La citation, et les deux cartes ================================== */}
            <section aria-labelledby="voice" className="bg-brand-deep">
                <div className="mx-auto flex w-full max-w-6xl flex-col items-center gap-12 px-6 py-16 text-[#F7F1E6] lg:py-24">
                    <h2 id="voice" className="sr-only">
                        {t('public.how_it_works.voice.title')}
                    </h2>
                    <figure className="flex max-w-[30em] flex-col items-center gap-5 text-center">
                        <blockquote className="font-display text-[1.6rem] leading-[1.3] font-medium text-[#F7F1E6] sm:text-[2rem]">
                            {t('public.landing.story.p3')}
                        </blockquote>
                        <figcaption className="text-[#C9C0B2]">
                            {t('public.how_it_works.voice.author', {
                                brand: brand.name,
                            })}
                        </figcaption>
                        <a
                            href="/#histoire"
                            className="bg-brand-surface text-brand hover:bg-brand-linen inline-flex min-h-[3.25rem] items-center justify-center rounded-md px-6 text-[1.0625rem] font-semibold"
                        >
                            {t('public.how_it_works.voice.cta')}
                        </a>
                    </figure>

                    <div className="grid w-full gap-6 lg:grid-cols-2">
                        <div className="text-brand-text flex flex-col items-start gap-4 rounded-2xl bg-[#F7F1E6] p-7 lg:p-9">
                            <h3 className="font-display text-brand text-[1.5rem] leading-tight font-medium">
                                {t('public.landing.faq.title')}
                            </h3>
                            <p className="text-brand-muted text-[1.02rem] leading-relaxed">
                                {t('public.how_it_works.more.faq.body')}
                            </p>
                            <a
                                href="/#questions"
                                className={`${TEXT_LINK} mt-auto`}
                            >
                                {t('public.how_it_works.more.faq.cta')}
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                        <div className="text-brand-text grid gap-5 rounded-2xl bg-[#F7F1E6] p-7 sm:grid-cols-[1fr_auto] sm:items-center lg:p-9">
                            <div className="flex flex-col items-start gap-4">
                                <h3 className="font-display text-brand text-[1.5rem] leading-tight font-medium">
                                    {t('public.how_it_works.more.try.title')}
                                </h3>
                                <p className="text-brand-muted text-[1.02rem] leading-relaxed">
                                    {t('public.how_it_works.more.try.body')}
                                </p>
                                {/* Un `<a>` ordinaire, pour que le micro puisse être demandé (T-151). */}
                                <a
                                    href="/essai"
                                    className={`${TEXT_LINK} mt-auto`}
                                >
                                    {t('public.how_it_works.more.try.cta')}
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                            <a
                                href="/essai"
                                aria-label={t(
                                    'public.how_it_works.more.try.cta',
                                )}
                                className="relative block w-full overflow-hidden rounded-xl sm:w-40"
                            >
                                <img
                                    {...photo('etape-2')}
                                    sizes="(min-width: 640px) 10rem, 100vw"
                                    alt=""
                                    width="1400"
                                    height="933"
                                    loading="lazy"
                                    className="aspect-[4/3] w-full object-cover"
                                />
                                <span
                                    aria-hidden="true"
                                    className="bg-brand-accent absolute top-1/2 left-1/2 flex size-12 -translate-1/2 items-center justify-center rounded-full text-white shadow-[0_4px_14px_rgba(0,0,0,0.3)]"
                                >
                                    <span className="size-3 rounded-full bg-white" />
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            {/* Réunir les générations =========================================== */}
            <section aria-labelledby="together" className="bg-brand-linen">
                <div className="mx-auto grid w-full max-w-6xl gap-10 px-6 py-16 lg:grid-cols-2 lg:items-center lg:gap-16 lg:py-24">
                    <img
                        {...photo('hero')}
                        sizes="(min-width: 1024px) 34rem, 100vw"
                        alt={t('public.how_it_works.together.alt')}
                        width="1400"
                        height="933"
                        loading="lazy"
                        className="aspect-[4/3] w-full rounded-2xl object-cover"
                    />
                    <div className="flex flex-col gap-6">
                        <h2 id="together" className={H2}>
                            {t(
                                `public.how_it_works.together.${audience}.headline`,
                            )}
                        </h2>
                        <p className={LEDE}>
                            {t(`public.how_it_works.together.${audience}.body`)}
                        </p>
                        <ul className="flex flex-col gap-3">
                            {POINTS.map((point) => (
                                <li
                                    key={point}
                                    className="flex gap-3 text-[1.05rem]"
                                >
                                    <Check />
                                    <span>
                                        {t(
                                            `public.how_it_works.together.points.${point}`,
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <Link href="/acheter" className={`${PRIMARY} w-fit`}>
                            {audience === 'gift'
                                ? t('public.landing.cta')
                                : t('public.how_it_works.together.self.button')}
                        </Link>
                    </div>
                </div>
            </section>

            <Newsletter
                enabled={welcomeOffer.enabled}
                discountPercent={welcomeOffer.discountPercent}
            />
        </>
    );
}
