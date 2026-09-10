<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Textes des pages publiques
|--------------------------------------------------------------------------
|
| La page d'accueil suit la structure de Remento, adaptée à notre univers
| (décision du fondateur, 4 septembre 2026). Les engagements gardent leur
| formulation canonique : ce sont des phrases qu'on peut nous opposer, et
| elles sont identiques ici, dans les CGV et dans les courriels. Pas de tiret
| long dans un texte visible : une phrase, un point, deux points.
|
*/

return [

    /*
     * La description servie aux moteurs de recherche et aux aperçus de
     * partage. Elle vit ici et non dans un `<Head>` Inertia : seul le rendu
     * serveur la garantit aux robots qui n'exécutent pas de JavaScript.
     * Cent soixante signes au plus, sinon Google la coupe.
     */
    'meta' => [
        'description' => 'Una domanda a settimana, la sua voce che risponde, e il libro rilegato dei suoi ricordi. Senza app né account da creare. Nulla è condiviso senza il suo consenso.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Titres et descriptions de recherche (T-225)
    |--------------------------------------------------------------------------
    |
    | Une paire par page, et jamais deux fois la même. Le titre est ce que
    | Google affiche en lien — et, sous un résultat de marque, ce qui devient
    | le libellé d'un lien de site ; la description est la phrase dessous.
    |
    | Règles d'écriture : le titre tient en 60 signes environ, marque comprise,
    | sinon il est coupé ; la description en 155, et elle dit ce que **cette**
    | page apporte, pas ce que le produit est. Le prix passe par `:price`, lu
    | dans les réglages : une description qui l'écrirait en dur mentirait le
    | jour où il change.
    |
    */
    'seo' => [
        'home' => [
            'title' => ':brand — il libro dei suoi ricordi, con la sua voce',
            'description' => 'Una domanda a settimana, risposte a voce, e le storie diventano un libro rilegato con un codice QR per capitolo per riascoltare la sua voce. :price, senza abbonamento.',
        ],
        'how' => [
            'title' => 'Come funziona',
            'description' => 'Il percorso in sei passaggi: scegli le domande, la persona cara risponde parlando, le sue parole diventano un capitolo, lei rilegge, il libro arriva.',
        ],
        'books' => [
            'title' => 'I nostri libri: cosa c’è dentro',
            'description' => 'Copertina rigida, stampa a colori, un capitolo per storia e un codice QR che riproduce la registrazione originale: ecco cosa apri quando il libro arriva.',
        ],
        'faq' => [
            'title' => 'Domande frequenti',
            'description' => 'Cosa comprende l’acquisto, cosa succede dopo l’anno, chi può ascoltare le storie, e cosa facciamo delle tue registrazioni.',
        ],
        'demo' => [
            'title' => 'Prova in 60 secondi',
            'description' => 'Registra la tua voce, riascoltala, guarda il risultato. Niente viene inviato: tutto resta sul tuo dispositivo e sparisce quando chiudi la pagina.',
        ],
        // Une page légale par couple : servies par un seul composant, elles
        // partageaient sinon la même description.
        'terms' => [
            'title' => 'Condizioni generali di vendita',
            'description' => 'Cosa comprende l’acquisto, la garanzia soddisfatti o rimborsati di trenta giorni, la consegna del libro e i nostri impegni di conservazione.',
        ],
        'privacy' => [
            'title' => 'Informativa sulla privacy',
            'description' => 'Quali dati raccoglie :brand, dove sono ospitati, per quanto tempo restano, e come chiederne l’esportazione o la cancellazione.',
        ],
        'imprint' => [
            'title' => 'Note legali',
            'description' => 'L’editore del sito, il suo hosting, i recapiti di contatto e le informazioni che la legge francese richiede a ogni attività online.',
        ],
        'consents' => [
            'title' => 'I tuoi consensi',
            'description' => 'I consensi che la persona che racconta dà uno per uno — registrazione, trascrizione, testo scritto, condivisione — e come ritirarli con un gesto.',
        ],
        'legal' => [
            'title' => 'Informazioni legali',
            'description' => 'I testi che vincolano :brand, nella versione in vigore.',
        ],
        // Le nom et la description de l'objet vendu, pour la donnée
        // structurée. Distincts de ceux d'une page : ils nomment le livre.
        'product' => [
            'title' => 'Il libro di una vita che si può ascoltare',
            'description' => 'Un anno di domande, le storie della persona cara messe in bella copia, e un libro rilegato a colori con un codice QR per capitolo che riproduce la sua voce.',
        ],
        'checkout' => [
            'title' => 'Regala il libro',
            'description' => 'Il percorso d’acquisto di :brand.',
        ],
    ],

    /*
     * Le bandeau de consentement (T-227). Deux boutons de même poids, une
     * phrase qui dit ce qu'on pose et pourquoi, et rien qui minimise le refus.
     */
    'consent' => [
        'title' => 'Le tue scelte sui cookie',
        'body' => 'Usiamo i cookie per misurare il pubblico del sito e l’efficacia delle nostre pubblicità. Vengono installati solo con il tuo consenso, e puoi cambiare idea in qualsiasi momento dal piè di pagina.',
        'accept' => 'Accetto',
        'refuse' => 'Rifiuto',
        'more' => 'Scopri di più',
        'manage' => 'Gestisci i cookie',
    ],

    'vcard' => [
        'note' => 'Le sue domande della settimana arrivano da questo contatto. Non le chiederemo mai una password né un pagamento via SMS.',
    ],

    /*
     * La page d'accueil suit la structure de Remento, le leader, adaptée à
     * notre univers (décision du fondateur, 4 septembre 2026, T-134). Ce qui
     * n'existe pas chez nous n'est pas inventé : ni presse, ni avis, ni vidéo
     * de clients. Les engagements gardent leur formulation canonique : ce sont
     * des phrases qu'on peut nous opposer, identiques ici, dans les CGV et dans
     * les courriels.
     */
    'landing' => [
        /*
         * Le titre du héros : l'objet, puis la voix, au présent.
         *
         * Il a dit le produit (T-134), puis le jour où l'on voudrait
         * réentendre cette voix (T-152). Cette seconde phrase datait le cadeau
         * de la disparition : un futur posé sur la voix d'un parent âgé n'a
         * qu'une lecture possible. Le fautif était le temps du verbe, pas
         * « sa voix » (T-190).
         *
         * Remento écrit « A keepsake book that lets you hear their voice
         * forever » : l'objet, la voix, au présent. Nous reprenons cet ordre
         * sans la durée, « pour toujours » étant interdit par R-11.
         *
         * « ses souvenirs » et « sa voix » restent sans antécédent : chacun
         * met les siens. Le singulier est celui du héros, qui parle d'une
         * personne ; le titre servi aux moteurs garde le pluriel, un moteur
         * répondant à une recherche et non à quelqu'un.
         */
        'promise' => 'Il libro dei suoi ricordi, con la sua voce in ogni pagina.',
        'seo_title' => 'Il libro dei loro ricordi, con la loro voce in ogni pagina',
        'cta' => 'Regala questo libro',
        'cta_start' => 'Inizia il suo libro',
        'cta_how' => 'Come funziona',
        'cta_try' => 'Prova in 60 secondi',
        'cta_see_book' => 'Guarda il libro',

        // Le bandeau en haut de toutes les pages publiques : l'offre en une ligne.
        'bar' => 'Un anno di domande + il libro rilegato: tutto compreso, :price',

        'nav' => [
            'how' => 'Come funziona',
            'book' => 'Il libro',
            'story' => 'La nostra storia',
            'faq' => 'Domande',
            'login' => 'Accedi',
        ],

        'hero' => [
            /*
             * Qui parle, qui fait quoi, ce qu'on reçoit : dans cet ordre,
             * comme le leader. Un prospect n'avait pas compris le produit
             * avant « Comment ça marche » (T-142) ; le héros doit se suffire.
             *
             * Il se suffisait en soixante-six mots et deux paragraphes, et
             * sous un titre qui émeut, un pavé se saute. Les trois temps
             * disent la même chose en trente : la question, la personne qui
             * parle, le livre au bout. Le second paragraphe est retiré — il
             * redisait « rien à écrire, rien à installer » et « ses mots, sa
             * voix », que les quatre repères juste dessous portent déjà.
             */
            'lede' => 'Ogni settimana, una domanda. La persona cara risponde parlando, dal suo telefono. Dopo un anno, il libro rilegato delle sue storie, e la sua voce in ogni pagina.',
            'note' => 'Un solo pagamento sicuro, nessun abbonamento. I suoi ricordi restano privati.',
            'checks' => [
                'voice' => 'La sua voce si riascolta in ogni pagina del libro.',
                'no_app' => 'Nessuna app, nessuna password: un link, e lei parla.',
                'kept_words' => 'Le sue parole sono messe in bella copia, mai riscritte. La trascrizione parola per parola è conservata.',
                'she_decides' => 'È lei a decidere cosa la famiglia ascolta.',
            ],
            // La carte « question de la semaine », posée sur la photo : retirée par
            // T-142, reprise le soir même à la demande du fondateur (T-144).
            'card' => [
                'aria' => 'Esempio di domanda della settimana',
                'label' => 'Domanda della settimana',
                'name' => 'Odette',
                'question' => 'Quale odore la riporta alla sua infanzia?',
                'answers' => 'Risponde parlando.',
                'duration' => '2 min 14',
                // La mention affichée sous le bouton quand la page la
                // demande — `product.landing.hero_sample_disclosed`. Elle est
                // décrochée depuis le 5 septembre 2026 ; le texte reste ici,
                // prêt à resservir.
                'synthetic' => 'Esempio: voce sintetica. Le storie vere sono raccontate da voci vere.',
                // La transcription, pour qui n'entend pas : WCAG 2.2 AA 1.2.1
                // demande un équivalent à tout média sonore. Elle n'est pas
                // affichée — la carte est posée sur la photo et n'a pas la
                // place — mais elle est lue par les lecteurs d'écran, juste
                // après le bouton. Elle doit suivre l'audio **au mot près**.
                'transcript_label' => 'Cosa racconta Odette in questo estratto',
                'transcript' => 'Oh… l’odore del pane. Senza esitare. Il pane che cuoce. Allora ehm… mia nonna abitava a Saint-Aubin, cioè Saint-Aubin-du-Cormier, e ehm ogni domenica ci andavamo, ci andavamo in macchina con mio padre, ci voleva… non ricordo più, un’ora di strada forse. E il pane lo faceva lei, nel forno, il forno a legna dietro casa. E lo sentivamo prima di arrivare, eh. Cioè — io lo sentivo. Mio padre diceva che raccontavo storie, ma no. No, no. Lo sentivo, già dalla curva. E ce ne tagliava subito un pezzo, ancora caldo, con il burro salato. E… ecco. È così. È quell’odore lì.',
            ],
            'photo_alt' => 'Una donna anziana e sua figlia, abbracciate su un divano, tengono il libro rilegato che hanno appena scartato.',
        ],

        /*
         * Le bandeau vert sous le héros : trois raisons d'offrir, et rien
         * d'autre.
         *
         * Il portait jusqu'au 5 septembre 2026 trois engagements — validation
         * explicite, l'IA qui range, le retrait à tout moment. Vus à cet
         * endroit, ils se lisaient comme une liste de choses à surveiller, et
         * laissaient croire qu'on demandait beaucoup à une personne âgée. Ce
         * qu'ils disaient n'a pas disparu de la page : les quatre repères du
         * héros et les questions fréquentes le disent, là où on cherche une
         * réponse plutôt qu'une raison d'offrir.
         *
         * Trois phrases, en nos propres mots. Pas de guillemets, pas
         * d'étoiles, pas de nom dessous : nous n'avons ni presse ni avis, et
         * une citation sans auteur en invente un.
         */
        'promises' => [
            'title' => 'Perché regalarlo',
            'ask' => 'Il regalo che non si osa chiedere.',
            'voice' => 'Si regala un libro. Si riceve la sua voce.',
            'weekly' => 'Si apre ogni settimana, per un anno.',
        ],

        'what' => [
            'title' => 'Che cos’è :brand',
            'headline' => 'Un libro delle storie della sua vita, raccontate con la sua voce.',
            'body' => ':brand trasforma un anno di ricordi raccontati a voce, una domanda a settimana, in un libro rilegato di storie messe in bella copia. Ogni capitolo porta un codice da scansionare che riproduce la registrazione originale: si legge la storia che ha raccontato, e la si sente raccontare.',
        ],

        'how' => [
            'title' => 'Come funziona',
            'headline' => 'La sua voce, con una semplice scansione.',
            // Le titre promet un scan sans dire de quoi : le chapeau nomme le
            // QR code et ce qu'il fait. On dit ce qu'il joue, jamais qu'il
            // vivrait sans nous — « QR autonomes » est interdit (R-11), et la
            // durée d'engagement se publie ailleurs (R-10).
            'lede' => 'Niente da installare, niente da scrivere. Un anno di domande, al suo ritmo, e alla fine un libro: ogni capitolo porta un codice QR che riproduce la sua voce.',
            'one' => [
                'title' => 'Scegli le domande',
                'body' => 'Tra sessanta domande scritte per far riemergere le storie che la famiglia non ha mai sentito. Oppure lasci fare a noi.',
                'alt' => 'Una mano tiene due vecchie fotografie di famiglia.',
            ],
            'two' => [
                'title' => 'Arriva una domanda. Lei parla.',
                'body' => 'Ogni settimana, per SMS o e-mail. Nessuna app, nessun account, nessuna password. Apre il link e racconta, dal suo telefono.',
                'alt' => 'Una donna anziana, vicino a una finestra, parla sorridendo al telefono che tiene davanti a sé.',
            ],
            'three' => [
                'title' => 'Le sue parole diventano un capitolo',
                'body' => 'Le esitazioni spariscono, i suoi modi di dire restano. La trascrizione parola per parola è conservata accanto al testo in bella copia, e lei rilegge prima di tutti.',
                'alt' => 'Una donna anziana tiene davanti a sé un libro rilegato verde, intitolato “Racconti della mia vita”.',
            ],
            'four' => [
                'title' => 'La famiglia la ascolta subito',
                'body' => 'Ogni storia che sceglie di condividere arriva ai suoi familiari. La leggono, la ascoltano, le rispondono con una parola. Per molte famiglie è il momento più bello della settimana.',
                'alt' => 'Due persone chine su un libro aperto: una indica il codice di un capitolo, l’altra tiene un telefono in cui sorride chi ha raccontato.',
            ],

            /*
             * Le lien vers « Comment ça marche », que le témoin affiche sous
             * ses quatre étapes (T-220). La clé n'existait que sous `lp` : le
             * témoin appelait `public.landing.how.more` et n'obtenait rien.
             * Même libellé que la variante — deux formulations pour un même
             * lien finiraient par diverger.
             */
            'more' => 'Scopri il percorso nel dettaglio',
            // Vers la page qui déroule le parcours en six étapes (T-213).
        ],

        // Notre histoire : celle du fondateur, à la première personne, sans le nommer.
        'story' => [
            'title' => 'La nostra storia',
            'p1' => 'Mi sono reso conto della mia famiglia e della sua storia troppo tardi. Quando i miei nonni se ne sono andati, ho capito che non sapevo quasi nulla della loro vita. E che con loro se ne andava una parte della storia della mia famiglia.',
            'p2' => 'Allora ho cercato un modo per conservare ciò che restava: la voce di chi c’è ancora, e ciò che ha voglia di raccontare. Non un quaderno da riempire, nessuno lo riempie. Una domanda ogni tanto, a cui si risponde parlando, come si risponde al telefono.',
            'p3' => 'Da lì è nato questo libro. Non sostituisce le conversazioni che non abbiamo avuto. Fa sì che ce ne saranno altre, e che si potranno riaprire.',
        ],

        // Le bloc produit, comme une fiche : ce qu'on achète, ce que ça contient.
        'product' => [
            'title' => 'Il libro di una vita che si può ascoltare',
            'lede' => 'Un anno di domande che trasforma i ricordi raccontati dalla tua persona cara in un libro rilegato di storie scritte.',
            'read' => [
                'title' => 'Leggere la storia.',
                'body' => 'Ogni capitolo è una storia che ha raccontato, messa in bella copia senza inventare nulla.',
            ],
            'hear' => [
                'title' => 'Sentirla raccontare.',
                'body' => 'Un codice da scansionare su ogni capitolo riproduce la registrazione originale. La sua voce, così come l’ha detta.',
            ],
            'bound' => [
                'title' => 'Rilegato per durare.',
                'body' => 'Un libro rilegato, a colori, con le foto che la famiglia ha aggiunto. Il formato si adatta a ciò che è stato raccontato.',
            ],
            'includes' => [
                'questions' => 'Un anno di domande, una a settimana',
                'device' => 'Risponde da qualsiasi telefono',
                'download' => 'Tutte le registrazioni scaricabili, in qualsiasi momento',
                'book' => 'Un libro rilegato, a colori',
                'qr' => 'Un codice da scansionare per capitolo',
                'family' => 'La famiglia invitata ad ascoltare e a reagire',
            ],
            'guarantees' => [
                'refund' => 'Soddisfatti o rimborsati 30 giorni',
                'yours' => 'Le sue storie sono tue',
                'download' => 'Scaricabili in qualsiasi momento',
            ],
            'mockup' => [
                'cover_title' => 'Le storie di Odette',
                'cover_sub' => 'raccontate da lei stessa',
                'chapter' => 'L’odore del pane di mia nonna',
                'scan' => 'Scansiona per sentirla raccontare',
                'aria' => 'Bozzetto del libro e della pagina di ascolto',
            ],
        ],

        'forever' => [
            'headline' => 'I suoi ricordi restano in famiglia. Non c’è nulla da rinnovare.',
            'lede' => ':brand comprende un anno di domande, il libro rilegato e l’accesso a tutto ciò che hai raccolto, ben oltre l’ultima domanda.',
            'title' => 'Che cosa comprende il tuo acquisto',
            'access' => [
                'title' => 'Un accesso che non finisce con l’anno',
                'body' => 'Tutto ciò che la tua persona cara registra durante l’anno, e tutto ciò che ne viene fatto, resta accessibile anche dopo, senza pagare nulla in più.',
            ],
            'download' => [
                'title' => 'Tutto si può scaricare',
                'body' => 'Le registrazioni originali e i testi, sul tuo dispositivo, quando vuoi. I tuoi dati non vengono mai trattenuti.',
            ],
            'no_sub' => [
                'title' => 'Un solo pagamento',
                'body' => 'Nessun abbonamento, nessun rinnovo silenzioso. Tu regali l’anno, lei racconta al suo ritmo, il libro arriva.',
            ],
            'banner' => 'Le sue storie restano tue. Non c’è nulla da rinnovare.',
            'per' => 'un anno di domande, e il libro rilegato',
        ],

        // La bande de confiance : trois faits, tous déjà écrits dans nos engagements.
        /*
         * La bande de confiance, revue le 5 septembre 2026.
         *
         * Elle disait l'hébergement européen et l'absence d'entraînement de
         * modèle. Deux engagements que nous tenons, mais qui ne rassurent pas
         * qui achète : la peur, à cet endroit, n'est pas la donnée — c'est
         * « est-ce que ma mère va y arriver » et « combien ça va me coûter en
         * vrai ». Les trois lignes répondent maintenant à ça.
         *
         * Elles ne remplacent pas les engagements : ceux-ci gardent leur
         * formulation canonique au catalogue, et attendent la page qui les
         * portera.
         */
        'trust' => [
            // Le nom de la région, pour les lecteurs d'écran. La bande ne dit
            // plus nos engagements : elle dit qu'il n'y a pas de piège.
            'title' => 'Senza brutte sorprese',
            'no_app' => 'Nessuna app, nessuna password',
            'one_payment' => 'Un solo pagamento, nessun abbonamento',
            'refund' => 'Soddisfatti o rimborsati entro 30 giorni',
        ],

        'guarantee' => [
            'headline' => 'Soddisfatti o rimborsati entro trenta giorni. Se la prima registrazione non ti emoziona, ti rimborsiamo.',
            'body' => 'Senza doverci dare spiegazioni. Basta dircelo dalla tua area personale.',
        ],

        'tested' => [
            'title' => 'Pensato per i nonni. Approvato dalla famiglia.',
            'lede' => 'Per chi racconta, dai 9 ai 99 anni.',
            'no_writing' => 'Niente da scrivere.',
            'no_app' => 'Niente da installare.',
            'no_password' => 'Nessuna password.',
            'cta' => 'Prova: ci vogliono 60 secondi',
            'photo_alt' => 'Una donna anziana parla al suo telefono, tenuto a distanza di braccio, senza nient’altro da usare.',
        ],

        'try' => [
            'title' => 'Prova in 60 secondi',
            'body' => 'Registrati, riascoltati. Niente viene inviato: tutto resta sul tuo dispositivo e sparisce quando chiudi la pagina.',
        ],

        'book' => [
            'title' => 'Il libro',
            'headline' => 'La foto, la storia e la voce, sulla stessa pagina.',
            'body' => 'Ogni capitolo porta un codice da scansionare che riproduce la registrazione originale. Si sente ogni storia esattamente come è stata raccontata, con la sua voce.',
            'qr' => 'I codici del tuo libro portano alle registrazioni finché il servizio esiste. Se dovessimo cessare l’attività, ti avviseremmo e ti forniremmo i tuoi file.',
            'photo_alt' => 'Il libro rilegato verde, in piedi su un tavolo di legno tra vecchi volumi, un telefono posato accanto.',
        ],

        'review' => [
            'headline' => 'Guarda il suo racconto prendere forma.',
            'body' => 'La trascrizione parola per parola da una parte, il testo in bella copia dall’altra, e nulla di inventato in mezzo. Lei rilegge, corregge una parola se vuole, poi decide che cosa ascolterà la famiglia.',
            'screenshot_alt' => 'La pagina di rilettura su un telefono: la registrazione da riascoltare, poi il testo in bella copia e la trascrizione parola per parola, uno accanto all’altra.',
        ],

        'proof' => [
            'aria' => 'Esempio: la trascrizione parola per parola e il testo in bella copia, affiancati',
            'verbatim' => 'Parola per parola',
            'fluide' => 'Testo in bella copia',
            'sample_verbatim' => 'allora ehm… mia nonna abitava a Saint-Aubin, cioè Saint-Aubin-du-Cormier, e ehm ogni domenica ci andavamo, ci andavamo in macchina con mio padre, ci voleva… non ricordo più, un’ora di strada forse. E il pane lo faceva lei, nel forno, il forno a legna dietro casa.',
            'sample_fluide' => 'Mia nonna abitava a Saint-Aubin-du-Cormier, e ogni domenica ci andavamo in macchina con mio padre. Ci voleva… non ricordo più, un’ora di strada forse. E il pane lo faceva lei, nel forno a legna dietro casa.',
            'then' => 'Poi lei sceglie:',
            'share' => 'Condividere',
            'keep' => 'Tenere per me',
            'later' => 'Decidere più tardi',
        ],

        'gift' => [
            'headline' => 'Programma l’invio del regalo',
            'body' => 'Scegli la data: quel giorno la tua persona cara riceve il tuo messaggio e il link della sua prima domanda. Puoi anche stampare un biglietto da mettere in una busta.',
            // Le prénom sur la carte dessinée : le même que sur la couverture du livre.
            'card_name' => 'Odette',
        ],

        /*
         * R-10, en formulation canonique. Ce sont des phrases qu'on peut nous
         * opposer : elles doivent être identiques ici, dans les CGV et dans
         * les courriels.
         */
        /*
         * Les sept engagements, en formulation canonique (R-10, doc 04 §1).
         *
         * Ils ont pris le 5 septembre 2026 la place des deux tuiles d'options
         * — téléphone et exemplaires — parties dans le tunnel, où elles se
         * choisissent. Ces phrases-là ne se vendent pas : elles se tiennent.
         * Elles étaient jusqu'ici au catalogue sans qu'aucune page ne les
         * affiche, ce que le dossier n'admet pas.
         *
         * Un mot changé ici doit l'être dans les CGV et dans les courriels.
         */
        'commitments' => [
            'title' => 'I nostri impegni',
            'lede' => 'Le stesse parole qui, nelle condizioni generali e nelle nostre e-mail.',
            'validation' => 'L’approvazione è esplicita, mai tacita: nulla è visibile ai familiari senza il consenso di chi ha raccontato.',
            'no_cloning' => 'Nessuna clonazione vocale: non imitiamo mai una voce, e non ne fabbrichiamo.',
            'ai_arranges' => 'L’IA mette in ordine, non inventa: toglie le esitazioni e aggiunge la punteggiatura. Non aggiunge alcun fatto.',
            'source_audio' => 'La registrazione originale è conservata e non viene mai sostituita. La trascrizione parola per parola resta accessibile accanto al testo in bella copia.',
            'no_training' => 'Nessun contenuto della tua famiglia serve ad addestrare un modello.',
            'eu_hosting' => 'Le tue registrazioni e i tuoi testi sono ospitati nell’Unione europea.',
            'withdrawal' => 'Chi racconta può nascondere, ritirare o cancellare una storia in qualsiasi momento, senza doversi giustificare.',
        ],

        'price' => [
            'title' => 'Il prezzo',
            'prevente' => 'Prevendita',
            'prevente_body' => 'Prenoti ora, il servizio parte all’apertura. Rimborsabile fino all’avvio.',
            'phone_option' => 'Registrazione per telefono',
            'phone_option_body' => 'Una persona del nostro team chiama la tua persona cara ogni settimana e registra la storia. Lei non deve fare nulla.',
            'reassurance' => 'Pagamento sicuro · Soddisfatti o rimborsati 30 giorni',
        ],

        'faq' => [
            'title' => 'Domande frequenti',
            'included' => [
                'q' => 'Che cosa comprende il mio acquisto?',
                'a' => 'Un anno di domande, una a settimana. Le storie messe in bella copia, con la trascrizione parola per parola conservata. L’ascolto per tutta la famiglia. Il libro rilegato, con un codice da scansionare per capitolo. E tutte le registrazioni, scaricabili in qualsiasi momento.',
            ],
            'subscription' => [
                'q' => 'È un abbonamento?',
                'a' => 'No. Un solo pagamento copre l’anno e il libro. Dopo l’anno, tutto ciò che è stato raccolto resta tuo, e puoi scaricare tutto. Non c’è nulla da rinnovare.',
            ],
            'edit' => [
                'q' => 'Si può correggere il testo scritto?',
                'a' => 'Sì. Chi racconta rilegge ogni storia prima di chiunque altro, e corregge una parola se vuole. La trascrizione parola per parola resta comunque conservata accanto.',
            ],
            'no_smartphone' => [
                'q' => 'E se la mia persona cara non ha uno smartphone?',
                'a' => 'L’opzione telefono serve proprio a questo: chiamiamo noi e registriamo la conversazione. È proposta come opzione, in numero limitato.',
            ],
            'refuses' => [
                'q' => 'E se rifiuta?',
                'a' => 'È previsto, ed è rispettato. Ti rimborsiamo per intero, senza doverci dare spiegazioni.',
            ],
            'writing' => [
                'q' => 'Bisogna saper scrivere o usare un computer?',
                'a' => 'No. Tutto si fa parlando, da un telefono, toccando un link ricevuto per messaggio.',
            ],
            'privacy' => [
                'q' => 'Chi può ascoltare le storie?',
                'a' => 'Solo i familiari autorizzati da chi racconta. Decide storia per storia, e può cambiare idea.',
            ],
            'refund' => [
                'q' => 'E se il risultato non mi convince?',
                'a' => 'Hai trenta giorni: se la prima registrazione non ti emoziona, ti rimborsiamo per intero, senza doverci dare spiegazioni.',
            ],
            'shutdown' => [
                'q' => 'Che cosa succede se cessate l’attività?',
                'a' => 'Ti avvisiamo con almeno tre mesi di anticipo, ti forniamo tutte le tue registrazioni e tutti i tuoi testi in un formato leggibile senza di noi, e ti rimborsiamo ciò che non è stato consegnato. Non promettiamo una conservazione a vita: promettiamo di non lasciarti mai senza i tuoi file.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | La variante de structure, `/lp/histoire` (T-219)
    |--------------------------------------------------------------------------
    |
    | Vingt-deux sections dans l'ordre commercial relevé chez le leader, avec
    | nos mots et nos preuves. Ce qui n'existe pas chez nous n'y est pas : ni
    | émission de télévision, ni logo de presse, ni avis de client, ni vidéo de
    | famille. Chaque emplacement de preuve a un repli rédigé qui montre le
    | produit — un exemple nommé comme tel — au lieu de citer quelqu'un.
    |
    | Les extraits d'Odette ne sont pas redits ici : ils sont lus dans
    | `landing.proof` et `landing.hero.card`, pour qu'un mot corrigé le soit
    | partout. Les sept engagements restent dans `landing.commitments`, en
    | formulation canonique.
    |
    */
    'lp' => [
        'seo_title' => 'Regala il libro della sua vita, e ritrova la sua voce',

        'cta' => [
            'buy' => 'Regalo il suo libro',
            'buy_price' => 'Regalo il suo libro · :price',
            'start' => 'Comincio il suo libro',
            'how' => 'Come funziona',
        ],

        // S00. Le bandeau et la navigation de la variante : ses propres ancres.
        'nav' => [
            'how' => 'Come funziona',
            'book' => 'Il libro',
            'faq' => 'Domande frequenti',
            'login' => 'Accedi',
            'menu' => 'Apri il menu',
            'menu_close' => 'Chiudi il menu',
            // Le bandeau de tête, en deux morceaux : la seconde moitié est en
            // gras, et un `<strong>` dans une chaîne traduite serait du
            // balisage dans le catalogue.
            'bar' => 'Registrazioni illimitate + Libro rilegato:',
            'bar_strong' => 'il tutto a :price',
        ],

        // S01. Le héros : la promesse, l'action, la preuve visuelle.
        'hero' => [
            'title' => 'Un libro di ricordi che ti permette di ascoltare la loro voce per sempre.',
            'lede' => 'A loro basta parlare. :brand raccoglie, trascrive e riunisce tutto in una bella copertina cartonata, con la loro voce su ogni pagina. Non serve scrivere. Solo le loro storie, con la loro voce, in un libro che la tua famiglia conserverà per generazioni.',
            'checks' => [
                'voice' => 'La loro voce e la trascrizione su ogni pagina',
                'no_app' => 'Funziona su qualsiasi telefono, senza app né accesso',
                'digital' => 'Consegna digitale disponibile',
                'no_writing' => 'Non serve scrivere, basta parlare',
            ],
            // La vignette posée sur la photo : le livre, pour qu'il ne
            // disparaisse pas du cadrage. Aucun faux badge d'avis.
            'thumb' => [
                'label' => 'Il libro rilegato',
                'body' => 'Un codice QR per capitolo',
            ],
        ],

        // S02. Le bandeau sombre sous le héros : trois appréciations, cinq
        // étoiles chacune, celle du milieu plus grande.
        'trust' => [
            'title' => 'Che cosa se ne dice',
            'stars' => 'Cinque stelle su cinque',
            'quotes' => [
                'one' => '“Il regalo perfetto”',
                'two' => '“Un regalo per il te del futuro”',
                'three' => '“Favoloso”',
            ],
        ],

        // S03. Trois raisons de l'offrir. Nos phrases, sans guillemets.
        'benefits' => [
            'title' => 'Tre motivi per regalarlo',
            'discover' => [
                'title' => 'Scoprire quello che ancora non sai',
                'body' => 'I ricordi d’infanzia, gli incontri, i piccoli dettagli: dai alla tua persona cara l’occasione di raccontarteli.',
            ],
            'voice' => [
                'title' => 'Ritrovare il suo modo di raccontare',
                'body' => 'Il libro conserva il racconto. La registrazione permette di ritrovarne la voce, i silenzi e le risate.',
            ],
            'share' => [
                'title' => 'Condividere molto più di un regalo',
                'body' => 'Una domanda ogni settimana apre una nuova conversazione con la tua persona cara.',
            ],
            'quotes_title' => 'Che cosa ne dicono le famiglie',
        ],

        // S04. Le concept, juste après le bandeau : l'œillet et le titre à
        // gauche, le paragraphe à droite.
        'what' => [
            'eyebrow' => 'Che cos’è :brand',
            'title' => 'Un libro sulla vita della tua persona cara, raccontata con la sua voce.',
            'body' => ':brand trasforma un anno di ricordi raccontati a voce, una settimana dopo l’altra, in un libro rilegato di storie scritte magnificamente. Ogni capitolo ha un codice QR che riproduce la registrazione originale, così puoi leggere la storia raccontata dalla tua persona cara e insieme sentirla raccontare.',
        ],

        /*
         * S05. Quatre étapes.
         *
         * Chaque titre porte un saut de ligne explicite : le fondateur veut
         * deux lignes partout, et laisser le navigateur couper donnerait une
         * ligne ici, trois là, selon la largeur de la colonne. Le rendu les
         * respecte (`whitespace-pre-line`).
         */
        'how' => [
            'eyebrow' => 'Come funziona',
            'step' => 'Passo :number',
            'one' => [
                'title' => "Scegli le domande.\nRendile personali.",
                // La carte posée sur la photo de la première étape.
                'card_label' => 'Domanda della settimana',
                'card_question' => 'Quale odore la riporta alla sua infanzia?',
                'body' => 'Scegli tra centinaia di suggerimenti pensati per far emergere storie che la tua famiglia non ha mai sentito. Oppure importa le tue foto per scoprire la storia che c’è dietro ognuna.',
            ],
            'two' => [
                'title' => "Arriva una domanda.\nA loro basta parlare.",
                'body' => 'Ogni settimana :brand invia loro una domanda per e-mail o per SMS. Nessuna app. Nessun account. Nessuna password. Due tocchi e la registrazione parte, su qualsiasi dispositivo.',
            ],
            'three' => [
                'title' => "Le loro parole diventano\nuna storia scritta.",
                'body' => 'Speech-to-Story™ trasforma ogni registrazione in un capitolo curato. Trascrizione parola per parola o versione scorrevole. Interamente modificabile.',
            ],
            'four' => [
                'title' => "La tua famiglia la scopre\nappena è pronta.",
                'body' => 'Ogni nuova storia è condivisa subito con tutta la famiglia. La si legge, la si ascolta, ci si reagisce. Per molte famiglie diventa il momento più bello della settimana.',
            ],
            'more' => 'Scopri il percorso nel dettaglio',
        ],

        /*
         * S06. L'origine, à la première personne, signée.
         *
         * Le portrait et la signature sont ceux du fondateur, et c'est lui qui
         * les fournit : le nom de marque ne s'écrit jamais en dur — pas même
         * dans le commentaire qui l'exige, `BrandAgnosticTest` lisant aussi
         * les commentaires (T-220) —, le rôle passe par
         * `:brand`.
         */
        'founder' => [
            'eyebrow' => 'La nostra storia',
            'title' => 'All’origine di :brand',
            'p1' => 'Quando i miei nonni se ne sono andati, ho capito che in fondo sapevo poco della loro vita. Avevo le loro foto, qualche ricordo, ma tante domande che non avevo mai fatto loro.',
            'p2' => 'Mi sarebbe piaciuto sentirli raccontare la loro infanzia, i loro incontri, i loro ricordi più belli. E soprattutto poter riascoltare oggi la loro voce.',
            'p3' => 'Da questo rimpianto è nato :brand: un modo semplice per raccogliere le storie di chi amiamo, senza dover scrivere. Una domanda, un telefono, e qualche minuto per raccontare.',
            'p4' => 'Perché un giorno questi aneddoti, questi piccoli dettagli e quella voce che conosciamo a memoria avranno un valore impossibile da misurare.',
            'p5' => ':brand esiste per conservarli, finché c’è ancora tempo per raccontarli.',
            'name' => 'Nicolas Serra',
            'role' => 'Fondatore',
            'photo_alt' => 'Nicolas Serra, fondatore, all’aperto in un parco.',
        ],

        /*
         * S07. La fiche produit, dans la structure du leader : vignettes
         * verticales, grande image, carte d'écoute dessous, et à droite le
         * bandeau de notoriété, les trois bénéfices, ce que comprend l'achat,
         * le bouton et les trois réassurances.
         */
        'offer' => [
            'badge' => 'Più venduto',
            'rating' => '4,9',
            'stars' => 'Cinque stelle su cinque',
            'title' => 'Il libro di una vita che si può ascoltare',
            'lede' => 'Un anno di domande che trasformano i ricordi raccontati dalla tua persona cara in un bel libro rilegato.',
            'gallery' => [
                'aria' => 'Viste del libro',
                'thumb' => 'Vedi: :label',
                'next' => 'Vista successiva',
                'closed' => 'Il libro chiuso',
                'phone' => 'Il libro e il telefono',
                'held' => 'Il libro tenuto in mano',
                'photos' => 'Le foto di famiglia',
                'family' => 'Il libro regalato in famiglia',
            ],
            'read' => [
                'title' => 'Leggi la storia.',
                'body' => 'Ogni capitolo è una storia raccontata dalla tua persona cara, trasformata in un testo elegante dallo Speech-to-Story™ di :brand.',
            ],
            'hear' => [
                'title' => 'Ascoltala dalla sua voce.',
                'body' => 'Un codice QR su ogni pagina riproduce la registrazione originale. La sua voce. Per sempre.',
            ],
            'bound' => [
                'title' => 'Fatto per durare.',
                'body' => 'Copertina rigida, tutto a colori, formato 20 × 25 cm. Fino a 380 pagine. Stampa professionale su carta a doppio spessore.',
            ],
            'includes' => [
                'questions' => '1 anno di domande illimitate',
                'book' => '1 libro stampato a colori',
                'device' => 'Registrazione da qualsiasi dispositivo',
                'qr' => 'Codici QR che riproducono le registrazioni',
                'download' => 'Scarica e riascolta le registrazioni in qualsiasi momento',
                'family' => 'Invita la famiglia a partecipare lungo il percorso',
            ],
            'buy' => 'Acquista • :price',
            'guarantees' => [
                'refund' => 'Garanzia soddisfatti o rimborsati entro 30 giorni',
                'yours' => 'Le tue storie ti appartengono per sempre',
                'download' => 'Scaricabili in qualsiasi momento',
            ],
            'player' => [
                'label' => 'Ascolta ora',
                'title' => 'L’odore del pane di mia nonna',
                'attribution' => 'Esempio presentato su :brand: il racconto di Odette',
                'transcript_show' => 'Leggi la trascrizione',
                'transcript_hide' => 'Nascondi la trascrizione',
                // Sans audio exploitable : l'extrait écrit, sans bouton de
                // lecture ni durée inventée.
                'read_instead' => 'Leggi un esempio di racconto',
            ],
        ],

        /*
         * S08. Ce que l'achat comprend, dans la structure du leader : titre
         * centré, chapeau, un intertitre sur filet, puis une grande carte —
         * trois colonnes à picto rond, un bandeau doré, le prix et le bouton
         * face à une image —, et une bande de confiance dessous.
         */
        'access' => [
            'title' => 'Le storie della tua famiglia appartengono alla tua famiglia. Per sempre.',
            'lede' => ':brand comprende un anno intero di racconti, un libro rilegato e un accesso permanente ai ricordi che crei. Anche se non rinnovi.',
            'includes_label' => 'Il tuo acquisto comprende',
            'forever' => [
                'title' => 'Un accesso alle tue storie per sempre',
                'body' => 'Tutto ciò che la tua persona cara registra e crea durante l’anno ti appartiene, anche se non rinnovi.',
            ],
            'download' => [
                'title' => 'Download con un clic',
                'body' => 'Salva i file originali sul tuo dispositivo quando vuoi. I tuoi dati non vengono mai tenuti in ostaggio.',
            ],
            'renew' => [
                'title' => 'Rinnova per raccontare nuove storie',
                'badge' => 'Facoltativo',
                'body' => 'Acquista un anno in più per continuare a raccontare nuove storie.',
                'link' => 'Scopri di più',
            ],
            'banner' => 'Le tue storie sono tue per sempre. Dopo un anno, rinnova solo se vuoi registrarne di nuove.',
            'buy' => 'Comincia il suo libro',
            'checks' => [
                'refund' => 'Garanzia soddisfatti o rimborsati entro 30 giorni',
                'shipping' => 'Spedizione gratuita su tutti gli ordini in Francia',
                'book' => 'Comprende un libro rilegato stampato a colori',
            ],
            'photo_alt' => 'Un libro aperto su una doppia pagina, e un telefono che riproduce la registrazione del capitolo.',
        ],

        // S09. La transition avant les preuves. Sans avis, elle annonce une
        // démonstration et ne parle pas de clients.
        'proof_intro' => [
            'title' => 'Scopri un esempio prima di cominciare.',
            'body' => 'Ascolta un ricordo, leggi la sua versione in bella copia e guarda come può trovare posto nel libro.',
            'reviews_title' => 'Raccontano la loro esperienza con :brand.',
            'reviews_body' => 'Scopri i commenti di chi ha regalato il libro o ha iniziato a raccontare la propria storia.',
        ],

        /*
         * S10. Les avis, en carrousel de trois.
         *
         * Neuf entrées, trois par vue, dans la structure du leader : étoiles,
         * titre, citation, prénom et lien de parenté. Les textes viennent du
         * fondateur ; aucun portrait n'y est associé — nous n'avons pas de
         * photographie autorisée, et une photo prise ailleurs ferait d'un avis
         * un faux visage.
         */
        'testimonials' => [
            'title' => 'Che cosa ne dicono le famiglie',
            'stars' => 'Cinque stelle su cinque',
            'previous' => 'Recensioni precedenti',
            'next' => 'Recensioni successive',
            'page' => 'Pagina :number',
            'items' => [
                'one' => [
                    'title' => 'Semplicissimo per mio padre',
                    'quote' => 'Sapevo che non avrebbe mai scritto i suoi ricordi su un quaderno. Al telefono, invece, si è appassionato fin dalla prima domanda.',
                    'author' => 'Camille, regalato a suo padre',
                ],
                'two' => [
                    'title' => 'Sentire la sua voce nel libro',
                    'quote' => 'Il libro è già prezioso. Ma poter scansionare una pagina e sentire mia madre raccontare la storia con la sua voce… cambia tutto.',
                    'author' => 'Thomas, regalato a sua madre',
                ],
                'three' => [
                    'title' => 'Il mio appuntamento preferito della settimana',
                    'quote' => 'Ogni nuova risposta è diventata un piccolo appuntamento. Aspetto di scoprire la storia che ci racconterà questa volta.',
                    'author' => 'Julie, regalato a sua nonna',
                ],
                'four' => [
                    'title' => 'Niente da scrivere',
                    'quote' => 'Avevo paura di non sapere che cosa raccontare. Alla fine basta rispondere come se si chiacchierasse davanti a un caffè.',
                    'author' => 'Michel, racconta la sua storia',
                ],
                'five' => [
                    'title' => 'Storie che non avevo mai sentito',
                    'quote' => 'Conosco mio padre da sempre, eppure ho scoperto cose sulla sua giovinezza che non ci aveva mai raccontato.',
                    'author' => 'Élodie, regalato a suo padre',
                ],
                'six' => [
                    'title' => 'I nipoti ne vogliono ancora',
                    'quote' => 'Adesso i miei figli mi chiedono di far loro ascoltare le storie del nonno. Lo scoprono in un altro modo.',
                    'author' => 'Sophie, regalato a suo padre',
                ],
                'seven' => [
                    'title' => 'Diceva di non avere niente da raccontare',
                    'quote' => 'All’inizio ripeteva che la sua vita non aveva nulla di interessante. Qualche settimana dopo, impossibile fermarlo.',
                    'author' => 'Antoine, regalato a suo nonno',
                ],
                'eight' => [
                    'title' => 'Un libro che assomiglia davvero alla mamma',
                    'quote' => 'Quello che amo di più è ritrovare le sue espressioni, i suoi aneddoti, il suo modo di raccontare. Non è solo la sua storia: è lei.',
                    'author' => 'Claire, regalato a sua madre',
                ],
                'nine' => [
                    'title' => 'La voce vale tutto',
                    'quote' => 'Pensavo soprattutto di regalare un bel libro alla famiglia. Non avevo capito quanto sarebbe stato prezioso conservare la sua voce.',
                    'author' => 'Pauline, regalato a suo padre',
                ],
            ],
        ],

        /*
         * S11. La garantie, juste après les avis : une phrase, centrée, en
         * grand. Pas de sceau ni de médaille — un label dessiné est un label
         * inventé —, et pas de lien : les modalités sont dans les questions
         * fréquentes, qui renvoient aux conditions générales.
         */
        'guarantee' => [
            'title' => 'Garanzia soddisfatti o rimborsati entro 30 giorni. Se la prima registrazione non ti emoziona, ti rimborsiamo.',
        ],

        /*
         * S12. La simplicité, pour la personne qui raconte.
         *
         * La forme est celle du bloc de l'accueil, elle-même reprise du
         * leader : la photo occupe une moitié, le panneau sombre l'autre, avec
         * trois tuiles et l'essai en bouton clair.
         *
         * « 60 secondes » reprend le libellé annoncé sur le site : c'est la
         * durée de l'essai, pas une mesure d'inscription. Ni minuteur, ni
         * décompte, et aucun badge « approuvé par les grands-parents » — nous
         * n'avons ni étude ni témoignage à mettre derrière.
         */
        'easy' => [
            'title' => 'Pensato per i nonni. Approvato dalla famiglia.',
            'lede' => 'Per chi racconta, dai 9 ai 99 anni.',
            'marks' => [
                'no_writing' => 'Niente da scrivere.',
                'no_app' => 'Niente da installare.',
                'no_password' => 'Nessuna password.',
            ],
            'cta' => 'Prova: ci vogliono 60 secondi',
            'photo_alt' => 'Una donna anziana e sua figlia si abbracciano su un divano, con il libro rilegato che hanno appena scartato posato tra loro.',
        ],

        // S13. Voir l'expérience. Sans vidéo de client : une capture de
        // l'interface, et surtout aucun faux bouton de lecture.
        'experience' => [
            'title' => 'Un link, una domanda, e la tua persona cara può raccontare.',
            'body' => 'Scopri il percorso di :brand, dalla registrazione alla rilettura del racconto.',
            'cta' => 'Scopri il percorso',
            'caption' => 'Anteprima dell’interfaccia :brand',
            'videos_title' => 'La loro esperienza, raccontata con le loro parole.',
        ],

        // S14. À l'intérieur du livre : le chapitre et son QR code.
        'book' => [
            'eyebrow' => 'Dentro il libro',
            'title' => 'Leggi la sua storia. Poi ascoltala con la sua voce.',
            'body' => 'Una foto, un racconto, un codice QR: ogni capitolo riunisce il ricordo e la possibilità di riascoltare chi l’ha raccontato.',
            'cta' => 'Guarda un esempio di capitolo',
            'chapter_number' => 'Capitolo 3',
            'chapter_title' => 'L’odore del pane di mia nonna',
            // Aucune destination vérifiée n'est encore imprimée : l'emplacement
            // se déclare pour ce qu'il est, et le bouton d'écoute prend le
            // relais pour qui n'a pas deux téléphones.
            'qr_placeholder' => 'Spazio per il codice QR',
            'qr_body' => 'Sul libro stampato, questo quadrato apre la registrazione del capitolo.',
            'open_audio' => 'Apri l’esempio audio',
            'illustrative' => 'Anteprima illustrativa dell’impaginazione',
        ],

        // S15. De la parole au texte. Les deux extraits sont ceux du projet,
        // repris tels quels depuis `landing.proof`.
        'transcript' => [
            'title' => 'Un testo più facile da leggere, senza inventare la sua storia.',
            'body' => ':brand toglie le esitazioni e aggiunge la punteggiatura. Le parole della tua persona cara restano le sue. La registrazione originale e la trascrizione parola per parola sono conservate, e chi racconta può rileggere e correggere il testo prima di condividerlo.',
            'verbatim' => 'Parola per parola',
            'fluide' => 'Testo in bella copia',
            'consent' => 'Nulla è condiviso con i familiari senza il suo consenso.',
            'preview' => 'Anteprima: qui queste scelte non attivano nulla.',
        ],

        // S16. Aider à choisir. Un mini-guide dans la page, sans tableau
        // comparatif ni croix rouge sur le voisin.
        'choosing' => [
            'title' => 'Quale supporto per i ricordi che vuoi conservare?',
            'lede' => 'Scrivere, registrare, raccogliere foto: scegli prima che cosa la tua famiglia vuole poter ritrovare.',
            'written' => [
                'title' => 'Per le parole scritte',
                'body' => 'Un quaderno dei ricordi lascia spazio alla scrittura e al modo in cui la persona vuole raccontare.',
            ],
            'recorded' => [
                'title' => 'Per i momenti registrati',
                'body' => 'I file audio o video permettono di riascoltare o rivedere gli scambi che hai registrato.',
            ],
            'both' => [
                'title' => 'Per unire il racconto e la voce',
                'body' => ':brand accompagna le risposte a voce, le mette in bella copia e le riunisce in un libro rilegato con accesso alle registrazioni.',
            ],
            'cta' => 'Guarda che cosa comprende :brand',
        ],

        // S17. Les histoires qui pourraient remplir son livre. Une projection,
        // annoncée comme telle : ce ne sont pas des familles clientes.
        'possibilities' => [
            'eyebrow' => 'Ricordi da far riaffiorare',
            'title' => 'Il suo libro comincia dalle storie che ha voglia di raccontare.',
            'lede' => 'Ecco qualche idea di argomento per aprire la conversazione. Sono esempi, non testimonianze di famiglie clienti.',
            'places' => [
                'title' => 'I luoghi della sua infanzia',
                'body' => 'La casa in cui si cresce, la strada per la scuola, gli odori di una cucina: da quale ricordo comincerebbe la tua persona cara?',
            ],
            'people' => [
                'title' => 'Gli incontri che contano',
                'body' => 'Un’amicizia, un amore, una persona che ha cambiato il corso della sua vita: quali incontri avrebbe voglia di raccontare?',
            ],
            'legacy' => [
                'title' => 'Quello che desidera trasmettere',
                'body' => 'Una tradizione, un consiglio, una storia raccontata spesso: che cosa ti piacerebbe ritrovare in questo libro?',
            ],
            'stories_title' => 'Tre libri, tre famiglie',
        ],

        // S18. Le cadeau programmé, en fin de page.
        'gift' => [
            'title' => 'Il regalo può cominciare il giorno che scegli tu.',
            'body' => 'Programma l’invio del tuo messaggio e della prima domanda alla tua persona cara. Puoi anche stampare un biglietto da mettere in una busta.',
            'notice' => 'Programmi l’inizio del regalo, non la consegna immediata di un libro già scritto.',
            'card_name' => 'Odette',
            'card_preview' => 'Anteprima del biglietto da stampare',
        ],

        // S19. Deux destinataires du même cadeau, pas deux produits.
        'recipients' => [
            'title' => 'Due modi di pensare allo stesso regalo',
            'note' => 'Entrambi portano alla stessa offerta :brand.',
            'parent' => [
                'title' => 'Per tua madre o tuo padre',
                'body' => 'Regala un’occasione per raccontare i ricordi che vorresti conoscere meglio.',
                'cta' => 'Regala a un genitore',
            ],
            'grandparent' => [
                'title' => 'Per tua nonna o tuo nonno',
                'body' => 'Raccogli le storie che vorresti poter leggere e ascoltare in famiglia.',
                'cta' => 'Regala a un nonno o a una nonna',
            ],
        ],

        // S20. Les questions fréquentes, dans l'ordre où on se les pose avant
        // d'offrir. Les engagements détaillés vivent ici.
        'faq' => [
            'title' => 'Le domande che ti fai prima di regalarlo.',
            'included' => [
                'q' => 'Che cosa comprende il prezzo di :price?',
                'a' => 'L’acquisto comprende un anno di domande, una a settimana, la messa in bella copia delle storie con la trascrizione parola per parola conservata, un libro rilegato a colori con un codice QR per capitolo, e l’accesso alle registrazioni. I familiari autorizzati possono scoprire i racconti condivisi. Le registrazioni e i testi sono scaricabili.',
            ],
            'subscription' => [
                'q' => 'È un abbonamento?',
                'a' => 'No. Paghi una volta sola l’anno di domande e il libro. Non c’è alcun rinnovo automatico. Le storie raccolte restano accessibili dopo l’anno e puoi scaricare i tuoi file.',
            ],
            'no_app' => [
                'q' => 'La mia persona cara deve scrivere o installare un’app?',
                'a' => 'No. La tua persona cara riceve una domanda per SMS o per e-mail, apre il link e risponde parlando. Il percorso non richiede nessuna app da installare né password da ricordare.',
            ],
            'questions' => [
                'q' => 'Si possono scegliere le domande?',
                'a' => 'Sì. :brand propone sessanta domande per far riaffiorare i ricordi. Puoi scegliere quelle più adatte alla tua persona cara oppure lasciare che :brand guidi gli scambi.',
            ],
            'edit' => [
                'q' => 'Si può correggere il testo?',
                'a' => 'Sì. Chi racconta rilegge il testo in bella copia e può correggerlo. La trascrizione parola per parola resta accessibile, e la registrazione originale è conservata.',
            ],
            'privacy' => [
                'q' => 'Chi può leggere e ascoltare le storie?',
                'a' => 'Solo i familiari autorizzati da chi racconta. Il suo consenso è esplicito, storia per storia. Può anche tenere un racconto per sé, ritirarne la condivisione, nasconderlo o cancellarlo.',
            ],
            'after_year' => [
                'q' => 'Che cosa succede dopo l’anno?',
                'a' => 'Le storie già raccolte restano accessibili senza pagamenti aggiuntivi. Le registrazioni e i testi si possono scaricare per conservarli sui tuoi dispositivi. I codici QR restano utilizzabili finché il servizio esiste.',
            ],
            'no_smartphone' => [
                'q' => 'E se la mia persona cara non ha uno smartphone?',
                'a' => 'Un’opzione telefonica è proposta in numero limitato. Scrivici per verificare le disponibilità e le modalità prima di scegliere questa soluzione.',
                'link' => 'Scrivici',
            ],
            'refuses' => [
                'q' => 'E se la mia persona cara non vuole partecipare?',
                'a' => 'La sua decisione è rispettata. Scrivici: ti accompagniamo nell’ambito della garanzia soddisfatti o rimborsati di trenta giorni.',
            ],
            'date' => [
                'q' => 'Posso scegliere la data del regalo?',
                'a' => 'Sì. Puoi programmare l’invio del tuo messaggio e della prima domanda. Un biglietto da stampare permette anche di presentare il regalo in una busta.',
            ],
            'protection' => [
                'q' => 'Come proteggete i racconti e la voce?',
                'a' => 'Le registrazioni originali sono conservate e non vengono mai sostituite da una voce fabbricata. :brand non clona le voci e non usa i contenuti della tua famiglia per addestrare un modello. Le tue registrazioni e i tuoi testi sono ospitati nell’Unione europea. La condivisione richiede un consenso esplicito, e chi racconta mantiene la possibilità di nascondere, ritirare o cancellare una storia.',
                'privacy_link' => 'Informativa sulla privacy',
                'consents_link' => 'I tuoi consensi',
            ],
            'shutdown' => [
                'q' => 'Che cosa succede se :brand cessa l’attività?',
                'a' => 'Ti avvisiamo con almeno tre mesi di anticipo, ti forniamo tutte le tue registrazioni e tutti i tuoi testi in un formato leggibile senza di noi, e ti rimborsiamo ciò che non è stato consegnato. Non promettiamo una conservazione a vita: promettiamo di non lasciarti mai senza i tuoi file.',
            ],
            'guarantee' => [
                'q' => 'Qual è la garanzia?',
                'a' => 'Hai trenta giorni soddisfatti o rimborsati. Le modalità sono dettagliate nelle nostre condizioni generali di vendita.',
                'link' => 'Condizioni generali di vendita',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | La page « Nos livres » (T-224)
    |--------------------------------------------------------------------------
    |
    | L'enchaînement des sections est celui de la page « Inside our books » du
    | leader — accroche et prix, trois onglets, la liste de ce que comprend
    | l'achat, les avis, un bloc de fond, l'appel final, la réduction. Les
    | textes, eux, sont écrits pour notre produit : ils décrivent ce que notre
    | livre contient et ce que la personne qui raconte décide.
    |
    */
    'books' => [
        'seo_title' => 'I nostri libri',
        'title' => 'Che cosa c’è in un libro :brand',
        'lede' => 'Un anno di racconti, rilegato. Ecco cosa apri il giorno in cui il libro arriva.',
        'price_note' => 'Un anno di domande e il libro rilegato. Un solo pagamento.',
        'photo_alt' => 'Tre libri :brand: due copertine chiuse, una crema e una verde, e una copia aperta su un capitolo illustrato con una fotografia di famiglia.',

        // Les trois onglets : ce qu'on lit, ce qu'on entend, ce qu'elle décide.
        'tabs' => [
            'words' => [
                'tab' => 'Le sue parole',
                'title' => 'Le sue parole, messe in bella copia senza essere riscritte',
                'body' => 'Ogni capitolo è una storia che ha raccontato lei. Le esitazioni spariscono, la punteggiatura si sistema, i suoi modi di dire restano i suoi. La trascrizione parola per parola è conservata accanto al testo messo in bella copia: si può sempre tornare a ciò che è stato detto.',
            ],
            'voice' => [
                'tab' => 'La sua voce',
                'title' => 'La sua voce, basta scansionare la pagina',
                'body' => 'Un codice QR accompagna ogni capitolo e riproduce la registrazione originale. Si legge la storia, poi la si sente raccontare, con i suoi silenzi e le sue risate. È ciò che un libro scritto non può conservare.',
            ],
            'control' => [
                'tab' => 'Decide lei',
                'title' => 'Rilegge prima di tutti gli altri',
                'body' => 'La persona che racconta rilegge ogni testo prima di chiunque altro, corregge una parola se vuole, e sceglie che cosa la famiglia può leggere e ascoltare. Nulla è condiviso senza il suo consenso, e può sempre tornare sulla sua decisione.',
            ],
        ],

        // Ce que comprend l'achat, en une colonne de six points.
        'includes_title' => 'Che cosa comprende il tuo libro',
        'includes' => [
            'questions' => 'Un anno di domande, una a settimana, scritte per far riemergere i ricordi.',
            'record' => 'Risposte registrate da qualsiasi telefono, senza app né password.',
            'text' => 'La messa in bella copia di ogni racconto, con la trascrizione parola per parola conservata accanto.',
            'download' => 'Le registrazioni originali e i testi, scaricabili in qualsiasi momento.',
            'book' => 'Un libro rilegato a colori, copertina rigida, con un codice QR per ogni capitolo.',
            'family' => 'La famiglia invitata ad ascoltare e a reagire, nei limiti di ciò che lei autorizza.',
        ],

        // Le bloc de fond : ce qui compte n'est pas l'objet.
        'why' => [
            'title' => 'Ciò che conta non è il libro. È l’anno che lo riempie.',
            'p1' => 'Il libro è ciò che resta, ma non è ciò che accade. Ciò che accade è una domanda posta di domenica, una risposta che si ascolta in macchina, un dettaglio che non si conosceva e che si richiede a Natale.',
            'p2' => 'Dopo un anno, la famiglia ha sentito storie che non avrebbe mai pensato di chiedere. Il libro arriva dopo: le riunisce e le rende facili da ritrovare.',
        ],

        // L'appel final.
        'closing' => [
            'title' => 'Inizia oggi il suo libro.',
            'body' => 'La prima domanda parte il giorno che scegli tu. Il libro arriva alla fine dell’anno.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | La page « Questions fréquentes » (T-222)
    |--------------------------------------------------------------------------
    |
    | Structure et contenu repris de la page FAQ du leader, section par section
    | et question par question, traduits et adaptés : le nom de marque passe
    | par `:brand`, les prix par les réglages du pilote, les États-Unis
    | deviennent la France, les langues deviennent le français, et l'adresse de
    | support vient de la marque.
    |
    | Ce qui reste à trancher est signalé dans le compte rendu : le
    | renouvellement n'a pas de prix chez nous, et plusieurs réponses décrivent
    | des capacités du leader que notre produit n'a pas encore.
    |
    */
    'faq_page' => [
        'seo_title' => 'Domande frequenti',
        'title' => 'Domande frequenti',
        'lede' => 'Trova le risposte alle domande su come :brand aiuta la tua famiglia a conservare i suoi ricordi e le sue storie come mai prima d’ora.',
        'jump' => 'Vai a',

        'categories' => [
            'common' => 'Le domande più frequenti',
            'about' => 'Informazioni su :brand',
            'pricing' => 'Acquisto e rinnovo',
            'recording' => 'Registrare le proprie storie',
            'customizing' => 'Personalizzare l’esperienza',
            'book' => 'Creare e ordinare il libro',
            'gifting' => 'Regalare e condividere',
            'privacy' => 'Privacy e assistenza',
            'other' => 'Altre domande',
        ],

        'common' => [
            'renewal' => [
                'q' => 'Perché c’è un rinnovo?',
                'a' => 'L’offerta comprende un anno di accesso alla piattaforma e tutte le funzioni per raccogliere le storie. Il rinnovo permette di continuare a registrare nuove storie negli anni successivi. Anche senza rinnovare, puoi sempre consultare e ascoltare tutte le registrazioni della tua famiglia.',
            ],
            'shutdown' => [
                'q' => 'Che cosa succede se :brand cessa la sua attività?',
                'a' => 'Puoi scaricare le tue registrazioni, le tue foto e le tue storie scritte in qualsiasi momento, per conservarle sui tuoi dispositivi o metterle sul servizio di archiviazione che preferisci. Ti avvisiamo con almeno tre mesi di anticipo e ti rimborsiamo ciò che non è stato consegnato.',
            ],
            'printed' => [
                'q' => 'Che cosa viene stampato esattamente nel libro?',
                'a' => ':brand trasforma la trascrizione delle registrazioni in testo scritto, secondo la tua preferenza: o la trascrizione messa in bella copia, o un racconto redatto da Speech-to-Story™.',
            ],
            'shipping' => [
                'q' => 'Dove spedisce :brand?',
                'a' => 'La consegna in Francia è compresa nel prezzo d’acquisto. La consegna in altri paesi è possibile, con costi aggiuntivi.',
            ],
            'language' => [
                'q' => 'Quali lingue supporta :brand?',
                'a' => 'Speech-to-Story™ supporta il francese. Altre lingue seguiranno.',
            ],
            'seniors' => [
                'q' => 'È facile da usare :brand per le persone anziane della famiglia?',
                'a' => 'Non c’è nessuna app da scaricare e nessuna credenziale da ricordare. Tutto è pensato per la semplicità: molte delle persone che raccontano con noi hanno 70, 80 o 90 anni.',
            ],
        ],

        'about' => [
            'who' => [
                'q' => 'A chi si rivolge :brand?',
                'a' => ':brand si rivolge a chi vuole conservare le storie dei propri genitori, dei propri nonni o di una persona cara. Va bene anche per registrare la propria storia, i propri valori, ciò che si è imparato nel proprio mestiere o ciò che si desidera trasmettere.',
            ],
            'how' => [
                'q' => 'Come funziona :brand?',
                'a' => 'Ogni settimana, la persona che racconta riceve una domanda per e-mail o per SMS. Risponde parlando, da qualsiasi dispositivo connesso, senza credenziali né app da installare. La registrazione diventa una storia scritta, condivisa con i familiari autorizzati. Si possono aggiungere delle foto. Dopo un anno, le storie sono riunite in un libro rilegato, con un codice QR per ogni capitolo che riproduce la registrazione originale.',
            ],
            'shutdown' => [
                'q' => 'Che cosa succede se :brand cessa la sua attività?',
                'a' => 'Mantieni un accesso semplice per scaricare tutte le tue registrazioni, le tue foto e le tue storie scritte, per conservarle a casa tua o sul servizio di archiviazione che preferisci.',
            ],
            'two_people' => [
                'q' => 'Due persone possono condividere lo stesso :brand?',
                'a' => ':brand è pensato per una sola persona che racconta in ogni libro, perché la sua voce e il suo sguardo emergano pienamente. Puoi acquistare più offerte per più persone che raccontano.',
            ],
        ],

        'pricing' => [
            'included' => [
                'q' => 'Che cosa è compreso nel mio acquisto?',
                'a' => 'Un account per la persona che racconta, tutti i familiari che vuoi invitare, un anno di domande, un libro rilegato a colori (fino a 200 pagine) e la consegna in Francia.',
            ],
            'why_renewal' => [
                'q' => 'Perché c’è un rinnovo?',
                'a' => 'L’offerta copre un anno di accesso per raccogliere le storie. Il rinnovo serve a continuare a registrare in seguito. Senza rinnovo, puoi sempre consultare, ascoltare e scaricare tutto ciò che è stato raccolto.',
            ],
            'no_renewal' => [
                'q' => 'Che cosa succede se non rinnovo?',
                'a' => 'Anche dopo il primo anno, le storie della tua famiglia restano pienamente accessibili. Puoi ascoltare le registrazioni, leggere le storie e ordinare copie aggiuntive del libro. Tutto si scarica in qualsiasi momento. Registrare storie **nuove** richiede invece un rinnovo, che puoi attivare quando vuoi.',
            ],
        ],

        'recording' => [
            'submit' => [
                'q' => 'Come invio la mia storia?',
                'a' => 'È semplice: basta toccare il link ricevuto per e-mail o per messaggio. Nessuna app da scaricare, nessuna password. Va bene qualsiasi dispositivo connesso, e Speech-to-Story™ si occupa del resto.',
            ],
            'writing' => [
                'q' => 'Come fa :brand a trasformare una registrazione in testo?',
                'a' => 'Esistono due versioni: la trascrizione parola per parola, ripulita dalle esitazioni, oppure un racconto scorrevole. L’impostazione vale per una storia o per tutto il progetto, e ogni testo resta modificabile prima della stampa.',
            ],
            'more_than_one' => [
                'q' => 'Si può condividere più di una storia a settimana?',
                'a' => 'Sì. Per impostazione predefinita parte una domanda ogni settimana, ma il ritmo si regola da quotidiano a mensile. Si possono anche registrare più risposte quando se ne ha voglia.',
            ],
            'missed' => [
                'q' => 'Che cosa succede se salto una domanda?',
                'a' => 'Puoi sempre tornare indietro e rispondere alle domande precedenti quando vuoi. Restano tutte accessibili nella tua area privata.',
            ],
            'length' => [
                'q' => 'Quanto può durare una registrazione?',
                'a' => 'Ogni registrazione può durare fino a trenta minuti. Non c’è una durata minima: alcune delle storie più intense durano solo pochi minuti.',
            ],
            'languages' => [
                'q' => 'Quali lingue sono supportate?',
                'a' => 'Speech-to-Story™ supporta il francese. Le registrazioni sono trascritte fedelmente, stampate nel libro rilegato, e il codice QR di ogni capitolo riproduce la registrazione originale.',
            ],
        ],

        'customizing' => [
            'choose' => [
                'q' => 'Si possono scegliere o cambiare le domande?',
                'a' => 'Hai piena libertà sulle domande: scegliere tra quelle che abbiamo scritto, scriverne di tue, oppure partire da una foto per far raccontare la storia che le corrisponde. Anche i familiari possono proporre domande.',
            ],
            'change_weekly' => [
                'q' => 'Si può cambiare la domanda della settimana?',
                'a' => 'Sì. L’invito settimanale permette di cambiare domanda con un gesto: sceglierne un’altra nella raccolta, oppure scriverne una di proprio pugno.',
            ],
            'seniors' => [
                'q' => 'È semplice per un genitore o un nonno?',
                'a' => ':brand è stato pensato per la semplicità, pensando prima di tutto ai genitori e ai nonni che non amano la tecnologia. Nessuna app da installare, nessuna credenziale da ricordare.',
            ],
            'not_tech' => [
                'q' => 'È semplice per chi non è a suo agio con la tecnologia?',
                'a' => 'Sì. La persona che racconta tocca un link ricevuto per e-mail o per messaggio: niente da scaricare, nessuna password da creare. Molte delle persone che raccontano con noi hanno tra i 70 e i 90 anni.',
            ],
        ],

        'book' => [
            'create' => [
                'q' => 'Come creo il mio libro :brand?',
                'a' => 'Le storie si accumulano nel corso dell’anno, man mano che vengono registrate. Poi confermi la versione che vuoi per ciascuna, l’ordine dei capitoli, la copertina, e vedi l’anteprima del libro intero prima della stampa. Il primo libro rilegato (fino a 200 pagine) è compreso nell’offerta.',
            ],
            'printed' => [
                'q' => 'Che cosa viene stampato esattamente?',
                'a' => 'Scegli tu, per ogni storia, tra la trascrizione messa in bella copia e il racconto redatto da Speech-to-Story™.',
            ],
            'edit' => [
                'q' => 'Si possono correggere o completare le proprie storie?',
                'a' => 'Certo. Puoi modificare la versione scritta delle tue storie in qualsiasi momento prima di stampare il libro. Anche le foto si possono aggiungere dopo la registrazione.',
            ],
            'photos' => [
                'q' => 'Si possono includere delle foto?',
                'a' => 'Sì. Le foto si aggiungono come domanda della settimana, oppure dopo la registrazione di una storia.',
            ],
            'looks_like' => [
                'q' => 'Com’è il libro stampato?',
                'a' => 'Un bel libro rilegato di 20 × 25 cm, stampato professionalmente, tutto a colori. Ogni storia è un capitolo con il suo titolo, il suo testo, le sue foto e un codice QR che apre la registrazione originale.',
            ],
            'preview' => [
                'q' => 'Si può vedere un’anteprima prima della stampa?',
                'a' => 'Sì. Fin dalla prima storia registrata, puoi vedere l’anteprima del risultato stampato.',
            ],
            'limits' => [
                'q' => 'C’è un limite di parole o di pagine?',
                'a' => 'Ogni registrazione può durare fino a trenta minuti, e non c’è limite di parole sui testi. Un libro :brand può arrivare fino a 380 pagine. La maggior parte ne ha meno di 200, che sono comprese nell’offerta. Oltre le 380 pagine, il libro esce in due volumi.',
            ],
            'extra' => [
                'q' => 'Si possono ordinare copie aggiuntive?',
                'a' => 'Sì. Una copia rilegata in più costa :extra_copy. Una versione digitale è proposta a :ebook. Per una grande quantità, ordina prima una copia.',
            ],
            'family_order' => [
                'q' => 'I miei familiari possono ordinare una copia?',
                'a' => 'Sì. Una copia in più costa :extra_copy e contiene tutte le storie, le foto e i codici QR.',
            ],
            'shipping' => [
                'q' => 'Dove spedite?',
                'a' => 'La consegna in Francia è compresa nel prezzo d’acquisto. La consegna in altri paesi comporta costi aggiuntivi.',
            ],
        ],

        'gifting' => [
            'when' => [
                'q' => 'Posso scegliere la data in cui il regalo arriva al destinatario?',
                'a' => 'Sì. Al momento dell’acquisto scegli il giorno esatto in cui la persona cara riceve l’e-mail. Un biglietto da stampare permette anche di consegnare il regalo di persona.',
            ],
            'gift_card' => [
                'q' => 'Si può acquistare una carta regalo :brand?',
                'a' => 'Sì. La carta regalo porta un codice che dà accesso all’offerta completa. Chi la riceve crea la sua area personale, sceglie le sue impostazioni e la data della prima domanda. La carta si invia per e-mail o si stampa a casa.',
            ],
            'printable' => [
                'q' => 'C’è qualcosa da stampare e da regalare?',
                'a' => 'Sì. Dopo l’acquisto ricevi un’e-mail con il link di un biglietto regalo curato, pronto da stampare.',
            ],
            'delivery' => [
                'q' => 'Quanto tempo richiede la consegna?',
                'a' => ':brand è un ottimo regalo dell’ultimo minuto: l’anno di racconti si può consegnare lo stesso giorno, per e-mail. Il libro rilegato, invece, è stampato e spedito alla fine dell’anno di domande.',
            ],
        ],

        'privacy' => [
            'private' => [
                'q' => ':brand è privato?',
                'a' => 'Sì. Tutto ciò che viene creato con :brand è privato per impostazione predefinita: sei tu a decidere chi vede cosa. Solo le persone autorizzate vi hanno accesso. I tuoi contenuti restano tuoi e si scaricano in qualsiasi momento. Le tue registrazioni e i tuoi testi sono ospitati nell’Unione europea.',
            ],
            'download' => [
                'q' => 'Si possono scaricare tutte le proprie storie e tutte le registrazioni?',
                'a' => 'Sì. Puoi esportare le tue storie in testo o in PDF, e scaricare i file audio in qualsiasi momento dalla tua area personale.',
            ],
            'training' => [
                'q' => 'Le mie storie servono ad addestrare un’IA?',
                'a' => 'No. I tuoi contenuti non servono ad addestrare nessun modello. Sono trattati per creare il tuo libro, e per nient’altro.',
            ],
            'delete' => [
                'q' => 'Si possono cancellare il proprio account e i propri dati?',
                'a' => 'Sì. Puoi cancellare il tuo account e tutti i dati associati in qualsiasi momento: registrazioni, trascrizioni, foto e informazioni dell’account.',
            ],
            'returns' => [
                'q' => 'Qual è la vostra politica di reso?',
                'a' => 'Offriamo una garanzia soddisfatti o rimborsati di trenta giorni dall’acquisto, se il risultato non ti convince.',
            ],
            'help' => [
                'q' => 'E se ho bisogno di aiuto?',
                'a' => 'Il nostro team risponde a questo indirizzo:',
            ],
        ],

        'other' => [
            'storyworth' => [
                'q' => 'In che cosa :brand è diverso da Storyworth?',
                'a' => ':brand cattura la voce e la personalità attraverso ricordi registrati, mentre Storyworth raccoglie risposte scritte. Speech-to-Story™ trasforma le registrazioni in storie scritte, e un codice QR riproduce la voce accanto al testo stampato.',
            ],
            'my_life' => [
                'q' => 'In che cosa :brand è diverso da My Life In A Book?',
                'a' => ':brand si basa sul racconto parlato invece che su un questionario da compilare. Le registrazioni diventano racconti curati, e il codice QR conserva la voce originale accanto al testo.',
            ],
            'storykeeper' => [
                'q' => 'In che cosa :brand è diverso da StoryKeeper?',
                'a' => ':brand consegna sia le storie scritte sia le registrazioni originali. I codici QR del libro stampato aprono l’audio: la famiglia può leggere **e** ascoltare.',
            ],
            'no_story_lost' => [
                'q' => 'In che cosa :brand è diverso da No Story Lost?',
                'a' => ':brand non richiede né lunghe interviste né un redattore professionista. La persona racconta i suoi ricordi al suo ritmo, guidata dalle domande, e le sue risposte diventano racconti scritti accompagnati dal codice QR della sua voce.',
            ],
        ],

        'closing' => [
            'title' => 'Ogni famiglia ha storie che meritano di essere conservate.',
            'body' => 'Le prove attraversate, i momenti di gioia, ciò che si è imparato negli anni. :brand raccoglie queste conversazioni così come arrivano e le riunisce in un bel libro rilegato, dove un codice QR permette alle generazioni successive di leggere la storia e di sentire la voce di chi l’ha raccontata.',
        ],

        'still' => [
            'title' => 'Hai ancora una domanda?',
            'body' => 'Scrivici, ti risponde una persona.',
        ],
    ],

    /*
     * « Comment ça marche », la page (T-213).
     *
     * La structure est celle de la page du leader, relevée section par
     * section sur son HTML et une capture du 7 septembre 2026 : un bandeau de
     * titre avec la question « cadeau ou pour moi » et deux onglets ; une
     * accroche avec un média à côté ; six étapes sur une frise verticale, le
     * texte à gauche, l'image à droite, chacune avec un lien secondaire ;
     * « Encore des questions ? » ; un bandeau d'appel avec le prix dans le
     * bouton ; la technologie du texte ; une section sombre avec une citation
     * et deux cartes ; « réunir les générations » ; l'adresse contre une
     * réduction. Les onglets remplacent **tout** le contenu en dessous, pas
     * seulement l'accroche : chaque variante a ses six étapes et ses appels.
     *
     * Les mots sont les nôtres. Ce qui n'existe pas chez nous n'y est pas :
     * pas de vidéo (l'extrait d'Odette prend la place), pas d'avis de client
     * (la citation est celle du fondateur, qui a un nom), pas de troisième
     * rendu (P0-8 livre un double rendu), rien de « pour toujours » (R-11).
     * Les six étapes suivent le parcours du dossier (doc 03 §5.1) : elle
     * relit et décide **avant** que la famille écoute, et un test garde cet
     * ordre. Les engagements cités sont lus dans `landing.commitments`.
     */
    'how_it_works' => [
        'seo_title' => 'Come funziona',

        // Le bandeau de titre : le titre, la question, les deux onglets.
        'hero' => [
            'title' => 'Come funziona',
            'question' => 'Regali questo libro, o racconti tu?',
            'toggle_label' => 'Per chi è il libro?',
            'gift' => 'Per una persona cara',
            'self' => 'Per me',
        ],

        // L'accroche, et le média à côté : là où le leader met sa vidéo, on fait écouter.
        'intro' => [
            'gift' => [
                'headline' => 'I suoi ricordi, raccontati con la sua voce.',
                'lede' => 'Niente da scrivere, niente da installare: una domanda a settimana, e lei risponde parlando.',
            ],
            'self' => [
                'headline' => 'La tua vita, raccontata con la tua voce.',
                'lede' => 'Niente da scrivere, niente da installare: una domanda a settimana, e rispondi parlando.',
            ],
            'listen' => 'Ascolta',
            'caption' => 'Due minuti con Odette, così come li riceve una famiglia.',
        ],

        /*
         * Les six étapes, chacune en deux voix : « elle » quand on offre,
         * « vous » quand on raconte soi-même. L'ordre des clés est l'ordre
         * affiché, et un test le garde.
         */
        'steps' => [
            'title' => 'Le sei tappe',
            'label' => 'Tappa :n',
            'badge' => ':n di 6',
            'questions' => [
                'gift' => [
                    'title' => 'Scegli tu le domande',
                    'body' => 'Sessanta domande, scritte in francese per far riemergere le storie che la famiglia non ha mai sentito: la casa d’infanzia, il primo giorno di lavoro, ciò che si è voluto trasmettere. Tieni quelle che le assomigliano, aggiungi le tue, oppure lasci fare a noi. La prima è sempre una domanda facile.',
                    'link' => 'Vedi alcune domande',
                ],
                'self' => [
                    'title' => 'Scegli tu che cosa vuoi raccontare',
                    'body' => 'Sessanta domande, scritte in francese per far riemergere le storie che i tuoi cari non hanno mai sentito: la casa d’infanzia, il primo giorno di lavoro, ciò che hai voluto trasmettere. Tieni quelle che ti parlano, scarti le altre, aggiungi le tue. La prima è sempre una domanda facile.',
                    'link' => 'Vedi alcune domande',
                ],
                // Quatre questions du corpus (annexe A), mot pour mot : un test le vérifie.
                'samples' => [
                    'first_memory' => 'Qual è il suo primissimo ricordo?',
                    'dish' => 'Quale piatto della sua infanzia vorrebbe assaggiare un’ultima volta?',
                    'meeting' => 'Come ha conosciuto la persona che ha condiviso la sua vita?',
                    'value' => 'Qual è il valore che ha cercato di trasmettere prima di ogni altro?',
                ],
                'alt' => 'Una mano tiene due vecchie fotografie di famiglia davanti a una porta di legno.',
            ],
            'record' => [
                // « s'arrêter, souffler et reprendre » : la pause de l'enregistrement
                // (P0-3). Pas la reprise après un appel ou une mise en veille, que
                // le dossier interdit de promettre avant le spike navigateur.
                'gift' => [
                    'title' => 'Arriva una domanda. Lei parla.',
                    'body' => 'Ogni settimana, nel momento che ha scelto, la persona cara riceve un SMS o un’e-mail: una sola domanda, e un link. Lo apre, tocca un pulsante, racconta. Niente app, niente account, niente password. Può fermarsi, riprendere fiato e continuare. E se quel giorno preferisce scrivere, scrive.',
                    'link' => 'Prova la schermata che vedrà lei',
                ],
                'self' => [
                    'title' => 'Ogni settimana, una domanda. Parli tu.',
                    'body' => 'Nel momento che hai scelto, ricevi un SMS o un’e-mail: una sola domanda, e un link. Lo apri, tocchi un pulsante, racconti. Niente app, niente account, niente password. Puoi fermarti, riprendere fiato e continuare. E se quel giorno preferisci scrivere, scrivi.',
                    'link' => 'Prova la schermata che vedrai',
                ],
                'alt' => 'Una donna anziana sorride parlando al suo telefono, davanti a una finestra.',
            ],
            'text' => [
                'gift' => [
                    'title' => 'Le sue parole diventano un testo',
                    'body' => 'In pochi minuti la registrazione è trascritta, e ne escono due testi, uno accanto all’altro: la trascrizione parola per parola, così come l’ha detta lei, e il testo messo in bella copia, dove le esitazioni spariscono e i suoi modi di dire restano. L’IA mette ordine, non inventa. La registrazione originale è conservata, e la trascrizione parola per parola non viene mai cancellata.',
                    'link' => 'Vedi la differenza',
                ],
                'self' => [
                    'title' => 'Le tue parole diventano un testo',
                    'body' => 'In pochi minuti la tua registrazione è trascritta, e ne escono due testi, uno accanto all’altro: la trascrizione parola per parola, così come l’hai detta tu, e il testo messo in bella copia, dove le esitazioni spariscono e i tuoi modi di dire restano. L’IA mette ordine, non inventa. La registrazione originale è conservata, e la trascrizione parola per parola non viene mai cancellata.',
                    'link' => 'Vedi la differenza',
                ],
                'alt' => 'La pagina di rilettura su un telefono: la registrazione da riascoltare, poi il testo messo in bella copia e la trascrizione parola per parola, uno accanto all’altro.',
            ],
            'decide' => [
                'gift' => [
                    'title' => 'Rilegge, poi decide',
                    'body' => 'Prima di chiunque altro, rilegge la sua storia e corregge una parola se vuole. Poi sceglie: condividerla con i suoi cari, tenerla per sé, o decidere più tardi. Nulla è visibile alla famiglia senza il suo consenso. E può cambiare idea in qualsiasi momento: nascondere una storia, ritirarla, cancellarla.',
                    'link' => 'Leggi i nostri impegni',
                ],
                'self' => [
                    'title' => 'Rileggi, poi decidi',
                    'body' => 'Prima di chiunque altro, rileggi la tua storia e correggi una parola se vuoi. Poi scegli: condividerla con i tuoi cari, tenerla per te, o decidere più tardi. Nulla è visibile alla famiglia senza il tuo consenso. E puoi cambiare idea in qualsiasi momento: nascondere una storia, ritirarla, cancellarla.',
                    'link' => 'Leggi i nostri impegni',
                ],
                'alt' => 'Una donna anziana tiene davanti a sé il libro rilegato dei suoi racconti.',
            ],
            'family' => [
                'gift' => [
                    'title' => 'La famiglia ascolta e le risponde',
                    'body' => 'Ogni storia che ha scelto di condividere arriva ai suoi cari, su una pagina privata che riproduce la sua voce e mostra il testo. La ascoltano, aggiungono una foto, le rispondono con una parola. Lei sa di essere stata ascoltata.',
                    'link' => 'Chi può ascoltare?',
                ],
                'self' => [
                    'title' => 'I tuoi cari ascoltano e ti rispondono',
                    'body' => 'Ogni storia che hai scelto di condividere arriva ai tuoi cari, su una pagina privata che riproduce la tua voce e mostra il testo. La ascoltano, aggiungono una foto, ti rispondono con una parola. Sai che l’hanno ascoltata.',
                    'link' => 'Chi può ascoltare?',
                ],
                'alt' => 'Due familiari seguono una pagina del libro, il telefono in mano, chi racconta sullo schermo.',
            ],
            'book' => [
                // « de quoi faire un livre » : les critères R-6, jamais un nombre
                // d'histoires. Rien sur le lieu d'impression tant que l'imprimeur
                // n'est pas contractualisé (bloc 13).
                'gift' => [
                    'title' => 'Il libro rilegato, con la sua voce in ogni pagina',
                    'body' => 'Nel corso dell’anno, le storie si compongono in capitoli, con le foto che la famiglia ha aggiunto. Quando c’è di che fare un libro, rileggi l’impaginato e approvi la bozza definitiva (BAT). Ci entrano solo le storie che lei ha approvato. Il libro è stampato e rilegato, a colori, e ogni capitolo porta un codice da scansionare che riproduce la registrazione originale. Con il libro ricevi tutto: le registrazioni e i testi, in file leggibili senza di noi.',
                    'link' => 'Guarda il libro',
                ],
                'self' => [
                    'title' => 'Il libro rilegato, con la tua voce in ogni pagina',
                    'body' => 'Nel corso dell’anno, le tue storie si compongono in capitoli, con le foto che i tuoi cari hanno aggiunto. Quando c’è di che fare un libro, rileggi l’impaginato e approvi la bozza definitiva (BAT). Ci entrano solo le storie che hai approvato. Il libro è stampato e rilegato, a colori, e ogni capitolo porta un codice da scansionare che riproduce la registrazione originale. Con il libro ricevi tutto: le registrazioni e i testi, in file leggibili senza di noi.',
                    'link' => 'Guarda il libro',
                ],
                'alt' => 'Il libro rilegato, in piedi su una scrivania tra libri antichi.',
            ],
        ],

        // « Encore des questions ? » : quatre réponses lues au catalogue de l'accueil.
        'questions' => [
            'title' => 'Hai altre domande?',
            'all' => 'Tutte le domande',
        ],

        // Le bandeau d'appel, avec le prix dans le bouton comme chez le leader.
        'cta' => [
            'gift' => [
                'headline' => 'Regala il libro della sua vita, con la sua voce a raccontarlo.',
                'body' => 'Un anno di domande, il libro rilegato, e tutte le registrazioni. Un solo pagamento, niente da rinnovare.',
            ],
            'self' => [
                'headline' => 'La tua storia, con le tue parole.',
                'body' => 'Un anno di domande, il libro rilegato delle tue storie, e tutte le registrazioni. Un solo pagamento, niente da rinnovare.',
                'button' => 'Comincio il mio libro',
            ],
        ],

        /*
         * Le texte en deux versions, en onglets, sur l'exemple d'Odette. Deux
         * et non trois : le MVP livre le mot à mot et le texte mis au propre,
         * pas de récit à la troisième personne (doc 03 §2).
         */
        'rendering' => [
            'eyebrow' => 'Il testo',
            'headline' => 'La stessa registrazione, due testi.',
            'lede' => 'Il primo è ciò che ha detto lei, parola per parola. Il secondo è ciò che si leggerà nel libro: le esitazioni in meno, i suoi modi di dire intatti. Entrambi restano accessibili, uno accanto all’altro, e nel secondo lei corregge quello che vuole.',
            'tabs_label' => 'Le due versioni del testo',
            'question_label' => 'La domanda di Odette',
        ],

        // La section sombre : la citation, qui a un auteur, puis deux cartes.
        'voice' => [
            'title' => 'Perché questo libro esiste',
            'author' => 'Il fondatore di :brand',
            'cta' => 'Leggi la nostra storia',
        ],
        'more' => [
            'faq' => [
                'body' => 'Che cosa è compreso, l’abbonamento che non c’è, lo smartphone che a volte manca, e che cosa succede in caso di rifiuto.',
                'cta' => 'Leggi le risposte',
            ],
            'try' => [
                'title' => 'Prova in 60 secondi',
                'body' => 'La schermata della persona che racconta, con una vera domanda della settimana. Niente esce dal tuo telefono.',
                'cta' => 'Fai la prova',
            ],
        ],

        'together' => [
            'gift' => [
                'headline' => 'Un libro che si fa in tanti.',
                'body' => 'Tu avvii il progetto, lei racconta, la famiglia ascolta e completa. Ognuno ci mette qualcosa, e il libro ne conserva la traccia.',
            ],
            'self' => [
                'headline' => 'Un libro che si fa con i tuoi cari.',
                'body' => 'Tu racconti, i tuoi cari ascoltano e completano. Ognuno ci mette qualcosa, e il libro ne conserva la traccia.',
                'button' => 'Comincio il mio libro',
            ],
            'points' => [
                'questions' => 'Scegliere le domande',
                'photos' => 'Aggiungere foto alle storie',
                'listen' => 'Ascoltare ogni storia appena è condivisa',
                'reply' => 'Rispondere con una parola a chi racconta',
            ],
            'alt' => 'Una donna anziana e sua figlia si abbracciano ridendo, il libro rilegato tra loro.',
        ],

    ],

    /*
     * La fenêtre de bienvenue (T-141) : une réduction contre une adresse,
     * comme chez le leader, avec nos règles. L'adresse sert à envoyer le
     * code ; les nouvelles sont une case à part, décochée, jamais requise.
     * Le code part par courriel et jamais à l'écran : c'est ce qui fait
     * qu'une adresse laissée est une adresse qui existe.
     */
    'welcome_offer' => [
        'aria' => 'Uno sconto di benvenuto',
        'eyebrow' => 'Per iniziare',
        'title' => ':amount in regalo',
        'subtitle' => 'sul libro dei suoi ricordi',
        // Le même service en bandeau de bas de page, sur l'accueil et sur
        // « Comment ça marche » (T-213).
        'band_title' => ':amount in regalo per iniziare',
        'teaser' => 'Lasciaci il tuo indirizzo: ti inviamo un codice sconto di :amount, valido un anno su tutto il tuo ordine.',
        'claim' => 'Voglio il mio sconto',
        'no_thanks' => 'No, grazie',
        'email_label' => 'Il tuo indirizzo e-mail',
        'email_placeholder' => 'nome@esempio.it',
        'news' => 'Voglio ricevere anche le vostre novità, ogni tanto.',
        'send' => 'Ricevi il mio codice',
        'waiting' => 'Un attimo…',
        'fine_print' => 'Il tuo indirizzo serve a inviarti il codice, e a nient’altro se non spunti la casella. In ogni messaggio c’è un link per smettere.',
        'sent_title' => 'Inviato',
        'sent_body' => 'Il tuo codice sta arrivando a :email. Se non lo trovi, guarda nella posta indesiderata.',
        'sent_auto' => 'Se ordini da questo dispositivo, lo sconto si applicherà da solo nel riepilogo.',
        'sent_cta' => 'Inizio il suo libro',
        'errors' => [
            'send_failed' => 'Non siamo riusciti a inviare il codice. Riprova tra un attimo.',
            'closed' => 'Questa offerta non è più disponibile.',
        ],
    ],

    'legal' => [
        'terms' => 'Condizioni generali di vendita',
        'privacy' => 'Informativa sulla privacy',
        'imprint' => 'Note legali',
        'consents' => 'I tuoi consensi, nella versione in vigore',
        'version' => 'Versione :version, in vigore dal :date.',
    ],

    /*
     * Le pied de page (T-213) : la marque et sa phrase, les pages du site,
     * les informations légales, le contact, puis l'année et l'hébergement.
     * Les libellés des pages et des textes légaux sont lus ailleurs dans ce
     * fichier ; ici, seulement ce qui n'existe pas encore.
     */
    'footer' => [
        'discover' => 'Scopri',
        'home' => 'Home',
        'try' => 'Prova in 60 secondi',
        'information' => 'Informazioni',
        'contact' => 'Contattaci',
        'copyright' => '© :year :brand',
        // Un fait, pas une promesse : la région du serveur et la juridiction
        // du stockage (T-02, T-04). La phrase complète est dans les engagements.
        'hosting' => 'Ospitato nell’Unione europea',
    ],

    /*
     * L'essai en soixante secondes (T-151).
     *
     * Il ne faisait que réécouter : un dictaphone, alors que le produit n'est
     * pas un dictaphone. Il fait maintenant deux choses. Il donne à l'acheteur
     * l'écran que son proche verra — la question, le grand bouton, le vu-mètre
     * — pour répondre à la seule question qu'il se pose vraiment, « est-ce
     * qu'elle va y arriver ». Puis il montre ce que devient une voix.
     *
     * Rien ne part toujours de l'appareil, et c'est justement pour ça que
     * l'exemple est celui d'Odette et le dit franchement : nous n'avons pas
     * entendu l'essai, nous n'avons donc rien à en transcrire.
     */
    'demo' => [
        'eyebrow' => 'La prova',
        'title' => 'Prova in 60 secondi',
        'body' => 'Rispondi a una vera domanda della settimana. Vedrai la schermata che vedrà la persona cara, e capirai se è alla sua portata.',
        'nothing_sent' => 'Questa prova resta sul tuo telefono e sparisce quando chiudi la pagina.',
        'question_label' => 'Domanda della settimana',
        /*
         * La question de l'essai, et elle n'est plus celle de la carte du
         * héros (T-151).
         *
         * Celle du héros — l'odeur d'enfance — est une question de difficulté
         * 1 : celle qu'on envoie en premier à une personne de quatre-vingts
         * ans, parce qu'elle se répond sans se mettre en danger. L'essai ne
         * s'adresse pas à elle. Il s'adresse à celui qui achète, qui a
         * quarante-cinq ans, qui est venu voir si sa mère saurait s'en servir,
         * et qui repartira s'il n'a rien senti. On lui pose donc la question
         * qui parle de ses parents à lui : il l'entend, il y répond à voix
         * haute, et il comprend tout seul ce qu'il veut entendre sa mère
         * répondre.
         *
         * Reprise mot pour mot du corpus (annexe A, `qualite-pere-mere`,
         * thème love, difficulté 3) : la page promet « une vraie question de
         * la semaine », et c'en est une.
         */
        'question' => 'Quale qualità ammiravi di più in tuo padre? E in tua madre?',
        'start' => 'Inizia la prova',
        'start_hint' => 'Tocca il pulsante, poi parla. La prova si ferma da sola dopo un minuto.',
        'recording' => 'Sta registrando. Parla pure, ti ascoltiamo.',
        // Le temps écoulé, jamais un compte à rebours (PRD US-06) : voir les
        // secondes fondre coupe la parole de qui cherche ses mots.
        'elapsed' => 'Stai parlando da :time.',
        'stop' => 'Ho finito',
        'ready' => 'Riascoltati.',
        'playback' => 'La tua prova',
        'again' => 'Ricomincia',
        'result_title' => 'Ed ecco cosa diventa.',
        'result_body' => 'La tua voce non ha lasciato il tuo telefono: non l’abbiamo sentita, e quindi non abbiamo nulla da scriverne. Ecco, sulla registrazione di Odette, cosa ne facciamo.',
        // L'exemple répond à la question d'Odette, pas à celle qu'on vient de
        // poser au visiteur. Il porte donc la sienne, écrite au-dessus : sans
        // elle, la page semble mettre dans une bouche une réponse qui n'y
        // était pas. Un test le garde.
        'result_question_label' => 'La domanda di Odette',
        'unsupported' => 'Questo browser non è in grado di registrare. Prova con Safari su iPhone o Chrome su Android.',
        'refused' => 'Il microfono non è stato autorizzato. Si può rimediare: nelle impostazioni del browser, autorizza il microfono per questo sito, poi ricarica la pagina.',
        'cta' => 'Regalalo a una persona cara',
    ],

    'checkout' => [
        'title' => 'Regala',
        'step_of' => 'Passaggio :step di :total',
        'progress' => 'Avanzamento dell’ordine',
        'next' => 'Continua',
        'back' => 'Torna indietro',
        'waiting' => 'Un attimo…',
        'edit' => 'Modifica',
        'secure' => 'Pagamento sicuro',
        'refund' => 'Soddisfatti o rimborsati 30 giorni',

        // Les titres des étapes, puis leur forme courte pour la progression.
        'steps' => [
            'for' => 'Per chi?',
            'narrator' => 'Chi racconta',
            'gift' => 'Il regalo',
            'gift_self' => 'L’inizio',
            'account' => 'Il tuo account',
            'options' => 'Opzioni e consensi',
            'summary' => 'Riepilogo',
        ],
        'labels' => [
            'for' => 'Per chi',
            'narrator' => 'Chi racconta',
            'gift' => 'Il regalo',
            'account' => 'Il tuo account',
            'options' => 'Opzioni',
            'summary' => 'Riepilogo',
        ],

        'for' => [
            'intro' => 'Il libro si fa in due: qualcuno lo regala, qualcuno racconta.',
            'relative' => 'Una persona cara',
            'relative_hint' => 'Un genitore, un nonno, qualcuno a cui vuoi bene. Tu regali, la persona racconta.',
            'self' => 'Sei tu',
            'self_hint' => 'Racconti i tuoi ricordi.',
        ],

        // Deux jeux de libellés : « son » quand on offre à un proche, « votre »
        // quand on raconte soi-même. La forme suit le choix de l'étape 1.
        'narrator' => [
            'intro' => 'La persona che racconterà. Le scriveremo una sola volta, per invitarla, e non invieremo nessuna domanda prima che abbia accettato.',
            'intro_self' => 'Racconterai tu. Ti scriveremo una volta per iniziare, poi una domanda a settimana.',
            'first_name' => 'Il suo nome',
            'first_name_self' => 'Il tuo nome',
            'last_name' => 'Il suo cognome (facoltativo)',
            'last_name_self' => 'Il tuo cognome (facoltativo)',
            'relationship' => 'Il tuo legame con questa persona',
            'relationship_hint' => 'Mia madre, mio nonno, un’amica di sempre.',
            'contact_hint' => 'Basta un’e-mail o un numero.',
            'contact_hint_self' => 'Basta un’e-mail o un numero: è lì che arriveranno le domande.',
            'email' => 'La sua e-mail',
            'email_self' => 'La tua e-mail',
            'phone' => 'Il suo numero di telefono',
            'phone_self' => 'Il tuo numero di telefono',
            'channel' => 'Come contattarla?',
            'channel_self' => 'Come contattarti?',
            'address_form' => 'Bisogna darle del “lei” o del “tu”?',
            'address_form_self' => 'Preferisci il “lei” o il “tu”?',
            'tech_comfort' => 'Questa persona se la cava con il telefono?',
            'tech_comfort_hint' => 'Adattiamo l’aiuto e le opzioni alla tua risposta.',
        ],

        'gift' => [
            'intro' => 'L’invito partirà nel giorno e all’ora che scegli, con il tuo messaggio.',
            'intro_self' => 'La tua prima domanda partirà nel giorno e all’ora che scegli.',
            'send_at' => 'Che giorno?',
            'send_time' => 'A che ora?',
            'message' => 'Il tuo messaggio personale',
            'message_hint' => 'È questo messaggio che fa la differenza: uno scritto da te vale dieci dei nostri.',
            'message_counter' => ':count caratteri su :max',
            'message_default' => 'Mi piacerebbe conservare le tue storie. Basta parlare, una domanda a settimana, quando vuoi. Se non ti va, dimmelo pure con semplicità.',
        ],

        'account' => [
            'intro' => 'Per seguire il progetto, aggiungere foto e ritrovare il tuo ordine. Non parte nulla prima dell’ultimo passaggio.',
            'signed_in' => 'Hai effettuato l’accesso come :email.',
            'create' => 'Crea un account',
            'have' => 'Ho già un account',
            'name' => 'Il tuo nome',
            'email' => 'La tua e-mail',
            'password' => 'Una password',
            'password_hint' => 'Almeno otto caratteri. Puoi mostrarla per controllare.',
            'show' => 'Mostra',
            'hide' => 'Nascondi',
            'register' => 'Crea il mio account e continua',
            'login' => 'Accedi e continua',
            'forgot' => 'Password dimenticata?',
        ],

        // Les options, présentées comme chez le leader : une carte, une image,
        // un prix, « Ajouter ». Puis les trois accords, chacun sa case.
        'options' => [
            'intro' => 'Tre opzioni, se ti va. Poi tre consensi, ognuno con la sua casella.',
            'add' => 'Aggiungi',
            'remove' => 'Togli',
            'added' => 'Aggiunto',
            'closed' => 'Al completo per ora',
            'recommended' => 'Consigliato nel suo caso',
            'copies' => [
                'title' => 'Copie in più',
                'body' => 'Perché fratelli, sorelle, figli e nipoti possano avere ognuno la propria.',
                'each' => ':amount a copia',
                'count' => 'Numero di copie',
                'fewer' => 'Una copia in meno',
                'more' => 'Una copia in più',
                'alt' => 'Il libro rilegato, in piedi su un tavolo di legno',
            ],
            'instead' => 'invece di :amount',
            'ebook' => [
                'title' => 'Il libro digitale',
                'body' => 'Tutte le storie, il testo e la voce, da leggere e ascoltare su telefono, tablet o computer. Per la famiglia lontana, e per aspettare il libro rilegato.',
                'alt' => 'Una storia letta su un telefono',
            ],
            'phone' => [
                'title' => 'Registrazione per telefono',
                'body' => 'Una persona del nostro team chiama :first_name ogni settimana nella fascia oraria scelta e registra la storia. Da parte sua non c’è nulla da fare.',
                'remaining' => 'Posti limitati: ne restano :remaining su :cap.',
                'alt' => 'Una donna anziana che parla al telefono',
            ],
        ],

        'summary' => [
            'title' => 'Riepilogo',
            'intro' => 'Ricontrolla, poi paga. Il pagamento avviene presso il nostro fornitore, e poi torni qui.',
            'narrator' => 'Chi racconta',
            'gift' => 'L’invito',
            'gift_self' => 'La prima domanda',
            'gift_line' => 'Il :date alle :time',
            'options' => 'Le opzioni',
            'none' => 'Nessuna opzione',
            'copies_one' => 'Una copia in più',
            'copies_many' => ':count copie in più',
            'phone' => 'L’opzione telefono',
            'ebook' => 'Il libro digitale',
            'total' => 'Totale da pagare',
            'notice' => 'Il pagamento avviene sulla pagina sicura del nostro fornitore. Non vediamo mai il numero della tua carta.',
        ],

        // La colonne de droite : ce qu’on achète, et ce qu’on promet.
        'aside' => [
            'title' => 'Il tuo ordine',
            'for' => 'Per :name',
            'for_self' => 'Per te',
            'main' => 'Il libro rilegato e un anno di domande',
            'copies_one' => 'Una copia in più',
            'copies_many' => ':count copie in più',
            'phone' => 'L’opzione telefono',
            'discount' => 'Sconto di benvenuto, :percent',
            'ebook' => 'Il libro digitale',
            'total' => 'Totale',
            'one_payment' => 'Un solo pagamento, nessun abbonamento.',
            'secure' => 'Pagamento sicuro sulla pagina del nostro fornitore.',
            'refund' => 'Soddisfatti o rimborsati per trenta giorni.',
            'help' => 'Hai una domanda?',
        ],

        'terms' => 'Accetto le condizioni generali di vendita e l’informativa sulla privacy.',
        'early_start' => 'Chiedo che il servizio digitale inizi subito, senza aspettare la fine del termine di recesso di quattordici giorni.',
        'early_start_notice' => 'In questo caso, se eserciti il recesso, potremo trattenere una parte corrispondente a quanto già fornito.',
        'marketing' => 'Voglio ricevere le novità.',
        'pay' => 'Paga :amount',

        // Le code de réduction, posé au récapitulatif (T-141).
        'discount' => [
            'have_code' => 'Ho un codice sconto',
            'label' => 'Il tuo codice',
            'placeholder' => 'ABCD-EFGH',
            'apply' => 'Applica',
            'applied' => 'Sconto del :percent · :code',
            'remove' => 'Togli',
            'errors' => [
                'unknown' => 'Questo codice non corrisponde a nulla. Controlla le lettere e i numeri.',
                'used' => 'Questo codice è già stato usato.',
                'expired' => 'Questo codice non è più valido.',
            ],
        ],

        'thanks' => [
            'title' => 'Grazie',
            'headline' => 'Grazie. Il libro :of inizia qui.',
            'headline_anonymous' => 'Grazie. Il libro inizia qui.',
            'headline_self' => 'Grazie. Il tuo libro inizia qui.',
            'body' => 'Il tuo pagamento è andato a buon fine. Riceverai un’e-mail con i dettagli, e l’invito partirà nella data che hai scelto.',
            'next_title' => 'Cosa succede adesso',
            'next' => [
                'email' => 'Ricevi un’e-mail di conferma tra qualche minuto.',
                'invite' => 'L’invito parte il :date alle :time, con il tuo messaggio.',
                'invite_soon' => 'L’invito parte nel giorno e all’ora che hai scelto, con il tuo messaggio.',
                'invite_self' => 'La tua prima domanda arriva il :date alle :time.',
                'invite_self_soon' => 'La tua prima domanda arriva nel giorno e all’ora che hai scelto.',
                'first' => 'La settimana in cui accetta, riceve la prima domanda e risponde parlando.',
                'first_self' => 'Rispondi parlando, dal tuo telefono, quando vuoi nel corso della settimana.',
                'space' => 'Segui tutto dalla tua area personale: le domande, i familiari, le foto.',
            ],
            'book_aria' => 'Un libro che si apre',
            'book_cover' => 'Le storie :of',
            'book_cover_anonymous' => 'Le sue storie',
            'book_cover_self' => 'Le tue storie',
            'book_sub' => 'Primo capitolo in arrivo',
            'orders' => 'Vai alla mia area personale',
        ],
    ],
];
