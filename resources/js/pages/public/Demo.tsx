import { Head } from '@inertiajs/react';

import { useUrls } from '@/hooks/useLocale';
import AudioPlayer from '@/components/AudioPlayer';
import { LevelBars } from '@/components/LevelBars';
import { useT } from '@/hooks/useT';
import { formatDuration } from '@/recorder/duration';
import { useLocalRecorder } from '@/recorder/useLocalRecorder';

type Props = {
    limits: {
        demoSeconds: number;
        segmentMilliseconds: number;
        acceptedMimes: string[];
    };
};

function MicIcon() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            aria-hidden="true"
            className="record-icon"
        >
            <rect x="9" y="3" width="6" height="11" rx="3" />
            <path d="M5 11a7 7 0 0 0 14 0M12 18v3M9 21h6" />
        </svg>
    );
}

/**
 * L'essai en soixante secondes (T-151).
 *
 * Ce que cette page **ne fait pas** est son intérêt : rien ne part. Pas
 * d'appel réseau, pas d'écriture dans IndexedDB, pas de brouillon. Les
 * tranches restent en mémoire et l'URL de lecture est révoquée à la
 * fermeture. Le recorder du bloc 04, lui, écrit chaque tranche sur le
 * téléphone avant tout — c'est ce qui rend un appel entrant survivable — mais
 * ici il n'y a rien à sauver : quelqu'un qui essaie le service ne nous a rien
 * confié, et ne doit rien laisser derrière lui.
 *
 * C'est aussi ce que le test bout en bout vérifie : zéro requête vers
 * `/recordings`.
 *
 * Elle répond à deux questions, dans cet ordre. « Est-ce que ma mère va y
 * arriver ? » — on lui donne l'écran du narrateur, le même bouton, le même
 * vu-mètre, la même question de la semaine, et il l'essaie lui-même. Puis
 * « qu'est-ce que j'achète, au juste ? » — le mot à mot et le texte mis au
 * propre, côte à côte. Sur l'exemple d'Odette, et la page dit pourquoi : nous
 * n'avons pas entendu son essai, nous n'avons donc rien à en écrire.
 */
