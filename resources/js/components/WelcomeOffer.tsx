import { Link, useForm } from '@inertiajs/react';
import {
    useEffect,
    useRef,
    useState,
    type FormEvent,
    type MouseEvent,
} from 'react';

import { useUrls } from '@/hooks/useLocale';
import { useFormat } from '@/hooks/useFormat';
import { CheckField } from '@/components/form/CheckField';
import { SubmitButton } from '@/components/form/SubmitButton';
import { TextField } from '@/components/form/TextField';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';
import {
    readWelcomeOfferMemory,
    rememberWelcomeOffer,
    shouldOfferWelcome,
    WELCOME_OFFER_SCROLL,
} from '@/lib/welcomeOffer';

type Props = {
    /** Proposée ou non : le réglage du pilote, et l'absence de code déjà pris. */
    enabled: boolean;
    /** En pour cent de la commande. */
    discountPercent: number;
    /** La part de la page lue qui l'ouvre, de 0 à 1. */
    atScroll?: number;
};

type Step = 'form' | 'sent';

/**
 * La fenêtre de bienvenue : une réduction contre une adresse (T-141).
 *
 * Elle était en deux temps — la promesse et un bouton, puis le champ. Elle
 * n'en fait plus qu'un (décision du fondateur, 15 septembre 2026) : la
 * promesse et le champ ensemble. Le premier temps demandait un clic pour
 * obtenir un formulaire, c'est-à-dire un pas de plus vers la même chose ; qui
 * n'a pas envie de laisser son adresse ferme aussi bien à la seconde fenêtre
 * qu'à la première.
 *
 * Un `<dialog>` natif et pas une bibliothèque : le navigateur tient le
 * piège du focus, la touche Échap, l'arrière-plan inerte, et il n'injecte
 * aucune feuille de style, ce que la politique de sécurité des pages
 * publiques refuserait. La fenêtre s'ouvre après un délai, jamais au
 * chargement : on lit la promesse avant qu'on propose autre chose. Fermée,
 * elle se tait trente jours ; le code demandé, elle se tait pour de bon.
 *
 * Le code part par courriel et jamais à l'écran : c'est ce qui fait qu'une
 * adresse laissée est une adresse qui existe.
 */
