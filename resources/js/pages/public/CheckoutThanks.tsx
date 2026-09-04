import { Head, Link } from '@inertiajs/react';

import { useT } from '@/hooks/useT';
import { ofName } from '@/lib/french';

import { formatDate, formatTime } from './Checkout';

type Props = {
    sessionId?: string | null;
    forSelf: boolean;
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
 */
export default function CheckoutThanks({
    forSelf,
    narratorFirstName,
    giftSendAt,
    giftSendTime,
}: Props) {
    const t = useT();
    const name = narratorFirstName ?? '';
    const of = ofName(name);

    const when = {
        date: giftSendAt !== null ? formatDate(giftSendAt) : '',
        time: formatTime(giftSendTime),
    };

    const steps = forSelf
        ? [
              t('public.checkout.thanks.next.email'),
              giftSendAt !== null
                  ? t('public.checkout.thanks.next.invite_self', when)
                  : t('public.checkout.thanks.next.invite_self_soon'),
              t('public.checkout.thanks.next.first_self'),
              t('public.checkout.thanks.next.space'),
          ]
        : [
              t('public.checkout.thanks.next.email'),
              giftSendAt !== null
                  ? t('public.checkout.thanks.next.invite', when)
                  : t('public.checkout.thanks.next.invite_soon'),
              t('public.checkout.thanks.next.first'),
              t('public.checkout.thanks.next.space'),
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

                    <Link href="/espace" className="btn-primary press mt-10">
                        {t('public.checkout.thanks.orders')}
                    </Link>
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
        </div>
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
