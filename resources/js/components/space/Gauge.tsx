import { useFormat } from '@/hooks/useFormat';
import { useT } from '@/hooks/useT';

export type GaugeMeasures = {
    words: number;
    audioMinutes: number;
    pages: number;
    themes: number;
    minWords: number;
    minAudioMinutes: number;
    minPages: number;
    minThemes: number;
};

type Props = GaugeMeasures & {
    /** Deux colonnes sur la page du livre, quatre sur le tableau de bord. */
    columns?: 2 | 4;
};

/**
 * Les quatre mesures de R-6, côte à côte.
 *
 * Quatre barres et pas un pourcentage, parce que R-6 est un « et » de
 * critères hétérogènes : un pourcentage unique ferait croire à une
 * progression linéaire vers un nombre, et le référentiel interdit
 * explicitement de compter en histoires.
 *
 * Le composant vivait dans la page « Le livre », c'est-à-dire là où l'on ne va
 * qu'à la fin. Il est partagé depuis le 22 septembre 2026 avec le tableau de
 * bord : « où en est l'histoire » est la question qu'on se pose en ouvrant son
 * espace, pas celle qu'on se pose une fois le livre prêt.
 */
export function Gauge({ columns = 2, ...gauge }: Props) {
    const t = useT();
    const fmt = useFormat();

    const measures = [
        {
            key: 'words',
            label: t('initiator.book.gauge.words'),
            value: gauge.words,
            min: gauge.minWords,
        },
        {
            key: 'audio',
            label: t('initiator.book.gauge.audio'),
            value: gauge.audioMinutes,
            min: gauge.minAudioMinutes,
        },
        {
            key: 'pages',
            label: t('initiator.book.gauge.pages'),
            value: gauge.pages,
            min: gauge.minPages,
        },
        {
            key: 'themes',
            label: t('initiator.book.gauge.themes'),
            value: gauge.themes,
            min: gauge.minThemes,
        },
    ];

    return (
        <ul
            className={`grid gap-4 sm:grid-cols-2 ${columns === 4 ? 'lg:grid-cols-4 lg:gap-8' : ''}`}
        >
            {measures.map((measure) => {
                const done = measure.value >= measure.min;
                const ratio = Math.min(
                    100,
                    Math.round((measure.value / measure.min) * 100),
                );

                return (
                    <li key={measure.key}>
                        {/*
                         * Côte à côte quand il y a de la place, l'un sous
                         * l'autre quand il y en a quatre : à quatre colonnes,
                         * « Mots » et « 10 / 12 000 » sur la même ligne
                         * touchaient le libellé suivant, et les quatre mesures
                         * se lisaient comme une seule phrase.
                         */}
                        <div
                            className={
                                columns === 4
                                    ? 'flex flex-col gap-0.5'
                                    : 'flex items-baseline justify-between gap-3'
                            }
                        >
                            <span className="text-[0.9375rem] font-medium">
                                {measure.label}
                            </span>
                            <span className="text-brand-muted text-[0.9375rem] tabular-nums">
                                {fmt.number(measure.value)} /{' '}
                                {fmt.number(measure.min)}
                            </span>
                        </div>

                        {/*
                         * `role="img"` avec un texte de remplacement : une
                         * barre colorée ne dit rien à un lecteur d'écran, et
                         * le chiffre est déjà au-dessus.
                         */}
                        <div
                            role="img"
                            aria-label={`${measure.label} : ${t(
                                'common.ratio',
                                {
                                    value: fmt.number(measure.value),
                                    total: fmt.number(measure.min),
                                },
                            )}`}
                            className="bg-brand-line mt-2 h-2 w-full overflow-hidden rounded-full"
                        >
                            <div
                                className={`h-full rounded-full transition-[width] duration-700 ${done ? 'bg-brand' : 'bg-brand-muted'}`}
                                style={{ width: `${ratio}%` }}
                            />
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
