import { Link, router } from '@inertiajs/react';

import { useLocaleProp } from '@/hooks/useLocale';
import { useT } from '@/hooks/useT';

type Props = {
    /** `inline` sur une page sobre, `footer` dans un pied de page. */
    tone?: 'inline' | 'footer';
    className?: string;
};

/**
 * Choisir sa langue.
 *
 * Deux mécanismes derrière une seule apparence, parce que les pages n'ont pas
 * toutes la même adresse selon la langue :
 *
 *  - **Une page publique** existe à cinq adresses (`/cgv`,
 *    `/it/condizioni-di-vendita`…). Le sélecteur y est fait de **vrais
 *    liens** : c'est ce que Google suit, et c'est ce qui permet d'ouvrir une
 *    langue dans un nouvel onglet. Le serveur pose le témoin en passant.
 *  - **Une page d'espace, de compte ou à jeton** n'a qu'une adresse. Le
 *    sélecteur y poste vers `/langue`, qui pose le témoin, met à jour le
 *    compte s'il y en a un, et revient sur place.
 *
 * Chaque langue est nommée **dans sa propre langue** : « Italiano », jamais
 * « Italien ». Personne ne cherche son idiome sous un nom étranger. Pas de
 * drapeau non plus : un drapeau désigne un pays, et l'italien de Suisse n'est
 * pas l'Italie.
 */
export default function LocaleSwitcher({
    tone = 'inline',
    className = '',
}: Props) {
    const t = useT();
    const { current, locales } = useLocaleProp();

    if (locales.length < 2) {
        return null;
    }

    const item =
        tone === 'footer'
            ? 'text-brand-muted hover:text-brand'
            : 'text-brand-muted hover:text-brand';

    return (
        <nav aria-label={t('common.locale.label')} className={className}>
            <ul className="flex flex-wrap items-center gap-x-4 gap-y-1">
                {locales.map((locale) => {
                    const active = locale.value === current;

                    /*
                     * La langue courante est un `<span>` et non un lien
                     * désactivé : un lien qui ne mène nulle part est annoncé
                     * comme un lien par un lecteur d'écran, et `aria-current`
                     * seul ne suffit pas à le dire.
                     */
                    if (active) {
                        return (
                            <li key={locale.value}>
                                <span
                                    aria-current="true"
                                    lang={locale.value}
                                    className="text-brand inline-flex min-h-[2.75rem] items-center text-base font-semibold"
                                >
                                    {locale.name}
                                </span>
                            </li>
                        );
                    }

                    return (
                        <li key={locale.value}>
                            {locale.url === null ? (
                                <button
                                    type="button"
                                    lang={locale.value}
                                    onClick={() =>
                                        router.post(
                                            '/langue',
                                            { locale: locale.value },
                                            { preserveScroll: true },
                                        )
                                    }
                                    className={`${item} inline-flex min-h-[2.75rem] items-center text-base`}
                                >
                                    {locale.name}
                                </button>
                            ) : (
                                <Link
                                    href={locale.url}
                                    lang={locale.value}
                                    hrefLang={locale.value}
                                    className={`${item} inline-flex min-h-[2.75rem] items-center text-base`}
                                >
                                    {locale.name}
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