export default function Demo({ limits }: Props) {
    const urls = useUrls();
    const t = useT();

    const { phase, seconds, levels, playbackUrl, start, stop, again } =
        useLocalRecorder({
            maxSeconds: limits.demoSeconds,
            segmentMilliseconds: limits.segmentMilliseconds,
        });

    return (
        <div className="mx-auto w-full max-w-3xl px-6 py-10 lg:py-16">
            <Head title={t('public.seo.demo.title')} />

            <span className="eyebrow">{t('public.demo.eyebrow')}</span>

            <h1 className="font-display mt-4 text-[2.25rem] leading-[1.1] font-medium sm:text-5xl">
                {t('public.demo.title')}
            </h1>

            <p className="text-brand-muted mt-4 max-w-[34em] text-lg leading-snug">
                {t('public.demo.body')}
            </p>

            {/*
             * La carte de la question, celle du héros : c'est le premier objet
             * que le narrateur voit dans son SMS, et l'acheteur doit le
             * reconnaître quand il le décrira au téléphone à sa mère.
             */}
            <figure className="card mt-9 flex flex-col gap-3 px-6 py-5">
                <figcaption className="text-brand-muted text-[0.78rem] font-semibold tracking-[0.08em] uppercase">
                    {t('public.demo.question_label')}
                </figcaption>
                <p className="font-display text-[1.35rem] leading-[1.3] font-medium">
                    {t('public.demo.question')}
                </p>
            </figure>

            {phase === 'unsupported' ? (
                <p role="status" className="mt-8 text-lg">
                    {t('public.demo.unsupported')}
                </p>
            ) : null}

            {phase === 'refused' ? (
                <p role="status" className="mt-8 text-lg">
                    {t('public.demo.refused')}
                </p>
            ) : null}

            {phase === 'idle' ? (
                <section className="mt-4 flex flex-col items-center gap-4 text-center">
                    <div className="record-halo">
                        <button
                            type="button"
                            onClick={() => void start()}
                            className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep press record-dial flex flex-col items-center justify-center gap-2 rounded-full shadow-[0_18px_40px_rgba(176,67,42,0.35)] transition-colors"
                        >
                            <MicIcon />
                            <span className="record-label leading-none font-semibold">
                                {t('public.demo.start')}
                            </span>
                        </button>
                    </div>
                    <p className="text-brand-muted max-w-sm text-base">
                        {t('public.demo.start_hint')}
                    </p>
                </section>
            ) : null}

            {phase === 'recording' ? (
                <section className="mt-4 flex flex-col items-center gap-5 text-center">
                    <div className="record-halo">
                        <div className="bg-brand-accent text-brand-accent-foreground record-dial flex flex-col items-center justify-center rounded-full">
                            <span className="record-time leading-none font-semibold tabular-nums">
                                {formatDuration(seconds)}
                            </span>
                        </div>
                    </div>

                    <p
                        role="status"
                        className="flex items-center gap-2.5 text-lg font-medium"
                    >
                        <span
                            aria-hidden="true"
                            className="bg-brand-accent size-3 flex-none rounded-full"
                        />
                        {t('public.demo.recording')}
                        <span className="sr-only">
                            {t('public.demo.elapsed', {
                                time: formatDuration(seconds),
                            })}
                        </span>
                    </p>

                    <LevelBars levels={levels} />

                    <button
                        type="button"
                        onClick={stop}
                        className="border-brand text-brand hover:bg-brand/5 press min-h-[3rem] rounded-md border-2 px-8 py-3 text-lg font-semibold"
                    >
                        {t('public.demo.stop')}
                    </button>
                </section>
            ) : null}

            {phase === 'ready' && playbackUrl !== null ? (
                <section className="mt-8 flex flex-col gap-4">
                    <h2 className="font-display text-2xl font-medium">
                        {t('public.demo.ready')}
                    </h2>

                    <AudioPlayer src={playbackUrl} />

                    <button
                        type="button"
                        onClick={again}
                        className="border-brand text-brand hover:bg-brand/5 press min-h-[2.75rem] w-fit rounded-md border-2 px-6 py-3 font-semibold"
                    >
                        {t('public.demo.again')}
                    </button>
                </section>
            ) : null}

            {/*
             * La démonstration proprement dite, et elle n'arrive qu'après :
             * on ne montre ce que devient une voix qu'à qui vient d'en donner
             * une. L'exemple est celui d'Odette, et la page dit pourquoi —
             * c'est la promesse « rien ne part » qui l'impose, pas une pudeur.
             */}
            {phase === 'ready' ? (
                <section className="border-brand-sand mt-12 flex flex-col gap-5 border-t pt-10">
                    <h2 className="font-display text-[1.75rem] leading-tight font-medium">
                        {t('public.demo.result_title')}
                    </h2>
                    <p className="text-brand-muted max-w-[36em] leading-relaxed">
                        {t('public.demo.result_body')}
                    </p>

                    <figure
                        aria-label={t('public.landing.proof.aria')}
                        className="card overflow-hidden"
                    >
                        {/*
                         * La question d'Odette, en tête de son exemple.
                         * L'essai n'est plus posé sur la même : sans cette
                         * ligne, la page a l'air de répondre à celle qu'on
                         * vient de poser au visiteur, et l'exemple ment.
                         */}
                        <div className="border-brand-sand flex flex-col gap-1.5 border-b px-5 py-4">
                            <span className="text-brand-muted text-[0.75rem] font-semibold tracking-[0.08em] uppercase">
                                {t('public.demo.result_question_label')}
                            </span>
                            <p className="font-display text-[1.15rem] leading-[1.3] font-medium">
                                {t('public.landing.hero.card.question')}
                            </p>
                        </div>

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
                </section>
            ) : null}

            <p className="text-brand-muted mt-10 text-base">
                {t('public.demo.nothing_sent')}
            </p>

            {/*
             * Lien ordinaire lui aussi : cette page est la seule du site
             * public à qui le micro est ouvert, et une navigation Inertia
             * emporterait cette permission sur toutes les suivantes.
             */}
            <a
                href={urls.checkout_show}
                className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep press mt-4 inline-block min-h-[3rem] rounded-md px-8 py-3.5 text-lg font-semibold"
            >
                {t('public.demo.cta')}
            </a>
        </div>
    );
}
