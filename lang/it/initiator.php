<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Espace de l'Initiateur·rice
|--------------------------------------------------------------------------
|
| Les actions en un tap du moteur de complétion (bloc 09). Le ton n'y met
| jamais en cause : on propose un geste, on ne reproche pas un silence. La
| personne qui lit ces pages a acheté le service et porte le projet — la
| fatiguer, c'est perdre le meilleur relais du produit.
|
*/

return [

    'status' => [
        'draft' => 'In preparazione',
        'awaiting_acceptance' => 'In attesa della risposta della persona invitata',
        'active' => 'In corso',
        'paused' => 'In pausa',
        'dormant' => 'Inattivo',
        'completed' => 'Concluso',
        'cancelled' => 'Annullato',
        'frozen_bereavement' => 'Sospeso',
    ],

    /*
     * L'état d'une histoire, du point de vue de l'Initiateur·rice. Elle voit
     * **où en est** chaque histoire, jamais son contenu tant que le narrateur
     * ne l'a pas partagée.
     */
    'story_state' => [
        'proposed' => 'Domanda inviata',
        'recorded' => 'Registrata',
        'transcribed' => 'Custodita da chi racconta',
        'to_review' => 'In attesa della sua decisione',
        'validated' => 'Approvata',
        'shared' => 'Condivisa con te',
        'in_book' => 'Nel libro',
        'hidden' => 'Nascosta da chi racconta',
        'archived' => 'Archiviata',
        'trashed' => 'Nel cestino',
        'deleted' => 'Eliminata',
    ],

    'alert' => [
        'invitation_not_accepted' => 'L’invito non è ancora stato aperto. Un tuo messaggio aiuterebbe.',
        'three_stories_no_reaction' => 'Tre storie condivise, nessuna reazione. Basterebbe un cuore.',
        'narrator_silence_21d' => 'Nessuna registrazione da tre settimane. Una telefonata spesso sblocca le cose.',
    ],

    'copy_link' => [
        'ready' => 'Ecco il link. Incollalo nel tuo messaggio.',
        'no_story' => 'Nessuna domanda in corso per il momento.',
        'no_family_member' => 'Non hai ancora un link per ascoltare.',
        'whatsapp' => 'Ciao, ecco il link per registrare la tua storia: :link',
    ],

    'one_tap' => [
        'expired' => 'Questo link è già stato usato. Puoi agire dalla tua area personale.',

        'resend_whatsapp' => [
            'title' => 'Reinvia tu il link',
            'body' => 'Un messaggio che arriva da te si nota molto più di un SMS da un numero sconosciuto. Ecco il link da trasmettere.',
            'button' => 'Ottieni il link',
            'done' => 'Ecco il link. Incollalo nel tuo messaggio.',
            'message' => 'Ciao, ecco il link per registrare la tua storia: :link',
            'audio_hint' => 'Un messaggio vocale di trenta secondi funziona ancora meglio: la tua voce si riconosce.',
            'no_question' => 'Tutte le domande sono già state poste. Aggiungine una dalla tua area personale.',
        ],

        'switch_biweekly' => [
            'title' => 'Una domanda ogni quindici giorni',
            'body' => 'Una domanda a settimana forse è troppo. Rallentare il ritmo è meglio che fermarsi, e il libro si costruisce altrettanto bene.',
            'button' => 'Passa a una ogni quindici giorni',
            'done' => 'Fatto: una domanda ogni quindici giorni.',
        ],

        'ack_call_parent' => [
            'title' => 'Preferisci telefonare tu',
            'body' => 'Una telefonata spesso sblocca quello che nessun messaggio riesce a sbloccare. Faccelo sapere, e noi ci faremo da parte.',
            'button' => 'D’accordo, chiamo io',
            'done' => 'Ricevuto. Per ora non invieremo nient’altro.',
        ],

        'offer_phone_option' => [
            'title' => 'La registrazione per telefono',
            'body' => 'Una persona del nostro team chiama chi racconta e registra durante la conversazione. Da parte sua non c’è nulla da fare.',
            'button' => 'Richiedi questa opzione',
            'done' => 'Richiesta inviata. Ti richiamiamo entro 48 ore per organizzare le telefonate.',
            'unavailable' => 'Questa opzione al momento non è disponibile. Scrivici e vediamo insieme.',
        ],

        'react_heart' => [
            'title' => 'Invia un cuore',
            'body' => 'Un cuore su “:title”. È quello che fa venire voglia di raccontare la prossima.',
            'button' => 'Invia un cuore',
            'done' => 'Inviato.',
            'no_story' => 'Nessuna storia condivisa per il momento.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | L'espace de l'Initiateur·rice
    |--------------------------------------------------------------------------
    |
    | Le vocabulaire de ces pages est celui de quelqu'un qui organise, pas de
    | quelqu'un qui surveille : « où en est » et non « a-t-il répondu ». Le
    | narrateur est souverain, y compris face à l'enfant qui a offert le
    | service, et les mots le disent avant que les règles ne l'appliquent.
    |
    */

    'days' => [
        '1' => 'Lunedì',
        '2' => 'Martedì',
        '3' => 'Mercoledì',
        '4' => 'Giovedì',
        '5' => 'Venerdì',
        '6' => 'Sabato',
        '7' => 'Domenica',
    ],

    'nav' => [
        'label' => 'Le pagine della tua area personale',
        'skip' => 'Vai al contenuto',
        'dashboard' => 'Il progetto',
        'questions' => 'Le domande',
        'family' => 'I familiari',
        'book' => 'Il libro',
        'data' => 'I tuoi dati',
        'settings' => 'Le impostazioni',
        'orders' => 'Il mio ordine',
    ],

    'no_project' => [
        'title' => 'Nessun progetto per il momento',
        'body' => 'Appena il tuo ordine è confermato, il progetto compare qui.',
        'cta' => 'Scopri l’offerta',
    ],

    'dashboard' => [
        'title' => 'Il progetto di :name',
        'title_generic' => 'Il tuo progetto',
        'next_prompt' => 'Prossima domanda: :when',
        'next_prompt_none' => 'Nessuna domanda programmata per il momento.',
        'paused_until' => 'Le domande sono in pausa fino al :date.',
        'cadence' => 'Ritmo: :cadence',
        'timeline' => 'Le storie',
        'timeline_empty' => 'Ancora niente. La prima domanda parte presto.',
        'not_shared_yet' => 'Non ancora condivisa',
        'private_notice' => 'Vedi a che punto è ogni storia. Il testo e la voce compaiono solo dopo la condivisione, e a decidere è :name.',
        'copy_link_hint' => 'Un messaggio da parte tua vale più di uno dei nostri. Il link precedente non funzionerà più.',
        'listen' => 'Ascolta come un familiare',
        'listen_hint' => 'Ascolti con il tuo link personale, come gli altri familiari.',
        'listen_open' => 'Apri la mia pagina di ascolto',
        'alerts' => 'Alla tua attenzione',
        'pause' => 'Chiedi una pausa',
        'this_week' => 'La domanda di questa settimana',
        'send_link' => 'Invia il link a :name',
        'send_link_generic' => 'Invia il link',
        'story_number' => 'Storia :n',
        'recorded_on' => 'Registrata il :date',
        'shared_on' => 'Condivisa il :date',
        'share' => [
            'title' => 'Il link è pronto',
            'copy' => 'Copia il link',
            'copied' => 'Copiato',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'hint' => 'Il link precedente non funziona più.',
        ],
    ],

    'questions' => [
        'reordered' => 'L’ordine è salvato.',
        'updated' => 'Salvato.',
        'added' => 'La tua domanda è stata aggiunta.',
        'title' => 'Le domande poste a :name',
        'title_generic' => 'Le domande',
        'intro' => 'Scegli tu l’ordine e puoi scartare quello che non va bene. :name ha sempre il diritto di non rispondere.',
        'asked' => 'Già posta',
        'excluded' => 'Scartata',
        'exclude' => 'Scarta',
        'restore' => 'Ripristina',
        'move_up' => 'Sposta su',
        'move_down' => 'Sposta giù',
        'queue_title' => 'Le prossime domande',
        'queue_intro' => 'In questo ordine, una per invio. Porta in alto quello che ti sta a cuore, scarta quello che non va: si salva subito.',
        'queue_empty' => 'Tutte le domande sono state poste. Aggiungi la tua qui sotto.',
        'first' => 'Metti per prima',
        'position' => 'Domanda :n',
        'see_more' => 'Vedi altre :count',
        'see_less' => 'Riduci',
        'excluded_count' => 'Domande scartate (:count)',
        'asked_count' => 'Già poste (:count)',
        'add' => [
            'title' => 'Fai una domanda tua',
            'label' => 'La tua domanda',
            'hint' => 'Sarà posta così com’è, al posto di una domanda della nostra raccolta.',
            'submit' => 'Aggiungi questa domanda',
            'waiting' => 'Un attimo…',
            'counter' => ':count / :max',
        ],
    ],

    'family' => [
        'invited' => 'L’invito è partito.',
        'link_reissued' => 'Ecco un nuovo link per questa persona.',
        'removed' => 'Questa persona non ha più accesso.',
        'title' => 'I familiari che ascoltano',
        'intro' => 'Ogni persona ha il proprio link. Togliere un accesso toglie solo quello.',
        'empty' => 'Per il momento nessuno, a parte te.',
        'you' => 'Tu',
        'can_contribute' => 'Può aggiungere foto e ricordi',
        'invited_at' => 'Invito inviato il :date',
        'first_seen_at' => 'Ha aperto il link il :date',
        'never_opened' => 'Non ha ancora aperto il link',
        'reissue' => 'Genera un nuovo link',
        'reissue_hint' => 'Il link precedente smette di funzionare.',
        'remove' => 'Togli l’accesso',
        'status_opened' => 'Link aperto',
        'status_pending' => 'Non ancora aperto',
        'link_title' => 'Il nuovo link di :name',
        'remove_confirm' => [
            'title' => 'Togliere l’accesso a :name?',
            'body' => 'Il suo link smetterà di funzionare immediatamente. Potrai rinnovare l’invito più avanti.',
            'confirm' => 'Togli l’accesso',
        ],
        'invite' => [
            'title' => 'Invita un familiare',
            'intro' => 'La persona riceve un link tutto suo, senza account né password.',
            'waiting' => 'Un attimo…',
            'name' => 'Il suo nome',
            'relationship' => 'Il grado di parentela',
            'email' => 'La sua e-mail',
            'phone' => 'Il suo numero di telefono',
            'contact_hint' => 'Basta un’e-mail o un numero.',
            'can_contribute' => 'Autorizza questa persona ad aggiungere foto e ricordi',
            'submit' => 'Invia l’invito',
        ],
    ],

    'settings' => [
        'saved' => 'Le tue impostazioni sono salvate.',
        'lexicon_added' => 'La parola è stata aggiunta al lessico.',
        'lexicon_removed' => 'La parola è stata tolta dal lessico.',
        'paused' => 'D’accordo: nessuna domanda per :weeks settimane.',
        'title' => 'Le impostazioni del progetto',
        'rhythm' => 'Il ritmo',
        'cadence' => 'Frequenza delle domande',
        'day' => 'Giorno di invio',
        'slot' => 'Momento della giornata',
        'address_form' => 'Dare del tu o del lei',
        'locale' => 'Lingua del progetto',
        'locale_help' => 'La lingua delle pagine che vedranno :name e i tuoi familiari. Non cambia la tua.',
        'locale_saved' => 'La lingua del progetto è salvata.',
        'timezone' => 'Fuso orario: :timezone',
        'next_prompt' => 'Prossimo invio: :when',
        'submit' => 'Salva',
        'saved_short' => 'Salvato',
        'waiting' => 'Un attimo…',
        'lexicon' => [
            'title' => 'Il lessico',
            'intro' => 'I nomi propri della tua famiglia: il paese, i soprannomi, l’ortografia esatta. Sei tu a conoscerli, non :name, e nemmeno noi.',
            'term' => 'Quello che si sente',
            'replacement' => 'Quello che va scritto',
            'notes' => 'Una precisazione (facoltativa)',
            'submit' => 'Aggiungi al lessico',
            'remove' => 'Togli',
            'empty' => 'Il lessico è vuoto.',
            'heard' => 'Sentito “:term”',
        ],
        'pause' => [
            'title' => 'Metti le domande in pausa',
            'intro' => 'Una pausa ha sempre una fine, e :name ne sarà al corrente.',
            'weeks' => 'Quante settimane?',
            'fewer' => 'Una settimana in meno',
            'more' => 'Una settimana in più',
            'submit' => 'Metti in pausa',
        ],
        'mandate' => [
            'title' => 'Approvare al posto di :name',
            'body' => 'Questa possibilità esiste per le situazioni in cui :name non può più approvare le sue storie. Richiede il suo consenso esplicito, e finisce non appena lo ritira.',
            'submit' => 'Scopri di più',
        ],
    ],

    'orders' => [
        'top_up_title' => 'Completa il mio ordine',
        'top_up_body' => 'Puoi ancora aggiungere questo. Il resto del tuo ordine non cambia.',
        'top_up_add' => 'Aggiungi — :price',
        'top_up_unavailable' => 'Questa opzione non è più disponibile. Scrivici se pensi che si tratti di un errore.',
        'top_up_done' => 'Aggiunto al tuo ordine.',
        'top_up_sku_phone_option' => 'La registrazione per telefono',
        'top_up_sku_phone_option_hint' => 'Una persona del team chiama ogni settimana, per un quarto d’ora circa, e pone la domanda al posto tuo.',
        'top_up_sku_ebook' => 'Il libro digitale',
        'top_up_sku_ebook_hint' => 'La versione digitale del libro, in aggiunta alla copia rilegata.',
        'withdrawal_requested' => 'La tua richiesta è registrata. Ti rispondiamo entro 48 ore.',
        // Ni refus sec ni silence : on explique la garantie et on donne le
        // contact. Le refus sec est l'occasion parfaite de perdre une famille
        // qu'on aurait pu garder.
        'withdrawal_closed' => 'Il termine di recesso di quattordici giorni è passato. La nostra garanzia “soddisfatti o rimborsati” di trenta giorni può applicarsi: scrivici e vediamo insieme.',
        'title' => 'Il mio ordine',
        'empty' => 'Nessun ordine per il momento.',
        'paid_at' => 'Pagato il :date',
        'total' => 'Totale: :amount',
        'refunded' => 'Rimborsato: :amount',
        'invoice' => 'Vedi la fattura',
        'items' => 'Il dettaglio',
        'withdrawal' => 'Esercita il diritto di recesso',
        'withdrawal_until' => 'Puoi recedere fino al :date, senza dover dare spiegazioni.',
        'withdraw_confirm' => [
            'title' => 'Vuoi esercitare il diritto di recesso?',
            'body' => 'Ti rispondiamo entro 48 ore e il rimborso segue. Se chi racconta ha già iniziato a registrare, ne teniamo conto insieme a te.',
            'confirm' => 'Confermo il recesso',
        ],
        'support' => 'Scrivi all’assistenza',
        'withdrawal_expired' => 'Il termine di quattordici giorni è passato. Se la persona che hai invitato preferisce non partecipare, ti rimborsiamo per intero entro trenta giorni: scrivici a :email.',
        'phone_option' => 'Registrazione per telefono',
        'phone_option_slot' => 'Chiamata prevista il :day, :slot',
    ],

    /*
     * Le livre (bloc 13).
     *
     * Le mot qui gouverne toute la page : « la matière ». R-6 interdit un
     * seuil en nombre d'histoires, et la jauge doit le faire comprendre sans
     * l'expliquer — quatre mesures visibles valent mieux qu'un pourcentage
     * unique qui laisserait croire qu'il suffit d'en enregistrer une de plus.
     *
     * Aucun délai d'impression n'est annoncé : le devis n'est pas fait
     * (doc 03 P0-14).
     */
    'book' => [
        'eyebrow' => 'Il libro',
        'title' => 'Il libro di :first_name',
        'intro' => 'Il libro parte quando c’è abbastanza materiale, non a un certo numero di storie. Ecco a che punto sei.',

        'gauge' => [
            'title' => 'Il materiale raccolto',
            'words' => 'Parole',
            'audio' => 'Minuti di voce',
            'pages' => 'Pagine stimate',
            'themes' => 'Temi trattati',
            'ready' => 'C’è abbastanza per fare un libro.',
            'not_ready' => 'Manca ancora del materiale — e quello che hai già non va perso.',
            // Le verrou réel n'est pas celui des mots : à 280 mots la page,
            // les 12 000 mots du référentiel ne font que 48 pages.
            'pages_hint' => 'Alla fine è il numero di pagine a decidere: un testo denso fa meno pagine di quanto si pensi, e le foto ne aggiungono.',
        ],

        'format' => [
            'title' => 'La forma proposta',
            'current' => 'Forma scelta',
            'proposed' => 'Forma proposta',
            'help' => 'Proponiamo la forma che il materiale permette. Niente ti obbliga a seguirla, e non c’è fretta.',
        ],

        'chapters' => [
            'title' => 'I capitoli',
            'help' => 'Tutte le storie approvate, nell’ordine in cui sono state raccontate. Togli la spunta a quello che non vuoi stampare, e porta in alto quello che deve aprire il libro.',
            'empty' => 'Nessuna storia approvata per il momento.',
            'include' => 'Includi nel libro',
            'move_up' => 'Sposta su',
            'move_down' => 'Sposta giù',
            'to_top' => 'Metti per primo',
            'words' => ':count parole',
            'photos' => ':count foto|:count foto',
            'locked' => 'La selezione è chiusa: il libro è andato in stampa.',
            'qr_revoke' => 'Disattiva il codice di questa storia',
            'qr_restore' => 'Riattiva il codice di questa storia',
        ],

        'foreword' => [
            'title' => 'La tua prefazione',
            'help' => 'Qualche riga in apertura, se lo desideri. Facoltativo.',
            'label' => 'Prefazione',
        ],

        'lexicon' => [
            'title' => 'I nomi propri',
            'help' => 'La trascrizione sbaglia spesso i nomi di luoghi e di persone. Controlla questo elenco prima di stampare: un errore su un nome è quello che si nota.',
            'none' => 'Nessun nome da controllare.',
            'add' => 'Aggiungi al lessico',
        ],

        'proof' => [
            'title' => 'La bozza definitiva (BAT)',
            'help' => 'La bozza definitiva è il libro così come sarà stampato. Rileggila con calma.',
            'generate' => 'Genera la bozza definitiva',
            'regenerate' => 'Rigenera la bozza definitiva',
            'open' => 'Apri la bozza definitiva',
            'version' => 'Versione :number, generata il :date',
            'pages' => ':count pagine',
            'pending' => 'La bozza definitiva è in preparazione. Riceverai un messaggio quando sarà pronta — di solito bastano pochi minuti.',
            'none' => 'Nessuna bozza definitiva per il momento.',
        ],

        'approve' => [
            'title' => 'Approva e ordina',
            'final_print' => 'Capisco che la stampa è definitiva: una volta stampato il libro, non si potrà più correggere nulla.',
            'lexicon_reviewed' => 'Ho ricontrollato i nomi propri e le date.',
            'submit' => 'Approva e ordina',
            'waiting' => 'Un attimo…',
            'help' => 'Non ti annunciamo tempi finché la tipografia non è scelta. Ti avviseremo a ogni passaggio.',
        ],

        'tracking' => [
            'title' => 'A che punto è il tuo libro',
            'approved' => 'Approvato il :date',
            'ordered' => 'Ordinato il :date',
            'printed' => 'Stampato il :date',
            'delivered' => 'Consegnato il :date',
            'extra_copies' => 'Copie aggiuntive',
            'extra_copies_price' => ':price € a copia, al massimo cinque per volta.',
            'order_copies' => 'Ordina',
            'defect' => 'Segnala un difetto di stampa',
            'defect_help' => 'Un libro danneggiato, tagliato male, con le pagine invertite: lo ristampiamo senza condizioni.',
        ],

        /*
         * Le code du livre (doc 04 §7). Le texte doit faire comprendre deux
         * choses en trois lignes : c'est facultatif, et cela se décide une
         * fois pour tous les exemplaires.
         */
        'code' => [
            'title' => 'Proteggi l’ascolto con un codice',
            'help' => 'Per impostazione predefinita, i codici stampati nel libro si aprono senza chiedere nulla: è quello che permette di prestare il libro. Puoi aggiungere un codice, da scrivere sul risvolto o da dire a voce — verrà chiesto una volta, poi ricordato per un mese sul dispositivo.',
            'label' => 'Il codice, almeno quattro caratteri',
            'submit' => 'Imposta questo codice',
            'change' => 'Cambia il codice',
            'remove' => 'Togli il codice',
            'is_set' => 'Un codice protegge l’ascolto. Non possiamo ricordartelo: è cifrato, come una password.',
            'saved' => 'Il codice è impostato. Verrà chiesto alla prossima scansione.',
            'removed' => 'Il codice è stato tolto: i codici del libro si aprono di nuovo senza chiedere nulla.',
        ],

        'saved' => 'Salvato.',
        'rendering' => 'La bozza definitiva è in preparazione. Riceverai un messaggio quando sarà pronta.',
        'ordered' => 'Il tuo libro è ordinato. Ti terremo al corrente a ogni passaggio.',
        'no_chapter' => 'Serve almeno un capitolo per preparare una bozza definitiva.',
    ],

    /*
     * « Mes données » (bloc 14).
     *
     * Le ton dit la non-captivité mieux qu'une promesse : ces fichiers sont à
     * la famille, elle n'a pas à nous remercier de les lui rendre, et
     * l'effacement s'explique sans être découragé.
     */
    'data' => [
        'eyebrow' => 'I tuoi dati',
        'title' => 'I tuoi dati appartengono a te',
        'intro' => 'Puoi recuperare tutto quello che hai registrato, in qualsiasi momento e senza costi. I file si aprono con i programmi che hai già: per leggerli non c’è bisogno di noi.',

        'export' => [
            'title' => 'Scarica i miei dati',
            'help' => 'Prepariamo un archivio completo — le voci, i testi, le foto, il libro se esiste. Riceverai un’e-mail appena è pronto: ci vogliono pochi minuti.',
            'full' => 'Tutto quello che ho registrato',
            'offline' => 'Tutto, con un lettore che funziona senza connessione',
            'gdpr' => 'Tutto, più i miei consensi e il registro dei miei dati',
            'submit' => 'Prepara il mio archivio',
            'waiting' => 'Un attimo…',
            'history' => 'I tuoi ultimi archivi',
            'ready' => 'Pronto, valido fino al :date',
            'building' => 'In preparazione',
            'expired' => 'Link scaduto — puoi richiederne uno nuovo',
            'size' => ':size MB',
        ],

        'erasure' => [
            'title' => 'Cancella il mio progetto',
            'help' => 'La cancellazione elimina definitivamente le registrazioni, i testi e le foto. È irreversibile: non potremo recuperare nulla, nemmeno su tua richiesta.',
            'kept' => 'Quello che conserviamo comunque: le fatture, per la contabilità, e la prova dei consensi dati — senza il tuo nome. Ce lo impone la legge.',
            'narrator_first' => 'Se chi racconta chiede in prima persona la cancellazione, la sua richiesta passa prima della tua: sono i suoi racconti.',
            'delay' => 'Ti confermiamo la cancellazione entro trenta giorni al massimo, e il più delle volte il giorno stesso.',
            'blocked' => 'Un libro di questo progetto è in stampa. La cancellazione avverrà alla consegna, o subito se annulli l’ordine — scrivici.',
            'requested' => 'La tua richiesta di cancellazione è registrata. Ti ricontattiamo molto presto.',
            'confirm_label' => 'Per confermare, digita EFFACER',
            'submit' => 'Richiedi la cancellazione',
        ],

        'export_queued' => 'Il tuo archivio è in preparazione. Riceverai un’e-mail appena è pronto.',
        'erasure_requested' => 'La tua richiesta è registrata. Ti rispondiamo entro trenta giorni al massimo.',
    ],
];
