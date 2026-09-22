import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

import { SubmitButton } from '@/components/form/SubmitButton';
import { TextAreaField } from '@/components/form/TextAreaField';
import { IconButton } from '@/components/space/IconButton';
import {
    ArrowDown,
    ArrowUp,
    Plus,
    Refresh,
    ToTop,
    Trash,
} from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { useSpacePath } from '@/hooks/useSpacePath';
import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';
import { move, toTop } from '@/lib/queue';

type Question = {
    id: string;
    text: string;
    theme: string;
    themeLabel: string;
};

type Props = {
    queue: Question[];
    excluded: Question[];
    asked: Question[];
    narratorFirstName: string | null;
};

const MAX_LENGTH = 300;

/** Quatre photos au plus : au-delà, ce n'est plus une question, c'est un diaporama. */
const MAX_PHOTOS = 4;

/** L'ordre part de lui-même, sept dixièmes de seconde après le dernier geste. */
const SAVE_DELAY = 700;

/**
 * Combien de questions comptent vraiment « bientôt ».
 *
 * Une par envoi, une par semaine : huit, c'est deux mois. Au-delà, l'ordre est
 * une intention, pas une décision — et le montrer sous forme de liste à
 * réordonner donnait une file de soixante-cinq rangs à trier à la main.
 */
const IMMINENT = 8;

/**
 * Le corpus et la file, côte à côte.
 *
 * **Ce que la page cachait.** La file *est* le corpus : soixante-cinq
 * questions actives, soixante-cinq dans la file. L'écran les présentait donc
 * toutes comme « les prochaines questions », cinq à la fois, avec « Voir dix
 * de plus » — et aucun moyen d'en **chercher** une. Le thème était affiché sur
 * chaque carte sans servir à rien : ni filtre, ni regroupement. Réordonner se
 * faisait un cran à la fois. Les écartées disparaissaient sans retour visible.
 * Et la question personnelle, l'acte le plus intime de la page, était repliée
 * dans un accordéon.
 *
 * **Deux panneaux, deux rôles.** À gauche le corpus : tout, cherchable,
 * filtrable par thème, avec « Poser bientôt » qui remonte en tête. À droite ce
 * qui part vraiment dans les deux prochains mois, dans l'ordre, qu'on réordonne
 * en glissant. Sur téléphone, deux onglets — deux panneaux côte à côte sur
 * 390 px ne sont pas deux panneaux, ce sont deux colonnes illisibles.
 *
 * **Le glisser-déposer ne remplace pas les flèches, il s'y ajoute.** Un
 * glisser n'existe ni au clavier ni pour une main qui tremble (WCAG 2.1.1 et
 * 2.5.7 : tout geste de pointage doit avoir son équivalent simple). Les flèches
 * et « Poser en premier » restent donc, et ce sont elles que les tests
 * exercent. Le commentaire qui disait « l'ordre se change par des boutons et
 * non par glisser-déposer, ça coûte plus que ça ne rend » tombe : il valait
 * pour une liste de cinq sur un téléphone, pas pour soixante-cinq sur un
 * écran large.
 *
 * Le narrateur, lui, garde le droit de ne pas répondre : sa souveraineté vit
 * là, pas dans le corpus.
 */
