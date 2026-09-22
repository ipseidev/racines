import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import { useLocaleProp } from '@/hooks/useLocale';
import { useT } from '@/hooks/useT';

type Props = {
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
 *    liens** : c'est ce qui s'ouvre dans un nouvel onglet, et ce qu'un moteur
 *    suit s'il tombe dessus. Le serveur pose le témoin en passant.
 *  - **Une page d'espace, de compte ou à jeton** n'a qu'une adresse. Le
 *    sélecteur y poste vers `/langue`, qui pose le témoin, met à jour le
 *    compte s'il y en a un, et revient sur place.
 *
 * Chaque langue est nommée **dans sa propre langue** : « Italiano », jamais
 * « Italien ». Personne ne cherche son idiome sous un nom étranger. Pas de
 * drapeau non plus : un drapeau désigne un pays, et l'italien de Suisse n'est
 * pas l'Italie.
 *
 * **Un dépliant, et non cinq lignes à plat** (20 septembre 2026). Les cinq
 * langues occupaient deux rangs en bas de chaque page, et sur un téléphone
 * « Français (Suisse) » et « Italiano (Svizzera) » retombaient à la ligne :
 * cinq choix étalés pour une personne qui n'en changera jamais. Le dépliant
 * n'en montre qu'un — celui qu'elle lit — et ouvre les autres au toucher.
 *
 * Un `<details>` natif, comme les accords de l'opt-in : il s'ouvre au clavier
 * sans script, se rend côté serveur, et **garde de vrais liens dedans** — ce
 * qu'un `<select>` aurait perdu. Le script n'ajoute que ce que le natif ne
 * sait pas faire : refermer sur Échap, sur un clic au-dehors, et après un
 * choix.
 *
 * Il s'ouvre **vers le haut** : ses quatre emplacements sont des pieds de
 * page, et un panneau qui descend y sortirait de l'écran. Le côté auquel il
 * s'accroche, lui, se mesure à l'ouverture — voir `alignRight`.
 */
/** La largeur du panneau, en pixels : `min-w-[13rem]` à 16 px de base. */
const PANEL_WIDTH = 208;

/** Ce qu'on laisse entre le panneau et le bord de l'écran. */
const EDGE = 16;

export default function LocaleSwitcher({ className = '' }: Props) {
    const t = useT();
    const { current, locales } = useLocaleProp();
    const box = useRef<HTMLDetailsElement>(null);

    /*
     * De quel côté le panneau s'accroche.
     *
     * Il ne peut pas s'accrocher toujours du même : le sélecteur est à gauche
     * des pieds de page à jeton et à **droite** de celui des pages publiques,
     * où un panneau accroché par la gauche débordait de trente-deux pixels à
     * 1280 px — et faisait défiler la page entière de côté. Mesuré à
     * l'ouverture plutôt que deviné : c'est la largeur de la fenêtre qui
     * décide, pas la page.
     */
    const [alignRight, setAlignRight] = useState(false);

    const onToggle = (): void => {
        const element = box.current;

        if (element === null || !element.open) {
            return;
        }

        const { left } = element.getBoundingClientRect();

        setAlignRight(left + PANEL_WIDTH > window.innerWidth - EDGE);
    };

    /*
     * Ce que `<details>` ne sait pas faire seul. Sans ces trois gestes, le
     * panneau reste ouvert derrière la page suivante, et il faut retrouver le
     * même bouton pour le refermer.
     */
    useEffect(() => {
        const close = (): void => {
            if (box.current !== null) {
                box.current.open = false;
            }
        };

        const onKey = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                close();
            }
        };

        const onPointer = (event: PointerEvent): void => {
            const target = event.target;

            if (
                box.current !== null &&
                box.current.open &&
                target instanceof Node &&
                !box.current.contains(target)
            ) {
                close();
            }
        };

        document.addEventListener('keydown', onKey);
        document.addEventListener('pointerdown', onPointer);

        return () => {
            document.removeEventListener('keydown', onKey);
            document.removeEventListener('pointerdown', onPointer);
        };
    }, []);

    if (locales.length < 2) {
        return null;
    }

    const chosen = locales.find((locale) => locale.value === current);

    const close = (): void => {
        if (box.current !== null) {
            box.current.open = false;
        }
    };

    const entry =
        'flex min-h-[2.75rem] w-full items-center rounded-md px-3 text-left text-base no-underline';

    return (
        <nav aria-label={t('common.locale.label')} className={className}>
            <details
                ref={box}
                onToggle={onToggle}
                className="group relative inline-block"
            >
                <summary className="text-brand-muted hover:text-brand inline-flex min-h-[2.75rem] cursor-pointer list-none items-center gap-2 text-base transition-colors marker:content-none [&::-webkit-details-marker]:hidden">
                    <span lang={chosen?.value}>
                        {chosen?.name ?? t('common.locale.label')}
                    </span>
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        className="size-4 transition-transform duration-200 group-open:rotate-180"
                    >
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </summary>

                {/*
                 * `bottom-full` : le panneau monte. Quatre pieds de page, et
                 * un panneau qui descendrait sortirait de l'écran.
                 */}
                <ul
                    className={`border-brand-sand bg-brand-surface absolute bottom-full z-20 mb-2 min-w-[13rem] rounded-xl border p-1 shadow-[0_8px_24px_rgba(38,33,28,0.12)] ${
                        alignRight ? 'right-0' : 'left-0'
                    }`}
                >
                    {locales.map((locale) => {
                        const active = locale.value === current;

                        /*
                         * La langue courante est un `<span>` et non un lien
                         * désactivé : un lien qui ne mène nulle part est
                         * annoncé comme un lien par un lecteur d'écran, et
                         * `aria-current` seul ne suffit pas à le dire.
                         */
                        if (active) {
                            return (
                                <li key={locale.value}>
                                    <span
                                        aria-current="true"
                                        lang={locale.value}
                                        className={`${entry} text-brand font-semibold`}
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
                                        onClick={() => {
                                            close();
                                            router.post(
                                                '/langue',
                                                { locale: locale.value },
                                                { preserveScroll: true },
                                            );
                                        }}
                                        className={`${entry} text-brand-text hover:bg-brand/5`}
                                    >
                                        {locale.name}
                                    </button>
                                ) : (
                                    <Link
                                        href={locale.url}
                                        lang={locale.value}
                                        hrefLang={locale.value}
                                        onClick={close}
                                        className={`${entry} text-brand-text hover:bg-brand/5`}
                                    >
                                        {locale.name}
                                    </Link>
                                )}
                            </li>
                        );
                    })}
                </ul>
            </details>
        </nav>
    );
}
