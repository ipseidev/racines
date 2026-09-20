import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import SampleAudio from '@/components/landing/SampleAudio';
import { track } from '@/components/landing/track';
import { CheckField } from '@/components/form/CheckField';
import { SubmitButton } from '@/components/form/SubmitButton';
import { TextField } from '@/components/form/TextField';
import {
    BookCover,
    CoverSwatch,
    type Cover,
} from '@/components/quiz/BookCover';
import { QuizCheck } from '@/components/quiz/QuizCheck';
import { QuizChoice } from '@/components/quiz/QuizChoice';
import { SmsBubble } from '@/components/quiz/SmsBubble';
import { useBrand } from '@/brand/BrandProvider';
import { useFormat } from '@/hooks/useFormat';
import { useUrls } from '@/hooks/useLocale';
import { useT } from '@/hooks/useT';
import {
    coverTitle,
    CUSTOM_TITLE,
    defaultSendAt,
    EMPTY_ANSWERS,
    forgetAnswers,
    isAnswered,
    isFar,
    maxSendAt,
    needsHelp,
    readAnswers,
    SCREENS,
    SELF,
    toggleTheme,
    writeAnswers,
    type Answers,
    type Screen,
} from '@/lib/quiz';

type Option = { value: string; label: string };

/** Une formule de titre : son libellé court, et le patron qui s'imprime. */
type Title = { value: string; label: string; pattern: string | null };

type Preview = {
    firstName: string;
    nickname: string | null;
    sender: string;
    channel: string;
    sendAt: string;
    sendTime: string;
    cover: string;
    title: string;
    titleCustom: string;
    first: string | null;
    next: string[];
    themes: string[];
};

type Props = {
    preview: Preview | null;
    checkoutUrl: string;
    emailSaved: boolean;
    welcomeOffer: boolean;
    minThemes: number;
    relationships: Option[];
    subjects: Record<string, string>;
    ageBands: Option[];
    distances: Option[];
    themes: Option[];
    storytellers: Option[];
    techComforts: Option[];
    channels: Option[];
    occasions: Option[];
    covers: Cover[];
    titles: Title[];
    bookSubtitle: string;
    sample: { src: string; disclosed: boolean } | null;
};

/** Le temps pendant lequel un choix reste visible avant que l'écran change. */
const ACKNOWLEDGE_MS = 260;

/**
 * Le tunnel de découverte : quatorze écrans, puis le plan.
 *
 * Dix questions et quatre écrans-miroir. Ce que les miroirs font, et
 * pourquoi ils comptent autant que les questions : ils ne demandent rien, ils
 * reprennent la réponse qui vient d'être donnée, et c'est là que passe ce
 * qu'on a à dire. Le leader y met des témoignages ; nous n'en avons pas
 * encore, alors nous y mettons ce qui est vrai — un extrait sonore réel, la
 * façon dont les questions sont écrites, ce qu'il n'y a pas à installer.
 *
 * Les quatorze écrans vivent ici, côté client, et n'appellent le serveur qu'une
 * fois. Un aller-retour par réponse ferait quatorze attentes là où tout l'effet
 * tient à ce qu'une réponse en appelle une autre sans rien qui clignote. Le
 * prix à payer est la reprise : elle passe par `localStorage`, donc par le
 * même navigateur, ce qui est le cas réaliste pour un parcours de deux
 * minutes.
 *
 * Une fois répondu, la même adresse rend l'aperçu : ce n'est pas une autre
 * page, c'est l'état du quiz, lu dans le brouillon de commande côté serveur.
 */
export default function Quiz(props: Props) {
    return props.preview === null ? (
        <Questions {...props} />
    ) : (
        <Plan {...props} preview={props.preview} />
    );
}

