import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import { useFormat } from '@/hooks/useFormat';
import { useT } from '@/hooks/useT';
import { celebrate } from '@/lib/celebrate';
import { stagger } from '@/lib/motion';

type Props = {
    firstName: string | null;
    nextPromptAt: string | null;
};

/**
 * Juste après le oui.
 *
 * Cet écran ne demande plus rien (20 septembre 2026). Il a porté la fiche
 * contact et les souhaits pour plus tard ; les deux posaient une tâche de
 * plus à quelqu'un qui venait d'accepter de raconter sa vie, et la seconde
 * lui parlait de sa mort à la minute où on la félicitait. Les souhaits
 * restent choisissables à l'acceptation et depuis son espace.
 *
 * La fiche contact, elle, n'a plus d'autre point d'entrée : c'est le seul
 * écran qui la proposait. Voir le commentaire de `optin_welcome` dans
 * `lang/fr/narrator.php` — la garde anti-hameçonnage du doc 04 §9 attend une
 * nouvelle place, elle n'a pas été déplacée.
 *
 * Ce qui reste est une fête et une date : la coche qui apparaît, le prénom,
 * un filet d'or qui se trace, le jour de la première question en gros, et la
 * permission de fermer la page. Rien à faire, rien à retenir.
 *
 * Le mouvement est tout l'écran, et il est ordonné : la salve de confettis
 * part des coins du bas pendant que les lignes montent l'une après l'autre.
 * Tout s'éteint sous « réduire les animations » — la salve par
 * `disableForReducedMotion`, les entrées par `.enter`, le filet par sa propre
 * garde. Il reste alors la même page, immobile, qui dit la même chose.
 *
 * La fête ne se rejoue ni au rechargement ni plus tard : elle tient au
 * message flash, qui n'existe qu'à l'arrivée depuis l'acceptation (T-235).
 */
export default function OptInWelcome({ firstName, nextPromptAt }: Props) {
    const t = useT();
    const fmt = useFormat();

    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

    useEffect(() => {
        if (status !== null) {
            void celebrate('soft');
        }
    }, [status]);

    /*
     * « lundi 21 septembre à 09:00 », dans la langue de la page. Repli quand
     * l'heure n'est pas encore connue : la première question part la nuit qui
     * suit l'acceptation, et le planificateur ne l'a pas encore posée.
     */
    const when =
        nextPromptAt === null
            ? t('narrator.optin_welcome.when_unknown')
            : fmt.dateTime(nextPromptAt);

    const title = t('narrator.optin_welcome.title', { name: firstName ?? '' });

    return (
        <>
            <Head title={title} />

            <div className="flex flex-col items-center py-10 text-center">
                <span
                    aria-hidden="true"
                    className="bg-brand text-brand-foreground animate-pop-in flex size-16 items-center justify-center rounded-full"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        className="size-8"
                    >
                        <path d="m6 12 4 4 8-9" />
                    </svg>
                </span>

                <h1
                    className="font-display enter mt-7 text-[2.125rem] leading-tight font-medium"
                    style={stagger(1)}
                >
                    {title}
                </h1>

                {status !== null && (
                    <p
                        role="status"
                        className="text-brand-muted enter mt-2 text-base"
                        style={stagger(2)}
                    >
                        {status}
                    </p>
                )}

                <span
                    aria-hidden="true"
                    className="rule-gold mt-8"
                    style={stagger(3)}
                />

                {/*
                 * La date en grand, et son étiquette en petit au-dessus :
                 * c'est la seule chose de cette page qu'on retient, et la
                 * seule qu'on vient parfois relire.
                 */}
                <p
                    className="text-brand-muted enter mt-8 text-base"
                    style={stagger(4)}
                >
                    {t('narrator.optin_welcome.first_question')}
                </p>

                <p
                    className="font-display enter mt-1 text-[1.625rem] leading-snug font-medium"
                    style={stagger(5)}
                >
                    {when}
                </p>

                <p
                    className="enter mt-8 text-[1.125rem] leading-snug"
                    style={stagger(6)}
                >
                    {t('narrator.optin_welcome.nothing_to_do')}
                </p>

                <p
                    className="text-brand-muted enter mt-3 max-w-[28ch] text-base leading-snug"
                    style={stagger(7)}
                >
                    {t('narrator.optin_welcome.leave')}
                </p>
            </div>
        </>
    );
}
