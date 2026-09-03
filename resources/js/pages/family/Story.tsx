import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import AudioPlayer from '@/components/AudioPlayer';
import PhotoGallery, { type Photo } from '@/components/PhotoGallery';
import PhotoUploader from '@/components/PhotoUploader';
import { useT } from '@/hooks/useT';

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
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

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
                className="text-brand-muted min-h-[2.75rem] text-base underline"
            >
                {t('family.story.back')}
            </Link>

            <h1 className="font-display mt-4 text-2xl leading-tight font-semibold sm:text-3xl">
                {heading}
            </h1>

            {question !== null ? (
                <p className="bg-brand-accent text-brand-accent-foreground mt-6 rounded-md px-4 py-4 text-[1.25rem]">
                    {question}
                </p>
            ) : null}

            <div className="mt-8">
                {audioUrl === null ? (
                    <p className="text-brand-muted text-base">
                        {t('family.story.no_audio')}
                    </p>
                ) : (
                    <AudioPlayer src={audioUrl} onProgress={reportProgress} />
                )}
            </div>

            <section aria-labelledby="story-text" className="mt-10">
                <h2 id="story-text" className="sr-only">
                    {t('family.story.tab_text')}
                </h2>

                <div role="tablist" className="flex gap-2">
                    {(['text', 'verbatim'] as const).map((name) => (
                        <button
                            key={name}
                            type="button"
                            role="tab"
                            aria-selected={tab === name}
                            onClick={() => setTab(name)}
                            className={`min-h-[2.75rem] rounded-md px-4 py-2 text-base font-medium ${
                                tab === name
                                    ? 'bg-brand text-brand-foreground'
                                    : 'border-brand-muted/40 border'
                            }`}
                        >
                            {t(`family.story.tab_${name}`)}
                        </button>
                    ))}
                </div>

                {tab === 'text' ? (
                    <p className="text-brand-muted mt-3 text-base">{aiLabel}</p>
                ) : null}

                <div className="mt-4 text-[1.125rem] leading-relaxed whitespace-pre-line">
                    {tab === 'text' ? text : verbatim}
                </div>
            </section>

            <section aria-labelledby="story-react" className="mt-12">
                <h2 id="story-react" className="text-lg font-medium">
                    {t('family.reaction.title', {
                        first_name: narratorFirstName,
                    })}
                </h2>

                {status !== null ? (
                    <p
                        role="status"
                        className="bg-brand-accent text-brand-accent-foreground mt-4 rounded-md px-4 py-3"
                    >
                        {status}
                    </p>
                ) : null}

                <label htmlFor="comment" className="sr-only">
                    {t('family.reaction.comment_label')}
                </label>
                <p className="text-brand-muted mt-3 text-base">
                    {t('family.reaction.comment_help', {
                        first_name: narratorFirstName,
                    })}
                </p>
                <textarea
                    id="comment"
                    value={comment}
                    maxLength={MAX_COMMENT}
                    rows={3}
                    onChange={(event) => setComment(event.target.value)}
                    className="border-brand-muted/40 mt-3 w-full rounded-md border px-4 py-3 text-[1.125rem]"
                />
                <p className="text-brand-muted mt-1 text-base">
                    {t('family.reaction.comment_counter', {
                        count: String(comment.length),
                        max: String(MAX_COMMENT),
                    })}
                </p>

                <div className="mt-4 flex flex-wrap gap-3">
                    {(['heart', 'thanks'] as const).map((type) => (
                        <button
                            key={type}
                            type="button"
                            disabled={sending}
                            aria-pressed={yourReactions.includes(type)}
                            onClick={() => react(type)}
                            className="bg-brand text-brand-foreground min-h-[2.75rem] rounded-md px-6 py-3 text-lg font-medium disabled:opacity-60"
                        >
                            {t(`family.reaction.${type}`)}
                        </button>
                    ))}
                </div>
            </section>

            {reactions.length > 0 ? (
                <section aria-labelledby="story-reacted" className="mt-10">
                    <h2 id="story-reacted" className="text-lg font-medium">
                        {t('family.story.reacted')}
                    </h2>
                    <ul className="mt-3 flex flex-col gap-2">
                        {reactions.map((one, index) => (
                            <li key={`${one.name}-${one.type}-${index}`}>
                                <span className="font-medium">{one.name}</span>
                                {' — '}
                                {t(`family.reaction.${one.type}`)}
                                {one.comment === null
                                    ? null
                                    : ` : « ${one.comment} »`}
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

            <nav className="mt-12 flex flex-wrap gap-4">
                {siblings.previous === null ? null : (
                    <Link
                        href={`${listPath}/stories/${siblings.previous}`}
                        className="border-brand-muted/40 min-h-[2.75rem] rounded-md border px-4 py-3 text-base"
                    >
                        {t('family.story.previous')}
                    </Link>
                )}
                {siblings.next === null ? null : (
                    <Link
                        href={`${listPath}/stories/${siblings.next}`}
                        className="border-brand-muted/40 min-h-[2.75rem] rounded-md border px-4 py-3 text-base"
                    >
                        {t('family.story.next')}
                    </Link>
                )}
            </nav>
        </>
    );
}
