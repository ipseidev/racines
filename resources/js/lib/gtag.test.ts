import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Ce que Google Analytics n'a pas le droit de rapporter, et ce qu'il doit
 * faire avant de mesurer quoi que ce soit.
 *
 * Le module garde un drapeau « démarré » au niveau du fichier : chaque test
 * le réimporte pour repartir d'un état neuf, faute de quoi le deuxième test
 * mesurerait avec la mesure du premier.
 */

const JETON = 'a'.repeat(43);

/** Le module, remis à zéro, sur la page demandée. */
async function surLaPage(url: string) {
    vi.resetModules();

    window.history.pushState({}, '', url);
    window.dataLayer = [];
    document.head.innerHTML = '<meta name="csp-nonce" content="nonce-de-test">';

    return import('@/lib/gtag');
}

/**
 * Les entrées de la file, la première commande d'abord.
 *
 * Les entrées sont des objets `arguments` et non des tableaux — voir le test
 * qui l'exige — d'où la conversion avant lecture.
 */
function file(): unknown[][] {
    return (window.dataLayer ?? []).map((entree) =>
        Array.from(entree as ArrayLike<unknown>),
    );
}

/** L'entrée qui porte cette commande, ou `undefined`. */
function commande(nom: string, second?: string): unknown[] | undefined {
    return file().find(
        (entree) =>
            entree[0] === nom && (second === undefined || entree[1] === second),
    );
}

beforeEach(() => {
    window.history.pushState({}, '', '/');
});

describe('initGoogleAnalytics', () => {
    it('refuse le consentement avant de charger le script', async () => {
        const { initGoogleAnalytics } = await surLaPage('/');

        initGoogleAnalytics('G-TEST');

        // L'ordre est la garantie : le consentement par défaut doit être dans
        // la file avant que `gtag.js` ne l'ouvre, sinon le script écrit ses
        // cookies puis apprend qu'il n'aurait pas dû.
        expect(file()[0]?.[0]).toBe('consent');
        expect(file()[0]?.[1]).toBe('default');
        expect(file()[0]?.[2]).toMatchObject({
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
        });
    });

    /*
     * Le défaut qui n'aurait rien dit.
     *
     * `gtag.js` ne reconnaît une commande que poussée comme un objet
     * `arguments` ; un tableau ordinaire est ignoré en silence. Le script
     * charge, le conteneur s'initialise, la propriété reste vide, et rien
     * — ni une erreur de console, ni une requête en échec — ne le signale.
     * Trouvé dans un navigateur, pas par un test : celui-ci existe pour que
     * la prochaine réécriture du module ne le réintroduise pas.
     */
    it('pousse de véritables objets `arguments`, seuls reconnus par gtag.js', async () => {
        const { initGoogleAnalytics } = await surLaPage('/');

        initGoogleAnalytics('G-TEST');

        for (const entree of window.dataLayer ?? []) {
            expect(Object.prototype.toString.call(entree)).toBe(
                '[object Arguments]',
            );
        }
    });

    it('charge le script avec le nonce du document', async () => {
        const { initGoogleAnalytics } = await surLaPage('/');

        initGoogleAnalytics('G-TEST');

        const script = document.head.querySelector('script');

        // Sans le nonce, la politique de contenu stricte refuse le script, et
        // le refus ne se voit nulle part côté mesure (T-75).
        expect(script?.nonce).toBe('nonce-de-test');
        expect(script?.src).toBe(
            'https://www.googletagmanager.com/gtag/js?id=G-TEST',
        );
        expect(script?.async).toBe(true);
    });

    it('n’envoie pas la page vue de GA, il l’envoie lui-même', async () => {
        const { initGoogleAnalytics } = await surLaPage('/acheter');

        initGoogleAnalytics('G-TEST');

        expect(commande('config', 'G-TEST')?.[2]).toMatchObject({
            send_page_view: false,
            page_path: '/acheter',
            allow_google_signals: false,
            allow_ad_personalization_signals: false,
        });

        expect(commande('event', 'page_view')?.[2]).toMatchObject({
            page_path: '/acheter',
            page_location: `${window.location.origin}/acheter`,
        });
    });

    it('ne rapporte jamais la chaîne de requête', async () => {
        const { initGoogleAnalytics } = await surLaPage(
            '/acheter?utm_source=x&email=marie@test.fr',
        );

        initGoogleAnalytics('G-TEST');

        const rapporte = JSON.stringify(file());

        expect(rapporte).not.toContain('marie@test.fr');
        expect(rapporte).not.toContain('utm_source');
    });

    it('ne démarre pas sur une page à jeton', async () => {
        const { initGoogleAnalytics } = await surLaPage(`/r/${JETON}`);

        initGoogleAnalytics('G-TEST');

        // Ceinture : le serveur ne donne pas d'identifiant à cette page. S'il
        // en donnait un par erreur, rien ne partirait quand même.
        expect(file()).toHaveLength(0);
        expect(document.head.querySelector('script')).toBeNull();
    });

    it('ne démarre pas sans identifiant', async () => {
        const { initGoogleAnalytics } = await surLaPage('/');

        initGoogleAnalytics('');

        expect(file()).toHaveLength(0);
    });

    it('ne démarre qu’une fois', async () => {
        const { initGoogleAnalytics } = await surLaPage('/');

        initGoogleAnalytics('G-TEST');
        initGoogleAnalytics('G-TEST');

        expect(document.head.querySelectorAll('script')).toHaveLength(1);
    });
});

describe('pageview', () => {
    it('compte les navigations d’Inertia, chemin nettoyé', async () => {
        const { initGoogleAnalytics, pageview } = await surLaPage('/');

        initGoogleAnalytics('G-TEST');
        pageview('/espace/questions?email=marie@test.fr');

        const vues = file().filter(
            (entree) => entree[0] === 'event' && entree[1] === 'page_view',
        );

        expect(vues).toHaveLength(2);
        expect(vues[1]?.[2]).toMatchObject({ page_path: '/espace/questions' });
    });

    it('ne compte rien sur un chemin à jeton', async () => {
        const { initGoogleAnalytics, pageview } = await surLaPage('/');

        initGoogleAnalytics('G-TEST');
        pageview(`/l/${JETON}`);

        const vues = file().filter(
            (entree) => entree[0] === 'event' && entree[1] === 'page_view',
        );

        expect(vues).toHaveLength(1);
        expect(JSON.stringify(vues)).not.toContain('aaa');
    });

    it('ne compte rien quand la mesure ne tourne pas', async () => {
        const { pageview } = await surLaPage('/');

        pageview('/acheter');

        expect(file()).toHaveLength(0);
    });
});

describe('sanitizeReferrer', () => {
    it('garde l’origine et coupe le jeton', async () => {
        const { sanitizeReferrer } = await surLaPage('/');

        // L'origine est toute la valeur du champ ; le chemin, lui, peut
        // porter un secret si quelqu'un a partagé un lien d'écoute.
        // Un domaine neutre : le nom de marque vient des réglages, jamais du
        // code, pas même dans un test.
        expect(sanitizeReferrer(`https://liens.exemple.test/l/${JETON}`)).toBe(
            'https://liens.exemple.test/l/:token',
        );
        expect(
            sanitizeReferrer('https://www.google.com/search?q=memoire'),
        ).toBe('https://www.google.com/search');
    });

    it('rend une chaîne vide sur un référent absent ou absurde', async () => {
        const { sanitizeReferrer } = await surLaPage('/');

        expect(sanitizeReferrer('')).toBe('');
        expect(sanitizeReferrer('pas-une-url')).toBe('');
    });
});
