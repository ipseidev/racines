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
import { useFormat } from '@/hooks/useFormat';
import { useSpacePath } from '@/hooks/useSpacePath';
import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';
import { move, shownCount, toTop } from '@/lib/queue';

/**
 * Une entrée de la file d'envoi.
 *
 * `question` vient du fonds proposé et s'écarte ; `story` est une question
 * écrite par la famille, déjà proposée, qui se retire. Les deux partagent un
 * rang, et c'est ce qui leur permet de se doubler.
 */
type Entry = {
    kind: 'story' | 'question';
    id: string;
    text: string;
    theme: string | null;
    themeLabel: string | null;
    askedBy: string | null;
    photos: number;
    /** La date d'envoi, sur les huit premières seulement. */
    sendAt: string | null;
};

type Archived = {
    id: string;
    text: string;
    themeLabel: string;
};

type Props = {
    next: Entry[];
    excluded: Archived[];
    asked: Archived[];
    narratorFirstName: string | null;
};

const MAX_LENGTH = 300;

/** Quatre photos au plus : au-delà, ce n'est plus une question, c'est un diaporama. */
const MAX_PHOTOS = 4;

/** L'ordre part de lui-même, sept dixièmes de seconde après le dernier geste. */
const SAVE_DELAY = 700;

/** Dix questions d'abord, vingt de plus à chaque « Voir plus ». */
const PAGE = 10;
const STEP = 20;

/**
 * Les questions posées à la narratrice : **une seule liste, datée**.
 *
 * L'écran en a montré deux — « le corpus » à gauche, « les prochaines » à
 * droite — et c'était une erreur de conception, la mienne. La file **est** le
 * fonds de questions : soixante-cinq actives, soixante-cinq dans la file. Les
 * deux panneaux affichaient donc la même chose deux fois ; mesuré sur l'écran
 * livré, **sept des huit** questions de droite étaient aussi listées à gauche,
 * au même instant, avec des boutons différents. Un panneau qui promet
 * « piochez ici » alors qu'il n'y a rien à piocher ne peut pas être clair.
 *
 * Le mot « corpus » disparaît avec lui : c'est le vocabulaire du dossier, et
 * personne n'a à l'apprendre pour ranger des questions.
 *
 * **Ce qui rend la liste lisible, c'est la date.** L'écran répond à « quelle
 * question, quand », et ne disait le quand nulle part — seul le tableau de
 * bord portait une date. Les premières cartes portent la leur, et la suite de
 * la liste cesse d'être un réservoir mystérieux : ce sont les questions des
 * semaines d'après. Au-delà de huit, plus de date : une pause ou un changement
 * de rythme décalerait tout, et une date qu'on ne tient pas vaut moins que pas
 * de date.
 *
 * La recherche et les thèmes **filtrent cette liste**, ils n'en ouvrent pas
 * une seconde. Chercher « guerre » montre les questions concernées, et quand
 * elles tomberaient.
 *
 * Le glisser-déposer s'ajoute aux flèches, il ne les remplace pas : un glisser
 * n'existe ni au clavier ni pour une main qui tremble (WCAG 2.1.1 et 2.5.7).
 * Il se retire pendant un filtrage, où « déposer ici » ne voudrait rien dire —
 * les rangs affichés ne sont plus contigus.
 *
 * Le narrateur, lui, garde le droit de ne pas répondre : sa souveraineté vit
 * là, pas dans la liste.
 */
