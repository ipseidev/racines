import { describe, expect, it } from 'vitest';

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
} from './quiz';

function answers(overrides: Partial<Answers> = {}): Answers {
    return { ...EMPTY_ANSWERS, ...overrides };
}

/** Un `Storage` de test : la navigation privée en lève un, on l'imite aussi. */
function memoryStorage(): Storage {
    const values = new Map<string, string>();

    return {
        get length() {
            return values.size;
        },
        clear: () => values.clear(),
        getItem: (key) => values.get(key) ?? null,
        key: (index) => [...values.keys()][index] ?? null,
        removeItem: (key) => void values.delete(key),
        setItem: (key, value) => void values.set(key, value),
    } as Storage;
}

function throwingStorage(): Storage {
    return {
        get length(): number {
            throw new Error('site data bloquée');
        },
        clear: () => {
            throw new Error('site data bloquée');
        },
        getItem: () => {
            throw new Error('site data bloquée');
        },
        key: () => {
            throw new Error('site data bloquée');
        },
        removeItem: () => {
            throw new Error('site data bloquée');
        },
        setItem: () => {
            throw new Error('site data bloquée');
        },
    } as unknown as Storage;
}

describe('l’enchaînement des écrans', () => {
    it('place un miroir après la question qu’il commente', () => {
        const keys = SCREENS.map((screen) => screen.key);

        /*
         * Ce que ce test protège : un miroir déplacé perd son sens. Celui de
         * l'éloignement parle de la distance qu'on vient de donner, celui de
         * « rien à raconter » répond à la façon de raconter, celui de
         * l'installation répond à l'aisance avec un téléphone.
         */
        expect(keys.indexOf('closeness')).toBe(keys.indexOf('distance') + 1);
        expect(keys.indexOf('nothing')).toBe(keys.indexOf('storyteller') + 1);
        expect(keys.indexOf('install')).toBe(keys.indexOf('tech') + 1);
    });

    it('ne met jamais deux miroirs de suite', () => {
        // Deux écrans d'affilée sans rien à toucher se lisent comme une page
        // de publicité, et c'est là qu'on abandonne.
        SCREENS.forEach((screen, index) => {
            if (screen.kind === 'mirror' && index > 0) {
                expect(SCREENS[index - 1]?.kind).not.toBe('mirror');
            }
        });
    });

    it('demande dix réponses et rien de plus', () => {
        const questions = SCREENS.filter((screen) => screen.kind !== 'mirror');

        expect(questions).toHaveLength(10);
    });

    it('montre le livre après avoir appris le prénom', () => {
        const keys = SCREENS.map((screen) => screen.key);

        // C'est le prénom que la couverture porte : montrée avant, elle
        // serait vide, et l'écran ne montrerait rien.
        expect(keys.indexOf('cover')).toBe(keys.indexOf('name') + 1);
    });

    it('commence par le lien de parenté', () => {
        // C'est le geste le moins coûteux du parcours, et il décide du sujet
        // de tous les écrans suivants.
        expect(SCREENS[0]?.key).toBe('relationship');
    });
});

describe('ce qui laisse passer à l’écran suivant', () => {
    it('laisse toujours passer un miroir', () => {
        const mirror = SCREENS.find((screen) => screen.kind === 'mirror');

        expect(isAnswered(mirror!, EMPTY_ANSWERS, 3)).toBe(true);
    });

    it('retient une question tant qu’elle n’a pas de réponse', () => {
        const age = SCREENS.find((screen) => screen.key === 'age');

        expect(isAnswered(age!, EMPTY_ANSWERS, 3)).toBe(false);
        expect(isAnswered(age!, answers({ age_band: 'seventies' }), 3)).toBe(
            true,
        );
    });

    it('exige le nombre de thèmes demandé', () => {
        const themes = SCREENS.find((screen) => screen.key === 'themes');

        expect(
            isAnswered(themes!, answers({ themes: ['work', 'love'] }), 3),
        ).toBe(false);
        expect(
            isAnswered(
                themes!,
                answers({ themes: ['work', 'love', 'legacy'] }),
                3,
            ),
        ).toBe(true);
    });

    it('refuse un prénom fait d’espaces', () => {
        const name = SCREENS.find((screen) => screen.key === 'name');

        expect(isAnswered(name!, answers({ first_name: '   ' }), 3)).toBe(
            false,
        );
        expect(isAnswered(name!, answers({ first_name: 'Jeanne' }), 3)).toBe(
            true,
        );
    });

    it('laisse toujours passer le livre, teinte et titre étant posés d’avance', () => {
        const cover = SCREENS.find((screen) => screen.key === 'cover');

        // L'écran n'est pas là pour filtrer : il est là pour montrer qu'au
        // bout de tout cela il y a un objet.
        expect(
            isAnswered(
                cover!,
                answers({ book_cover: 'ivory', book_title: 'first_name' }),
                3,
            ),
        ).toBe(true);
        expect(isAnswered(cover!, EMPTY_ANSWERS, 3)).toBe(false);
    });

    it('exige l’occasion et sa date', () => {
        const occasion = SCREENS.find((screen) => screen.key === 'occasion');

        expect(
            isAnswered(occasion!, answers({ occasion: 'birthday' }), 3),
        ).toBe(false);
        expect(
            isAnswered(
                occasion!,
                answers({ occasion: 'birthday', send_at: '2026-12-01' }),
                3,
            ),
        ).toBe(true);
    });
});

