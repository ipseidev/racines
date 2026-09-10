<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Libellés des énumérations de domaine
|--------------------------------------------------------------------------
|
| Une clé par valeur d'énumération, appelée par la méthode `label()`. Le
| vocabulaire suit le référentiel doc 05 et la liste d'expressions proscrites
| de R-11, que `tests/Unit/ForbiddenVocabularyTest.php` vérifie ici même.
|
*/

return [

    'project_status' => [
        'draft' => 'Bozza',
        'awaiting_acceptance' => 'In attesa di accettazione',
        'active' => 'In corso',
        'paused' => 'In pausa',
        'dormant' => 'Inattivo',
        'completed' => 'Concluso',
        'cancelled' => 'Annullato',
        'frozen_bereavement' => 'Congelato (lutto)',
    ],

    'offer' => [
        'pilot' => 'Pilota',
        'core' => 'Offerta principale',
        'prevente' => 'Prevendita',
    ],

    'address_form' => [
        'vous' => 'Dare del lei',
        'tu' => 'Dare del tu',
    ],

    'cadence' => [
        'weekly' => 'Una domanda a settimana',
        'biweekly' => 'Una domanda ogni quindici giorni',
    ],

    'prompt_slot' => [
        'morning' => 'Mattina',
        'afternoon' => 'Pomeriggio',
        'evening' => 'Sera',
    ],

    // À quel point la personne qui racontera est à l'aise avec un téléphone.
    // Posé à l'achat pour un proche, afin d'adapter ce qu'on lui propose
    // (T-136) : l'option téléphone est recommandée aux deux derniers.
    'tech_comfort' => [
        'daily' => 'Molto a suo agio: il telefono fa parte della sua vita quotidiana',
        'sometimes' => 'Dipende: gli SMS sì, aprire un link a volte meno',
        'rarely' => 'Poco a suo agio: spesso ha bisogno di aiuto',
        'no_smartphone' => 'Non ha uno smartphone',
    ],

    'channel' => [
        'sms' => 'SMS',
        'email' => 'E-mail',
        'both' => 'SMS ed e-mail',
        'phone_operator' => 'Telefono (operatore)',
    ],

    'outbound_message_status' => [
        'queued' => 'In attesa di invio',
        'sent' => 'Accettato dall’operatore',
        'delivered' => 'Consegnato',
        'failed' => 'Invio non riuscito',
        'bounced' => 'Indirizzo rifiutato',
        'undelivered' => 'Non consegnato',
    ],

    'recording_source' => [
        'browser' => 'Browser',
        'phone_operator' => 'Telefonata',
        'upload_admin' => 'Caricato dall’assistenza',
    ],

    'upload_status' => [
        'initiated' => 'Aperto',
        'uploading' => 'Caricamento in corso',
        'completed' => 'Caricato',
        'failed' => 'Non riuscito',
        'aborted' => 'Annullato',
    ],

    'client_event_name' => [
        'mic_denied' => 'Microfono negato',
        'mic_granted' => 'Microfono autorizzato',
        'recorder_unsupported' => 'Browser non in grado di registrare',
        'recording_started' => 'Registrazione avviata',
        'recording_paused' => 'Registrazione in pausa',
        'recording_resumed' => 'Registrazione ripresa',
        'recording_stopped' => 'Registrazione terminata',
        'page_hidden' => 'Pagina abbandonata',
        'interrupted' => 'Registrazione interrotta',
        'resumed_from_draft' => 'Ripresa dalla bozza',
        'draft_discarded' => 'Bozza scartata',
        'soft_warning_reached' => 'Avviso di durata raggiunto',
        'hard_stop_reached' => 'Arresto alla durata massima',
        'upload_started' => 'Caricamento avviato',
        'upload_retried' => 'Caricamento ritentato',
        'upload_failed' => 'Caricamento non riuscito',
        'storage_quota_low' => 'Poco spazio sul dispositivo',
        'written_answer_chosen' => 'Scelta la risposta scritta',
        // Bloc 10 : quelqu'un a répondu « vous-même » à la première
        // étape du tunnel. C'est une information de marché, pas une
        // erreur de saisie.
        'self_narration_interest' => 'Interesse a raccontare la propria storia',
        // T-210 : la forme choisie, mesurée avant toute autorisation.
        'camera_denied' => 'Fotocamera negata',
        'camera_granted' => 'Fotocamera autorizzata',
        'video_chosen' => 'Scelto il video',
        'audio_chosen' => 'Scelta la sola voce',
    ],

    'share_decision' => [
        'share' => 'Condividere con i familiari',
        'keep_private' => 'Tenere per me',
        'decide_later' => 'Decidere più tardi',
    ],

    'validated_via' => [
        'recording_end' => 'Alla fine della registrazione',
        'post_transcription' => 'Dopo la rilettura del testo',
        'mandate' => 'Dalla persona delegata',
        'phone_operator' => 'Accordo verbale raccolto al telefono',
    ],

    'story_visibility' => [
        'all_family' => 'Tutti i familiari',
        'restricted' => 'Solo alcuni familiari',
        'book_only' => 'Solo nel libro',
    ],

    'answer_type' => [
        'audio' => 'Voce',
        'text' => 'Testo scritto',
        'phone' => 'Telefono',
        'video' => 'Video',
    ],

    'recording_kind' => [
        'audio' => 'Voce',
        'video' => 'Video',
    ],

    'order_status' => [
        'pending' => 'In attesa di pagamento',
        'paid' => 'Pagato',
        'refunded' => 'Rimborsato',
        'partially_refunded' => 'Parzialmente rimborsato',
        'cancelled' => 'Annullato',
    ],

    'sku' => [
        'pilot' => 'Offerta pilota',
        'core_prevente' => 'Prevendita',
        'extra_copy' => 'Copia aggiuntiva',
        'phone_option' => 'Registrazione per telefono',
        'ebook' => 'Libro digitale',
    ],

    'phone_option_entry' => [
        'checkout' => 'Acquistata con l’ordine',
        'complement' => 'Aggiunta dopo l’ordine',
        'rescue' => 'Proposta come rimedio',
    ],

    'phone_option_status' => [
        'requested' => 'Richiesta',
        'active' => 'Attiva',
        'cancelled' => 'Annullata',
        'refunded' => 'Rimborsata',
    ],

    'post_mortem_wish' => [
        'transfer_to_family' => 'Trasmettere alla mia famiglia',
        'freeze' => 'Congelare, senza trasmettere nulla',
        'delete' => 'Eliminare tutto',
    ],

    'refusal_reason' => [
        'not_the_right_time' => 'Non è il momento giusto',
        'prefer_not_to' => 'Preferisco di no',
        'other' => 'Altro',
    ],

    'support_ticket_kind' => [
        'mic_denied_twice' => 'Microfono negato due volte',
        'phone_option_requested' => 'Opzione telefono richiesta',
        'transcription_failed' => 'Trascrizione non riuscita',
        'withdrawal_requested' => 'Recesso richiesto',
        'refund_offer' => 'Rimborso da proporre',
        'print_order' => 'Libro da ordinare',
        'print_defect' => 'Difetto di stampa segnalato',
        'erasure_requested' => 'Cancellazione richiesta',
    ],

    'support_ticket_status' => [
        'open' => 'Aperto',
        'closed' => 'Chiuso',
    ],

    'reaction_type' => [
        'heart' => 'Mi piace',
        'thanks' => 'Grazie',
    ],

    'consent_kind' => [
        'voice_recording' => 'Registrazione della voce',
        'transcription' => 'Trascrizione dell’audio',
        'ai_rendering' => 'Rielaborazione del testo da parte di un’intelligenza artificiale',
        'family_sharing' => 'Condivisione con i familiari',
        'sensitive_categories' => 'Argomenti sensibili (salute, convinzioni, origini)',
        'phone_call_recording' => 'Registrazione della telefonata',
        'photo_rights' => 'Diritti sulle foto caricate',
        'post_mortem_directives' => 'Disposizioni da applicare dopo il decesso',
        'declared_sharing' => 'Condivisione delle mie storie appena sono pronte',
        'mandate_delegation' => 'Delega dell’approvazione a un familiare',
        'early_service_start' => 'Avvio immediato del servizio digitale',
        'marketing_email' => 'Ricezione delle nostre novità via e-mail',
    ],

    'consent_status' => [
        'granted' => 'Concesso',
        'revoked' => 'Revocato',
    ],

    /*
     * D'où vient une action inscrite au journal d'audit. « Le support a
     * masqué une histoire » et « une commande planifiée a masqué une
     * histoire » sont deux faits différents, et un journal qui les
     * confondrait ne servirait à rien le jour où il faut répondre à une
     * famille.
     */
    'actor_context' => [
        'web' => 'Dal sito',
        'filament' => 'Dall’amministrazione',
        'cli' => 'Da riga di comando',
        'phone_operator' => 'Da un operatore al telefono',
        'system' => 'Automatico',
    ],

    /*
     * La forme du livrable selon la matière (PRD §10). « Chapitre fondateur »
     * n'est pas un lot de consolation : c'est un objet relié, court, qui
     * existe — et le libellé doit s'entendre ainsi.
     */
    'book_format' => [
        'book' => 'Libro',
        'booklet' => 'Libretto',
        'founding_chapter' => 'Capitolo fondativo',
    ],

    'consent_channel' => [
        'web' => 'Sul sito',
        'phone' => 'Per telefono',
        'admin' => 'Inserito dall’assistenza',
    ],

    'question_theme' => [
        'childhood' => 'Infanzia',
        'family_origins' => 'Origini familiari',
        'youth' => 'Giovinezza',
        'work' => 'Lavoro',
        'love' => 'Amore',
        'places' => 'Luoghi',
        'joys' => 'Gioie',
        'hardships' => 'Momenti difficili',
        'beliefs_values' => 'Convinzioni e valori',
        'legacy' => 'Ciò che resta',
    ],

    'project_member_role' => [
        'initiator' => 'Chi organizza',
        'editor' => 'Curatrice o curatore designato',
    ],

    'validation_variant' => [
        'immediate' => 'Approvazione a fine registrazione',
        'deferred' => 'Approvazione dopo la rilettura',
    ],

    'deletion_requested_by' => [
        'narrator' => 'Chi racconta',
        'mandate' => 'La persona delegata',
        'admin' => 'L’assistenza, su richiesta scritta',
    ],

    'cohort_phase' => [
        '0A' => 'Fase 0A',
        '0B' => 'Fase 0B',
        'launch' => 'Lancio',
    ],

    'token_type' => [
        'record' => 'Link di registrazione',
        'listen_project' => 'Link di ascolto del progetto',
        'listen_story' => 'Link di ascolto di una storia',
        'qr' => 'Pagina raggiunta da un QR stampato',
        'invitation' => 'Link di invito',
        'action' => 'Azione in un tocco',
        'export' => 'Download di un’esportazione',
        'narrator_space' => 'Area personale di chi racconta',
        'sensitive_grant' => 'Autorizzazione di un atto sensibile',
    ],

    'token_issued_reason' => [
        'initial' => 'Primo invio',
        'reissue_support' => 'Riemesso dall’assistenza',
        'resend_other_channel' => 'Reinviato tramite un altro canale',
        'rotation' => 'Sostituito',
    ],

    'otp_purpose' => [
        'narrator_space' => 'Apertura dell’area personale',
        'sensitive_act' => 'Autorizzazione di un atto sensibile',
    ],

    'story_state' => [
        'proposed' => 'Proposta',
        'recorded' => 'Registrata',
        'transcribed' => 'Trascritta',
        'to_review' => 'Da rileggere',
        'validated' => 'Approvata',
        'shared' => 'Condivisa',
        'in_book' => 'Inclusa nel libro',
        'hidden' => 'Nascosta',
        'archived' => 'Archiviata',
        'trashed' => 'Nel cestino',
        'deleted' => 'Eliminata',
    ],

    /*
     * Les langues et les marchés que l'interface sait servir (T-238). Une
     * locale est une langue **et** un marché : `it-CH` parle italien et
     * formate ses montants à la suisse. Le sélecteur de langue, lui,
     * n'utilise pas ces libellés : une langue se propose dans sa propre
     * langue (`Locale::nativeName()`), sans quoi personne ne se reconnaît.
     */
    'locale' => [
        'fr' => 'Francese',
        'it' => 'Italiano',
        'es' => 'Spagnolo',
        'fr-CH' => 'Francese (Svizzera)',
        'it-CH' => 'Italiano (Svizzera)',
    ],

    'market' => [
        'FR' => 'Francia',
        'IT' => 'Italia',
        'ES' => 'Spagna',
        'CH' => 'Svizzera',
    ],

    'currency' => [
        'EUR' => 'Euro',
        'CHF' => 'Franco svizzero',
    ],

];
