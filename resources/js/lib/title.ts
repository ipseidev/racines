/**
 * Le titre du document, à partir du titre de la page et du nom de marque.
 *
 * La règle est celle de `App\Support\Seo::forComponent()`, et elle doit le
 * rester : la vue racine écrit le titre pour le premier affichage, Inertia le
 * réécrit à l'hydratation, et le rendu serveur le produit à leur place quand
 * il répond. Trois auteurs, un seul titre — sinon l'onglet change de nom sous
 * les yeux du visiteur, et Google lit celui des trois qui lui est servi.
 *
 * Le nom n'est suffixé que s'il n'ouvre pas déjà le titre : celui de
 * l'accueil le porte en tête.
 */
export function documentTitle(title: string, brand: string): string {
    if (title === '') {
        return brand;
    }

    if (brand === '' || title.startsWith(brand)) {
        return title;
    }

    return `${title} · ${brand}`;
}
