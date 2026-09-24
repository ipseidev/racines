import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';

import { useFormat } from '@/hooks/useFormat';
import { useT } from '@/hooks/useT';
import { celebrateOnce } from '@/lib/celebrate';

type Props = {
    sessionId?: string | null;
    forSelf: boolean;
    /** « Dès la commande » : l'invitation est déjà partie (T-260). */
    giftNow: boolean;
    narratorFirstName: string | null;
    giftSendAt: string | null;
    giftSendTime: string;
};

/**
 * L'écran d'après-paiement.
 *
 * Il ne dit **pas** que la commande est enregistrée à partir de l'URL de
 * retour : Stripe y ramène le navigateur avant que le webhook n'arrive, et
 * annoncer une commande créée avant qu'elle existe est le meilleur moyen de
 * produire un courriel de support. Il dit ce qui est vrai dans tous les cas :
 * le paiement est passé, la confirmation arrive par courriel.
 *
 * Le livre qui s'ouvre est le geste le plus appuyé de toute l'interface
 * (T-135) : c'est l'objet qu'on vient d'offrir, à l'instant où on l'offre.
 * Il s'ouvre une fois, et reste ouvert pour qui a demandé qu'on ne bouge pas.
 * Les confettis partent quand la couverture bascule (T-235), une fois par
 * commande : un rechargement ne refait pas la fête.
 */

/** La couverture commence à pivoter à 0,9 s et passe l'équerre vers 1,5 s. */
const COVER_OPEN_MS = 1500;

export default function CheckoutThanks({
    sessionId,
    forSelf,
    giftNow,
    narratorFirstName,
    giftSendAt,
    giftSendTime,
}: Props) {
    const fmt = useFormat();
    const ofName = fmt.of;
    const t = useT();

    useEffect(() => {
        const timer = window.setTimeout(
            () => celebrateOnce(sessionId ?? 'commande', 'generous'),
            COVER_OPEN_MS,
        );

        return () => window.clearTimeout(timer);
    }, [sessionId]);
    const name = narratorFirstName ?? '';
    const of = ofName(name);

    const when = {
        date: giftSendAt !== null ? fmt.longDate(giftSendAt) : '',
        time: fmt.time(giftSendTime),
    };

    const steps = forSelf
        ? [
              t('public.checkout.thanks.next.email'),
              giftNow
                  ? t('public.checkout.thanks.next.invite_now_self')
                  : giftSendAt !== null
                    ? t('public.checkout.thanks.next.invite_self', when)
                    : t('public.checkout.thanks.next.invite_self_soon'),
              t('public.checkout.thanks.next.first_self'),
          ]
        : [
              t('public.checkout.thanks.next.email'),
              giftNow
                  ? t('public.checkout.thanks.next.invite_now')
                  : giftSendAt !== null
                    ? t('public.checkout.thanks.next.invite', when)
                    : t('public.checkout.thanks.next.invite_soon'),
              t('public.checkout.thanks.next.first'),
          ];

    return (
        <div className="mx-auto w-full max-w-6xl px-6 py-12 lg:py-20">
            <Head title={t('public.checkout.thanks.title')} />

            <div className="grid items-center gap-14 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)] lg:gap-20">
                <div className="enter">
                    <p className="eyebrow">
                        {t('public.checkout.thanks.title')}
                    </p>

                    <h1 className="font-display mt-4 text-[2.25rem] leading-[1.1] font-medium sm:text-5xl">
                        {forSelf
                            ? t('public.checkout.thanks.headline_self')
                            : name !== ''
                              ? t('public.checkout.thanks.headline', { of })
                              : t('public.checkout.thanks.headline_anonymous')}
                    </h1>

                    <p className="text-brand-muted mt-5 text-xl leading-snug">
                        {t('public.checkout.thanks.body')}
                    </p>

                    <h2 className="mt-10 text-xl font-semibold">
                        {t('public.checkout.thanks.next_title')}
                    </h2>

                    <ol className="mt-4 flex flex-col gap-4">
                        {steps.map((step, index) => (
                            <li
                                key={step}
                                className="enter flex items-start gap-4"
                                style={{
                                    animationDelay: `${0.25 + index * 0.12}s`,
                                }}
                            >
                                <span className="bg-brand text-brand-foreground flex size-8 flex-none items-center justify-center rounded-full text-[0.95rem] font-semibold tabular-nums">
                                    {index + 1}
                                </span>
                                <span className="pt-0.5">{step}</span>
                            </li>
                        ))}
                    </ol>

                    {/* Le tunnel de personnalisation, tout de suite : c'est
                        le moment où l'on pense le plus à la personne à qui
                        l'on offre. `/espace/personnaliser` mène au projet,
                        que cette page ne connaît pas encore. */}
                    {!forSelf && (
                        <div
                            className="enter pz-banner mt-10"
                            style={{ animationDelay: '0.7s' }}
                        >
                            <p className="min-w-0 flex-1 leading-snug">
                                {t('public.checkout.thanks.personalize_hint')}
                            </p>
                            <Link
                                href="/espace/personnaliser"
                                className="bg-brand text-brand-foreground hover:bg-brand-deep inline-flex min-h-12 flex-none items-center justify-center rounded-full px-5 font-semibold"
                            >
                                {t('public.checkout.thanks.personalize_cta')}
                            </Link>
                        </div>
                    )}
                </div>

                <Book
                    title={
                        forSelf
                            ? t('public.checkout.thanks.book_cover_self')
                            : name !== ''
                              ? t('public.checkout.thanks.book_cover', { of })
                              : t('public.checkout.thanks.book_cover_anonymous')
                    }
                    sub={t('public.checkout.thanks.book_sub')}
                    aria={t('public.checkout.thanks.book_aria')}
                />
            </div>

            {/*
             * Deux gestes, et non « allez voir votre espace ».
             *
             * La page finissait sur un bouton qui menait à un tableau
             * de bord vide : le projet n'existe que quand le webhook
             * arrive, la narratrice n'a pas encore accepté, il n'y a
             * rien à y voir. Or c'est le seul moment où l'acheteuse a
             * du temps et de l'élan — et deux gestes décident
             * vraiment de la suite : quelle question part en premier,
             * et qui écoutera.
             *
             * Les adresses ne portent pas de projet : `/espace/…`
             * résout celui du compte et sert une page qui explique
             * quand la commande n'est pas encore honorée (T-199).
             * Une adresse avec un identifiant répondrait 404 dans les
             * secondes qui suivent le paiement.
             */}
            <h2 className="mt-12 text-xl font-semibold">
                {t('public.checkout.thanks.now_title')}
            </h2>

            <div className="mt-4 grid max-w-4xl gap-4 sm:grid-cols-2">
                <NextStep
                    title={t('public.checkout.thanks.now.questions_title')}
                    body={
                        forSelf
                            ? t(
                                  'public.checkout.thanks.now.questions_body_self',
                              )
                            : name !== ''
                              ? t('public.checkout.thanks.now.questions_body', {
                                    name,
                                })
                              : t(
                                    'public.checkout.thanks.now.questions_body_soon',
                                )
                    }
                    cta={t('public.checkout.thanks.now.questions_cta')}
                    href="/espace/questions"
                    primary
                />

                <NextStep
                    title={t('public.checkout.thanks.now.family_title')}
                    body={t('public.checkout.thanks.now.family_body')}
                    cta={t('public.checkout.thanks.now.family_cta')}
                    href="/espace/proches"
                />
            </div>

            <p className="mt-8">
                <Link
                    href="/espace"
                    className="text-brand-muted hover:text-brand underline underline-offset-4 transition-colors"
                >
                    {t('public.checkout.thanks.orders')}
                </Link>
            </p>
        </div>
    );
}

