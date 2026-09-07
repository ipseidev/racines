import { useBrand } from '@/brand/BrandProvider';
import { BAND, Heading, Section, SHELL } from '@/components/landing/primitives';
import { Lock } from '@/components/marketing/Check';
import { useT } from '@/hooks/useT';

const CHOICES = ['share', 'keep', 'later'] as const;

/**
 * S15 — De la parole au texte.
 *
 * Les deux extraits sont ceux du projet, repris **intégralement** depuis
 * `landing.proof` : réécrire l'exemple pour creuser l'écart entre le mot à mot
 * et le texte mis au propre exagérerait ce que le produit fait, et le produit
 * ne fait que retirer les hésitations et poser la ponctuation.
 *
 * Deux panneaux successifs sur téléphone, côte à côte dès la tablette : pas de
 * widget d'onglets, donc rien à piloter au clavier, aucun texte rogné, et les
 * deux versions lisibles dans tous les cas. C'est la variante que le brief
 * autorise, et c'est la plus sûre.
 *
 * Les trois choix sont un aperçu, dit comme tel : ils ne déclenchent rien.
 */
export default function TranscriptDemo() {
    const t = useT();
    const brand = useBrand();

    return (
        <Section
            id="texte-mis-au-propre"
            tone="white"
            labelledBy="lp-transcript-demo"
            className={BAND}
        >
            <div className={`${SHELL} flex flex-col gap-8`}>
                <Heading
                    id="lp-transcript-demo"
                    title={t('public.lp.transcript.title')}
                    lede={t('public.lp.transcript.body', {
                        brand: brand.name,
                    })}
                />

                <div className="border-brand-sand overflow-hidden rounded-xl border">
                    <div className="grid sm:grid-cols-2">
                        <div className="border-brand-sand flex flex-col gap-3 border-b p-6 sm:border-r sm:border-b-0">
                            <h3 className="text-brand-muted text-[0.78rem] font-semibold tracking-[0.1em] uppercase">
                                {t('public.lp.transcript.verbatim')}
                            </h3>
                            <p className="bg-brand-linen text-brand-muted rounded-md p-4 text-[1rem] leading-relaxed italic">
                                {t('public.landing.proof.sample_verbatim')}
                            </p>
                        </div>

                        <div className="flex flex-col gap-3 p-6">
                            <h3 className="text-brand text-[0.78rem] font-semibold tracking-[0.1em] uppercase">
                                {t('public.lp.transcript.fluide')}
                            </h3>
                            <p className="font-display p-4 text-[1.05rem] leading-relaxed">
                                {t('public.landing.proof.sample_fluide')}
                            </p>
                        </div>
                    </div>

                    <div className="border-brand-sand bg-brand-surface flex flex-col gap-3 border-t p-6">
                        <p className="text-brand-muted text-[0.85rem]">
                            {t('public.lp.transcript.preview')}
                        </p>
                        <ul className="flex flex-wrap gap-2.5">
                            {CHOICES.map((key) => (
                                <li key={key} className="chip">
                                    {t(`public.landing.proof.${key}`)}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                <p className="text-brand flex items-center gap-2 text-[1.0625rem] font-semibold">
                    <Lock />
                    {t('public.lp.transcript.consent')}
                </p>
            </div>
        </Section>
    );
}