export default function Questions({
    queue,
    excluded,
    asked,
    narratorFirstName,
}: Props) {
    const t = useT();
    const spacePath = useSpacePath();
    const name = narratorFirstName;

    const title =
        name === null
            ? t('initiator.questions.title_generic')
            : t('initiator.questions.title', { name });

    const [order, setOrder] = useState<string[]>(() =>
        queue.map((question) => question.id),
    );

    const timer = useRef<number | null>(null);
    const inflight = useRef(false);

    const byId = useMemo(
        () => new Map(queue.map((question) => [question.id, question])),
        [queue],
    );

    // Le serveur a répondu : on reprend son ordre, sauf si un geste attend
    // encore de partir ou qu'un envoi est en cours — ce que la personne vient
    // de faire prime sur ce que le serveur savait avant.
    useEffect(() => {
        if (timer.current === null && !inflight.current) {
            setOrder(queue.map((question) => question.id));
        }
    }, [queue]);

    useEffect(
        () => () => {
            if (timer.current !== null) {
                window.clearTimeout(timer.current);
            }
        },
        [],
    );

    const reorder = (next: string[]) => {
        setOrder(next);

        if (timer.current !== null) {
            window.clearTimeout(timer.current);
        }

        timer.current = window.setTimeout(() => {
            timer.current = null;
            inflight.current = true;

            router.post(
                spacePath('/questions/ordre'),
                { order: next },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onFinish: () => {
                        inflight.current = false;
                    },
                },
            );
        }, SAVE_DELAY);
    };

    const exclude = (id: string, value: boolean) =>
        router.post(
            spacePath(`/questions/${id}/exclure`),
            { excluded: value },
            { preserveScroll: true, preserveState: true },
        );

    /*
     * Le formulaire porte des fichiers : `forceFormData` pour qu'Inertia les
     * envoie en `multipart` plutôt qu'en JSON, où un `File` se sérialise en
     * objet vide — le champ part, le fichier non, et rien ne le dit.
     */
    const custom = useForm<{ text: string; photos: File[] }>({
        text: '',
        photos: [],
    });

    /*
     * Les aperçus vivent dans un état à part, et non dans le formulaire.
     *
     * `URL.createObjectURL` réserve de la mémoire jusqu'à ce qu'on la rende :
     * les URL se révoquent au démontage et à chaque retrait, sinon une
     * personne qui hésite entre six photos en laisse six derrière elle.
     */
    const [previews, setPreviews] = useState<{ url: string; name: string }[]>(
        [],
    );

    useEffect(
        () => () => {
            for (const preview of previews) {
                URL.revokeObjectURL(preview.url);
            }
        },
        [previews],
    );

    const addPhotos = (files: FileList | null) => {
        if (files === null || files.length === 0) {
            return;
        }

        const room = MAX_PHOTOS - custom.data.photos.length;
        const accepted = [...files].slice(0, Math.max(0, room));

        if (accepted.length === 0) {
            return;
        }

        custom.setData('photos', [...custom.data.photos, ...accepted]);
        setPreviews((current) => [
            ...current,
            ...accepted.map((file) => ({
                url: URL.createObjectURL(file),
                name: file.name,
            })),
        ]);
    };

    const removePhoto = (index: number) => {
        const preview = previews[index];

        if (preview !== undefined) {
            URL.revokeObjectURL(preview.url);
        }

        custom.setData(
            'photos',
            custom.data.photos.filter((_, i) => i !== index),
        );
        setPreviews((current) => current.filter((_, i) => i !== index));
    };

    /* --- Le corpus : recherche et thèmes ------------------------------- */

    const [search, setSearch] = useState('');
    const [theme, setTheme] = useState<string | null>(null);
    const [tab, setTab] = useState<'next' | 'corpus'>('next');

    /** Les thèmes du corpus, comptés, dans l'ordre où ils y apparaissent. */
    const themes = useMemo(() => {
        const counts = new Map<string, { label: string; n: number }>();

        for (const question of queue) {
            const seen = counts.get(question.theme);
            counts.set(question.theme, {
                label: question.themeLabel,
                n: (seen?.n ?? 0) + 1,
            });
        }

        return [...counts.entries()].map(([value, { label, n }]) => ({
            value,
            label,
            n,
        }));
    }, [queue]);

    /*
     * La recherche est **sans accents et sans casse** : personne ne tape
     * « épreuve » avec son accent dans un champ de recherche, et ne rien
     * trouver pour cette raison-là donne l'impression d'un corpus vide.
     */
    const fold = (value: string) =>
        value
            .normalize('NFD')
            .replace(/\p{Diacritic}/gu, '')
            .toLowerCase();

    const needle = fold(search.trim());

    const corpus = order
        .map((id) => byId.get(id))
        .filter((question): question is Question => question !== undefined)
        .filter((question) => theme === null || question.theme === theme)
        .filter(
            (question) => needle === '' || fold(question.text).includes(needle),
        );

    /* --- La file : glisser pour réordonner ----------------------------- */

    const [dragged, setDragged] = useState<string | null>(null);

    /** Déplace `id` à l'indice `to`, et enregistre comme les flèches. */
    const dropAt = (id: string, to: number) => {
        const from = order.indexOf(id);

        if (from === -1 || from === to) {
            return;
        }

        const next = [...order];
        const [picked] = next.splice(from, 1);
        next.splice(to, 0, picked);

        reorder(next);
    };

    const next = order.slice(0, IMMINENT);
    const rest = order.length - next.length;

    const panels =
        'lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-10 lg:items-start';

    return (
        <>
            <Head title={title} />

            <div className="enter" style={stagger(0)}>
                <PageHeader
                    eyebrow={t('initiator.nav.questions')}
                    title={title}
                    intro={t('initiator.questions.intro', { name: name ?? '' })}
                />
            </div>

            {/*
             * Les deux onglets du téléphone. Ils ne s'affichent qu'en dessous
             * de `lg`, où les deux panneaux ne tiennent pas côte à côte ; sur
             * grand écran les deux sont là et les onglets n'auraient rien à
             * commuter.
             */}
            <div
                role="tablist"
                aria-label={t('initiator.nav.questions')}
                className="enter border-brand-sand mt-8 flex gap-1 border-b lg:hidden"
                style={stagger(1)}
            >
                {(
                    [
                        ['next', 'initiator.questions.tab_next'],
                        ['corpus', 'initiator.questions.tab_corpus'],
                    ] as const
                ).map(([key, label]) => (
                    <button
                        key={key}
                        type="button"
                        role="tab"
                        aria-selected={tab === key}
                        onClick={() => setTab(key)}
                        className={`tab ${tab === key ? 'tab-current' : ''}`}
                    >
                        {t(label)}
                    </button>
                ))}
            </div>

            <div className={`mt-8 ${panels}`}>
                {/* ---- Le corpus ------------------------------------- */}
                <section
                    aria-labelledby="corpus"
                    className={`enter min-w-0 ${tab === 'corpus' ? '' : 'max-lg:hidden'}`}
                    style={stagger(2)}
                >
                    <h2 id="corpus" className="eyebrow">
                        {t('initiator.questions.corpus_title')}
                    </h2>

                    <p className="text-brand-muted mt-3 text-base">
                        {t('initiator.questions.corpus_intro', {
                            count: order.length,
                        })}
                    </p>

                    {/*
                     * La question personnelle sort de l'accordéon.
                     *
                     * C'est l'acte le plus intime de la page — « raconte-nous
                     * ce dont je n'ai jamais osé te parler » —, et il était
                     * replié derrière un chevron, au même rang qu'un réglage.
                     */}
                    <details className="card group mt-5">
                        <summary className="flex cursor-pointer list-none items-center gap-3 p-4 [&::-webkit-details-marker]:hidden">
                            <span className="bg-brand-linen text-brand inline-flex size-9 flex-none items-center justify-center rounded-full transition-transform duration-300 group-open:rotate-45">
                                <Plus />
                            </span>
                            <span className="font-display text-brand text-lg leading-snug font-medium">
                                {t('initiator.questions.add.title')}
                            </span>
                        </summary>

                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                custom.post(
                                    spacePath('/questions/personnalisee'),
                                    {
                                        forceFormData: true,
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            custom.reset();

                                            for (const preview of previews) {
                                                URL.revokeObjectURL(
                                                    preview.url,
                                                );
                                            }

                                            setPreviews([]);
                                        },
                                    },
                                );
                            }}
                            className="border-brand-sand flex flex-col gap-4 border-t p-4"
                        >
                            <TextAreaField
                                label={t('initiator.questions.add.label')}
                                hint={t('initiator.questions.add.hint')}
                                error={custom.errors.text}
                                value={custom.data.text}
                                onChange={(event) =>
                                    custom.setData('text', event.target.value)
                                }
                                rows={3}
                                minLength={10}
                                maxLength={MAX_LENGTH}
                                required
                                className="min-h-[6rem]"
                                counter={t('initiator.questions.add.counter', {
                                    count: custom.data.text.length,
                                    max: MAX_LENGTH,
                                })}
                            />

                            {/*
                             * Les photos de la question (T-253).
                             *
                             * Une famille qui écrit sa propre question a
                             * souvent l'image sous la main — « raconte-nous
                             * cette photo » est la question la plus naturelle
                             * qui soit, et elle ne se pose pas sans l'image.
                             * Le dépôt existait depuis le bloc 12, mais
                             * seulement après coup, depuis le tableau de bord.
                             */}
                            <fieldset className="flex flex-col gap-3">
                                <legend className="font-medium">
                                    {t('initiator.questions.add.photos')}
                                </legend>

                                <p className="text-brand-muted text-base">
                                    {t('initiator.questions.add.photos_hint', {
                                        name: name ?? '',
                                    })}
                                </p>

                                {previews.length > 0 && (
                                    <ul className="flex flex-wrap gap-3">
                                        {previews.map((preview, index) => (
                                            <li
                                                key={preview.url}
                                                className="relative"
                                            >
                                                <img
                                                    src={preview.url}
                                                    alt={preview.name}
                                                    className="border-brand-sand size-20 rounded-lg border object-cover"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        removePhoto(index)
                                                    }
                                                    aria-label={t(
                                                        'initiator.questions.add.photos_remove',
                                                    )}
                                                    className="bg-brand-surface border-brand-sand text-brand-muted hover:text-brand press absolute -top-2 -right-2 inline-flex size-7 items-center justify-center rounded-full border"
                                                >
                                                    <Trash />
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                {custom.data.photos.length < MAX_PHOTOS ? (
                                    <label className="border-brand-sand hover:border-brand text-brand-muted hover:text-brand press inline-flex min-h-[2.75rem] cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed px-4 py-2 transition-colors">
                                        <Plus />
                                        {t('initiator.questions.add.photos')}
                                        <input
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            className="sr-only"
                                            onChange={(event) => {
                                                addPhotos(event.target.files);
                                                event.target.value = '';
                                            }}
                                        />
                                    </label>
                                ) : (
                                    <p className="text-brand-muted text-base">
                                        {t(
                                            'initiator.questions.add.photos_too_many',
                                        )}
                                    </p>
                                )}

                                {custom.errors.photos !== undefined && (
                                    <p role="alert" className="text-base">
                                        {custom.errors.photos}
                                    </p>
                                )}
                            </fieldset>

                            <SubmitButton
                                processing={custom.processing}
                                waitingLabel={t(
                                    'initiator.questions.add.waiting',
                                )}
                                className="self-start"
                            >
                                {t('initiator.questions.add.submit')}
                            </SubmitButton>
                        </form>
                    </details>

                    <label className="mt-5 block">
                        <span className="sr-only">
                            {t('initiator.questions.search')}
                        </span>
                        <input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t(
                                'initiator.questions.search_placeholder',
                            )}
                            className="border-brand-sand bg-brand-surface focus:border-brand min-h-[2.75rem] w-full rounded-md border px-4 py-2 outline-none"
                        />
                    </label>

                    <ul className="mt-3 flex flex-wrap gap-2">
                        {[
                            {
                                value: null,
                                label: t('initiator.questions.theme_all'),
                                n: order.length,
                            },
                            ...themes,
                        ].map((entry) => (
                            <li key={entry.value ?? 'all'}>
                                <button
                                    type="button"
                                    aria-pressed={theme === entry.value}
                                    onClick={() => setTheme(entry.value)}
                                    className={`press min-h-[2.25rem] rounded-full border px-3 py-1 text-[0.9rem] transition-colors ${
                                        theme === entry.value
                                            ? 'border-brand bg-brand text-brand-foreground'
                                            : 'border-brand-sand text-brand-muted hover:border-brand hover:text-brand'
                                    }`}
                                >
                                    {entry.label}{' '}
                                    <span className="tabular-nums opacity-70">
                                        {entry.n}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>

                    {corpus.length === 0 ? (
                        <p className="card mt-5 p-5">
                            {t('initiator.questions.no_match')}
                        </p>
                    ) : (
                        <ul className="mt-5 flex flex-col gap-2">
                            {corpus.map((question) => {
                                const rank = order.indexOf(question.id);

                                return (
                                    <li
                                        key={question.id}
                                        className="card flex flex-wrap items-baseline gap-x-4 gap-y-2 p-4"
                                    >
                                        <p className="min-w-0 flex-1 leading-snug">
                                            {question.text}
                                            <span className="text-brand-muted ml-2 text-[0.8rem] font-semibold tracking-[0.08em] uppercase">
                                                {question.themeLabel}
                                            </span>
                                        </p>

                                        <div className="flex flex-none gap-x-4 text-base">
                                            {rank >= IMMINENT && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        reorder(
                                                            toTop(order, rank),
                                                        )
                                                    }
                                                    className="text-brand press inline-flex min-h-[2.75rem] items-center gap-1.5 font-medium underline-offset-4 hover:underline"
                                                >
                                                    <ToTop />
                                                    {t(
                                                        'initiator.questions.soon',
                                                    )}
                                                </button>
                                            )}

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    exclude(question.id, true)
                                                }
                                                className="text-brand-muted hover:text-brand press inline-flex min-h-[2.75rem] items-center gap-1.5 underline-offset-4 hover:underline"
                                            >
                                                <Trash />
                                                {t(
                                                    'initiator.questions.exclude',
                                                )}
                                            </button>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}

                    {excluded.length > 0 && (
                        <details className="mt-8">
                            <summary className="eyebrow cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                                {t('initiator.questions.excluded_count', {
                                    count: excluded.length,
                                })}
                            </summary>

                            <ul className="mt-4 flex flex-col gap-2">
                                {excluded.map((question) => (
                                    <li
                                        key={question.id}
                                        className="card flex flex-wrap items-baseline gap-x-4 gap-y-2 p-4"
                                    >
                                        <p className="text-brand-muted min-w-0 flex-1 leading-snug line-through">
                                            {question.text}
                                        </p>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                exclude(question.id, false)
                                            }
                                            className="text-brand press inline-flex min-h-[2.75rem] flex-none items-center gap-1.5 font-medium underline-offset-4 hover:underline"
                                        >
                                            <Refresh />
                                            {t('initiator.questions.restore')}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </details>
                    )}

                    {asked.length > 0 && (
                        <details className="mt-6">
                            <summary className="eyebrow cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                                {t('initiator.questions.asked_count', {
                                    count: asked.length,
                                })}
                            </summary>

                            <ul className="mt-4 flex flex-col gap-2">
                                {asked.map((question) => (
                                    <li
                                        key={question.id}
                                        className="card text-brand-muted p-4 leading-snug"
                                    >
                                        {question.text}
                                    </li>
                                ))}
                            </ul>
                        </details>
                    )}
                </section>

                {/* ---- Les prochaines -------------------------------- */}
                <section
                    aria-labelledby="next"
                    className={`enter min-w-0 max-lg:mt-8 ${tab === 'next' ? '' : 'max-lg:hidden'}`}
                    style={stagger(3)}
                >
                    <h2 id="next" className="eyebrow">
                        {t('initiator.questions.next_title')}
                    </h2>

                    <p className="text-brand-muted mt-3 text-base">
                        {t('initiator.questions.next_intro')}
                    </p>

                    {next.length === 0 ? (
                        <p className="card mt-5 p-5">
                            {t('initiator.questions.queue_empty')}
                        </p>
                    ) : (
                        <ol
                            aria-labelledby="next"
                            className="mt-5 flex flex-col gap-3"
                        >
                            {next.map((id, index) => {
                                const question = byId.get(id);

                                if (question === undefined) {
                                    return null;
                                }

                                return (
                                    <li
                                        key={id}
                                        draggable
                                        onDragStart={() => setDragged(id)}
                                        onDragEnd={() => setDragged(null)}
                                        onDragOver={(event) =>
                                            event.preventDefault()
                                        }
                                        onDrop={(event) => {
                                            event.preventDefault();

                                            if (dragged !== null) {
                                                dropAt(dragged, index);
                                            }

                                            setDragged(null);
                                        }}
                                        className={`card p-4 transition-opacity ${
                                            index === 0
                                                ? 'border-l-brand-gold border-l-4'
                                                : ''
                                        } ${dragged === id ? 'opacity-40' : ''}`}
                                    >
                                        <div className="flex items-start gap-3">
                                            {/*
                                             * La poignée est décorative : ce
                                             * qui se glisse est la carte
                                             * entière, et ce qui se fait au
                                             * clavier passe par les flèches
                                             * ci-contre.
                                             */}
                                            <span
                                                aria-hidden="true"
                                                className="text-brand-muted/60 mt-1 flex-none cursor-grab select-none"
                                            >
                                                <Grip />
                                            </span>

                                            <span
                                                aria-hidden="true"
                                                className="bg-brand-linen text-brand font-display inline-flex size-8 flex-none items-center justify-center rounded-full text-base font-semibold tabular-nums"
                                            >
                                                {index + 1}
                                            </span>

                                            <div className="min-w-0 flex-1">
                                                <span className="sr-only">
                                                    {t(
                                                        'initiator.questions.position',
                                                        { n: index + 1 },
                                                    )}
                                                </span>
                                                <p className="leading-snug">
                                                    {question.text}
                                                </p>
                                                <p className="text-brand-muted mt-1.5 text-[0.8rem] font-semibold tracking-[0.08em] uppercase">
                                                    {question.themeLabel}
                                                </p>
                                            </div>

                                            <div className="flex flex-none flex-col gap-2">
                                                <IconButton
                                                    label={t(
                                                        'initiator.questions.move_up',
                                                    )}
                                                    disabled={index === 0}
                                                    onClick={() =>
                                                        reorder(
                                                            move(
                                                                order,
                                                                index,
                                                                -1,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <ArrowUp />
                                                </IconButton>

                                                <IconButton
                                                    label={t(
                                                        'initiator.questions.move_down',
                                                    )}
                                                    disabled={
                                                        index ===
                                                        order.length - 1
                                                    }
                                                    onClick={() =>
                                                        reorder(
                                                            move(
                                                                order,
                                                                index,
                                                                1,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <ArrowDown />
                                                </IconButton>
                                            </div>
                                        </div>

                                        <div className="mt-3 flex flex-wrap gap-x-5 gap-y-2 pl-14 text-base">
                                            {index > 0 && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        reorder(
                                                            toTop(order, index),
                                                        )
                                                    }
                                                    className="text-brand press inline-flex min-h-[2.75rem] items-center gap-1.5 font-medium underline-offset-4 hover:underline"
                                                >
                                                    <ToTop />
                                                    {t(
                                                        'initiator.questions.first',
                                                    )}
                                                </button>
                                            )}

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    exclude(id, true)
                                                }
                                                className="text-brand-muted hover:text-brand press inline-flex min-h-[2.75rem] items-center gap-1.5 underline-offset-4 hover:underline"
                                            >
                                                <Trash />
                                                {t(
                                                    'initiator.questions.exclude',
                                                )}
                                            </button>
                                        </div>
                                    </li>
                                );
                            })}
                        </ol>
                    )}

                    {rest > 0 && (
                        <p className="text-brand-muted mt-4 text-base">
                            {t('initiator.questions.next_rest', {
                                count: rest,
                            })}
                        </p>
                    )}
                </section>
            </div>
        </>
    );
}

/** La poignée du glisser : six points, comme partout ailleurs. */
function Grip() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="size-5">
            {[8, 12, 16].map((y) =>
                [9, 15].map((x) => (
                    <circle
                        key={`${x}-${y}`}
                        cx={x}
                        cy={y}
                        r="1.4"
                        fill="currentColor"
                    />
                )),
            )}
        </svg>
    );
}
