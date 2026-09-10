<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Espace narrateur·rice
|--------------------------------------------------------------------------
|
| Langage simple, phrases courtes, aucune formulation technique. Une erreur
| dit toujours quoi faire ensuite (convention §16). Le vouvoiement est le
| réglage par défaut du projet.
|
*/

return [

    'link_unavailable' => [
        'not_found' => [
            'title' => 'Questo link non funziona',
            'body' => 'Forse il link è incompleto. Controlli di averlo aperto per intero, dal messaggio che ha ricevuto.',
        ],
        'expired' => [
            'title' => 'Questo link è scaduto',
            'body' => 'I link restano validi solo per un certo tempo, per la sua sicurezza. Può chiederne uno nuovo.',
        ],
        'revoked' => [
            'title' => 'Questo link non è più valido',
            'body' => 'È stato sostituito, oppure la sua storia è già registrata. Può chiedere un nuovo link.',
        ],
        'used' => [
            'title' => 'Questo link è già stato usato',
            'body' => 'Funzionava una volta sola. Se ha ancora qualcosa da fare, chieda un nuovo link.',
        ],
        'type_mismatch' => [
            'title' => 'Questo link non porta qui',
            'body' => 'Corrisponde a un’altra pagina. Apra il link dal messaggio che ha ricevuto.',
        ],
        'request_new_link' => 'Chiedi un nuovo link',
        'request_sent' => 'D’accordo. Le rimandiamo un link al più presto.',
        'help' => 'Ha bisogno di aiuto? Ci scriva a :email.',
    ],

    'otp' => [
        'title' => 'Il suo codice di conferma',
        'intro' => 'Abbiamo inviato un codice di 6 cifre a :destination.',
        'intro_no_code' => 'Per continuare, dobbiamo inviarle un codice di 6 cifre.',
        'code_label' => 'Codice di 6 cifre',
        'submit' => 'Conferma',
        'send' => 'Invia il codice',
        'resend' => 'Invia di nuovo il codice',
        'sent' => 'Codice inviato. Arriva tra qualche secondo.',
        'already_sent' => 'Le è già stato inviato un codice. Usi quello: è ancora valido.',
        'invalid' => 'Questo codice non corrisponde. Controlli le sei cifre e riprovi.',
        'expired' => 'Questo codice non è più valido. Ne chieda uno nuovo.',
        'locked' => 'Troppi tentativi. Aspetti quindici minuti, poi chieda un nuovo codice.',
        'warning' => 'Non comunichi questo codice a nessuno, nemmeno a chi dicesse di chiamare da parte nostra.',
    ],

    'record' => [
        /*
         * Écran 0 — la forme (T-210). Il vient avant l'explication, donc
         * avant toute demande d'autorisation : la voix et la caméra ne se
         * demandent pas de la même façon, et faire surgir l'objectif sur
         * quelqu'un qui pensait parler se solderait par un refus réflexe.
         *
         * La voix est le premier bouton, et le principal. Ce n'est pas un
         * hasard : c'est ce que le produit sait faire de mieux, se filmer
         * intimide, et le livre se lit avec une voix dans l'oreille.
         */
        'mode_title' => 'Come preferisce rispondere, :name?',
        'mode_title_tu' => 'Come preferisci rispondere, :name?',
        'mode_audio' => 'Con la voce',
        'mode_video' => 'Con il video',
        'mode_help' => 'Basta la voce, ed è la voce che accompagnerà il libro. Filmarsi aggiunge il suo viso, per la sua famiglia.',
        'mode_help_tu' => 'Basta la voce, ed è la voce che accompagnerà il libro. Filmarti aggiunge il tuo viso, per la tua famiglia.',

        /*
         * L'écran caméra (T-212). Il prend tout l'écran, la question posée
         * dessus s'efface dès que ça tourne, et « Sortir » n'existe que tant
         * que rien n'a été dit — après, on passe par « Terminer », qui garde
         * ce qui a été raconté.
         */
        'video_stage' => 'Filmarsi',
        'mode_exit' => 'Esci',

        // Écran 1 — explication. Elle précède toujours la demande de micro :
        // une autorisation qui surgit sans prévenir se refuse par réflexe.
        'greeting' => ':name, ecco la sua domanda della settimana',
        'greeting_tu' => ':name, ecco la tua domanda della settimana',
        'mic_notice' => 'Quando premerà il pulsante, il telefono le chiederà il permesso di usare il microfono. Scelga “Consenti”.',
        'mic_notice_tu' => 'Quando premerai il pulsante, il telefono ti chiederà il permesso di usare il microfono. Scegli “Consenti”.',
        'camera_notice' => 'Quando premerà il pulsante, il telefono le chiederà il permesso di usare il microfono e la fotocamera. Scelga “Consenti”. Si vedrà sullo schermo prima di cominciare.',
        'camera_notice_tu' => 'Quando premerai il pulsante, il telefono ti chiederà il permesso di usare il microfono e la fotocamera. Scegli “Consenti”. Ti vedrai sullo schermo prima di cominciare.',
        'ready' => 'Ho capito, avanti',

        // Écran 2 — permission.
        'requesting' => 'Il telefono le chiederà il permesso. Scelga “Consenti”.',

        // Écran 3 — enregistrement.
        'start' => 'Inizia',
        'tap_hint' => 'Prema, poi parli come al telefono. Si prenda tutto il tempo che vuole.',
        'tap_hint_tu' => 'Premi, poi parla come al telefono. Prenditi tutto il tempo che vuoi.',
        'tap_hint_video' => 'Se può, si metta davanti a una finestra, poi prema e racconti. Si prenda tutto il tempo che vuole.',
        'tap_hint_video_tu' => 'Se puoi, mettiti davanti a una finestra, poi premi e racconta. Prenditi tutto il tempo che vuoi.',
        'pause' => 'Pausa',
        'resume' => 'Riprendi',
        'finish' => 'Ho finito',
        'recording' => 'Registrazione in corso',
        'paused' => 'In pausa',
        'elapsed' => 'Durata: :time',
        'soft_warning' => 'Sta parlando da dieci minuti. Si prenda tutto il tempo che vuole: la registrazione si fermerà da sola a venti minuti.',
        'hard_stop' => 'La registrazione si è fermata a venti minuti. Quello che ha detto è conservato: può inviarlo.',
        'interrupted' => 'La registrazione si è interrotta. Quello che ha detto è conservato.',
        'interrupted_resume' => 'Continua la storia',
        'level_label' => 'Livello del microfono',

        // Écran 4 — vérification.
        'review_title' => 'Vuole riascoltarsi?',
        'review_title_video' => 'Vuole rivedersi?',
        'review_body' => 'Se va bene così, prema “Invia”. Altrimenti può ricominciare.',
        'listen' => 'Riascolta',
        'send' => 'Invia',
        'restart' => 'Ricomincia',
        'restart_confirm' => 'Ricominciare cancellerà quello che ha appena registrato. Continuare?',

        // Écran 5 — envoi.
        'uploading' => 'Invio in corso',
        'uploading_notice' => 'Non chiuda questa pagina. Se dovesse succedere, la registrazione resta comunque salvata sul suo telefono.',
        'upload_failed_title' => 'L’invio non è riuscito',
        'upload_failed_body' => 'La sua registrazione è salvata sul suo telefono. Può riprovare.',
        'retry' => 'Riprova',

        // Écran 6 — confirmation.
        'confirmed_title' => 'La sua storia è stata registrata',
        'confirmed_body' => 'Grazie, :name.',
        'confirmed_next' => 'Ora la mettiamo in bella copia. La rileggerà prima che qualcuno la ascolti.',

        // Brouillon retrouvé au chargement.
        'draft_title' => 'Ha una registrazione in corso',
        'draft_body' => 'Abbiamo ritrovato quello che aveva cominciato a raccontare.',
        'draft_resume' => 'Riprendi la registrazione',
        'draft_discard' => 'Ricomincia',
        'storage_low' => 'Sul suo telefono c’è poco spazio. La registrazione funziona, ma eviti le risposte molto lunghe.',

        'written_link' => 'Rispondere per iscritto',
        'question_label' => 'La sua domanda',
    ],

    /*
     * L'aide quand c'est la caméra qui a été refusée (T-210). Un texte à
     * part, et pas une variante du précédent : envoyer quelqu'un dans le
     * réglage du micro alors qu'il a refusé la caméra le ferait tourner en
     * rond. La voix reste offerte comme issue, avant l'écrit.
     */
    'camera_help' => [
        'title' => 'La fotocamera non è autorizzata',
        'body' => 'Senza fotocamera non possiamo riprenderla. Ecco come autorizzarla; può anche rispondere solo con la voce.',
        'retry' => 'Riprova',
        'ios' => 'Su iPhone: apra Impostazioni, scorra fino a Safari, tocchi Fotocamera, poi scelga “Chiedi” o “Consenti”. Poi torni su questa pagina.',
        'android' => 'Su Android: tocchi il lucchetto a sinistra dell’indirizzo, in alto sullo schermo, poi Autorizzazioni, poi Fotocamera, e scelga “Consenti”.',
        'samsung' => 'Su Samsung Internet: tocchi il lucchetto a sinistra dell’indirizzo, poi Autorizzazioni, poi Fotocamera, e scelga “Consenti”.',
        'other' => 'Cerchi l’icona del lucchetto accanto all’indirizzo di questa pagina, poi autorizzi la fotocamera.',
        'unsupported' => 'Il suo browser non permette di filmare. Può rispondere con la voce, per iscritto, oppure scriverci: la chiameremo noi.',
    ],

    'mic_help' => [
        'title' => 'Il microfono non è autorizzato',
        'body' => 'Senza microfono non possiamo registrare la sua voce. Ecco come autorizzarlo.',
        'retry' => 'Riprova',
        'ios' => 'Su iPhone: apra Impostazioni, scorra fino a Safari, tocchi Microfono, poi scelga “Chiedi” o “Consenti”. Poi torni su questa pagina.',
        'android' => 'Su Android: tocchi il lucchetto a sinistra dell’indirizzo, in alto sullo schermo, poi Autorizzazioni, poi Microfono, e scelga “Consenti”.',
        'samsung' => 'Su Samsung Internet: tocchi il lucchetto a sinistra dell’indirizzo, poi Autorizzazioni, poi Microfono, e scelga “Consenti”.',
        'other' => 'Cerchi l’icona del lucchetto accanto all’indirizzo di questa pagina, poi autorizzi il microfono.',
        'unsupported' => 'Il suo browser non permette di registrare l’audio. Può rispondere per iscritto, oppure scriverci: la chiameremo noi.',
    ],

    'written_answer' => [
        'title' => 'Rispondere per iscritto',
        'body' => 'Scriva quello che avrebbe raccontato. Anche questa è una storia.',
        'label' => 'La sua risposta',
        'counter' => ':count caratteri su :max',
        'send' => 'Invia',
        'sent' => 'Grazie, la sua risposta è stata salvata.',
    ],

    /*
     * Les trois choix de fin d'enregistrement (bloc 07).
     *
     * Toujours dans cet ordre, jamais présélectionnés, sans minuteur : le
     * dossier est formel, l'absence de réaction ne vaut jamais accord. Chaque
     * choix dit sa conséquence en une phrase, au présent, sans jargon.
     */
    'share_decision' => [
        'title' => 'Cosa desidera fare di questa storia?',
        'body' => 'È il suo racconto. Decide lei, e potrà cambiare idea.',
        'share' => [
            'label' => 'Condividerla con i miei cari',
            'hint' => 'I suoi cari potranno ascoltarla e leggere il testo.',
        ],
        'keep_private' => [
            'label' => 'Tenerla per me',
            'hint' => 'Nessuno oltre a lei la ascolterà.',
        ],
        'decide_later' => [
            'label' => 'Decidere più tardi',
            'hint' => 'Glielo chiederemo di nuovo, senza insistere.',
        ],
        'recorded' => [
            'share' => 'D’accordo: i suoi cari potranno ascoltarla appena il testo sarà pronto.',
            'keep_private' => 'D’accordo: questa storia resta solo per lei.',
            'decide_later' => 'D’accordo: glielo chiederemo di nuovo più tardi.',
        ],
        'change' => 'Cambia risposta',
    ],

    /*
     * Relecture (bloc 07). Le mot à mot n'est pas caché : c'est la parole de
     * la personne, et elle a le droit de vérifier ce que la machine en a fait.
     */
    'review' => [
        'title' => 'La sua storia è pronta',
        'body' => 'La rilegga, la corregga se vuole, poi ci dica cosa desidera farne.',
        // Trois temps, numérotés à l'écran : on écoute, on relit, on décide.
        'steps' => [
            'listen' => 'Ascolti',
            'read' => 'Rilegga',
            'decide' => 'Decida',
        ],
        'decide_body' => 'È il suo racconto. Decide lei, e potrà cambiare idea dalla pagina delle sue storie.',
        'listen' => 'Ascolta la registrazione',
        'tab_fluide' => 'Versione scorrevole',
        'tab_verbatim' => 'Parola per parola',
        'edit' => 'Correggi il testo',
        'edit_label' => 'Il suo testo',
        'edit_help' => 'Cambi quello che vuole. La registrazione, invece, resta com’è.',
        'save' => 'Salva la correzione',
        'cancel' => 'Annulla',
        'saved' => 'La sua correzione è salvata.',
        'empty' => 'Il testo non può essere vuoto.',
        'no_audio' => 'La registrazione non è ancora disponibile all’ascolto.',
        'visibility' => [
            'title' => 'Chi può ascoltare?',
            'all_family' => 'Tutti i miei cari',
            'choose' => 'Scegliere chi può ascoltare',
            'book_only' => 'Solo per il libro',
            'book_only_hint' => 'Sarà stampata, ma nessuno la ascolterà online.',
        ],
        'keep_for_book' => 'Tenere questa storia per il libro',
        'keep_for_book_hint' => 'Sarà stampata, senza che nessuno la ascolti online.',
        /*
     * Opt-in (bloc 10). Le moment H0. Deux principes de rédaction : on
     * explique avant de demander, et on n'attend rien. « Non merci » est un
     * bouton de même taille que « J'accepte » — rendre le refus discret ne
     * produit pas des oui, ça produit des gens qui ne répondent pas.
     */
        'optin' => [
            'title' => ':inviter le regala un libro dei suoi ricordi',
            'intro' => 'Ecco di cosa si tratta, e cosa significa per lei. Si prenda il tempo di leggere: niente comincia senza il suo accordo.',
            'what_it_means' => [
                'one' => 'Ogni settimana riceve una domanda. Risponde parlando, dal suo telefono, quando vuole.',
                'two' => 'Mettiamo il suo racconto in bella copia. Lo rilegge, lo corregge se vuole, e **lei** decide chi può ascoltarlo.',
                'three' => 'Alla fine, le sue storie diventano un libro. Può fermarsi, fare una pausa o cancellare tutto in qualsiasi momento.',
            ],
            'consents_title' => 'I suoi consensi',
            'consents_intro' => 'Ognuno è distinto, e ognuno si può ritirare quando vuole.',
            'read' => 'Leggere il testo',
            'sensitive_title' => 'Argomenti personali',
            'preferences_title' => 'Come preferisce che la contattiamo',
            'channel' => 'In che modo?',
            'cadence' => 'Con che ritmo?',
            'day' => 'Che giorno?',
            'slot' => 'In che momento della giornata?',
            'phone_confirm' => 'Il suo numero',
            'address_form' => 'Come preferisce che ci rivolgiamo a lei?',
            'accept' => 'Accetto',
            'refuse' => 'No, grazie',
            'accepted' => 'D’accordo. Un caro benvenuto.',
            'refuse_title' => 'Preferisce di no',
            'refuse_intro' => 'È una sua scelta, e la rispettiamo. Può dirci il perché, se vuole. Non è obbligatorio.',
            'refuse_confirm' => 'Confermo il mio no',
            'welcome' => [
                'title' => 'Un caro benvenuto, :first_name',
                'body' => 'La sua prima domanda arriverà il :date. Fino ad allora non deve fare nulla.',
                'body_soon' => 'La sua prima domanda arriverà presto. Fino ad allora non deve fare nulla.',
                'vcard' => 'Aggiungi il nostro contatto al telefono',
                'vcard_why' => 'Le sue domande arriveranno da questo numero: salvarlo evita che sembrino un messaggio sconosciuto.',
                'wishes_title' => 'I suoi desideri per il futuro',
                'wishes_intro' => 'Può dire già ora cosa desidera che accada alle sue storie. Oppure farlo più tardi, o mai.',
                'wishes_later' => 'Più tardi',
            ],
            'farewell' => [
                'title' => 'D’accordo',
                'body' => 'Grazie di avercelo detto. Non le scriveremo più su questo argomento, e i suoi dati di contatto saranno cancellati entro trenta giorni.',
            ],
        ],

        'thanks' => [
            'share' => 'Grazie. I suoi cari possono ora ascoltare questa storia.',
            'keep_private' => 'Grazie. Questa storia resta solo per lei.',
            'decide_later' => 'Grazie. Glielo chiederemo di nuovo più tardi.',
        ],
    ],

    'thanks' => [
        'title' => 'Grazie',
        'body' => 'Può chiudere questa pagina.',
    ],

    /*
     * Espace narrateur (bloc 07).
     *
     * Aucun nom d'état technique n'apparaît : un narrateur ne lit pas
     * « transcribed », il lit « en attente de votre choix ». Les libellés
     * disent où en est l'histoire *de son point de vue*, pas du point de vue
     * de la machine.
     */
    'space' => [
        'title' => 'Le sue storie',
        'empty' => 'Non ha ancora nessuna storia registrata.',
        'empty_hint' => 'Compariranno qui settimana dopo settimana, dopo ogni domanda.',
        'actions' => 'Cosa può fare di questa storia',
        'pause_title' => 'Ha bisogno di una pausa?',
        'pause_body' => 'In questo periodo non partirà nessuna domanda. Riprenderà quando vorrà.',
        'pause_fewer' => 'Una settimana in meno',
        'pause_more' => 'Una settimana in più',
        'request' => [
            'title' => 'Aprire le sue storie',
            'body' => 'Indichi il numero di telefono o l’indirizzo e-mail su cui riceve le sue domande. Le invieremo un codice.',
            'label' => 'Numero o indirizzo e-mail',
            'send' => 'Ricevi un codice',
            // La même phrase, que la coordonnée soit connue ou non : une
            // réponse différente ferait de cette page un annuaire.
            'sent' => 'Se la conosciamo, un codice è appena partito. È valido per qualche minuto.',
            'already_sent' => 'Le è già stato inviato un codice. Usi quello: è ancora valido.',
            'have_code' => 'Ho già un codice',
            'code_label' => 'Il suo codice',
            'verify' => 'Apri le mie storie',
        ],
        'states' => [
            'recorded' => 'Registrata, trascrizione in corso',
            'transcribed' => 'Tenuta per lei',
            'to_review' => 'In attesa della sua scelta',
            'validated' => 'Approvata',
            'shared' => 'Condivisa con i suoi cari',
            'in_book' => 'Nel libro',
            'hidden' => 'Nascosta',
            'archived' => 'Archiviata',
            'trashed' => 'Nel cestino',
            'deleted' => 'Eliminata',
        ],
        'restorable_until' => 'Recuperabile fino al :date',
        'pause' => 'Chiedi una pausa',
        'pause_weeks' => 'Per quante settimane?',
        'paused' => 'D’accordo: nessuna domanda per :weeks settimane.',
        'paused_until' => 'Le sue domande sono in pausa fino al :date.',
        /*
         * Le QR imprimé dans le livre (bloc 13). Le mot « désactiver » et non
         * « supprimer » : le livre reste, c'est l'écoute en ligne qui s'arrête,
         * et la distinction compte pour quelqu'un qui a le livre en main.
         */
        'qr' => [
            'title' => 'Il codice del libro',
            'help' => 'Ogni storia stampata ha un codice che permette di sentire la sua voce. Può spegnerlo: il libro resta, l’ascolto online si ferma.',
            'revoke' => 'Disattiva il codice di questa storia',
            'restore' => 'Riattiva il codice',
            'revoked' => 'Il codice è disattivato. Il testo stampato resta com’è.',
            'restored' => 'Il codice funziona di nuovo, lo stesso di prima.',
        ],

    ],

    /*
     * Retraits (bloc 07). Cinq gestes, du plus doux au définitif. Chacun dit
     * ce qu'il fait et ce qu'il ne fait pas : « vous pourrez la remettre »
     * n'est pas une formule de politesse, c'est l'information qui permet
     * d'oser.
     */
    'withdrawals' => [
        'hide' => 'Nascondi questa storia',
        'hide_confirm' => 'Nascondere questa storia? Potrà rimetterla più tardi.',
        'hidden' => 'Questa storia è nascosta. Può rimetterla quando vuole.',
        'unhide' => 'Rimetti questa storia',
        'unhidden' => 'Questa storia è di nuovo visibile.',
        'trash' => 'Sposta nel cestino',
        'trash_confirm' => 'Spostare nel cestino? Avrà trenta giorni per recuperarla.',
        'trashed' => 'Questa storia è nel cestino. Ha trenta giorni per recuperarla.',
        'restore' => 'Recupera questa storia',
        'restored' => 'Questa storia è stata recuperata.',
        'restore_window_closed' => 'I :days giorni sono passati: questa storia non può più essere recuperata.',
        'delete' => 'Elimina definitivamente',
        'delete_confirm' => 'Questa eliminazione è definitiva: la registrazione e il testo saranno cancellati, e non potremo più ritrovarli.',
        'delete_word' => 'ELIMINA',
        'delete_word_label' => 'Scriva ELIMINA per confermare',
        'delete_word_missing' => 'Scriva :word in maiuscolo per confermare l’eliminazione.',
        'deleted' => 'Questa storia è stata eliminata.',
        // Ce qui est imprimé est imprimé : le dire est la seule honnêteté
        // possible, et le taire serait promettre l'impossible.
        'printed_copies_warning' => 'Questa storia è in un libro già stampato. La togliamo dall’area online e dalle prossime stampe, ma non possiamo cambiare nulla nelle copie già a casa sua.',
        'visibility_changed' => 'D’accordo: solo i familiari che ha scelto possono ascoltare.',
    ],

    'already_recorded' => [
        'title' => 'Ha già risposto a questa domanda',
        'title_with_date' => 'Ha già risposto a questa domanda il :date',
        'body' => 'Può ricominciare se preferisce un’altra versione. La sua prima registrazione resta conservata.',
        'restart' => 'Ricomincia',
        'close' => 'Chiudi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Opt-in : le moment H0
    |--------------------------------------------------------------------------
    |
    | La page qui décide de tout. Elle explique avant de demander, elle ne
    | propose aucun enregistrement, et ses deux boutons sont de même taille :
    | un non franc vaut mieux qu'un silence.
    |
    */

    'optin' => [
        'greeting' => 'Buongiorno :name,',
        'title' => ':inviter ha un regalo per lei',
        'from' => 'Un messaggio da :inviter',
        'listen_message' => 'Ascolta il messaggio',

        /*
         * L'ouverture (T-232) : un écran vide, le nom de marque, puis trois
         * phrases qui se succèdent avant la page. Le prénom reprend
         * `greeting` ; `greeting` d'ici sert quand on ne le connaît pas. Ce
         * qu'elles disent se retrouve sur la page : personne ne perd rien à
         * lire lentement.
         */
        'overture' => [
            'greeting' => 'Buongiorno,',
            'offered' => ':inviter le ha fatto un regalo.',
            'promise' => 'I suoi ricordi, con la sua voce, per i suoi cari.',
        ],

        'means' => [
            'title' => 'Cosa significa per lei',
            'one' => 'Riceve una domanda a settimana, e risponde parlando, dal suo telefono. Bastano due minuti.',
            'two' => 'Rilegge il testo prima che qualcuno lo veda, e decide lei, e solo lei, cosa condividere. Niente parte senza il suo accordo.',
            'three' => 'Può fermarsi, nascondere una storia o cancellare tutto in qualsiasi momento, senza doversi giustificare.',
        ],

        /*
         * Les cinq accords sont donnés par le bouton « J'accepte », sans case
         * à cocher (T-233). Rien n'est pré-coché : un accord donné par un
         * geste explicite n'est pas une case remplie d'avance. Ils restent
         * cinq lignes distinctes dans le journal, chacune révocable seule.
         * `before_accept` se lit juste au-dessus du bouton et nomme les cinq :
         * leurs textes complets sont repliés sous les boutons.
         */
        'consents' => [
            'title' => 'I suoi consensi',
            'summary' => 'I testi completi dei cinque consensi, e la loro versione.',
            'before_accept' => 'Premendo “Accetto”, dà i cinque consensi descritti più sotto: la registrazione della sua voce, la sua trascrizione, la messa in forma del testo da parte di un’intelligenza artificiale, la condivisione con i suoi cari, e gli argomenti delicati che i suoi racconti possono toccare.',
            'intro' => 'Tocchi un consenso per leggerne il testo. Ognuno è separato, e ognuno si può ritirare quando vuole, indipendentemente dagli altri.',
            'version' => 'Versione :version',
        ],

        'settings' => [
            'title' => 'Come la contattiamo',
            'hint' => 'È già tutto impostato dalla persona che le fa questo regalo. Cambi solo quello che non le va bene.',
            'channel' => 'In che modo?',
            'phone' => 'Il suo numero di telefono',
            'phone_hint' => 'Come lo scrive di solito, per esempio 06 12 34 56 78.',
            'phone_confirm' => 'Le scriveremo a questo numero: è corretto?',
            'phone_required' => 'Ci serve un numero per inviarle gli SMS.',
            'email' => 'Il suo indirizzo e-mail',
            'email_hint' => 'Qui invieremo ogni domanda.',
            'email_required' => 'Ci serve un indirizzo per inviarle le e-mail.',
            'cadence' => 'Con che frequenza?',
            'day' => 'Che giorno?',
            'slot' => 'In che momento della giornata?',
            'address_form' => 'Preferisce che le diamo del “lei” o del “tu”?',
        ],

        /*
         * Les souhaits pour plus tard, repliés sous les accords (T-236) :
         * « transmettre à ma famille » est proposé d'avance parce que c'est ce
         * qui arrivera de toute façon sans directive (doc 04 §6). Rien n'est
         * écrit tant que la personne ne choisit pas autre chose ou ne désigne
         * personne. La ligne sous le titre le dit sans qu'il faille ouvrir.
         */
        'advanced' => [
            'title' => 'Impostazioni avanzate',
            'summary' => 'Dopo di lei: le sue storie potranno essere trasmesse alla sua famiglia, salvo diversa scelta.',
            'wishes_title' => 'I suoi desideri per il futuro',
            'wishes_body' => 'Cosa fare delle sue storie dopo la sua scomparsa. I suoi desideri contano più della richiesta dei suoi cari, e potrà cambiarli quando vorrà.',
            'referent' => 'La persona a cui ci rivolgeremo (facoltativo)',
            'referent_contact' => 'Come raggiungerla, telefono o e-mail (facoltativo)',
        ],

        'days' => [
            '1' => 'Lunedì',
            '2' => 'Martedì',
            '3' => 'Mercoledì',
            '4' => 'Giovedì',
            '5' => 'Venerdì',
            '6' => 'Sabato',
            '7' => 'Domenica',
        ],

        'accept' => 'Accetto',
        'refuse' => 'No, grazie',
        'accepted' => 'D’accordo. Un caro benvenuto.',
        'already_answered' => 'Ha già risposto a questo invito. Se desidera cambiare idea, ci scriva a :email.',
        'no_password' => 'Questa pagina non le chiederà mai una password, un pagamento né un codice.',

        'refusal' => [
            'title' => 'Preferisce di no',
            'body' => 'È una sua scelta, e la rispettiamo. Vuole dirci il perché? Non è obbligatorio.',
            'no_reason' => 'Preferisco non dire nulla',
            'confirm' => 'Confermo il mio no',
            'back' => 'Torna indietro',
        ],
    ],

    'optin_welcome' => [
        'title' => 'Un caro benvenuto, :name',
        'body' => 'La sua prima domanda arriva :when. Non deve installare né preparare nulla.',
        'when_unknown' => 'molto presto',
        'vcard' => [
            'title' => 'Ci aggiunga ai suoi contatti',
            'body' => 'I nostri messaggi arriveranno sempre da questo contatto. Se le arriva un messaggio da altrove che ci imita, è falso.',
            'button' => 'Aggiungi il contatto',
        ],
        /*
         * Plus de question ici (T-236) : les souhaits se choisissent à
         * l'acceptation. On dit ce qui vaut, et où le changer.
         */
        'wishes' => [
            'title' => 'I suoi desideri per il futuro',
            'default' => 'Salvo sua diversa scelta, le sue storie potranno essere trasmesse alla sua famiglia. Potrà precisarlo quando vorrà, dalla sua area personale.',
            'saved' => 'I suoi desideri sono salvati. Potrà cambiarli quando vorrà.',
        ],
    ],

    'optin_farewell' => [
        'title' => 'D’accordo',
        'body' => 'Non le scriveremo più su questo argomento. I suoi dati di contatto saranno cancellati entro trenta giorni.',
        'reassure' => 'La persona che le ha fatto questo invito è avvisata con delicatezza, e sarà rimborsata se lo desidera.',
    ],

];
