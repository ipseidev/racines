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
        'draft' => 'En preparación',
        'awaiting_acceptance' => 'A la espera de la respuesta de tu familiar',
        'active' => 'En curso',
        'paused' => 'En pausa',
        'dormant' => 'Inactivo',
        'completed' => 'Terminado',
        'cancelled' => 'Cancelado',
        'frozen_bereavement' => 'Suspendido',
    ],

    /*
     * L'état d'une histoire, du point de vue de l'Initiateur·rice. Elle voit
     * **où en est** chaque histoire, jamais son contenu tant que le narrateur
     * ne l'a pas partagée.
     */
    'story_state' => [
        'proposed' => 'Pregunta enviada',
        'recorded' => 'Grabada',
        'transcribed' => 'Guardada por tu familiar',
        'to_review' => 'A la espera de su decisión',
        'validated' => 'Validada',
        'shared' => 'Compartida contigo',
        'in_book' => 'En el libro',
        'hidden' => 'Ocultada por tu familiar',
        'archived' => 'Archivada',
        'trashed' => 'En la papelera',
        'deleted' => 'Eliminada',
    ],

    'alert' => [
        'invitation_not_accepted' => 'La invitación aún no se ha abierto. Un mensaje tuyo ayudaría.',
        'three_stories_no_reaction' => 'Tres historias compartidas y ninguna reacción. Con un corazón bastaría.',
        'narrator_silence_21d' => 'Ninguna grabación desde hace tres semanas. Una llamada suele desbloquear las cosas.',
    ],

    'copy_link' => [
        'ready' => 'Aquí tienes el enlace. Pégalo en tu mensaje.',
        'no_story' => 'No hay ninguna pregunta en curso por ahora.',
        'no_family_member' => 'Aún no tienes un enlace para escuchar.',
        'whatsapp' => 'Hola, aquí tienes el enlace para grabar tu historia: :link',
    ],

    'one_tap' => [
        'expired' => 'Este enlace ya se ha usado. Puedes actuar desde tu espacio personal.',

        'resend_whatsapp' => [
            'title' => 'Reenviar el enlace personalmente',
            'body' => 'Un mensaje tuyo llama mucho más la atención que un SMS de un número desconocido. Aquí tienes el enlace para reenviar.',
            'button' => 'Obtener el enlace',
            'done' => 'Aquí tienes el enlace. Pégalo en tu mensaje.',
            'message' => 'Hola, aquí tienes el enlace para grabar tu historia: :link',
            'audio_hint' => 'Un mensaje de voz de treinta segundos funciona aún mejor: tu voz se reconoce.',
            'no_question' => 'Ya se han hecho todas las preguntas. Añade una desde tu espacio personal.',
        ],

        'switch_biweekly' => [
            'title' => 'Una pregunta cada dos semanas',
            'body' => 'Una pregunta por semana quizá sea mucho. Bajar el ritmo es mejor que parar, y el libro se construye igual de bien.',
            'button' => 'Pasar a cada dos semanas',
            'done' => 'Hecho: una pregunta cada dos semanas.',
        ],

        'ack_call_parent' => [
            'title' => 'La llamada la haces tú',
            'body' => 'Una llamada suele desbloquear lo que ningún mensaje consigue. Dínoslo y nos haremos a un lado.',
            'button' => 'Anotado, llamo yo',
            'done' => 'Anotado. No enviaremos nada más por ahora.',
        ],

        'offer_phone_option' => [
            'title' => 'La grabación por teléfono',
            'body' => 'Una persona de nuestro equipo llama a tu familiar y graba la conversación. Por su parte, no hay nada que manejar.',
            'button' => 'Solicitar esta opción',
            'done' => 'Solicitado. Te llamamos en un plazo de 48 horas para organizar las llamadas.',
            'unavailable' => 'Esta opción no está disponible por el momento. Escríbenos y lo miramos contigo.',
        ],

        'react_heart' => [
            'title' => 'Enviar un corazón',
            'body' => 'Un corazón en «:title». Es lo que da ganas de contar la siguiente.',
            'button' => 'Enviar un corazón',
            'done' => 'Enviado.',
            'no_story' => 'Ninguna historia compartida por ahora.',
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
        '1' => 'Lunes',
        '2' => 'Martes',
        '3' => 'Miércoles',
        '4' => 'Jueves',
        '5' => 'Viernes',
        '6' => 'Sábado',
        '7' => 'Domingo',
    ],

    'nav' => [
        'label' => 'Las páginas de tu espacio personal',
        'skip' => 'Ir al contenido',
        'untitled_project' => 'Proyecto sin narrador',
        'project_of' => 'Proyecto de :name',
        'dashboard' => 'El proyecto',
        'questions' => 'Las preguntas',
        'family' => 'Los familiares',
        'book' => 'El libro',
        'data' => 'Tus datos',
        'settings' => 'Los ajustes',
        'orders' => 'Mi pedido',
    ],

    'no_project' => [
        'title' => 'Ningún proyecto por ahora',
        'body' => 'En cuanto se confirme tu pedido, tu proyecto aparecerá aquí.',
        'cta' => 'Descubrir la oferta',
    ],

    'dashboard' => [
        'title' => 'El proyecto de :name',
        'title_generic' => 'Tu proyecto',
        'next_prompt' => 'Próxima pregunta: :when',
        'next_prompt_none' => 'Ninguna pregunta programada por ahora.',
        'paused_until' => 'Las preguntas están en pausa hasta el :date.',
        'cadence' => 'Ritmo: :cadence',
        'timeline' => 'Las historias',
        'gauge_title' => 'Dónde está el libro',
        'gauge_open' => 'Ver el libro',
        'counts_asked' => ':count pregunta hecha|:count preguntas hechas',
        'counts_asked_of' => ':count pregunta hecha de :total|:count preguntas hechas de :total',
        'counts_recorded' => ':count contada|:count contadas',
        'counts_shared' => ':count compartida|:count compartidas',
        'counts_none' => 'Todavía no ha salido nada.',
        'until' => 'Preguntas hasta el :date',
        'today' => 'Hoy',
        'upcoming' => 'Lo que viene',
        'upcoming_none' => 'Ya no queda nada esperando.',
        'upcoming_all' => 'Ver y reordenar las preguntas',
        'yours' => 'Su pregunta',
        'asked_by' => 'Pregunta de :name',
        'photos' => ':count foto|:count fotos',
        'timeline_empty' => 'Nada todavía. La primera pregunta sale pronto.',
        'not_shared_yet' => 'Aún no compartida',
        'private_notice' => 'Ves en qué punto está cada historia. El texto y la voz solo aparecen después de compartirla, y es :name quien lo decide.',
        'copy_link_hint' => 'Un mensaje tuyo vale más que uno nuestro. El enlace anterior deja de funcionar.',
        'listen' => 'Escuchar como un familiar',
        'listen_hint' => 'Escuchas con tu propio enlace, como los demás familiares.',
        'listen_open' => 'Abrir mi página de escucha',
        'alerts' => 'Avisos para ti',
        'pause' => 'Pedir una pausa',
        'this_week' => 'La pregunta de esta semana',
        'send_link' => 'Enviar el enlace a :name',
        'send_link_generic' => 'Enviar el enlace',
        'story_number' => 'Historia :n',
        'recorded_on' => 'Grabada el :date',
        'shared_on' => 'Compartida el :date',
        'share' => [
            'title' => 'El enlace está listo',
            'copy' => 'Copiar el enlace',
            'copied' => 'Copiado',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'hint' => 'El enlace anterior ya no funciona.',
        ],
    ],

    'questions' => [
        'reordered' => 'El orden se ha guardado.',
        'removed' => 'Su pregunta está retirada.',
        'remove' => 'Quitar mi pregunta',
        'updated' => 'Guardado.',
        'added' => 'Tu pregunta se ha añadido.',
        'added_without_photo' => 'Su pregunta está añadida, pero una foto no ha podido adjuntarse.',
        'title' => 'Las preguntas para :name',
        'title_generic' => 'Las preguntas',
        'intro' => 'En este orden, una por envío. Suba lo que le importa, descarte lo que no conviene: se guarda enseguida. :name conserva siempre el derecho a no responder.',
        'asked' => 'Ya hecha',
        'excluded' => 'Descartada',
        'exclude' => 'Descartar',
        'restore' => 'Recuperar',
        'move_up' => 'Subir',
        'move_down' => 'Bajar',
        'queue_title' => 'Las próximas preguntas',
        'queue_intro' => 'En este orden, una por envío. Sube lo que más te importe y descarta lo que no encaje: se guarda al instante.',
        'queue_empty' => 'Ya se han hecho todas las preguntas. Añade la tuya aquí abajo.',
        'search_all' => 'Buscar entre las :count preguntas',
        'later' => 'más tarde',
        'sends_on' => 'sale el :date',
        'list_title' => 'El orden de los envíos',
        'corpus_title' => 'El corpus',
        'corpus_intro' => 'Las :count preguntas disponibles. Busque, filtre por tema, y proponga pronto las que importan.',
        'search' => 'Buscar una pregunta',
        'search_placeholder' => 'una palabra: escuela, guerra, cocina…',
        'theme_all' => 'Todos los temas',
        'soon' => 'Preguntar pronto',
        'no_match' => 'Ninguna pregunta coincide. Pruebe otra palabra, o escriba la suya.',
        'next_title' => 'Las próximas',
        'next_intro' => 'En este orden, una por envío. Arrastre para reordenar, o use las flechas.',
        'next_rest' => 'y otras :count en el corpus',
        'pending_badge' => 'Su pregunta',
        'pending_hint' => 'Se enviará en el próximo envío, antes que las del corpus.',
        'pending_photos' => ':count foto|:count fotos',
        'tab_next' => 'Las próximas',
        'tab_corpus' => 'El corpus',
        'drag' => 'Mover',
        'first' => 'Preguntar primero',
        'position' => 'Pregunta :n',
        'see_more' => 'Ver :count más',
        'see_less' => 'Ver menos',
        'excluded_count' => 'Preguntas descartadas (:count)',
        'asked_count' => 'Ya hechas (:count)',
        'add' => [
            'title' => 'Hacer tu propia pregunta',
            'label' => 'Tu pregunta',
            'hint' => 'Se hará tal cual, en lugar de una pregunta del repertorio.',
            'submit' => 'Añadir esta pregunta',
            'waiting' => 'Un momento…',
            'counter' => ':count / :max',
            'photos' => 'Adjuntar fotos',
            'photos_hint' => 'Opcional, cuatro como máximo. :name las verá con la pregunta — « cuéntenos esta ».',
            'photos_remove' => 'Quitar esta foto',
            'photos_too_many' => 'Cuatro fotos como máximo por pregunta.',
        ],
    ],

    'family' => [
        'invited' => 'La invitación ya está enviada.',
        'link_reissued' => 'Aquí tienes un nuevo enlace para esta persona.',
        'removed' => 'Esta persona ya no tiene acceso.',
        'title' => 'Los familiares que escuchan',
        'intro' => 'Cada persona tiene su propio enlace. Retirar un acceso solo retira ese.',
        'empty' => 'Nadie por ahora, aparte de ti.',
        'you' => 'Tú',
        'can_ask' => 'Puede hacer preguntas',
        'invited_at' => 'Invitación enviada el :date',
        'first_seen_at' => 'Abrió su enlace el :date',
        'never_opened' => 'Aún no ha abierto su enlace',
        'reissue' => 'Generar un nuevo enlace',
        'reissue_hint' => 'El enlace anterior deja de funcionar.',
        'remove' => 'Retirar el acceso',
        'status_opened' => 'Enlace abierto',
        'status_pending' => 'Aún no abierto',
        'link_title' => 'El nuevo enlace de :name',
        'remove_confirm' => [
            'title' => '¿Retirar el acceso de :name?',
            'body' => 'Su enlace dejará de funcionar de inmediato. Podrás invitar a esta persona de nuevo más adelante.',
            'confirm' => 'Retirar el acceso',
        ],
        'invite' => [
            'title' => 'Invitar a un familiar',
            'intro' => 'La persona recibe un enlace propio, sin cuenta ni contraseña.',
            'waiting' => 'Un momento…',
            'name' => 'Su nombre',
            'relationship' => 'Su parentesco',
            'email' => 'Su correo electrónico',
            'phone' => 'Su número de teléfono',
            'contact_hint' => 'Basta con un correo electrónico o un número.',
            'can_ask' => 'Permitirle hacer sus propias preguntas',
            'can_ask_help' => 'Podrá preguntar lo que le gustaría saber, con sus fotos. Su pregunta saldrá después de las que ya esperan, y usted podrá retirarla.',
            'submit' => 'Enviar la invitación',
        ],
    ],

    'settings' => [
        'saved' => 'Tus ajustes se han guardado.',
        'lexicon_added' => 'La palabra se ha añadido al léxico.',
        'lexicon_removed' => 'La palabra se ha retirado del léxico.',
        'paused' => 'Anotado: ninguna pregunta durante :weeks semanas.',
        'title' => 'Los ajustes del proyecto',
        'rhythm' => 'El ritmo',
        'cadence' => 'Frecuencia de las preguntas',
        'day' => 'Día de envío',
        'days_hint' => 'Las preguntas saldrán cada semana: :days.',
        'slot' => 'Momento del día',
        'address_form' => 'Forma de tratamiento',
        'locale' => 'Idioma del proyecto',
        'locale_help' => 'El idioma de las páginas que verán :name y tus familiares. No cambia el tuyo.',
        'locale_saved' => 'El idioma del proyecto está guardado.',
        'timezone' => 'Zona horaria: :timezone',
        'next_prompt' => 'Próximo envío: :when',
        'submit' => 'Guardar',
        'saved_short' => 'Guardado',
        'waiting' => 'Un momento…',
        'lexicon' => [
            'title' => 'El léxico',
            'intro' => 'Los nombres propios de tu familia: el pueblo, los apodos, la ortografía exacta. Eres tú quien los conoce, no :name ni nosotros.',
            'term' => 'Lo que se oye',
            'replacement' => 'Lo que hay que escribir',
            'notes' => 'Una aclaración (opcional)',
            'submit' => 'Añadir al léxico',
            'remove' => 'Retirar',
            'empty' => 'El léxico está vacío.',
            'heard' => 'Se oyó «:term»',
        ],
        'pause' => [
            'title' => 'Poner las preguntas en pausa',
            'intro' => 'Una pausa siempre tiene un final, y :name recibirá aviso.',
            'weeks' => '¿Cuántas semanas?',
            'fewer' => 'Una semana menos',
            'more' => 'Una semana más',
            'submit' => 'Poner en pausa',
        ],
        'mandate' => [
            'title' => 'Validar en lugar de :name',
            'body' => 'Esta posibilidad existe para las situaciones en las que :name ya no puede validar sus historias. Requiere su acuerdo explícito y cesa en cuanto esa persona lo retira.',
            'submit' => 'Saber más',
        ],
    ],

    'orders' => [
        'top_up_title' => 'Completar mi pedido',
        'top_up_body' => 'Todavía puedes añadir esto. El resto de tu pedido no cambia.',
        'top_up_add' => 'Añadir — :price',
        'top_up_unavailable' => 'Esta opción ya no está disponible. Escríbenos si crees que se trata de un error.',
        'top_up_done' => 'Añadido a tu pedido.',
        'top_up_sku_phone_option' => 'La grabación por teléfono',
        'top_up_sku_phone_option_hint' => 'Una persona del equipo llama cada semana, unos quince minutos, y hace la pregunta en tu lugar.',
        'top_up_sku_ebook' => 'El libro digital',
        'top_up_sku_ebook_hint' => 'La versión digital del libro, además del ejemplar encuadernado.',
        'withdrawal_requested' => 'Tu solicitud queda registrada. Te respondemos en un plazo de 48 horas.',
        // Ni refus sec ni silence : on explique la garantie et on donne le
        // contact. Le refus sec est l'occasion parfaite de perdre une famille
        // qu'on aurait pu garder.
        'withdrawal_closed' => 'El plazo de desistimiento de catorce días ha pasado. Nuestra garantía «satisfecho o reembolsado» de treinta días puede aplicarse: escríbenos y lo miramos contigo.',
        'title' => 'Mi pedido',
        'empty' => 'Ningún pedido por ahora.',
        'paid_at' => 'Pagado el :date',
        'total' => 'Total: :amount',
        'refunded' => 'Reembolsado: :amount',
        'invoice' => 'Ver la factura',
        'items' => 'El detalle',
        'withdrawal' => 'Ejercer mi derecho de desistimiento',
        'withdrawal_until' => 'Puedes desistir hasta el :date, sin tener que justificarte.',
        'withdraw_confirm' => [
            'title' => '¿Ejercer tu derecho de desistimiento?',
            'body' => 'Te respondemos en un plazo de 48 horas y el reembolso llega después. Si tu familiar ya ha empezado a grabar, lo tenemos en cuenta contigo.',
            'confirm' => 'Quiero desistir',
        ],
        'support' => 'Escribir al soporte',
        'withdrawal_expired' => 'El plazo de catorce días ha pasado. Si la persona a la que has invitado prefiere no participar, te reembolsamos íntegramente dentro de los treinta días: escríbenos a :email.',
        'phone_option' => 'Grabación por teléfono',
        'phone_option_slot' => 'Llamada prevista el día :day, :slot',
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
        'eyebrow' => 'El libro',
        'title' => 'El libro de :first_name',
        'intro' => 'El libro se pone en marcha cuando el material es suficiente, no al llegar a un número de historias. Aquí ves en qué punto estás.',

        'gauge' => [
            'title' => 'El material reunido',
            'words' => 'Palabras',
            'audio' => 'Minutos de voz',
            'pages' => 'Páginas estimadas',
            'themes' => 'Temas tratados',
            'ready' => 'Hay material para hacer un libro.',
            'not_ready' => 'Aún falta material — y lo que ya tienes no se pierde.',
            // Le verrou réel n'est pas celui des mots : à 280 mots la page,
            // les 12 000 mots du référentiel ne font que 48 pages.
            'pages_hint' => 'El número de páginas es lo que decide al final: un texto denso ocupa menos páginas de lo que parece, y las fotos añaden.',
        ],

        'format' => [
            'title' => 'El formato propuesto',
            'current' => 'Formato elegido',
            'proposed' => 'Formato propuesto',
            'help' => 'Proponemos el formato que el material permite. Nada te obliga a seguirlo, y no hay prisa.',
        ],

        'chapters' => [
            'title' => 'Los capítulos',
            'help' => 'Todas las historias validadas, en el orden en que se contaron. Desmarca lo que no quieras imprimir y sube lo que deba abrir el libro.',
            'empty' => 'Ninguna historia validada por ahora.',
            'include' => 'Incluir en el libro',
            'move_up' => 'Subir',
            'move_down' => 'Bajar',
            'to_top' => 'Poner en primer lugar',
            'words' => ':count palabras',
            'photos' => ':count foto|:count fotos',
            'locked' => 'La selección está cerrada: el libro ya está en imprenta.',
            'qr_revoke' => 'Desactivar el código de esta historia',
            'qr_restore' => 'Reactivar el código de esta historia',
        ],

        'foreword' => [
            'title' => 'Tu prólogo',
            'help' => 'Unas líneas de apertura, si lo deseas. Opcional.',
            'label' => 'Prólogo',
        ],

        'lexicon' => [
            'title' => 'Los nombres propios',
            'help' => 'La transcripción se equivoca a menudo con los nombres de lugares y de personas. Revisa esta lista antes de imprimir: un error en un nombre es el que más se nota.',
            'none' => 'Ningún nombre que revisar.',
            'add' => 'Añadir al léxico',
        ],

        'proof' => [
            'title' => 'La prueba final',
            'help' => 'La prueba final es el libro tal y como se imprimirá. Reléela con calma.',
            'generate' => 'Generar la prueba final',
            'regenerate' => 'Volver a generar la prueba final',
            'open' => 'Abrir la prueba final',
            'version' => 'Versión :number, generada el :date',
            'pages' => ':count páginas',
            'pending' => 'La prueba final se está preparando. Recibirás un mensaje cuando esté lista — por lo general bastan unos minutos.',
            'none' => 'Ninguna prueba final por ahora.',
        ],

        'approve' => [
            'title' => 'Aprobar y pedir',
            'final_print' => 'Entiendo que lo impreso es definitivo: una vez impreso el libro, ya no se puede corregir nada.',
            'lexicon_reviewed' => 'He revisado los nombres propios y las fechas.',
            'submit' => 'Aprobar y pedir',
            'waiting' => 'Un momento…',
            'help' => 'No te anunciamos ningún plazo mientras no esté elegida la imprenta. Te avisaremos en cada etapa.',
        ],

        'tracking' => [
            'title' => 'En qué punto está tu libro',
            'approved' => 'Aprobado el :date',
            'ordered' => 'Pedido el :date',
            'printed' => 'Impreso el :date',
            'delivered' => 'Entregado el :date',
            'extra_copies' => 'Ejemplares adicionales',
            'extra_copies_price' => ':price € por ejemplar, cinco como máximo cada vez.',
            'order_copies' => 'Pedir',
            'defect' => 'Comunicar un defecto de impresión',
            'defect_help' => 'Un libro dañado, mal guillotinado o con las páginas invertidas: lo reimprimimos sin condiciones.',
        ],

        /*
         * Le code du livre (doc 04 §7). Le texte doit faire comprendre deux
         * choses en trois lignes : c'est facultatif, et cela se décide une
         * fois pour tous les exemplaires.
         */
        'code' => [
            'title' => 'Proteger la escucha con un código',
            'help' => 'Por defecto, los códigos impresos en el libro se abren sin pedir nada: es lo que permite prestar el libro. Puedes añadir un código, para escribirlo en la solapa o decirlo de viva voz — se pedirá una vez y luego se recordará durante un mes en el dispositivo.',
            'label' => 'El código, al menos cuatro caracteres',
            'submit' => 'Establecer este código',
            'change' => 'Cambiar el código',
            'remove' => 'Quitar el código',
            'is_set' => 'Un código protege la escucha. No podemos recordártelo: está cifrado, como una contraseña.',
            'saved' => 'El código está establecido. Se pedirá en el próximo escaneo.',
            'removed' => 'El código se ha quitado: los códigos del libro vuelven a abrirse sin pedir nada.',
        ],

        'saved' => 'Guardado.',
        'rendering' => 'La prueba final se está preparando. Recibirás un mensaje cuando esté lista.',
        'ordered' => 'Tu libro está pedido. Te mantenemos al tanto en cada etapa.',
        'no_chapter' => 'Hace falta al menos un capítulo para preparar una prueba final.',
    ],

    /*
     * « Mes données » (bloc 14).
     *
     * Le ton dit la non-captivité mieux qu'une promesse : ces fichiers sont à
     * la famille, elle n'a pas à nous remercier de les lui rendre, et
     * l'effacement s'explique sans être découragé.
     */
    'data' => [
        'eyebrow' => 'Tus datos',
        'title' => 'Tus datos son tuyos',
        'intro' => 'Puedes recuperar todo lo que has grabado, en cualquier momento y sin coste. Los archivos se abren con los programas que ya tienes: no nos necesitas para leerlos.',

        'export' => [
            'title' => 'Descargar mis datos',
            'help' => 'Preparamos una carpeta completa — las voces, los textos, las fotos y el libro, si existe. Recibirás un correo electrónico en cuanto esté lista: tarda unos minutos.',
            'full' => 'Todo lo que he grabado',
            'offline' => 'Todo, con un reproductor que funciona sin conexión',
            'gdpr' => 'Todo, más mis consentimientos y el registro de mis datos',
            'submit' => 'Preparar mi carpeta',
            'waiting' => 'Un momento…',
            'history' => 'Tus últimas carpetas',
            'ready' => 'Lista, válida hasta el :date',
            'building' => 'En preparación',
            'expired' => 'Enlace caducado — puedes pedir uno nuevo',
            'size' => ':size MB',
        ],

        'erasure' => [
            'title' => 'Borrar mi proyecto',
            'help' => 'El borrado elimina definitivamente las grabaciones, los textos y las fotos. Es irreversible: no podremos recuperar nada, ni siquiera si nos lo pides.',
            'kept' => 'Lo que conservamos a pesar de todo: las facturas, para la contabilidad, y la prueba de los consentimientos dados — sin tu nombre. La ley nos obliga a ello.',
            'narrator_first' => 'Si quien narra pide el borrado por su cuenta, su solicitud pasa por delante de la tuya: son sus relatos.',
            'delay' => 'Te confirmamos el borrado en treinta días como máximo, y casi siempre el mismo día.',
            'blocked' => 'Un libro de este proyecto está en imprenta. El borrado se hará en cuanto se entregue, o de inmediato si cancelas el pedido — escríbenos.',
            'requested' => 'Tu solicitud de borrado queda registrada. Te contestamos muy pronto.',
            'confirm_label' => 'Para confirmar, escribe EFFACER',
            'submit' => 'Solicitar el borrado',
        ],

        'export_queued' => 'Tu carpeta está en preparación. Recibirás un correo electrónico en cuanto esté lista.',
        'erasure_requested' => 'Tu solicitud queda registrada. Te respondemos en treinta días como máximo.',
    ],

    /*
     * Le tunnel de personnalisation d'après-achat.
     */
    'personalize' => [
        'title' => 'Personalizar las preguntas',
        'quit' => 'Salir',
        'back' => 'Volver a la pantalla anterior',
        'skip' => 'Saltar',
        'progress' => 'Avance',
        'continue' => 'Continuar',
        'welcome' => [
            'eyebrow' => 'Dos minutos, no más',
            'title' => '¿Y si sus preguntas se le parecieran de verdad?',
            'body' => 'Dos minutos para hablarnos de :name. Elegiremos sus preguntas según su vida, y podrás cambiarlo todo después.',
            'start' => 'Empezar',
            'skip_all' => 'Saltar, elegid por mí',
        ],
        'relation' => [
            'eyebrow' => ':n · Vuestro vínculo',
            'title' => '¿Quién es :name para ti?',
            'body' => 'Sabremos cómo hablarle, y qué preguntas te conciernen.',
            'mother' => 'Mi madre',
            'father' => 'Mi padre',
            'grandmother' => 'Mi abuela',
            'grandfather' => 'Mi abuelo',
            'partner' => 'Mi pareja',
            'other' => 'Otra persona querida',
            'gender_label' => 'Sus preguntas le hablarán…',
            'feminine' => 'En femenino',
            'masculine' => 'En masculino',
        ],
        'about' => [
            'eyebrow' => ':n · ¿Y tú?',
            'title' => '¿También algunas preguntas sobre ti?',
            'body' => 'Algunas preguntas, repartidas en el año, en las que :name habla de ti. Nunca la primera.',
            'buyer' => 'Sí, algunas sobre mí',
            'everyone' => 'Mejor sobre todos sus hijos',
            'none' => 'No, gracias',
            'name_label_feminine' => 'El nombre con el que ella te llama',
            'name_label_masculine' => 'El nombre con el que él te llama',
            'placeholder' => 'Clara',
            'child_feminine' => 'Su hija',
            'child_masculine' => 'Su hijo',
            'grandchild_feminine' => 'Su nieta',
            'grandchild_masculine' => 'Su nieto',
            'other_feminine' => 'En femenino',
            'other_masculine' => 'En masculino',
            'preview' => 'Por ejemplo, hacia la sexta semana',
        ],
        'life' => [
            'eyebrow' => ':n · Su vida',
            'title' => 'Un poco de su vida',
            'body_feminine' => 'Un «no» quita las preguntas que no le conciernen. En caso de duda, no respondas: no se quita nada.',
            'body_masculine' => 'Un «no» quita las preguntas que no le conciernen. En caso de duda, no respondas: no se quita nada.',
            'yes' => 'Sí',
            'no' => 'No',
            'facts' => [
                'partner' => 'Ha vivido en pareja',
                'children' => 'Ha tenido hijos',
                'grandchildren' => 'Tiene nietos',
                'career' => 'Ha ejercido un oficio',
                'siblings' => 'Tiene hermanos',
                'migration_feminine' => 'Creció lejos de donde vive',
                'migration_masculine' => 'Creció lejos de donde vive',
                'rural' => 'Creció en el campo',
            ],
        ],
        'avoid' => [
            'eyebrow' => ':n · Con delicadeza',
            'title' => '¿Temas que dejar de lado?',
            'body' => 'No haremos ninguna pregunta que los toque. Nada está marcado de antemano.',
            'hint' => ':name siempre podrá saltarse una pregunta, sin tener que decir por qué.',
            'none' => 'Ninguno, continuar',
        ],
        'themes' => [
            'eyebrow' => ':n · Lo que te importa',
            'title' => '¿Qué te gustaría oírle contar?',
            'body' => 'Hasta tres temas, que irán primero. Los demás seguirán: un libro necesita de todo.',
            'count' => ':count de 3',
            'count_none' => 'Ningún tema elegido: equilibraremos nosotros',
            'cta' => 'Ver sus primeras preguntas',
            'lines' => [
                'childhood' => 'La casa, la escuela, las travesuras',
                'family_origins' => 'Los abuelos, las tradiciones',
                'youth' => 'Los bailes, la música, la libertad',
                'work' => 'Los compañeros, el primer sueldo',
                'love' => 'El encuentro, la vida en pareja',
                'places' => 'El pueblo, la casa que se dejó',
                'joys' => 'Las pasiones, las carcajadas',
                'hardships' => 'Lo que fue duro, y lo que ayudó',
                'beliefs_values' => 'Lo que la vida le ha enseñado',
                'legacy' => 'Las recetas, los gestos, los mensajes',
            ],
        ],
        'first' => [
            'eyebrow' => 'Por aquí empezamos',
            'title' => 'Elige su primerísima pregunta',
            'badge' => 'Primera',
            'later' => 'Y hacia la sexta semana:',
            'cta' => 'Adelante',
            'empty' => 'Ninguna pregunta que proponer por ahora: elegiremos nosotros.',
        ],
        'done' => [
            'title' => 'Listo',
            'body' => 'Sus preguntas seguirán lo que nos has contado de :name. Podrás ajustarlo todo desde tu espacio.',
            'skipped_title' => 'Nos ocupamos nosotros',
            'skipped_body' => 'Elegiremos sus preguntas con cuidado. Podrás personalizarlas en cualquier momento desde tu espacio.',
            'cta' => 'Ir a mi espacio',
            'redo' => 'Volver a ver el recorrido',
        ],
        'errors' => [
            'first' => 'Esta pregunta ya no está entre las primeras. Elige otra.',
            'facts' => 'Una respuesta sobre su vida no se reconoce.',
        ],
        'banner' => [
            'title' => '¿Y si sus preguntas se le parecieran?',
            'body' => 'Dos minutos para hablarnos de :name: elegiremos sus preguntas según su vida.',
            'cta' => 'Personalizar sus preguntas',
        ],
        'questions_link' => 'Personalizar según su vida',
    ],
];
