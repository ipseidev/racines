import { Head } from '@inertiajs/react';

import EasyForStorytellers from '@/components/landing/EasyForStorytellers';
import FounderStory from '@/components/landing/FounderStory';
import GiftBenefits from '@/components/landing/GiftBenefits';
import GuaranteeBanner from '@/components/landing/GuaranteeBanner';
import HowItWorks from '@/components/landing/HowItWorks';
import LandingHero from '@/components/landing/LandingHero';
import OwnershipAndAccess from '@/components/landing/OwnershipAndAccess';
import ProductOffer from '@/components/landing/ProductOffer';
import Testimonials from '@/components/landing/Testimonials';
import TrustStrip from '@/components/landing/TrustStrip';
import WhatItIs from '@/components/landing/WhatItIs';
import Newsletter from '@/components/marketing/Newsletter';
import { useT } from '@/hooks/useT';

type Proof = {
    press: { name: string; quote?: string; url?: string }[];
    quotes: { text: string; author?: string; source?: string }[];
    reviews: {
        text: string;
        author?: string;
        source?: string;
        date?: string;
    }[];
    videos: {
        title: string;
        author?: string;
        src?: string;
        poster?: string;
        duration?: string;
    }[];
    stories: {
        title: string;
        body?: string;
        author?: string;
        photo?: string;
    }[];
};

type Props = {
    /** `pilot`, `prevente` ou `core`. */
    mode: string;
    /** Le prix vu par ce visiteur, en centimes. */
    price: number;
    /** L'identifiant de la variante, pour la mesure. */
    variant: string;
    /** La fenêtre de bienvenue (T-141) : proposée ou non, et son pourcentage. */
    welcomeOffer: { enabled: boolean; discountPercent: number };
    /** L'extrait écoutable (T-149), absent tant que le fichier l'est. */
    heroSample: { src: string; disclosed: boolean } | null;
    /** Les cinq collections de preuves. Vides, elles activent les replis. */
    proof: Proof;
};

/*
 * La variante de structure, `/lp/histoire` (T-219).
 *
 * Vingt-deux sections dans l'ordre commercial du leader, S00 à S21, avec nos
 * contenus. L'ordre est la seule chose empruntée : l'accroche et l'achat, la
 * confiance, les raisons d'offrir, le concept, les étapes, l'origine, la fiche
 * produit, la propriété, l'introduction des preuves, les preuves, la garantie,
 * la simplicité, l'expérience, le livre, la mise au propre, l'aide au choix,
 * les sujets possibles, le cadeau programmé, les deux destinataires, les
 * questions, la réduction et le pied de page.
 *
 * Ce que la structure du leader porte et que nous n'avons pas — une émission de
 * télévision, des logos de presse, un mur d'avis, des vidéos de familles — est
 * soit supprimé (le module Shark Tank, sans équivalent français), soit tenu par
 * un repli qui montre le produit et se **déclare** comme démonstration. Le
 * mécanisme est dans `product.landing.structure` : une collection vide choisit
 * le repli, une collection remplie prend sa place.
 *
 * La page d'accueil n'est pas touchée : elle est le témoin, et une variante
 * mesurée contre une page qui a bougé ne mesure rien. Cette page est servie en
 * `noindex, follow` avec une canonique vers l'accueil, et ne figure dans aucun
 * plan de site.
 *
 * Le test porte sur **la variante entière** — structure, rédaction et
 * présentation des médias ensemble. Aucune conclusion sur un titre isolé, et
 * aucune annonce de hausse de conversion avant résultats.
 */
export default function LandingStructure({
    price,
    variant,
    welcomeOffer,
    heroSample,
    proof,
}: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('public.lp.seo_title')} />

            {/* S01 */}
            <LandingHero variant={variant} price={price} />

            {/* S02 */}
            <TrustStrip />

            {/* S04 — remonté juste après le bandeau, à la demande du fondateur. */}
            <WhatItIs />

            {/* S05 — avant les raisons d'offrir, à la demande du fondateur. */}
            <HowItWorks />

            {/* S06 — avant les raisons d'offrir, à la demande du fondateur. */}
            <FounderStory />

            {/* S07 — avant les raisons d'offrir, à la demande du fondateur. */}
            <ProductOffer variant={variant} price={price} sample={heroSample} />

            {/* S08 — avant les raisons d'offrir, à la demande du fondateur. */}
            <OwnershipAndAccess variant={variant} price={price} />

            {/* S10 — juste après ce que l'achat comprend, à la demande du fondateur. */}
            <Testimonials />

            {/* S11 — la garantie ferme le bloc des avis. */}
            <GuaranteeBanner />

            {/* S12 — juste après la garantie, à la demande du fondateur. */}
            <EasyForStorytellers variant={variant} />

            {/* S03 */}
            <GiftBenefits quotes={proof.quotes} />

            {/*
             * S21 — L'adresse contre une réduction, puis le pied de page (celui
             * de la mise en page). Le même service que la fenêtre de bienvenue,
             * avec les mêmes règles : le code part par courriel, la case des
             * nouvelles est à part et décochée, et rien ne s'affiche quand la
             * réduction n'est pas proposée à ce visiteur. Aucune remise n'est
             * annoncée ailleurs sur la page.
             */}
            <Newsletter
                enabled={welcomeOffer.enabled}
                discountPercent={welcomeOffer.discountPercent}
            />
        </>
    );
}
