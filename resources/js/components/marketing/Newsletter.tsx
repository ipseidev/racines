import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { track } from '@/components/landing/track';
import { CheckField } from '@/components/form/CheckField';
import { SubmitButton } from '@/components/form/SubmitButton';
import { TextField } from '@/components/form/TextField';
import { Lock } from '@/components/marketing/Check';
import { useT } from '@/hooks/useT';
import { formatPercent } from '@/lib/format';
import { rememberWelcomeOffer } from '@/lib/welcomeOffer';

type Props = {
    /** Proposée ou non : le réglage du pilote, et l'absence de code déjà pris. */
    enabled: boolean;
    /** En pour cent de la commande. */
    discountPercent: number;
};

/**
 * L'adresse contre une réduction, en bandeau de bas de page (T-213).
 *
 * Le même service que la fenêtre de bienvenue (T-141), avec les mêmes
 * règles : le code part par courriel et jamais à l'écran, les nouvelles
 * sont une case à part, décochée, jamais requise, et un champ que personne
 * ne voit arrête les robots. Posé sur « Comment ça marche » et sur
 * l'accueil, comme chez le leader, juste avant le pied de page. Posé aussi sur
 * la variante de structure (T-219).
 */
export default function Newsletter({ enabled, discountPercent }: Props) {
    const t = useT();
    const [sent, setSent] = useState(false);
    const form = useForm({ email: '', news: false, website: '' });

    /*
     * Rien quand la réduction n'est pas proposée — un bandeau qui promet un
     * code qu'on ne peut pas donner est pire que pas de bandeau.
     *
     * `&& !sent` n'est pas une précaution en trop, c'est un défaut corrigé :
     * réclamer le code pose le cookie côté serveur, donc la réponse Inertia
     * rend `enabled` faux, et le bandeau disparaissait **avec** sa
     * confirmation. Le courriel partait, l'écran ne disait rien, et on ne
     * pouvait que réessayer. Une fois envoyé, la confirmation reste.
     */
    if (!enabled && !sent) {
        return null;
    }

    const amount = formatPercent(discountPercent);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/offre-de-bienvenue', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                // La fenêtre de bienvenue se tait pour de bon : le code est pris.
                rememberWelcomeOffer({ status: 'claimed', at: Date.now() });
                // Une adresse laissée : l'événement que la publicité sait
                // optimiser avant l'achat (T-226). Aucune donnée personnelle
                // ne voyage avec — le nom de l'événement suffit.
                track('welcome_offer_claimed', { section: 'newsletter' });
                setSent(true);
            },
        });
    };

    return (
        <section aria-labelledby="newsletter" className="bg-brand-deep">
            <div className="mx-auto grid w-full max-w-6xl gap-8 px-6 py-14 text-[#F7F1E6] lg:grid-cols-[5fr_7fr] lg:items-start lg:gap-16 lg:py-16">
                <div className="flex flex-col gap-3">
                    <h2
                        id="newsletter"
                        className="font-display text-[1.75rem] leading-[1.15] font-medium text-[#F7F1E6] sm:text-3xl"
                    >
                        {t('public.welcome_offer.band_title', { amount })}
                    </h2>
                    <p className="text-[#C9C0B2]">
                        {t('public.welcome_offer.teaser', { amount })}
                    </p>
                </div>

                {sent ? (
                    <div className="flex flex-col gap-2">
                        <p className="font-display text-[1.5rem] font-medium text-[#F7F1E6]">
                            {t('public.welcome_offer.sent_title')}
                        </p>
                        <p className="text-[#C9C0B2]">
                            {t('public.welcome_offer.sent_body', {
                                email: form.data.email,
                            })}
                        </p>
                    </div>
                ) : (
                    <form
                        onSubmit={submit}
                        className="relative flex flex-col gap-4"
                    >
                        <div className="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                            <TextField
                                type="email"
                                name="email"
                                autoComplete="email"
                                inputMode="email"
                                required
                                label={t('public.welcome_offer.email_label')}
                                placeholder={t(
                                    'public.welcome_offer.email_placeholder',
                                )}
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                error={form.errors.email}
                            />
                            <SubmitButton
                                processing={form.processing}
                                waitingLabel={t('public.welcome_offer.waiting')}
                            >
                                {t('public.welcome_offer.send')}
                            </SubmitButton>
                        </div>
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
                            <label htmlFor="newsletter-website">Site web</label>
                            <input
                                id="newsletter-website"
                                type="text"
                                name="website"
                                tabIndex={-1}
                                autoComplete="off"
                                value={form.data.website}
                                onChange={(event) =>
                                    form.setData('website', event.target.value)
                                }
                            />
                        </div>
                        <p className="flex items-start gap-2 text-[0.9rem] leading-snug text-[#C9C0B2]">
                            <Lock />
                            {t('public.welcome_offer.fine_print')}
                        </p>
                    </form>
                )}
            </div>
        </section>
    );
}
