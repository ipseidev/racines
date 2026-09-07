import {
    BAND,
    Heading,
    QrSlot,
    Section,
    SHELL,
} from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

/**
 * S14 — Le livre et les QR codes.
 *
 * L'aperçu de chapitre est **ouvert**, pas derrière un bouton : c'est la
 * démonstration la plus convaincante de la page, et un « feuilleter un
 * exemple » qui déplie ce qui pourrait être visible ne sert que la mise en
 * scène. Le brief prévoyait un bouton faute de feuilletage réel ; nous
 * n'avons pas de feuilletage, et nous n'en simulons pas non plus.
 *
 * Le carré du QR code est un **emplacement**, dit comme tel. Aucune
 * destination imprimée n'est vérifiée à ce jour, et dessiner un code
 * plausible ferait scanner dans le vide. Le lien « ouvrir l'exemple audio »
 * fait le travail à la place : sur téléphone, personne n'a un second appareil
 * pour scanner l'écran qu'il tient.
 *
 * La mise en page est une composition, pas une page du modèle d'impression :
 * la légende le dit.
 */
export default function BookAndVoice() {
    const t = useT();

    return (
        <Section labelledBy="lp-book" className={BAND}>
            <div className={`${SHELL} flex flex-col gap-9`}>
                <Heading
                    id="lp-book"
                    eyebrow={t('public.lp.book.eyebrow')}
                    title={t('public.lp.book.title')}
                    lede={t('public.lp.book.body')}
                    centered
                />

                <figure className="border-brand-sand bg-brand-surface mx-auto grid w-full max-w-4xl overflow-hidden rounded-xl border sm:grid-cols-2">
                    <img
                        {...photo('etape-1')}
                        sizes="(min-width: 640px) 28rem, 100vw"
                        alt={t('public.landing.how.one.alt')}
                        width="1400"
                        height="930"
                        loading="lazy"
                        className="aspect-[4/3] w-full object-cover sm:aspect-auto sm:h-full"
                    />

                    <div className="flex flex-col gap-3.5 p-6 lg:p-8">
                        <span className="text-brand-muted text-[0.72rem] font-semibold tracking-[0.12em] uppercase">
                            {t('public.lp.book.chapter_number')}
                        </span>
                        <span className="font-display text-brand text-[1.4rem] leading-tight font-medium">
                            {t('public.lp.book.chapter_title')}
                        </span>
                        <p className="font-display text-[1rem] leading-relaxed">
                            {t('public.landing.proof.sample_fluide')}
                        </p>

                        <div className="mt-auto flex items-center gap-4 pt-3">
                            <QrSlot
                                label={t('public.lp.book.qr_placeholder')}
                                className="size-20 flex-none"
                            />
                            <span className="flex flex-col gap-1.5">
                                <span className="text-brand-muted text-[0.85rem] leading-snug">
                                    {t('public.lp.book.qr_body')}
                                </span>
                                <a
                                    href="#ecouter"
                                    className="text-brand inline-flex min-h-[2.75rem] items-center text-[0.95rem] font-semibold underline decoration-2 underline-offset-4"
                                >
                                    {t('public.lp.book.open_audio')}
                                </a>
                            </span>
                        </div>
                    </div>

                    <figcaption className="text-brand-muted border-brand-sand border-t px-6 py-3 text-[0.85rem] sm:col-span-2">
                        {t('public.lp.book.illustrative')}
                    </figcaption>
                </figure>
            </div>
        </Section>
    );
}
