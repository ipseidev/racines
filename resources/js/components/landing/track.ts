import { event as posthogEvent } from '@/lib/analytics';
import { event as googleEvent } from '@/lib/gtag';
import { custom as metaCustom, standard as metaStandard } from '@/lib/meta';

/*
 * Nos noms d'événements vers ceux que Meta sait optimiser.
 *
 * Le nom compte : Meta ne pilote une campagne que sur les événements de sa
 * liste. `lp_buy_click` devient donc `InitiateCheckout` — l'intention d'entrer
 * dans le tunnel —, et la demande de code de réduction devient `Lead`. Le
 * reste part en événement à nous : mesuré, jamais optimisé.
 *
 * `Purchase` n'est pas ici, et c'est volontaire : il part du **serveur**,
 * depuis le webhook Stripe, où il ne dépend ni d'un bloqueur de publicité ni
 * d'un onglet resté ouvert (T-226).
 */
const META_STANDARD: Record<string, string> = {
    lp_buy_click: 'InitiateCheckout',
    welcome_offer_claimed: 'Lead',
};

/**
 * Un événement de la variante de page de vente, envoyé aux deux mesures.
 *
 * Les deux répondent à des questions différentes — PostHog l'entonnoir du
 * produit, Google Analytics l'audience du site marchand — et une intention
 * mesurée dans l'une seulement se paie par deux chiffres qui ne se recoupent
 * jamais. Les deux fonctions se gardent elles-mêmes quand leur mesure ne
 * tourne pas : ici, aucun test à faire.
 *
 * Ce qui voyage : un nom d'événement, la variante, la section. **Jamais** un
 * enregistrement, un texte de souvenir, un prénom ou une adresse.
 */
export function track(
    name: string,
    properties: Record<string, string | number | boolean> = {},
): void {
    posthogEvent(name, properties);
    googleEvent(name, properties);

    const meta = META_STANDARD[name];

    if (meta === undefined) {
        metaCustom(name, properties);
    } else {
        metaStandard(meta, properties);
    }
}
