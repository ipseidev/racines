import { BrandLogo } from '@/brand/BrandProvider';

/**
 * Le logo de l'espace authentifié du kit — barre latérale et en-tête.
 *
 * C'est la marque, lue dans les réglages, et jamais un pictogramme livré avec
 * le kit de démarrage : le nom n'est pas arrêté, et le jour où il le sera, il
 * changera ici comme partout, depuis l'administration (BrandAgnosticTest).
 */
export default function AppLogo() {
    return (
        <BrandLogo className="font-display text-brand text-xl leading-none font-semibold" />
    );
}
