/*
 * La logique du tunnel de découverte, hors de React.
 *
 * Les treize écrans, ce qui fait avancer, ce qui fait reculer, ce qu'on a le
 * droit d'envoyer. Ici plutôt que dans le composant pour une raison précise :
 * l'enchaînement est la seule partie du quiz qui puisse se tromper en
 * silence — un écran sauté, un retour qui perd une réponse, un miroir qui
 * s'affiche avant la question qu'il commente — et une fonction pure s'éprouve
 * en trois lignes là où un composant demanderait un rendu.
 */

export type ScreenKind =
    | 'choice'
    | 'multi'
    | 'mirror'
    | 'name'
    | 'cover'
    | 'occasion';

export type ScreenKey =
    | 'relationship'
    | 'voice'
    | 'age'
    | 'distance'
    | 'closeness'
    | 'themes'
    | 'storyteller'
    | 'nothing'
    | 'tech'
    | 'install'
    | 'channel'
    | 'name'
    | 'cover'
    | 'occasion';

export type Screen = {
    key: ScreenKey;
    kind: ScreenKind;
    /** Le champ de la réponse, pour les écrans qui en portent une. */
    field?: AnswerField;
};

export type AnswerField =
    | 'relationship'
    | 'age_band'
    | 'distance'
    | 'themes'
    | 'storyteller'
    | 'tech_comfort'
    | 'channel'
    | 'first_name'
    | 'book_cover'
    | 'book_title'
    | 'occasion';

export type Answers = {
    relationship: string;
    age_band: string;
    distance: string;
    themes: string[];
    storyteller: string;
    tech_comfort: string;
    channel: string;
    first_name: string;
    nickname: string;
    book_cover: string;
    book_title: string;
    book_title_custom: string;
    occasion: string;
    send_at: string;
};

/**
 * Les treize écrans, dans l'ordre.
 *
 * Un miroir toutes les deux ou trois questions, jamais deux de suite : ce
 * sont eux qui portent la preuve, et deux écrans d'affilée sans rien à
 * toucher se lisent comme une page de publicité.
 *
 * Chaque miroir commente la réponse qui vient d'être donnée — `closeness`
 * suit `distance`, `nothing` suit `storyteller`, `install` suit `tech`. Les
 * déplacer casse le sens : le test vérifie cet appariement.
 */
export const SCREENS: Screen[] = [
    { key: 'relationship', kind: 'choice', field: 'relationship' },
    { key: 'voice', kind: 'mirror' },
    { key: 'age', kind: 'choice', field: 'age_band' },
    { key: 'distance', kind: 'choice', field: 'distance' },
    { key: 'closeness', kind: 'mirror' },
    { key: 'themes', kind: 'multi', field: 'themes' },
    { key: 'storyteller', kind: 'choice', field: 'storyteller' },
    { key: 'nothing', kind: 'mirror' },
    { key: 'tech', kind: 'choice', field: 'tech_comfort' },
    { key: 'install', kind: 'mirror' },
    { key: 'channel', kind: 'choice', field: 'channel' },
    { key: 'name', kind: 'name', field: 'first_name' },
    // La couverture vient **après** le prénom, et pas avant : c'est lui
    // qu'elle porte, et un livre montré vide ne montre rien.
    { key: 'cover', kind: 'cover', field: 'book_cover' },
    { key: 'occasion', kind: 'occasion', field: 'occasion' },
];

export const EMPTY_ANSWERS: Answers = {
    relationship: '',
    age_band: '',
    distance: '',
    themes: [],
    storyteller: '',
    tech_comfort: '',
    channel: '',
    first_name: '',
    nickname: '',
    book_cover: '',
    book_title: '',
    book_title_custom: '',
    occasion: '',
    send_at: '',
};

/** Le choix qui quitte le quiz pour le tunnel : on ne raconte pas sa propre vie ici. */
export const SELF = 'myself';

/** Les réponses d'éloignement qui appellent le miroir « on raccroche toujours ». */
const FAR = ['far_away', 'abroad'];

/** Les aisances qui appellent la variante d'aide du miroir « rien à installer ». */
const NEEDS_HELP = ['rarely', 'no_smartphone'];

export function isFar(distance: string): boolean {
    return FAR.includes(distance);
}

export function needsHelp(techComfort: string): boolean {
    return NEEDS_HELP.includes(techComfort);
}

/**
 * L'écran est-il franchissable ?
 *
 * Un miroir l'est toujours : il ne demande rien. Une question ne l'est que
 * répondue — le bouton reste éteint plutôt que de laisser passer un vide
 * que le serveur refusera trois écrans plus loin.
 */