export default function WelcomeOffer({
    enabled,
    discountPercent,
    atScroll = WELCOME_OFFER_SCROLL,
}: Props) {
    const urls = useUrls();
    const fmt = useFormat();
    const formatPercent = fmt.percent;
    const t = useT();
    const dialogRef = useRef<HTMLDialogElement>(null);
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState<Step>('form');

    const form = useForm({
        email: '',
        /*
         * Cochée à l'ouverture (demande du fondateur, 15 septembre 2026).
         *
         * À signaler et à trancher par le conseil : une case pré-cochée ne
         * vaut pas consentement au sens du RGPD (considérant 32, arrêt
         * Planet49), et `ClaimWelcomeOffer` enregistre justement une preuve —
         * version du texte, empreinte d'IP, agent — qui serait alors une
         * preuve de rien. Le décochage reste possible d'un geste, et l'adresse
         * ne sert qu'au code si la case est décochée.
         */
        news: true,
        // Le champ que personne ne voit : rempli, le serveur remercie et ne
        // garde rien.
        website: '',
    });

    /*
     * Elle s'ouvre à la profondeur de lecture, pas au bout d'un délai (T-221).
     *
     * Un délai mesure la patience, pas l'intérêt : six secondes sur le héros,
     * c'est quelqu'un qui n'a encore rien lu. La part de la page parcourue,
     * elle, dit qu'on a vu la promesse et les étapes.
     *
     * Le détachement de l'écouteur au premier déclenchement n'est pas une
     * optimisation : sans lui, refermer la fenêtre et continuer à défiler la
     * rouvrirait à chaque pixel.
     */
    useEffect(() => {
        if (!enabled || !shouldOfferWelcome(readWelcomeOfferMemory())) {
            return;
        }

        let done = false;

        const check = () => {
            if (done) {
                return;
            }

            const scrollable =
                document.documentElement.scrollHeight - window.innerHeight;

            // Une page qu'on ne peut pas défiler n'a pas de trente-cinq pour
            // cent : on ne propose rien plutôt que d'ouvrir aussitôt.
            if (scrollable <= 0) {
                return;
            }

            if (window.scrollY / scrollable >= atScroll) {
                done = true;
                setOpen(true);
                window.removeEventListener('scroll', check);
            }
        };

        window.addEventListener('scroll', check, { passive: true });
        check();

        return () => window.removeEventListener('scroll', check);
    }, [atScroll, enabled]);

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
            document.documentElement.classList.add('overflow-hidden');
            /*
             * Le focus reste où le navigateur le pose, sur la croix. Il allait
             * au bouton « Je prends ma réduction », qui n'existe plus ; le
             * mettre dans le champ ouvrirait le clavier d'un téléphone et
             * cacherait la promesse au moment de demander l'adresse.
             */
        } else if (!open && dialog.open) {
            dialog.close();
        }

        return () =>
            document.documentElement.classList.remove('overflow-hidden');
    }, [open]);

    const remember = () => {
        if (step !== 'sent') {
            rememberWelcomeOffer({ status: 'dismissed', at: Date.now() });
        }
    };

    const dismiss = () => {
        remember();
        setOpen(false);
    };

    /* La touche Échap ferme le `<dialog>` sans passer par nous : on l'écoute. */
    const onClose = () => {
        remember();
        document.documentElement.classList.remove('overflow-hidden');
        setOpen(false);
    };

    const onBackdrop = (event: MouseEvent<HTMLDialogElement>) => {
        if (event.target === dialogRef.current) {
            dismiss();
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/offre-de-bienvenue', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                rememberWelcomeOffer({ status: 'claimed', at: Date.now() });
                setStep('sent');
            },
        });
    };

    const amount = formatPercent(discountPercent);

    return (
        <dialog
            ref={dialogRef}
            aria-label={t('public.welcome_offer.aria')}
            onClose={onClose}
            onClick={onBackdrop}
            className="bg-brand-background text-brand-text m-auto max-h-[92dvh] w-[min(92vw,54rem)] overflow-auto rounded-2xl border-0 p-0 shadow-[0_40px_90px_rgba(38,33,28,0.35)] backdrop:bg-[#26211C]/60"
        >
            {/* L'entrée est celle de tout ce qui arrive à l'écran : dix pixels en
                fondu, sur le contenu monté à chaque ouverture. Le pop-in, avec
                son rebond, était trop violent pour une fenêtre entière. */}
            {open && (
                <div className="enter relative grid sm:grid-cols-[minmax(0,1fr)_minmax(0,42%)]">
                    <button
                        type="button"
                        onClick={dismiss}
                        aria-label={t('common.actions.close')}
                        className="bg-brand-surface text-brand press hover:bg-brand-linen absolute top-3 right-3 z-10 flex size-11 items-center justify-center rounded-full shadow-[0_2px_8px_rgba(38,33,28,0.18)]"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            aria-hidden="true"
                            className="size-5"
                        >
                            <path d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>

                    <div className="flex flex-col gap-5 px-7 py-9 sm:px-10 sm:py-12">
                        {step === 'form' && (
                            <form
                                onSubmit={submit}
                                className="enter flex flex-col gap-5"
                            >
                                <p className="font-display text-brand text-[2.4rem] leading-[1.05] font-medium sm:text-5xl">
                                    {t('public.welcome_offer.title', {
                                        amount,
                                    })}
                                    <br />
                                    <span className="text-brand-muted text-[0.55em] font-normal italic">
                                        {t('public.welcome_offer.subtitle')}
                                    </span>
                                </p>
                                {/*
                                 * Pas de focus automatique sur le champ : sur
                                 * un téléphone, le clavier s'ouvrirait aussitôt
                                 * et cacherait la promesse au moment même où
                                 * l'on demande l'adresse.
                                 */}
                                <TextField
                                    type="email"
                                    name="email"
                                    autoComplete="email"
                                    inputMode="email"
                                    required
                                    label={t(
                                        'public.welcome_offer.email_label',
                                    )}
                                    placeholder={t(
                                        'public.welcome_offer.email_placeholder',
                                    )}
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                    error={form.errors.email}
                                />

                                <CheckField
                                    name="news"
                                    checked={form.data.news}
                                    onChange={(checked) =>
                                        form.setData('news', checked)
                                    }
                                    label={t('public.welcome_offer.news')}
                                />

                                <div
                                    aria-hidden="true"
                                    className="absolute -left-[9999px] size-px overflow-hidden"
                                >
                                    <label htmlFor="welcome-offer-website">
                                        Site web
                                    </label>
                                    <input
                                        id="welcome-offer-website"
                                        type="text"
                                        name="website"
                                        tabIndex={-1}
                                        autoComplete="off"
                                        value={form.data.website}
                                        onChange={(event) =>
                                            form.setData(
                                                'website',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>

                                <SubmitButton
                                    processing={form.processing}
                                    waitingLabel={t(
                                        'public.welcome_offer.waiting',
                                    )}
                                >
                                    {t('public.welcome_offer.send')}
                                </SubmitButton>

                                {/*
                                 * Plus petite et plus claire que le reste
                                 * (demande du fondateur, deux fois) : 12 px et
                                 * 82 % du gris secondaire, soit 4,64:1 sur le
                                 * fond de la fenêtre. C'est la limite : à 80 %
                                 * on tombe à 4,44:1, sous le seuil AA de 4,5
                                 * que le dossier tient (doc 04, WCAG 2.2 AA),
                                 * et cette phrase est justement celle qui dit
                                 * ce qu'on fait de l'adresse. La taille, elle,
                                 * n'est bornée par aucune règle.
                                 */}
                                <p className="text-brand-muted/82 text-[0.75rem] leading-snug">
                                    {t('public.welcome_offer.fine_print')}
                                </p>

                                <button
                                    type="button"
                                    onClick={dismiss}
                                    className="text-brand-muted hover:text-brand self-center text-base underline underline-offset-4"
                                >
                                    {t('public.welcome_offer.no_thanks')}
                                </button>
                            </form>
                        )}

                        {step === 'sent' && (
                            <div className="enter flex flex-col gap-5">
                                <span className="bg-brand text-brand-foreground animate-pop-in flex size-12 items-center justify-center rounded-full">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2.5"
                                        aria-hidden="true"
                                        className="size-6"
                                    >
                                        <path d="m6 12 4 4 8-9" />
                                    </svg>
                                </span>
                                <p className="font-display text-brand text-[2rem] leading-[1.1] font-medium sm:text-4xl">
                                    {t('public.welcome_offer.sent_title')}
                                </p>
                                <p className="text-lg leading-snug">
                                    {t('public.welcome_offer.sent_body', {
                                        email: form.data.email,
                                    })}
                                </p>
                                <p className="text-brand-muted text-base leading-snug">
                                    {t('public.welcome_offer.sent_auto')}
                                </p>
                                <Link
                                    href={urls.checkout_show}
                                    className="btn-primary press mt-1"
                                >
                                    {t('public.welcome_offer.sent_cta')}
                                </Link>
                            </div>
                        )}
                    </div>

                    <img
                        {...photo('hero')}
                        sizes="(min-width: 640px) 28rem, 100vw"
                        alt=""
                        width="1400"
                        height="933"
                        className="order-first h-40 w-full object-cover object-[60%_25%] sm:order-none sm:h-full sm:min-h-[26rem]"
                    />
                </div>
            )}
        </dialog>
    );
}
