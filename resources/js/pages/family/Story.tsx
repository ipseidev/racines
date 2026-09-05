import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

import AudioPlayer from '@/components/AudioPlayer';
import PhotoGallery, { type Photo } from '@/components/PhotoGallery';
import PhotoUploader from '@/components/PhotoUploader';
import { Avatar } from '@/components/space/Avatar';
import { Check, Chevron, Heart, Send } from '@/components/space/Icons';
import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';

type ReactionRow = {
    name: string;
    type: string;
    comment: string | null;
};

type Props = {
    id: string;
    narratorFirstName: string;
    title: string | null;
    question: string | null;
    sharedAt: string | null;
    durationSeconds: number | null;
    audioUrl: string | null;
    text: string | null;
    verbatim: string | null;
    aiLabel: string;
    reactions: ReactionRow[];
    yourReactions: string[];
    photos: Photo[];
    /** Vrai seulement si ce proche a le droit d'ajouter des photos. */
    canContribute: boolean;
    siblings: { previous: string | null; next: string | null };
};

const MAX_COMMENT = 280;

/**
 * Une histoire, écoutée par un proche.
 *
 * L'audio vient avant le texte : c'est la voix qui compte, le texte est là
 * pour ceux qui entendent mal ou qui lisent dans le métro. Le mot à mot reste
 * accessible d'un onglet — la parole de la personne n'est pas cachée derrière
 * un réglage.
 *
 * Deux réactions, et aucune façon de désapprouver : le produit ne propose pas
 * de pouce baissé sur le souvenir de quelqu'un.
 *
 * La page a été reprise après le checkpoint du bloc 08 (T-158), sur le même
 * socle que l'espace Initiateur·rice. Le mécanisme n'a pas bougé — deux
 * réactions, un mot facultatif, la mesure d'écoute inchangée — mais l'écoute
 * est désormais posée sur une carte à elle, les deux textes se prennent par un
 * sélecteur à curseur glissant, et un bouton déjà pressé le montre au lieu de
 * redevenir neutre. Les sections entrent l'une après l'autre.
 */
