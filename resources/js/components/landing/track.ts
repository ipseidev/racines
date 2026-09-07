import { event as posthogEvent } from '@/lib/analytics';
import { event as googleEvent } from '@/lib/gtag';

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
}