export function isAnswered(
    screen: Screen,
    answers: Answers,
    minThemes: number,
): boolean {
    switch (screen.kind) {
        case 'mirror':
            return true;
        case 'multi':
            return answers.themes.length >= minThemes;
        case 'name':
            return answers.first_name.trim() !== '';
        // La couverture a toujours une réponse : une teinte est posée en
        // arrivant. L'écran n'est pas là pour filtrer, il est là pour montrer
        // qu'au bout de tout cela il y a un livre.
        case 'cover':
            return answers.book_cover !== '' && answers.book_title !== '';
        case 'occasion':
            return answers.occasion !== '' && answers.send_at !== '';
        default:
            return screen.field !== undefined && answers[screen.field] !== '';
    }
}

/**
 * Coche ou décoche un thème, en gardant l'ordre où ils ont été cochés.
 *
 * L'ordre n'est pas cosmétique : c'est lui que suit l'aperçu pour composer
 * les premières semaines. Le thème coché en premier donne la question qui
 * arrive en premier.
 */
export function toggleTheme(themes: string[], value: string): string[] {
    return themes.includes(value)
        ? themes.filter((theme) => theme !== value)
        : [...themes, value];
}

/** La date proposée : demain, sauf occasion choisie. */
export function defaultSendAt(today = new Date()): string {
    const date = new Date(today);
    date.setDate(date.getDate() + 1);

    /*
     * Les parties **locales**, pas `toISOString()` : à minuit trente à Paris
     * l'UTC est encore la veille, et « demain » deviendrait aujourd'hui — le
     * champ proposerait alors une date que le serveur refuse (même piège
     * qu'au tunnel d'achat).
     */
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

/** Le dernier jour acceptable : quatre-vingt-dix, comme au tunnel. */
export function maxSendAt(today = new Date()): string {
    const date = new Date(today);
    date.setDate(date.getDate() + 90);

    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

/**
 * Les réponses gardées sur l'appareil, pour survivre à un onglet fermé.
 *
 * Sur l'appareil et non sur le serveur : un quiz dure deux minutes, la
 * reprise réaliste est celle du même navigateur, et garder côté serveur les
 * réponses partielles de chaque visiteur reviendrait à conserver le prénom
 * d'un parent et la distance qui vous en sépare pour des gens qui n'ont rien
 * demandé. Ce qui part au serveur ne part qu'une fois, à la fin, quand la
 * personne a choisi de voir son plan.
 *
 * Toute lecture et toute écriture est gardée : en navigation privée,
 * l'accesseur lui-même peut lever.
 */
const STORAGE_KEY = 'quiz.answers';

export function readAnswers(
    storage?: Storage,
    base: Answers = EMPTY_ANSWERS,
): Answers | null {
    try {
        const raw = (storage ?? window.localStorage).getItem(STORAGE_KEY);

        if (raw === null) {
            return null;
        }

        const parsed: unknown = JSON.parse(raw);

        if (typeof parsed !== 'object' || parsed === null) {
            return null;
        }

        /*
         * Fusionné avec le socle : une version antérieure du quiz a pu écrire
         * moins de champs, et un `undefined` ferait passer un `<input>` de
         * contrôlé à non contrôlé au milieu du parcours. Le socle porte aussi
         * les valeurs que seul le serveur connaît — la teinte de couverture
         * par défaut est la première de sa palette, pas une constante d'ici.
         */
        return { ...base, ...(parsed as Partial<Answers>) };
    } catch {
        return null;
    }
}

export function writeAnswers(answers: Answers, storage?: Storage): void {
    try {
        (storage ?? window.localStorage).setItem(
            STORAGE_KEY,
            JSON.stringify(answers),
        );
    } catch {
        // Navigation privée, quota, site data bloqué : le quiz marche sans.
    }
}

export function forgetAnswers(storage?: Storage): void {
    try {
        (storage ?? window.localStorage).removeItem(STORAGE_KEY);
    } catch {
        // Idem.
    }
}

/** La formule de titre qui ouvre un champ libre. */
export const CUSTOM_TITLE = 'custom';

/**
 * Le titre de la couverture, composé.
 *
 * Le jumeau de `BookTitle::compose()`, et il en est le jumeau au sens
 * strict : le titre montré à l'écran est celui qui s'imprimera, et deux
 * compositions qui divergeraient d'une apostrophe donneraient une couverture
 * différente de celle qu'on avait fait choisir.
 *
 * Ce qui garantit l'accord n'est pas la vigilance : la formule vient du
 * serveur toute faite (`titles[].pattern`, lue dans le catalogue du livre
 * imprimé), et `of` est `useFormat().of`, jumeau de `Names::of()`. Il ne
 * reste ici qu'une substitution.
 *
 * Un titre libre vide retombe sur le prénom, comme côté serveur : mieux vaut
 * une couverture sobre qu'une couverture muette.
 */
export function coverTitle(
    kind: string,
    pattern: string | null,
    firstName: string,
    custom: string,
    of: (name: string) => string,
): string {
    const name = firstName.trim();

    if (kind === CUSTOM_TITLE) {
        return custom.trim() !== '' ? custom.trim() : name;
    }

    if (name === '' || pattern === null) {
        return name;
    }

    return pattern.replace(':of', of(name)).replace(':name', name);
}