describe('les thèmes', () => {
    it('garde l’ordre où ils ont été cochés', () => {
        // L'ordre décide des premières semaines : le thème coché en premier
        // donne la question qui arrive en premier.
        let themes = toggleTheme([], 'work');
        themes = toggleTheme(themes, 'childhood');
        themes = toggleTheme(themes, 'legacy');

        expect(themes).toEqual(['work', 'childhood', 'legacy']);
    });

    it('décoche sans déranger les autres', () => {
        expect(
            toggleTheme(['work', 'childhood', 'legacy'], 'childhood'),
        ).toEqual(['work', 'legacy']);
    });
});

describe('les variantes de miroir', () => {
    it('reconnaît l’éloignement', () => {
        expect(isFar('abroad')).toBe(true);
        expect(isFar('far_away')).toBe(true);
        expect(isFar('same_town')).toBe(false);
        expect(isFar('')).toBe(false);
    });

    it('reconnaît qui aura besoin d’aide', () => {
        // Les deux mêmes valeurs que `TechComfort::suggestsPhoneOption()`.
        expect(needsHelp('no_smartphone')).toBe(true);
        expect(needsHelp('rarely')).toBe(true);
        expect(needsHelp('daily')).toBe(false);
    });
});

describe('les dates', () => {
    it('propose demain, en heure locale', () => {
        // Pas `toISOString()` : à minuit trente à Paris, l'UTC est encore la
        // veille, et « demain » proposerait une date que le serveur refuse.
        expect(defaultSendAt(new Date(2026, 11, 31, 0, 30))).toBe('2027-01-01');
        expect(defaultSendAt(new Date(2026, 8, 19, 23, 45))).toBe('2026-09-20');
    });

    it('ne va pas au-delà de quatre-vingt-dix jours', () => {
        expect(maxSendAt(new Date(2026, 8, 19))).toBe('2026-12-18');
    });
});

describe('la reprise sur l’appareil', () => {
    it('relit ce qui a été écrit', () => {
        const storage = memoryStorage();
        const saved = answers({ first_name: 'Jeanne', themes: ['work'] });

        writeAnswers(saved, storage);

        expect(readAnswers(storage)).toEqual(saved);
    });

    it('complète les champs qu’une version antérieure n’écrivait pas', () => {
        const storage = memoryStorage();
        storage.setItem(
            'quiz.answers',
            JSON.stringify({ first_name: 'Jeanne' }),
        );

        // Un `undefined` rendu à un `<input>` le ferait passer de contrôlé à
        // non contrôlé au milieu du parcours.
        expect(readAnswers(storage)).toEqual(answers({ first_name: 'Jeanne' }));
    });

    it('rend null sur un contenu illisible', () => {
        const storage = memoryStorage();
        storage.setItem('quiz.answers', 'ceci n’est pas du JSON');

        expect(readAnswers(storage)).toBeNull();
    });

    it('ne casse pas quand le stockage est refusé', () => {
        // Navigation privée, quota, site data bloquée : le quiz marche sans.
        const storage = throwingStorage();

        expect(readAnswers(storage)).toBeNull();
        expect(() => writeAnswers(EMPTY_ANSWERS, storage)).not.toThrow();
        expect(() => forgetAnswers(storage)).not.toThrow();
    });

    it('oublie sur demande', () => {
        const storage = memoryStorage();
        writeAnswers(answers({ first_name: 'Jeanne' }), storage);

        forgetAnswers(storage);

        expect(readAnswers(storage)).toBeNull();
    });
});

it('garde le choix « c’est mon histoire » hors du parcours', () => {
    // Il quitte le quiz pour le tunnel : la valeur doit rester celle du
    // serveur, sans quoi le filtre du premier écran laisserait passer un
    // choix qui n'a pas d'écrans derrière lui.
    expect(SELF).toBe('myself');
});

/*
 * Le titre de la couverture.
 *
 * Les mêmes exemples que `tests/Unit/Enums/BookTitleTest.php`,
 * volontairement : les deux compositions sont jumelles, et une divergence
 * d'une apostrophe donnerait une couverture différente de celle qu'on avait
 * fait choisir. `of` est ici un double de `useFormat().of`, lui-même jumeau
 * de `Names::of()`.
 */
describe('le titre de la couverture', () => {
    const of = (name: string) =>
        /^[aeiouyhàâäéèêëîïôöùûü]/i.test(name) ? `d’${name}` : `de ${name}`;

    it('substitue le prénom élidé dans la formule du serveur', () => {
        expect(coverTitle('story', 'L’histoire :of', 'Jeanne', '', of)).toBe(
            'L’histoire de Jeanne',
        );
        expect(
            coverTitle('stories', 'Les histoires :of', 'Odette', '', of),
        ).toBe('Les histoires d’Odette');
        expect(
            coverTitle('memories', 'Les souvenirs :of', 'Yvonne', '', of),
        ).toBe('Les souvenirs d’Yvonne');
    });

    it('rend le prénom seul pour la formule qui ne porte que lui', () => {
        expect(coverTitle('first_name', ':name', 'Jeanne', '', of)).toBe(
            'Jeanne',
        );
    });

    it('imprime le titre libre tel qu’il a été écrit', () => {
        expect(
            coverTitle(
                CUSTOM_TITLE,
                null,
                'Jeanne',
                'Les dimanches chez Mamie',
                of,
            ),
        ).toBe('Les dimanches chez Mamie');
    });

    it('retombe sur le prénom quand le titre libre est vide', () => {
        expect(coverTitle(CUSTOM_TITLE, null, 'Jeanne', '   ', of)).toBe(
            'Jeanne',
        );
    });

    it('ne compose rien tant que le prénom n’est pas saisi', () => {
        // L'écran précédent le demande, mais on peut y revenir et l'effacer.
        expect(coverTitle('story', 'L’histoire :of', '  ', '', of)).toBe('');
    });
});