function Questions({
    minThemes,
    relationships,
    subjects,
    ageBands,
    distances,
    themes,
    storytellers,
    techComforts,
    channels,
    occasions,
    covers,
    titles,
    bookSubtitle,
    sample,
}: Props) {
    const t = useT();
    const urls = useUrls();
    const fmt = useFormat();

    const [index, setIndex] = useState(0);
    const blank: Answers = {
        ...EMPTY_ANSWERS,
        book_cover: covers[0]?.value ?? '',
        book_title: titles[0]?.value ?? '',
    };
    const [answers, setAnswers] = useState<Answers>(blank);
    const heading = useRef<HTMLHeadingElement>(null);
    const started = useRef(false);

    // `router` et non `useForm` : les réponses sont déjà un état local, et
    // un second exemplaire dans un formulaire finirait par diverger du
    // premier. Il ne manquait que le témoin d'envoi.
    const [sending, setSending] = useState(false);

    const screen = SCREENS[index] as Screen;
    const total = SCREENS.length;
    const brandName = useBrand().name;
    // La teinte posée, ou la première de la palette : le repli vit ici et non
    // dans le socle des réponses, pour qu'un brouillon écrit par une version
    // antérieure du quiz montre quand même une couverture.
    const chosenCover =
        covers.find((cover) => cover.value === answers.book_cover) ??
        (covers[0] as Cover);
    const chosenTitle =
        titles.find((title) => title.value === answers.book_title) ??
        (titles[0] as Title);
    const subject = subjects[answers.relationship] ?? '';
    const answered = isAnswered(screen, answers, minThemes);

    /*
     * La reprise, au premier rendu seulement. Quelqu'un dont l'onglet a été
     * purgé par Safari — ce qui arrive — revient là où il en était plutôt
     * que de recommencer, et c'est la différence entre un parcours fini et
     * un parcours abandonné.
     */
    useEffect(() => {
        const saved = readAnswers(undefined, blank);

        if (saved !== null) {
            setAnswers(saved);
        }

        if (!started.current) {
            started.current = true;
            track('quiz_started', { resumed: saved !== null });
        }
        // Au premier rendu seulement : relire à chaque changement de
        // palette écraserait ce qu'on vient de répondre.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        track('quiz_screen', { index: index + 1, screen: screen.key });

        // Le titre prend le focus à chaque écran : sans cela, qui n'y voit
        // pas reste au bas de la page précédente et n'apprend jamais que
        // l'écran a changé (même défaut qu'au tunnel, T-215).
        heading.current?.focus();
    }, [index, screen.key]);

    function remember(next: Answers): void {
        setAnswers(next);
        writeAnswers(next);
    }

    /** Répondre, puis avancer — avec le délai qui laisse voir le choix. */
    function choose(field: keyof Answers, value: string): void {
        remember({ ...answers, [field]: value });
        track('quiz_answer', { screen: screen.key, value });

        window.setTimeout(
            () => setIndex((current) => current + 1),
            ACKNOWLEDGE_MS,
        );
    }

    function forward(): void {
        if (index + 1 < total) {
            setIndex(index + 1);

            return;
        }

        track('quiz_completed', { themes: answers.themes.length });
        setSending(true);

        router.post(
            '/commencer',
            { ...answers },
            {
                // Les réponses restent sur l'appareil jusqu'à ce que le serveur
                // les ait prises : une validation refusée ne doit pas laisser
                // quelqu'un devant un questionnaire vide.
                onSuccess: () => forgetAnswers(),
                onFinish: () => setSending(false),
            },
        );
    }

    function backward(): void {
        setIndex((current) => Math.max(0, current - 1));
    }

    return (
        <>
            <Head title={t('public.seo.quiz.title')} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-7 px-5 py-7 sm:py-10">
                <Progress index={index} total={total} onBack={backward} />

                <div key={screen.key} className="enter flex flex-col gap-6">
                    {screen.key === 'relationship' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.relationship.title')}
                                lede={t('public.quiz.relationship.lede')}
                            />
                            <Choices
                                options={relationships.filter(
                                    (option) => option.value !== SELF,
                                )}
                                chosen={answers.relationship}
                                onChoose={(value) =>
                                    choose('relationship', value)
                                }
                                t={t}
                            />
                            {/*
                             * Raconter sa propre histoire quitte le quiz : les
                             * douze écrans suivants sont écrits pour quelqu'un
                             * qui offre, et le tunnel d'achat sait déjà tenir
                             * ce cas. Un lien, pas un choix qui mènerait à des
                             * textes retournés à la première personne.
                             */}
                            <div className="border-brand-sand flex flex-col gap-2 border-t pt-5">
                                <p className="text-brand-muted text-base">
                                    {t('public.quiz.relationship.self_note')}
                                </p>
                                <Link
                                    href={urls.checkout_show}
                                    onClick={() => track('quiz_self_chosen')}
                                    className="text-brand w-fit font-medium underline underline-offset-4"
                                >
                                    {
                                        relationships.find(
                                            (option) => option.value === SELF,
                                        )?.label
                                    }
                                </Link>
                            </div>
                        </>
                    )}

                    {screen.key === 'voice' && (
                        <Mirror
                            ref={heading}
                            title={t('public.quiz.voice.title')}
                            body={t('public.quiz.voice.body', { subject })}
                            onNext={forward}
                            t={t}
                        >
                            <div className="flex flex-col gap-3">
                                <SampleAudio sample={sample} variant="quiz" />
                                <p className="text-brand-muted text-base">
                                    {t('public.quiz.voice.listen')}
                                </p>
                            </div>
                        </Mirror>
                    )}

                    {screen.key === 'age' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.age.title', { subject })}
                                lede={t('public.quiz.age.hint')}
                            />
                            <Choices
                                options={ageBands}
                                chosen={answers.age_band}
                                onChoose={(value) => choose('age_band', value)}
                                t={t}
                            />
                        </>
                    )}

                    {screen.key === 'distance' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.distance.title')}
                            />
                            <Choices
                                options={distances}
                                chosen={answers.distance}
                                onChoose={(value) => choose('distance', value)}
                                t={t}
                            />
                        </>
                    )}

                    {screen.key === 'closeness' && (
                        <Mirror
                            ref={heading}
                            title={t(
                                isFar(answers.distance)
                                    ? 'public.quiz.closeness.far_title'
                                    : 'public.quiz.closeness.near_title',
                            )}
                            body={t(
                                isFar(answers.distance)
                                    ? 'public.quiz.closeness.far_body'
                                    : 'public.quiz.closeness.near_body',
                            )}
                            onNext={forward}
                            t={t}
                        />
                    )}

                    {screen.key === 'themes' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.themes.title', {
                                    subject,
                                })}
                                lede={t('public.quiz.themes.hint')}
                            />

                            <div className="flex flex-col gap-2.5">
                                {themes.map((theme) => {
                                    const rank = answers.themes.indexOf(
                                        theme.value,
                                    );

                                    return (
                                        <QuizCheck
                                            key={theme.value}
                                            label={theme.label}
                                            checked={rank !== -1}
                                            rank={rank === -1 ? null : rank + 1}
                                            onToggle={() =>
                                                remember({
                                                    ...answers,
                                                    themes: toggleTheme(
                                                        answers.themes,
                                                        theme.value,
                                                    ),
                                                })
                                            }
                                        />
                                    );
                                })}
                            </div>

                            <Next
                                answered={answered}
                                onNext={forward}
                                t={t}
                                note={
                                    answered
                                        ? t('public.quiz.themes.chosen', {
                                              count: answers.themes.length,
                                          })
                                        : t('public.quiz.themes.remaining', {
                                              count:
                                                  minThemes -
                                                  answers.themes.length,
                                          })
                                }
                            />
                        </>
                    )}

                    {screen.key === 'storyteller' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.storyteller.title', {
                                    subject,
                                })}
                            />
                            <Choices
                                options={storytellers}
                                chosen={answers.storyteller}
                                onChoose={(value) =>
                                    choose('storyteller', value)
                                }
                                t={t}
                            />
                        </>
                    )}

                    {screen.key === 'nothing' && (
                        <Mirror
                            ref={heading}
                            title={t('public.quiz.nothing.title')}
                            body={t('public.quiz.nothing.body')}
                            second={t('public.quiz.nothing.body_2')}
                            onNext={forward}
                            t={t}
                        />
                    )}

                    {screen.key === 'tech' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.tech.title', { subject })}
                                lede={t('public.quiz.tech.hint')}
                            />
                            <Choices
                                options={techComforts}
                                chosen={answers.tech_comfort}
                                onChoose={(value) =>
                                    choose('tech_comfort', value)
                                }
                                t={t}
                            />
                        </>
                    )}

                    {screen.key === 'install' && (
                        <Mirror
                            ref={heading}
                            title={t(
                                needsHelp(answers.tech_comfort)
                                    ? 'public.quiz.install.help_title'
                                    : 'public.quiz.install.title',
                            )}
                            body={t(
                                needsHelp(answers.tech_comfort)
                                    ? 'public.quiz.install.help_body'
                                    : 'public.quiz.install.body',
                                { subject },
                            )}
                            onNext={forward}
                            t={t}
                        />
                    )}

                    {screen.key === 'channel' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.channel.title', {
                                    subject,
                                })}
                                lede={t('public.quiz.channel.hint')}
                            />
                            <Choices
                                options={channels}
                                chosen={answers.channel}
                                onChoose={(value) => choose('channel', value)}
                                t={t}
                            />
                        </>
                    )}

                    {screen.key === 'name' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.name.title', { subject })}
                            />

                            <div className="flex flex-col gap-5">
                                <TextField
                                    label={t('public.quiz.name.first_name')}
                                    value={answers.first_name}
                                    autoComplete="off"
                                    placeholder={t(
                                        'public.quiz.name.first_name_placeholder',
                                    )}
                                    onChange={(event) =>
                                        remember({
                                            ...answers,
                                            first_name: event.target.value,
                                        })
                                    }
                                />

                                <TextField
                                    label={t('public.quiz.name.nickname')}
                                    hint={t('public.quiz.name.nickname_hint')}
                                    value={answers.nickname}
                                    autoComplete="off"
                                    placeholder={t(
                                        'public.quiz.name.nickname_placeholder',
                                    )}
                                    onChange={(event) =>
                                        remember({
                                            ...answers,
                                            nickname: event.target.value,
                                        })
                                    }
                                />
                            </div>

                            <Next answered={answered} onNext={forward} t={t} />
                        </>
                    )}

                    {screen.key === 'cover' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.cover.title', {
                                    name: answers.first_name.trim(),
                                })}
                                lede={t('public.quiz.cover.hint')}
                            />

                            {/*
                             * La formule au-dessus de la couverture, en
                             * pastilles courtes : c'est le titre composé, sur
                             * le livre, qui montre ce qu'on vient de choisir.
                             * Répéter la formule entière dans chaque pastille
                             * ferait cinq lignes à lire pour un choix qui se
                             * voit.
                             */}
                            <div
                                role="group"
                                aria-label={t('public.quiz.cover.titles')}
                                className="flex flex-wrap justify-center gap-2"
                            >
                                {titles.map((title) => (
                                    <button
                                        key={title.value}
                                        type="button"
                                        onClick={() =>
                                            remember({
                                                ...answers,
                                                book_title: title.value,
                                            })
                                        }
                                        aria-pressed={
                                            title.value === chosenTitle.value
                                        }
                                        className={`press min-h-[2.75rem] rounded-full border px-4 text-base transition-colors duration-200 ${
                                            title.value === chosenTitle.value
                                                ? 'border-brand bg-brand text-brand-foreground font-medium'
                                                : 'border-brand-sand hover:border-brand/50'
                                        }`}
                                    >
                                        {title.label}
                                    </button>
                                ))}
                            </div>

                            {chosenTitle.value === CUSTOM_TITLE && (
                                <div className="enter">
                                    <TextField
                                        label={t('public.quiz.cover.custom')}
                                        hint={t(
                                            'public.quiz.cover.custom_hint',
                                        )}
                                        value={answers.book_title_custom}
                                        maxLength={80}
                                        autoComplete="off"
                                        placeholder={t(
                                            'public.quiz.cover.custom_placeholder',
                                        )}
                                        onChange={(event) =>
                                            remember({
                                                ...answers,
                                                book_title_custom:
                                                    event.target.value,
                                            })
                                        }
                                    />
                                </div>
                            )}

                            <BookCover
                                cover={chosenCover}
                                title={coverTitle(
                                    chosenTitle.value,
                                    chosenTitle.pattern,
                                    answers.first_name,
                                    answers.book_title_custom,
                                    fmt.of,
                                )}
                                subtitle={bookSubtitle}
                                brand={brandName}
                            />

                            <div
                                role="group"
                                aria-label={t('public.quiz.cover.swatches')}
                                className="flex flex-wrap justify-center gap-3"
                            >
                                {covers.map((cover) => (
                                    <CoverSwatch
                                        key={cover.value}
                                        cover={cover}
                                        chosen={
                                            cover.value === chosenCover.value
                                        }
                                        onChoose={() =>
                                            remember({
                                                ...answers,
                                                book_cover: cover.value,
                                            })
                                        }
                                    />
                                ))}
                            </div>

                            <p className="text-brand-muted text-center text-base">
                                {chosenCover.label}
                            </p>

                            <Next
                                answered={answered}
                                onNext={forward}
                                t={t}
                                note={t('public.quiz.cover.note')}
                            />
                        </>
                    )}

                    {screen.key === 'occasion' && (
                        <>
                            <Title
                                ref={heading}
                                title={t('public.quiz.occasion.title')}
                            />

                            <div className="flex flex-col gap-2.5">
                                {occasions.map((occasion) => (
                                    <QuizChoice
                                        key={occasion.value}
                                        label={occasion.label}
                                        chosen={
                                            answers.occasion === occasion.value
                                        }
                                        ariaLabel={t('public.quiz.choose', {
                                            label: occasion.label,
                                        })}
                                        onChoose={() =>
                                            remember({
                                                ...answers,
                                                occasion: occasion.value,
                                                // La date n'est proposée qu'une
                                                // fois l'occasion connue, et
                                                // demain par défaut.
                                                send_at:
                                                    answers.send_at === ''
                                                        ? defaultSendAt()
                                                        : answers.send_at,
                                            })
                                        }
                                    />
                                ))}
                            </div>

                            {answers.occasion !== '' && (
                                <div className="enter flex flex-col gap-5">
                                    <TextField
                                        type="date"
                                        label={t('public.quiz.occasion.date')}
                                        hint={t(
                                            'public.quiz.occasion.date_hint',
                                        )}
                                        value={answers.send_at}
                                        min={defaultSendAt()}
                                        max={maxSendAt()}
                                        onChange={(event) =>
                                            remember({
                                                ...answers,
                                                send_at: event.target.value,
                                            })
                                        }
                                    />

                                    <button
                                        type="button"
                                        onClick={forward}
                                        disabled={!answered || sending}
                                        aria-busy={sending || undefined}
                                        className="btn-primary press w-full disabled:opacity-50"
                                    >
                                        {sending ? (
                                            <>
                                                <span
                                                    className="spinner"
                                                    aria-hidden="true"
                                                />
                                                {t('public.quiz.sending')}
                                            </>
                                        ) : (
                                            t('public.quiz.next')
                                        )}
                                    </button>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

/**
 * Le plan : le message réel, puis les vraies premières questions.
 *
 * Aucune barre d'analyse, aucun archétype. Ce qu'on affiche a été calculé —
 * la première question sort du corpus selon les thèmes cochés — et ce calcul
 * a déjà eu lieu côté serveur. Simuler une attente de trois secondes pour le
 * dramatiser serait la seule chose fausse de tout le parcours.
 */
function Plan({
    preview,
    checkoutUrl,
    emailSaved,
    welcomeOffer,
    channels,
    themes,
    covers,
    titles,
    bookSubtitle,
}: Props & { preview: Preview }) {
    const t = useT();
    const brand = useBrand();
    const fmt = useFormat();

    const name = preview.nickname ?? preview.firstName;
    const channel =
        channels.find((option) => option.value === preview.channel)?.label ??
        '';
    const chosen = preview.themes
        .map((value) => themes.find((theme) => theme.value === value)?.label)
        .filter((label): label is string => label !== undefined);
    const cover =
        covers.find((one) => one.value === preview.cover) ??
        (covers[0] as Cover);
    // La formule, pas le titre : le prénom peut avoir été corrigé depuis, et
    // le serveur recomposera au moment du BAT comme on recompose ici.
    const title = titles.find((one) => one.value === preview.title) ?? null;

    const email = useForm({ email: '', news: false, website: '' });

    useEffect(() => {
        track('quiz_plan_view');
    }, []);

    return (
        <>
            <Head title={t('public.seo.quiz.title')} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-10 px-5 py-8 sm:py-12">
                <header className="flex flex-col gap-3">
                    <span className="eyebrow">
                        {t('public.quiz.preview.eyebrow')}
                    </span>
                    <h1 className="font-display text-[2rem] leading-[1.12] font-medium sm:text-[2.4rem]">
                        {t('public.quiz.preview.title', { name })}
                    </h1>
                    <p className="text-brand-muted">
                        {t('public.quiz.preview.when', {
                            date: fmt.longDate(preview.sendAt),
                            time: fmt.time(preview.sendTime),
                            channel,
                        })}
                    </p>
                </header>

                {/*
                 * Le livre d'abord, le message ensuite. L'ordre n'est pas
                 * neutre : le plan raconte un chemin, et on montre d'abord où
                 * il mène. Le reste de la page explique comment on y va.
                 */}
                <section className="flex flex-col gap-4">
                    <h2 className="font-display text-[1.3rem] font-medium">
                        {t('public.quiz.preview.book_label')}
                    </h2>

                    <BookCover
                        cover={cover}
                        title={coverTitle(
                            preview.title,
                            title?.pattern ?? null,
                            preview.firstName,
                            preview.titleCustom,
                            fmt.of,
                        )}
                        subtitle={bookSubtitle}
                        brand={brand.name}
                    />

                    <p className="text-brand-muted text-center text-base">
                        {t('public.quiz.preview.book_note', {
                            cover: cover.label.toLocaleLowerCase(),
                        })}
                    </p>
                </section>

                <section className="flex flex-col gap-3">
                    <h2 className="font-display text-[1.3rem] font-medium">
                        {t('public.quiz.preview.invitation_label')}
                    </h2>

                    <SmsBubble
                        testId="quiz-invitation"
                        from={t('public.quiz.preview.from', {
                            sender: preview.sender,
                        })}
                    >
                        {t('public.quiz.preview.invitation', {
                            name,
                            inviter: t(
                                'public.quiz.preview.inviter_placeholder',
                            ),
                            brand: brand.name,
                            link: t('public.quiz.preview.link_placeholder'),
                        })}
                    </SmsBubble>

                    <p className="text-brand-muted text-base">
                        {t('public.quiz.preview.invitation_note')}
                    </p>
                </section>

                {preview.first === null ? (
                    <p className="text-brand-muted text-base">
                        {t('public.quiz.preview.empty')}
                    </p>
                ) : (
                    <>
                        <section className="flex flex-col gap-3">
                            <h2 className="font-display text-[1.3rem] font-medium">
                                {t('public.quiz.preview.first_label', { name })}
                            </h2>

                            <SmsBubble testId="quiz-question" tone="question">
                                {preview.first}
                            </SmsBubble>

                            <p className="text-brand-muted text-base">
                                {t('public.quiz.preview.first_note', { name })}
                            </p>
                        </section>

                        <section className="flex flex-col gap-3">
                            <h2 className="font-display text-[1.3rem] font-medium">
                                {t('public.quiz.preview.next_label')}
                            </h2>

                            <ol className="flex flex-col gap-2.5">
                                {preview.next.map((question, position) => (
                                    <li
                                        key={question}
                                        className="card flex items-start gap-4 px-5 py-4"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="border-brand-sand text-brand-muted flex size-7 flex-none items-center justify-center rounded-full border text-[0.85rem] font-semibold tabular-nums"
                                        >
                                            {position + 2}
                                        </span>
                                        <span>{question}</span>
                                    </li>
                                ))}
                            </ol>

                            <p className="text-brand-muted text-base">
                                {t('public.quiz.preview.next_note')}
                            </p>
                        </section>
                    </>
                )}

                {chosen.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <h2 className="font-display text-[1.3rem] font-medium">
                            {t('public.quiz.preview.themes_label')}
                        </h2>
                        <ul className="flex flex-wrap gap-2">
                            {chosen.map((label) => (
                                <li key={label} className="chip">
                                    {label}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <p className="bg-brand-linen rounded-lg px-5 py-4 text-base">
                    {t('public.quiz.preview.validation_note', { name })}
                </p>

                <div className="flex flex-col gap-4">
                    <Link
                        href={checkoutUrl}
                        onClick={() =>
                            track('lp_buy_click', {
                                variant: 'quiz',
                                section: 'plan',
                            })
                        }
                        className="btn-primary press w-full"
                    >
                        {t('public.quiz.preview.cta', { name })}
                    </Link>

                    <button
                        type="button"
                        onClick={() => {
                            forgetAnswers();
                            router.delete('/commencer');
                        }}
                        className="text-brand-muted hover:text-brand min-h-[2.75rem] text-base underline underline-offset-4"
                    >
                        {t('public.quiz.preview.restart')}
                    </button>
                </div>

                {welcomeOffer && (
                    <section className="border-brand-sand flex flex-col gap-4 border-t pt-8">
                        <h2 className="font-display text-[1.3rem] font-medium">
                            {t('public.quiz.preview.email.title')}
                        </h2>

                        {emailSaved ? (
                            <p className="enter text-base" role="status">
                                {t('public.quiz.preview.email.saved')}
                            </p>
                        ) : (
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    track('quiz_email_submitted');
                                    email.post('/commencer/adresse', {
                                        preserveScroll: true,
                                    });
                                }}
                                className="flex flex-col gap-4"
                            >
                                <p className="text-brand-muted text-base">
                                    {t('public.quiz.preview.email.body')}
                                </p>

                                <TextField
                                    type="email"
                                    required
                                    label={t('public.quiz.preview.email.label')}
                                    autoComplete="email"
                                    value={email.data.email}
                                    error={email.errors.email}
                                    onChange={(event) =>
                                        email.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                />

                                {/* Le champ que personne ne voit et que personne ne remplit. */}
                                <input
                                    type="text"
                                    name="website"
                                    tabIndex={-1}
                                    autoComplete="off"
                                    aria-hidden="true"
                                    value={email.data.website}
                                    onChange={(event) =>
                                        email.setData(
                                            'website',
                                            event.target.value,
                                        )
                                    }
                                    className="hidden"
                                />

                                <CheckField
                                    checked={email.data.news}
                                    onChange={(checked) =>
                                        email.setData('news', checked)
                                    }
                                    label={t('public.quiz.preview.email.news')}
                                    hint={t(
                                        'public.quiz.preview.email.news_hint',
                                    )}
                                />

                                <SubmitButton
                                    processing={email.processing}
                                    waitingLabel={t('public.quiz.sending')}
                                    className="w-fit"
                                >
                                    {t('public.quiz.preview.email.submit')}
                                </SubmitButton>

                                <p className="text-brand-muted text-base">
                                    {t('public.quiz.preview.email.privacy')}
                                </p>
                            </form>
                        )}
                    </section>
                )}
            </div>
        </>
    );
}

/**
 * La barre d'avancement et le retour.
 *
 * Le compteur fait plus de travail que n'importe quel argument : un parcours
 * commencé se finit, et « 6 sur 13 » dit qu'il reste peu. Le retour est
 * toujours là — un questionnaire dont on ne peut pas corriger une réponse se
 * quitte à la première hésitation.
 */
function Progress({
    index,
    total,
    onBack,
}: {
    index: number;
    total: number;
    onBack: () => void;
}) {
    const t = useT();
    const percent = Math.round(((index + 1) / total) * 100);

    return (
        <nav
            aria-label={t('public.quiz.progress')}
            className="flex flex-col gap-3"
        >
            <div className="flex items-center justify-between gap-4">
                <button
                    type="button"
                    onClick={onBack}
                    disabled={index === 0}
                    className="text-brand-muted hover:text-brand min-h-[2.75rem] text-base underline underline-offset-4 disabled:invisible"
                >
                    {t('public.quiz.back')}
                </button>

                <p className="text-brand-muted text-base tabular-nums">
                    {t('public.quiz.of', { step: index + 1, total })}
                </p>
            </div>

            <div
                className="bg-brand-sand h-1 w-full overflow-hidden rounded-full"
                aria-hidden="true"
            >
                <div
                    data-testid="quiz-progress"
                    className="bg-brand ease-soft h-full rounded-full transition-[width] duration-500"
                    style={{ width: `${percent}%` }}
                />
            </div>
        </nav>
    );
}

function Title({
    ref,
    title,
    lede,
}: {
    ref: React.Ref<HTMLHeadingElement>;
    title: string;
    lede?: string;
}) {
    return (
        <header className="flex flex-col gap-3">
            <h1
                ref={ref}
                tabIndex={-1}
                className="font-display text-[1.75rem] leading-[1.15] font-medium outline-none sm:text-[2.1rem]"
            >
                {title}
            </h1>
            {lede !== undefined && (
                <p className="text-brand-muted text-base">{lede}</p>
            )}
        </header>
    );
}

function Choices({
    options,
    chosen,
    onChoose,
    t,
}: {
    options: Option[];
    chosen: string;
    onChoose: (value: string) => void;
    t: (key: string, params?: Record<string, string | number>) => string;
}) {
    return (
        <div className="flex flex-col gap-2.5">
            {options.map((option) => (
                <QuizChoice
                    key={option.value}
                    label={option.label}
                    chosen={chosen === option.value}
                    ariaLabel={t('public.quiz.choose', { label: option.label })}
                    onChoose={() => onChoose(option.value)}
                />
            ))}
        </div>
    );
}

/** Un écran-miroir : rien à répondre, un seul bouton. */
function Mirror({
    ref,
    title,
    body,
    second,
    children,
    onNext,
    t,
}: {
    ref: React.Ref<HTMLHeadingElement>;
    title: string;
    body: string;
    second?: string;
    children?: React.ReactNode;
    onNext: () => void;
    t: (key: string, params?: Record<string, string | number>) => string;
}) {
    return (
        <>
            <Title ref={ref} title={title} />

            <div className="flex flex-col gap-4 text-[1.05rem] leading-relaxed">
                <p>{body}</p>
                {second !== undefined && <p>{second}</p>}
            </div>

            {children}

            <button
                type="button"
                onClick={onNext}
                className="btn-primary press w-full"
            >
                {t('public.quiz.next')}
            </button>
        </>
    );
}

/** Le « Continuer » des écrans qu'on ne quitte pas d'un seul tap. */
function Next({
    answered,
    onNext,
    note,
    t,
}: {
    answered: boolean;
    onNext: () => void;
    note?: string;
    t: (key: string, params?: Record<string, string | number>) => string;
}) {
    return (
        <div className="flex flex-col gap-2">
            <button
                type="button"
                onClick={onNext}
                disabled={!answered}
                className="btn-primary press w-full disabled:opacity-50"
            >
                {t('public.quiz.next')}
            </button>

            {note !== undefined && (
                <p className="text-brand-muted text-center text-base">{note}</p>
            )}
        </div>
    );
}