/**
 * Une des deux choses à faire, avec sa raison.
 *
 * Le titre dit le geste, le corps dit **pourquoi maintenant** : les deux ont
 * une échéance naturelle — ils doivent être faits avant l'acceptation, sans
 * quoi la première question part sans eux. Un bouton sans cette phrase ne
 * serait qu'un lien de plus sur une page de remerciement.
 */
function NextStep({
    title,
    body,
    cta,
    href,
    primary = false,
}: {
    title: string;
    body: string;
    cta: string;
    href: string;
    primary?: boolean;
}) {
    return (
        <section className="card enter flex flex-col gap-3 p-5">
            <h3 className="font-display text-brand text-xl leading-snug font-medium">
                {title}
            </h3>

            <p className="text-brand-muted flex-1 text-base">{body}</p>

            <Link
                href={href}
                className={`${primary ? 'btn-primary' : 'btn-secondary'} press self-start no-underline`}
            >
                {cta}
            </Link>
        </section>
    );
}

/** Un livre relié qui s'ouvre sur sa première page, en CSS. */
function Book({
    title,
    sub,
    aria,
}: {
    title: string;
    sub: string;
    aria: string;
}) {
    return (
        <figure
            aria-label={aria}
            className="mx-auto flex w-full max-w-[30rem] justify-end sm:max-w-[36rem] lg:mx-0 lg:ml-auto"
        >
            <div className="book-scene w-1/2">
                <div className="book-spine" aria-hidden="true" />

                <div className="book-page" aria-hidden="true">
                    <div className="book-page-content">
                        <span className="bg-brand-gold h-px w-8" />
                        <span className="font-display text-brand text-[1.15rem] leading-tight font-medium">
                            {title}
                        </span>
                        <span className="text-brand-muted text-[0.7rem] tracking-[0.14em] uppercase">
                            {sub}
                        </span>
                        <span className="mt-4 flex w-full flex-col gap-2">
                            {[88, 100, 94, 70].map((width, index) => (
                                <span
                                    key={index}
                                    className="bg-brand-sand block h-1.5 rounded-full"
                                    style={{ width: `${width}%` }}
                                />
                            ))}
                        </span>
                    </div>
                </div>

                <div className="book-cover" aria-hidden="true">
                    <div className="book-face book-front">
                        <span className="bg-brand-gold h-px w-10" />
                        <span className="font-display text-[1.35rem] leading-tight font-medium text-[#F7F1E6]">
                            {title}
                        </span>
                        <span className="bg-brand-gold h-px w-10" />
                    </div>
                    <div className="book-face book-back" />
                </div>
            </div>
        </figure>
    );
}
