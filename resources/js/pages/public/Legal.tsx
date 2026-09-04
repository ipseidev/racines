import { Head } from '@inertiajs/react';

type Props = {
    title: string;
    /** Markdown déjà converti côté serveur, entrées HTML échappées. */
    html: string;
};

/**
 * Une page légale.
 *
 * Le texte vient d'un fichier markdown converti côté serveur, et non d'un
 * composant : ces textes seront relus par un conseil, et un conseil relit un
 * texte — pas du JSX entrecoupé de classes CSS. Le markdown se diffuse,
 * s'annote et se compare d'une version à l'autre.
 *
 * `dangerouslySetInnerHTML` est ici sans danger : le convertisseur est
 * configuré en `html_input => escape`, la source est un fichier du dépôt, et
 * aucune saisie d'utilisateur n'y entre.
 */
export default function Legal({ title, html }: Props) {
    return (
        <div className="mx-auto w-full max-w-3xl px-6 py-8 text-[1.125rem] leading-relaxed">
            <Head title={title} />

            <article
                className="legal-prose"
                dangerouslySetInnerHTML={{ __html: html }}
            />
        </div>
    );
}