export default function Questions({
    next: serverNext,
    excluded,
    asked,
    narratorFirstName,
}: Props) {
    const t = useT();
    const fmt = useFormat();
    const spacePath = useSpacePath();
    const name = narratorFirstName;

    const title =
        name === null
            ? t('initiator.questions.title_generic')
            : t('initiator.questions.title', { name });

    const [next, setNext] = useState<Entry[]>(serverNext);
    const [shown, setShown] = useState(PAGE);

    const timer = useRef<number | null>(null);
    const inflight = useRef(false);

    // Le serveur a répondu : on reprend son ordre, sauf si un geste attend
    // encore de partir ou qu'un envoi est en cours — ce que la personne vient
    // de faire prime sur ce que le serveur savait avant.
    useEffect(() => {
        if (timer.current === null && !inflight.current) {
            setNext(serverNext);
        }
    }, [serverNext]);

    useEffect(
        () => () => {
            if (timer.current !== null) {
                window.clearTimeout(timer.current);
            }
        },
        [],
    );

    const reorder = (entries: Entry[]) => {
        setNext(entries);

        if (timer.current !== null) {
            window.clearTimeout(timer.current);
        }

        timer.current = window.setTimeout(() => {
            timer.current = null;
            inflight.current = true;

            router.post(
                spacePath('/questions/ordre'),
                {
                    order: entries.map((entry) => ({
                        kind: entry.kind,
                        id: entry.id,
                    })),
                },
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

    /** Retirer une question qu'on a écrite : elle n'existe que pour ce projet. */
    const removeOwn = (id: string) =>
        router.delete(spacePath(`/questions/proposees/${id}`), {
            preserveScroll: true,
        });

    /* --- La question que la famille écrit ------------------------------ */

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

    /* --- Chercher et filtrer, dans cette liste-ci ----------------------- */

    const [search, setSearch] = useState('');
    const [theme, setTheme] = useState<string | null>(null);

    /** Les thèmes présents, comptés, dans l'ordre où ils apparaissent. */
    const themes = useMemo(() => {
        const counts = new Map<string, { label: string; n: number }>();

        for (const entry of next) {
            if (entry.theme === null || entry.themeLabel === null) {
                continue;
            }

            const seen = counts.get(entry.theme);
            counts.set(entry.theme, {
                label: entry.themeLabel,
                n: (seen?.n ?? 0) + 1,
            });
        }

        return [...counts.entries()].map(([value, { label, n }]) => ({
            value,
            label,
            n,
        }));
    }, [next]);

    /*
     * La recherche est **sans accents et sans casse** : personne ne tape
     * « épreuve » avec son accent dans un champ de recherche, et ne rien
     * trouver pour cette raison-là donne l'impression d'une liste vide.
     */
    const fold = (value: string) =>
        value
            .normalize('NFD')
            .replace(/\p{Diacritic}/gu, '')
            .toLowerCase();

    const needle = fold(search.trim());
    const filtering = needle !== '' || theme !== null;

    const matching = next
        .filter((entry) => theme === null || entry.theme === theme)
        .filter((entry) => needle === '' || fold(entry.text).includes(needle));

    // Ce qui est filtré s'affiche en entier ; sinon on déroule par paquets.
    const rows = filtering
        ? matching
        : matching.slice(0, shownCount(matching.length, shown));
    const remaining = matching.length - rows.length;

    /* --- Glisser pour réordonner ---------------------------------------- */

    const [dragged, setDragged] = useState<string | null>(null);

    const dropAt = (id: string, to: number) => {
        const from = next.findIndex((entry) => entry.id === id);

        if (from === -1 || from === to) {
            return;
        }

        const entries = [...next];
        const [picked] = entries.splice(from, 1);
        entries.splice(to, 0, picked);

        reorder(entries);
    };

    /** Remonter une entrée en tête de file, quelle que soit sa nature. */
    const promote = (id: string) => {
        const index = next.findIndex((entry) => entry.id === id);

        if (index > 0) {
            reorder(toTop(next, index));
        }
    };

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

            <div className="mt-8 lg:grid lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] lg:items-start lg:gap-10">
                {/* ---- La liste, dans l'ordre des envois -------------- */}
                <section
                    aria-labelledby="list"
                    className="enter min-w-0"
                    style={stagger(1)}
                >
                    <h2 id="list" className="eyebrow">
                        {t('initiator.questions.list_title')}
                    </h2>

                    <label className="mt-4 block">
                        <span className="sr-only">
                            {t('initiator.questions.search_all', {
                                count: next.length,
                            })}
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

                    {/*
                     * Une bande qui défile sur téléphone, et qui se répand
                     * sur grand écran : dix thèmes sur cinq rangs prenaient
                     * 212 px avant la première question.
                     */}
                    <ul className="-mx-6 mt-3 flex [scrollbar-width:none] gap-2 overflow-x-auto px-6 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden">
                        {[
                            {
                                value: null,
                                label: t('initiator.questions.theme_all'),
                                n: next.length,
                            },
                            ...themes,
                        ].map((entry) => (
                            <li
                                key={entry.value ?? 'all'}
                                className="flex-none"
                            >
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

                    {rows.length === 0 ? (
                        <p className="card mt-5 p-5">
                            {t(
                                filtering
                                    ? 'initiator.questions.no_match'
                                    : 'initiator.questions.queue_empty',
                            )}
                        </p>
                    ) : (
                        <ol
                            aria-labelledby="list"
                            className="mt-5 flex flex-col gap-3"
                        >
                            {rows.map((entry) => {
                                const index = next.indexOf(entry);
                                const mine = entry.kind === 'story';

                                return (
                                    <li
                                        key={entry.id}
                                        draggable={!filtering}
                                        onDragStart={() => setDragged(entry.id)}
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
                                            mine
                                                ? 'border-l-brand border-l-4'
                                                : index === 0
                                                  ? 'border-l-brand-gold border-l-4'
                                                  : ''
                                        } ${dragged === entry.id ? 'opacity-40' : ''}`}
                                    >
                                        {/*
                                         * La date d'abord : c'est la réponse à
                                         * « quand », et c'est elle qui fait
                                         * comprendre que la suite de la liste
                                         * n'est pas un réservoir à part.
                                         */}
                                        <p className="text-brand-muted flex flex-wrap items-baseline gap-x-3 text-base">
                                            <span className="tabular-nums">
                                                {index + 1}
                                            </span>
                                            <span>
                                                {entry.sendAt === null
                                                    ? t(
                                                          'initiator.questions.later',
                                                      )
                                                    : t(
                                                          'initiator.questions.sends_on',
                                                          {
                                                              date: fmt.dateTime(
                                                                  entry.sendAt,
                                                              ),
                                                          },
                                                      )}
                                            </span>
                                            {mine && (
                                                <span className="text-brand font-medium">
                                                    {/*
                                                     * Qui l'a posée : elle, ou
                                                     * un proche nommé (R-1,
                                                     * v3.1). La frise et cette
                                                     * page disent la même
                                                     * chose, parce qu'elles
                                                     * lisent la même file.
                                                     */}
                                                    {entry.askedBy === null
                                                        ? t(
                                                              'initiator.questions.pending_badge',
                                                          )
                                                        : t(
                                                              'initiator.dashboard.asked_by',
                                                              {
                                                                  name: entry.askedBy,
                                                              },
                                                          )}
                                                </span>
                                            )}
                                        </p>

                                        {/*
                                         * Les commandes passent **sous** le
                                         * texte sur téléphone.
                                         *
                                         * À 393 px, quatre cibles de 44 px
                                         * mangeaient la moitié de la largeur
                                         * et la question tombait sur cinq
                                         * lignes : la carte mesurait 366 px.
                                         * Une question doit se lire d'un
                                         * coup d'œil ; les boutons peuvent
                                         * attendre la ligne d'en dessous.
                                         */}
                                        <div className="mt-1.5 flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-3">
                                            <div className="flex min-w-0 flex-1 items-start gap-3">
                                                {!filtering && (
                                                    <span
                                                        aria-hidden="true"
                                                        className="text-brand-muted/60 mt-1 flex-none cursor-grab select-none"
                                                    >
                                                        <Grip />
                                                    </span>
                                                )}

                                                <div className="min-w-0 flex-1">
                                                    {/*
                                                     * La question en Fraunces, et
                                                     * non en Inter.
                                                     *
                                                     * C'est ainsi que la
                                                     * narratrice la lit sur son
                                                     * téléphone ; la voir dans la
                                                     * même voix des deux côtés
                                                     * rappelle qu'on range des
                                                     * questions à poser, pas des
                                                     * lignes d'un tableau.
                                                     *
                                                     * `data-test` comme ailleurs
                                                     * dans le dépôt : la carte
                                                     * porte plusieurs
                                                     * paragraphes, et une spéc qui
                                                     * vise « le premier » lirait
                                                     * la date.
                                                     */}
                                                    <p
                                                        data-test="question-text"
                                                        className="font-display text-brand text-[1.15rem] leading-snug"
                                                    >
                                                        {entry.text}
                                                    </p>
                                                    {(entry.themeLabel !==
                                                        null ||
                                                        entry.photos > 0) && (
                                                        <p className="text-brand-muted mt-1 text-[0.8rem] font-semibold tracking-[0.08em] uppercase">
                                                            {entry.themeLabel ??
                                                                t(
                                                                    'initiator.questions.pending_photos',
                                                                    {
                                                                        count: entry.photos,
                                                                    },
                                                                )}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            {/*
                                             * Les quatre commandes sur **une
                                             * rangée**, en icônes.
                                             *
                                             * Empilées avec deux liens en
                                             * toutes lettres dessous, la carte
                                             * mesurait 218 px pour une ligne
                                             * de texte — dix cartes faisaient
                                             * les deux tiers d'une page de
                                             * trois mille pixels. Répétés
                                             * soixante-cinq fois, « Poser en
                                             * premier » et « Écarter » en
                                             * clair sont du bruit ; leurs
                                             * libellés restent pour le clavier
                                             * et les lecteurs d'écran, et la
                                             * cible garde ses 44 px.
                                             */}
                                            <div className="flex flex-none gap-1.5 max-sm:self-end">
                                                <IconButton
                                                    label={t(
                                                        'initiator.questions.move_up',
                                                    )}
                                                    disabled={index === 0}
                                                    onClick={() =>
                                                        reorder(
                                                            move(
                                                                next,
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
                                                        next.length - 1
                                                    }
                                                    onClick={() =>
                                                        reorder(
                                                            move(
                                                                next,
                                                                index,
                                                                1,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <ArrowDown />
                                                </IconButton>

                                                <IconButton
                                                    label={t(
                                                        'initiator.questions.first',
                                                    )}
                                                    disabled={index === 0}
                                                    onClick={() =>
                                                        promote(entry.id)
                                                    }
                                                >
                                                    <ToTop />
                                                </IconButton>

                                                <IconButton
                                                    label={t(
                                                        mine
                                                            ? 'initiator.questions.remove'
                                                            : 'initiator.questions.exclude',
                                                    )}
                                                    onClick={() =>
                                                        mine
                                                            ? removeOwn(
                                                                  entry.id,
                                                              )
                                                            : exclude(
                                                                  entry.id,
                                                                  true,
                                                              )
                                                    }
                                                >
                                                    <Trash />
                                                </IconButton>
                                            </div>
                                        </div>
                                    </li>
                                );
                            })}
                        </ol>
                    )}

                    {remaining > 0 && (
                        <button
                            type="button"
                            onClick={() => setShown(shown + STEP)}
                            className="btn-secondary press mt-5"
                        >
                            {t('initiator.questions.see_more', {
                                count: Math.min(remaining, STEP),
                            })}
                        </button>
                    )}
                </section>

                {/* ---- Ce qui s'écrit, et ce qui est rangé ------------ */}
                <aside
                    className="enter min-w-0 max-lg:mt-10"
                    style={stagger(2)}
                >
                    {/*
                     * La question personnelle, à part et visible.
                     *
                     * C'est l'acte le plus intime de la page — « raconte-nous
                     * ce dont je n'ai jamais osé te parler » —, et il était
                     * replié derrière un chevron, au même rang qu'un réglage.
                     */}
                    {/*
                     * Un vrai bouton, et non un titre de carte.
                     *
                     * C'était un `<summary>` orné d'un rond : ça ressemblait à
                     * un en-tête qu'on déplie, pas à l'action la plus
                     * personnelle de la page. Il porte maintenant la couleur
                     * d'action de l'espace, comme « Envoyer le lien » sur le
                     * tableau de bord — c'est le même registre : un geste
                     * qu'on pose, pas un réglage qu'on ouvre.
                     *
                     * `<details>` reste dessous : le clavier l'ouvre sans
                     * script, et le formulaire ne pèse rien tant qu'il est
                     * replié.
                     */}
                    <details className="group">
                        <summary className="btn-primary press w-full cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                            <span className="transition-transform duration-300 group-open:rotate-45">
                                <Plus />
                            </span>
                            {t('initiator.questions.add.title')}
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
                            className="card mt-3 flex flex-col gap-4 p-4"
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

                    {excluded.length > 0 && (
                        <details className="mt-6">
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
                </aside>
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