export default function Story({
    narratorFirstName,
    title,
    question,
    audioUrl,
    text,
    verbatim,
    aiLabel,
    reactions,
    yourReactions,
    photos,
    canContribute,
    siblings,
}: Props) {
    const t = useT();

    const [tab, setTab] = useState<'text' | 'verbatim'>('text');
    const [comment, setComment] = useState('');
    const [sending, setSending] = useState(false);

    const base = window.location.pathname;
    const listPath = base.replace(/\/stories\/[^/]+$/, '');

    const react = (type: 'heart' | 'thanks') => {
        setSending(true);
        router.post(
            `${base}/reactions`,
            { type, comment: comment.trim() === '' ? null : comment.trim() },
            {
                preserveScroll: true,
                onSuccess: () => setComment(''),
                onFinish: () => setSending(false),
            },
        );
    };

    const reportProgress = (seconds: number) => {
        void fetch(`${base}/listen`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? '',
            },
            body: JSON.stringify({ seconds }),
            keepalive: true,
        });
    };

    const heading =
        title ?? t('family.story.untitled', { first_name: narratorFirstName });

    return (
        <>
            <Head title={heading} />

            <Link
                href={listPath}
                className="text-brand-muted hover:text-brand enter inline-flex min-h-[2.75rem] items-center gap-1 text-[0.9375rem] no-underline transition-colors"
            >
                <Chevron aria-hidden="true" className="size-4 rotate-90" />
                {t('family.story.back')}
            </Link>

            <header className="enter" style={stagger(1)}>
                <p className="eyebrow mt-2">
                    {t('family.story.eyebrow', {
                        first_name: narratorFirstName,
                    })}
                </p>

                <h1 className="font-display mt-3 text-[2rem] leading-[1.1] font-semibold sm:text-[2.375rem]">
                    {heading}
                </h1>
            </header>

            {question !== null ? (
                <p
                    className="panel enter mt-6 text-[1.125rem]"
                    style={stagger(2)}
                >
                    {question}
                </p>
            ) : null}

            {/*
             * Le lecteur porte déjà sa carte (il est partagé avec la page
             * narratrice) : l'envelopper dans une seconde en dessinait deux,
             * l'une dans l'autre. Ce conteneur ne fait que l'entrée en fondu.
             */}
            <div className="enter mt-7" style={stagger(3)}>
                {audioUrl === null ? (
                    <p className="card text-brand-muted px-5 py-5 text-base">
                        {t('family.story.no_audio')}
                    </p>
                ) : (
                    <AudioPlayer src={audioUrl} onProgress={reportProgress} />
                )}
            </div>

            <section
                aria-labelledby="story-text"
                className="enter mt-10"
                style={stagger(4)}
            >
                <h2 id="story-text" className="sr-only">
                    {t('family.story.tab_text')}
                </h2>

                {/*
                 * Un sélecteur à deux positions, et le curseur glisse de l'une
                 * à l'autre : le mouvement dit que c'est le même texte vu
                 * autrement, là où deux boutons qui s'allument diraient deux
                 * contenus différents.
                 */}
                <div
                    role="tablist"
                    className="border-brand-sand bg-brand-surface relative inline-flex rounded-full border p-1"
                >
                    <span
                        aria-hidden="true"
                        className="bg-brand-linen absolute inset-y-1 w-[calc(50%-0.25rem)] rounded-full transition-transform duration-300 ease-out"
                        style={{
                            transform:
                                tab === 'text'
                                    ? 'translateX(0)'
                                    : 'translateX(100%)',
                        }}
                    />
                    {(['text', 'verbatim'] as const).map((name) => (
                        <button
                            key={name}
                            type="button"
                            role="tab"
                            aria-selected={tab === name}
                            onClick={() => setTab(name)}
                            /*
                             * `whitespace-nowrap` : `flex-1` part d'une base
                             * de zéro, donc les deux onglets se serrent au
                             * point de couper « Mot à mot » en deux. Le
                             * conteneur étant `inline-flex`, il s'élargit
                             * jusqu'au plus long des deux libellés au lieu de
                             * le rompre — et les deux gardent la même largeur,
                             * ce dont le curseur glissant a besoin.
                             */
                            className={`relative z-10 min-h-[2.75rem] flex-1 rounded-full px-4 text-[0.9375rem] font-semibold whitespace-nowrap transition-colors ${
                                tab === name ? 'text-brand' : 'text-brand-muted'
                            }`}
                        >
                            {t(`family.story.tab_${name}`)}
                        </button>
                    ))}
                </div>

                {tab === 'text' ? (
                    <p className="text-brand-muted mt-4 text-[0.9375rem]">
                        {aiLabel}
                    </p>
                ) : null}

                <div
                    key={tab}
                    className="enter mt-4 text-[1.125rem] leading-relaxed whitespace-pre-line"
                >
                    {tab === 'text' ? text : verbatim}
                </div>
            </section>

            <section
                aria-labelledby="story-react"
                className="card enter mt-12 px-5 py-6"
                style={stagger(5)}
            >
                <p className="eyebrow">{t('family.reaction.eyebrow')}</p>

                <h2
                    id="story-react"
                    className="font-display mt-3 text-[1.375rem] leading-snug font-semibold"
                >
                    {t('family.reaction.title', {
                        first_name: narratorFirstName,
                    })}
                </h2>

                <p className="text-brand-muted mt-2 text-[0.9375rem]">
                    {t('family.reaction.comment_help', {
                        first_name: narratorFirstName,
                    })}
                </p>

                <label htmlFor="comment" className="sr-only">
                    {t('family.reaction.comment_label')}
                </label>
                <textarea
                    id="comment"
                    value={comment}
                    maxLength={MAX_COMMENT}
                    rows={3}
                    onChange={(event) => setComment(event.target.value)}
                    className="input mt-4 w-full resize-y text-[1.0625rem]"
                />
                <p className="text-brand-muted mt-1 text-[0.875rem]">
                    {t('family.reaction.comment_counter', {
                        count: String(comment.length),
                        max: String(MAX_COMMENT),
                    })}
                </p>

                <div className="mt-4 flex flex-wrap gap-3">
                    {(['heart', 'thanks'] as const).map((type) => {
                        const done = yourReactions.includes(type);

                        return (
                            <button
                                key={type}
                                type="button"
                                disabled={sending}
                                aria-pressed={done}
                                onClick={() => react(type)}
                                /*
                                 * `basis` plutôt qu'un simple `flex-1` : à
                                 * deux par ligne, « J'ai aimé » se coupait en
                                 * deux sur un téléphone étroit. En dessous de
                                 * la largeur de base, la ligne se rompt et
                                 * chaque bouton prend toute la largeur —
                                 * plutôt qu'un mot coupé en deux.
                                 */
                                className={`press inline-flex min-h-[3.25rem] flex-1 basis-[9.5rem] items-center justify-center gap-2 rounded-md px-4 py-3 text-[1.0625rem] font-semibold whitespace-nowrap transition-colors disabled:opacity-60 ${
                                    done
                                        ? 'border-brand-sage text-brand bg-brand-sage/12 border-2'
                                        : 'bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep'
                                }`}
                            >
                                {done ? (
                                    <Check
                                        aria-hidden="true"
                                        className="text-brand-sage size-5 flex-none"
                                    />
                                ) : type === 'heart' ? (
                                    <Heart
                                        aria-hidden="true"
                                        className="size-5 flex-none"
                                    />
                                ) : (
                                    <Send
                                        aria-hidden="true"
                                        className="size-5 flex-none"
                                    />
                                )}
                                {t(`family.reaction.${type}`)}
                            </button>
                        );
                    })}
                </div>
            </section>

            {reactions.length > 0 ? (
                <section
                    aria-labelledby="story-reacted"
                    className="enter mt-10"
                    style={stagger(6)}
                >
                    <h2
                        id="story-reacted"
                        className="text-[1.0625rem] font-semibold"
                    >
                        {t('family.story.reacted')}
                    </h2>

                    <ul className="mt-4 flex flex-col gap-3">
                        {reactions.map((one, index) => (
                            <li
                                key={`${one.name}-${one.type}-${index}`}
                                className="flex items-start gap-3"
                            >
                                <Avatar name={one.name} />

                                <div className="min-w-0 flex-1">
                                    <p className="text-[1rem]">
                                        <span className="font-semibold">
                                            {one.name}
                                        </span>
                                        <span className="text-brand-muted">
                                            {' · '}
                                            {t(`family.reaction.${one.type}`)}
                                        </span>
                                    </p>

                                    {one.comment === null ? null : (
                                        <p className="panel mt-2 text-[1rem]">
                                            {`« ${one.comment} »`}
                                        </p>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>
            ) : null}

            {/*
             * Les photos après le texte, et pas avant : c'est la voix qui
             * compte, et une grille d'images en tête de page ferait passer
             * l'histoire pour une galerie.
             *
             * Le retrait n'est offert qu'à qui peut contribuer — et le
             * serveur revérifie que la photo est bien la sienne : un bouton
             * n'est pas une autorisation.
             */}
            <PhotoGallery
                photos={photos}
                onRemove={
                    canContribute
                        ? (id) =>
                              router.delete(`${base}/photos/${id}`, {
                                  preserveScroll: true,
                              })
                        : undefined
                }
            />

            {canContribute && <PhotoUploader action={`${base}/photos`} />}

            <nav className="mt-12 flex flex-wrap gap-3">
                {siblings.previous === null ? null : (
                    <Link
                        href={`${listPath}/stories/${siblings.previous}`}
                        className="btn-secondary press flex-1"
                    >
                        <Chevron
                            aria-hidden="true"
                            className="size-4 rotate-90"
                        />
                        {t('family.story.previous')}
                    </Link>
                )}
                {siblings.next === null ? null : (
                    <Link
                        href={`${listPath}/stories/${siblings.next}`}
                        className="btn-secondary press flex-1"
                    >
                        {t('family.story.next')}
                        <Chevron
                            aria-hidden="true"
                            className="size-4 -rotate-90"
                        />
                    </Link>
                )}
            </nav>
        </>
    );
}
