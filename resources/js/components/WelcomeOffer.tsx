import { Link, useForm } from '@inertiajs/react';
import {
    useEffect,
    useRef,
    useState,
    type FormEvent,
    type MouseEvent,
} from 'react';

import { CheckField } from '@/components/form/CheckField';
import { SubmitButton } from '@/components/form/SubmitButton';
import { TextField } from '@/components/form/TextField';
import { useT } from '@/hooks/useT';
import { formatPercent } from '@/lib/format';
import { photo } from '@/lib/photo';
import {
    readWelcomeOfferMemory,
    rememberWelcomeOffer,
    shouldOfferWelcome,
    WELCOME_OFFER_DELAY_MS,
} from '@/lib/welcomeOffer';

type Props = {
    /** Proposée ou non : le réglage du pilote, et l'absence de code déjà pris. */
    enabled: boolean;
    /** En pour cent de la commande. */
    discountPercent: number;
    delayMs?: number;
};

type Step = 'teaser' | 'form' | 'sent';

/**
 * La fenêtre de bienvenue : une réduction contre une adresse (T-141).
 *
 * Empruntée au leader dans sa forme, en deux temps : d'abord la promesse et
 * un seul bouton, puis le champ. Le second temps ne s'ouvre qu'à qui a dit
 * oui au premier, et c'est ce qui fait qu'on remplit le champ.
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
    delayMs = WELCOME_OFFER_DELAY_MS,
}: Props) {
    const t = useT();
    const dialogRef = useRef<HTMLDialogElement>(null);
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState<Step>('teaser');

    const form = useForm({
        email: '',
        news: false,
        // Le champ que personne ne voit : rempli, le serveur remercie et ne
        // garde rien.
        website: '',
    });

    useEffect(() => {
        if (!enabled || !shouldOfferWelcome(readWelcomeOfferMemory())) {
            return;
        }

        const timer = window.setTimeout(() => setOpen(true), delayMs);

        return () => window.clearTimeout(timer);
    }, [enabled, delayMs]);

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
            document.documentElement.classList.add('overflow-hidden');
            // Le navigateur pose le focus sur le premier bouton, la croix : on
            // le met sur l'action, qui est ce qu'on propose.
            dialog.querySelector<HTMLElement>('[data-autofocus]')?.focus();
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
                        {step === 'teaser' && (
                            <>
                                <span className="eyebrow">
                                    {t('public.welcome_offer.eyebrow')}
                                </span>
                                <p className="font-display text-brand text-[2.6rem] leading-[1.05] font-medium sm:text-5xl">
                                    {t('public.welcome_offer.title', {
                                        amount,
                                    })}
                                    <br />
                                    <span className="text-brand-muted text-[0.55em] font-normal italic">
                                        {t('public.welcome_offer.subtitle')}
                                    </span>
                                </p>
                                <p className="text-brand-muted text-lg leading-snug">
                                    {t('public.welcome_offer.teaser', {
                                        amount,
                                    })}
                                </p>
                                <button
                                    type="button"
                                    data-autofocus
                                    onClick={() => setStep('form')}
                                    className="btn-primary press mt-1"
                                >
                                    {t('public.welcome_offer.claim')}
                                </button>
                                <button
                                    type="button"
                                    onClick={dismiss}
                                    className="text-brand-muted hover:text-brand self-center text-base underline underline-offset-4"
                                >
                                    {t('public.welcome_offer.no_thanks')}
                                </button>
                            </>
                        )}

                        {step === 'form' && (
                            <form
                                onSubmit={submit}
                                className="enter flex flex-col gap-5"
                            >
                                <p className="font-display text-brand text-[2rem] leading-[1.1] font-medium sm:text-4xl">
                                    {t('public.welcome_offer.title', {
                                        amount,
                                    })}
                                </p>

                                <TextField
                                    autoFocus
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

                                <p className="text-brand-muted text-[0.9rem] leading-snug">
                                    {t('public.welcome_offer.fine_print')}
                                </p>
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
                                    href="/acheter"
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
