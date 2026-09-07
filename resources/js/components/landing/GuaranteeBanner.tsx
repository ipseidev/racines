import { Section, SHELL } from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';

/**
 * S11 — La garantie, juste après les avis.
 *
 * Une phrase, centrée, en grand, dans la forme de la référence. Trente jours,
 * ni plus ni moins. Aucun sceau, aucune médaille, aucun « certifié » : un
 * label dessiné est un label inventé. Les modalités ne sont pas réécrites ici,
 * celles des questions fréquentes renvoient aux conditions générales.
 */
export default function GuaranteeBanner() {
    const t = useT();

    return (
        <Section
            tone="white"
            labelledBy="lp-guarantee"
            className="pb-11 sm:pb-14 lg:pb-20"
        >
            <div className={SHELL}>
                <h2
                    id="lp-guarantee"
                    className="font-display text-brand mx-auto max-w-[36em] text-center text-[1.45rem] leading-[1.25] font-medium sm:text-[1.75rem] lg:text-[2.1rem]"
                >
                    {t('public.lp.guarantee.title')}
                </h2>
            </div>
        </Section>
    );
}
