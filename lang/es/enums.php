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
        'draft' => 'Borrador',
        'awaiting_acceptance' => 'Pendiente de aceptación',
        'active' => 'En curso',
        'paused' => 'En pausa',
        'dormant' => 'Inactivo',
        'completed' => 'Terminado',
        'cancelled' => 'Cancelado',
        'frozen_bereavement' => 'Congelado (duelo)',
    ],

    'offer' => [
        'pilot' => 'Piloto',
        'core' => 'Oferta principal',
        'prevente' => 'Preventa',
    ],

    'address_form' => [
        'vous' => 'Trato de usted',
        'tu' => 'Trato de tú',
    ],

    'cadence' => [
        'weekly' => 'Una pregunta por semana',
        'biweekly' => 'Una pregunta cada quince días',
    ],

    'prompt_slot' => [
        'morning' => 'Mañana',
        'afternoon' => 'Tarde',
        'evening' => 'Noche',
    ],

    // À quel point la personne qui racontera est à l'aise avec un téléphone.
    // Posé à l'achat pour un proche, afin d'adapter ce qu'on lui propose
    // (T-136) : l'option téléphone est recommandée aux deux derniers.
    'tech_comfort' => [
        'daily' => 'Con soltura: el teléfono forma parte de su día a día',
        'sometimes' => 'Depende: los SMS sí, abrir un enlace a veces menos',
        'rarely' => 'Con dificultad: a menudo necesita ayuda',
        'no_smartphone' => 'Sin smartphone',
    ],

    'channel' => [
        'sms' => 'SMS',
        'email' => 'Correo electrónico',
        'both' => 'SMS y correo electrónico',
        'phone_operator' => 'Teléfono (con operador)',
    ],

    'outbound_message_status' => [
        'queued' => 'Pendiente de envío',
        'sent' => 'Aceptado por la operadora',
        'delivered' => 'Recibido',
        'failed' => 'Error de envío',
        'bounced' => 'Dirección rechazada',
        'undelivered' => 'No entregado',
    ],

    'recording_source' => [
        'browser' => 'Navegador',
        'phone_operator' => 'Llamada telefónica',
        'upload_admin' => 'Subido por el equipo de soporte',
    ],

    'upload_status' => [
        'initiated' => 'Abierto',
        'uploading' => 'Envío en curso',
        'completed' => 'Enviado',
        'failed' => 'Error',
        'aborted' => 'Abandonado',
    ],

    'client_event_name' => [
        'mic_denied' => 'Micrófono denegado',
        'mic_granted' => 'Micrófono autorizado',
        'recorder_unsupported' => 'Navegador incapaz de grabar',
        'recording_started' => 'Grabación iniciada',
        'recording_paused' => 'Grabación en pausa',
        'recording_resumed' => 'Grabación reanudada',
        'recording_stopped' => 'Grabación terminada',
        'page_hidden' => 'Página abandonada',
        'interrupted' => 'Grabación interrumpida',
        'resumed_from_draft' => 'Reanudada desde el borrador',
        'draft_discarded' => 'Borrador descartado',
        'soft_warning_reached' => 'Aviso de duración alcanzado',
        'hard_stop_reached' => 'Parada por duración máxima',
        'upload_started' => 'Envío iniciado',
        'upload_retried' => 'Envío reintentado',
        'upload_failed' => 'Envío fallido',
        'storage_quota_low' => 'Poco espacio en el dispositivo',
        'written_answer_chosen' => 'Respuesta escrita elegida',
        // Bloc 10 : quelqu'un a répondu « vous-même » à la première
        // étape du tunnel. C'est une information de marché, pas une
        // erreur de saisie.
        'self_narration_interest' => 'Interés por contar su propia historia',
        // T-210 : la forme choisie, mesurée avant toute autorisation.
        'camera_denied' => 'Cámara denegada',
        'camera_granted' => 'Cámara autorizada',
        'video_chosen' => 'Vídeo elegido',
        'audio_chosen' => 'Solo voz elegida',
    ],

    'share_decision' => [
        'share' => 'Compartir con los familiares',
        'keep_private' => 'Guardar para mí',
        'decide_later' => 'Decidir más tarde',
    ],

    'validated_via' => [
        'recording_end' => 'Al final de la grabación',
        'post_transcription' => 'Tras releer el texto',
        'mandate' => 'Por la persona delegada',
        'phone_operator' => 'Conformidad verbal recogida por teléfono',
    ],

    'story_visibility' => [
        'all_family' => 'Todos los familiares',
        'restricted' => 'Solo algunos familiares',
        'book_only' => 'Solo en el libro',
    ],

    'answer_type' => [
        'audio' => 'Voz',
        'text' => 'Texto escrito',
        'phone' => 'Teléfono',
        'video' => 'Vídeo',
    ],

    'recording_kind' => [
        'audio' => 'Voz',
        'video' => 'Vídeo',
    ],

    'order_status' => [
        'pending' => 'Pendiente de pago',
        'paid' => 'Pagado',
        'refunded' => 'Reembolsado',
        'partially_refunded' => 'Reembolsado parcialmente',
        'cancelled' => 'Cancelado',
    ],

    'sku' => [
        'pilot' => 'Oferta piloto',
        'core_prevente' => 'Preventa',
        'extra_copy' => 'Ejemplar adicional',
        'phone_option' => 'Grabación por teléfono',
        'ebook' => 'Libro digital',
    ],

    'phone_option_entry' => [
        'checkout' => 'Comprada con el pedido',
        'complement' => 'Añadida después del pedido',
        'rescue' => 'Propuesta como alternativa',
    ],

    'phone_option_status' => [
        'requested' => 'Solicitada',
        'active' => 'Activa',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
    ],

    'post_mortem_wish' => [
        'transfer_to_family' => 'Transmitir a mi familia',
        'freeze' => 'Congelar, sin transmitir nada',
        'delete' => 'Eliminar todo',
    ],

    'refusal_reason' => [
        'not_the_right_time' => 'No es el momento adecuado',
        'prefer_not_to' => 'Prefiero no hacerlo',
        'other' => 'Otro',
    ],

    'support_ticket_kind' => [
        'mic_denied_twice' => 'Micrófono denegado dos veces',
        'phone_option_requested' => 'Opción de teléfono solicitada',
        'transcription_failed' => 'Transcripción fallida',
        'withdrawal_requested' => 'Desistimiento solicitado',
        'refund_offer' => 'Reembolso por proponer',
        'print_order' => 'Libro por encargar',
        'print_defect' => 'Defecto de impresión notificado',
        'erasure_requested' => 'Supresión solicitada',
    ],

    'support_ticket_status' => [
        'open' => 'Abierto',
        'closed' => 'Cerrado',
    ],

    'reaction_type' => [
        'heart' => 'Me ha gustado',
        'thanks' => 'Gracias',
    ],

    'consent_kind' => [
        'voice_recording' => 'Grabación de la voz',
        'transcription' => 'Transcripción del audio',
        'ai_rendering' => 'Edición del texto por una inteligencia artificial',
        'family_sharing' => 'Compartir con los familiares',
        'sensitive_categories' => 'Temas sensibles (salud, convicciones, orígenes)',
        'phone_call_recording' => 'Grabación de la llamada telefónica',
        'photo_rights' => 'Derechos sobre las fotos subidas',
        'post_mortem_directives' => 'Instrucciones para después del fallecimiento',
        'declared_sharing' => 'Compartir mis historias en cuanto estén listas',
        'mandate_delegation' => 'Delegación de la validación en un familiar',
        'early_service_start' => 'Inicio inmediato del servicio digital',
        'marketing_email' => 'Recibir nuestras novedades por correo electrónico',
    ],

    'consent_status' => [
        'granted' => 'Otorgado',
        'revoked' => 'Retirado',
    ],

    /*
     * D'où vient une action inscrite au journal d'audit. « Le support a
     * masqué une histoire » et « une commande planifiée a masqué une
     * histoire » sont deux faits différents, et un journal qui les
     * confondrait ne servirait à rien le jour où il faut répondre à une
     * famille.
     */
    'actor_context' => [
        'web' => 'Desde el sitio web',
        'filament' => 'Desde la administración',
        'cli' => 'Por línea de comandos',
        'phone_operator' => 'Por un operador al teléfono',
        'system' => 'Automático',
    ],

    /*
     * La forme du livrable selon la matière (PRD §10). « Chapitre fondateur »
     * n'est pas un lot de consolation : c'est un objet relié, court, qui
     * existe — et le libellé doit s'entendre ainsi.
     */
    'book_format' => [
        'book' => 'Libro',
        'booklet' => 'Cuadernillo',
        'founding_chapter' => 'Capítulo fundacional',
    ],

    'consent_channel' => [
        'web' => 'En el sitio web',
        'phone' => 'Por teléfono',
        'admin' => 'Registrado por el equipo de soporte',
    ],

    'question_theme' => [
        'childhood' => 'Infancia',
        'family_origins' => 'Orígenes familiares',
        'youth' => 'Juventud',
        'work' => 'Oficio',
        'love' => 'Amor',
        'places' => 'Lugares',
        'joys' => 'Alegrías',
        'hardships' => 'Momentos difíciles',
        'beliefs_values' => 'Convicciones y valores',
        'legacy' => 'Lo que permanece',
    ],

    'project_member_role' => [
        'initiator' => 'Quien organiza',
        'editor' => 'Persona designada para la edición',
    ],

    'validation_variant' => [
        'immediate' => 'Validación al final de la grabación',
        'deferred' => 'Validación tras la relectura',
    ],

    'deletion_requested_by' => [
        'narrator' => 'La persona que narra',
        'mandate' => 'La persona delegada',
        'admin' => 'El equipo de soporte, previa solicitud por escrito',
    ],

    'cohort_phase' => [
        '0A' => 'Fase 0A',
        '0B' => 'Fase 0B',
        'launch' => 'Lanzamiento',
    ],

    'token_type' => [
        'record' => 'Enlace de grabación',
        'listen_project' => 'Enlace de escucha del proyecto',
        'listen_story' => 'Enlace de escucha de una historia',
        'qr' => 'Página abierta desde un QR impreso',
        'invitation' => 'Enlace de invitación',
        'action' => 'Acción con un solo toque',
        'export' => 'Descarga de una exportación',
        'narrator_space' => 'Espacio personal de quien narra',
        'sensitive_grant' => 'Autorización de un acto sensible',
    ],

    'token_issued_reason' => [
        'initial' => 'Primer envío',
        'reissue_support' => 'Reemitido por el equipo de soporte',
        'resend_other_channel' => 'Reenviado por otro canal',
        'rotation' => 'Sustituido',
    ],

    'otp_purpose' => [
        'narrator_space' => 'Apertura del espacio personal',
        'sensitive_act' => 'Autorización de un acto sensible',
    ],

    'story_state' => [
        'proposed' => 'Propuesta',
        'recorded' => 'Grabada',
        'transcribed' => 'Transcrita',
        'to_review' => 'Por releer',
        'validated' => 'Validada',
        'shared' => 'Compartida',
        'in_book' => 'Incluida en el libro',
        'hidden' => 'Oculta',
        'archived' => 'Archivada',
        'trashed' => 'En la papelera',
        'deleted' => 'Eliminada',
    ],

    /*
     * Les langues et les marchés que l'interface sait servir (T-238). Une
     * locale est une langue **et** un marché : `it-CH` parle italien et
     * formate ses montants à la suisse. Le sélecteur de langue, lui,
     * n'utilise pas ces libellés : une langue se propose dans sa propre
     * langue (`Locale::nativeName()`), sans quoi personne ne se reconnaît.
     */
    'locale' => [
        'fr' => 'Francés',
        'it' => 'Italiano',
        'es' => 'Español',
        'fr-CH' => 'Francés (Suiza)',
        'it-CH' => 'Italiano (Suiza)',
    ],

    'market' => [
        'FR' => 'Francia',
        'IT' => 'Italia',
        'ES' => 'España',
        'CH' => 'Suiza',
    ],

    'currency' => [
        'EUR' => 'Euro',
        'CHF' => 'Franco suizo',
    ],

];
