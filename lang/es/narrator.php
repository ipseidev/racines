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
            'title' => 'Este enlace no funciona',
            'body' => 'Puede que el enlace esté incompleto. Compruebe que lo ha abierto entero, desde el mensaje que recibió.',
        ],
        'expired' => [
            'title' => 'Este enlace ha caducado',
            'body' => 'Por su seguridad, los enlaces solo son válidos durante un tiempo. Puede pedir uno nuevo.',
        ],
        'revoked' => [
            'title' => 'Este enlace ya no es válido',
            'body' => 'Ha sido sustituido, o su historia ya está grabada. Puede pedir un nuevo enlace.',
        ],
        'used' => [
            'title' => 'Este enlace ya se ha usado',
            'body' => 'Solo funcionaba una vez. Si aún le queda algo por hacer, pida un nuevo enlace.',
        ],
        'type_mismatch' => [
            'title' => 'Este enlace no lleva aquí',
            'body' => 'Corresponde a otra página. Abra el enlace desde el mensaje que recibió.',
        ],
        'request_new_link' => 'Pedir un nuevo enlace',
        'request_sent' => 'Anotado. Le enviaremos un nuevo enlace muy pronto.',
        'help' => '¿Necesita ayuda? Escríbanos a :email.',
    ],

    'otp' => [
        'title' => 'Su código de confirmación',
        'intro' => 'Hemos enviado un código de 6 cifras a :destination.',
        'intro_no_code' => 'Para continuar, tenemos que enviarle un código de 6 cifras.',
        'code_label' => 'Código de 6 cifras',
        'submit' => 'Confirmar',
        'send' => 'Enviar el código',
        'resend' => 'Volver a enviar el código',
        'sent' => 'Código enviado. Llegará en unos segundos.',
        'already_sent' => 'Ya le hemos enviado un código. Use ese: todavía es válido.',
        'invalid' => 'Este código no coincide. Compruebe las seis cifras y vuelva a intentarlo.',
        'expired' => 'Este código ya no es válido. Pida uno nuevo.',
        'locked' => 'Demasiados intentos. Espere quince minutos y luego pida un nuevo código.',
        'warning' => 'No dé este código a nadie, ni siquiera a alguien que diga llamar de nuestra parte.',
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
        'mode_title' => '¿Cómo quiere responder, :name?',
        'mode_title_tu' => '¿Cómo quieres responder, :name?',
        'mode_audio' => 'Con su voz',
        'mode_video' => 'Grabándose en vídeo',
        'mode_help' => 'Basta con la voz, y es la que acompañará el libro. Grabarse en vídeo añade su imagen, para su familia.',
        'mode_help_tu' => 'Basta con la voz, y es la que acompañará el libro. Grabarte en vídeo añade tu imagen, para tu familia.',

        /*
         * L'écran caméra (T-212). Il prend tout l'écran, la question posée
         * dessus s'efface dès que ça tourne, et « Sortir » n'existe que tant
         * que rien n'a été dit — après, on passe par « Terminer », qui garde
         * ce qui a été raconté.
         */
        'video_stage' => 'Grabarse en vídeo',
        'mode_exit' => 'Salir',

        // Écran 1 — explication. Elle précède toujours la demande de micro :
        // une autorisation qui surgit sans prévenir se refuse par réflexe.
        'greeting' => ':name, esta es su pregunta de la semana',
        'greeting_tu' => ':name, esta es tu pregunta de la semana',
        'mic_notice' => 'Cuando pulse el botón, su teléfono le pedirá permiso para usar el micrófono. Elija «Permitir».',
        'mic_notice_tu' => 'Cuando pulses el botón, tu teléfono te pedirá permiso para usar el micrófono. Elige «Permitir».',
        'camera_notice' => 'Cuando pulse el botón, su teléfono le pedirá permiso para usar el micrófono y la cámara. Elija «Permitir». Se verá en la pantalla antes de empezar.',
        'camera_notice_tu' => 'Cuando pulses el botón, tu teléfono te pedirá permiso para usar el micrófono y la cámara. Elige «Permitir». Te verás en la pantalla antes de empezar.',
        'ready' => 'Adelante',

        // Écran 2 — permission.
        'requesting' => 'Su teléfono va a pedirle permiso. Elija «Permitir».',

        // Écran 3 — enregistrement.
        'start' => 'Empezar',
        'tap_hint' => 'Pulse y luego hable, como por teléfono. Tómese todo el tiempo que quiera.',
        'tap_hint_tu' => 'Pulsa y luego habla, como por teléfono. Tómate todo el tiempo que quieras.',
        'tap_hint_video' => 'Si puede, colóquese frente a una ventana; luego pulse y cuente. Tómese todo el tiempo que quiera.',
        'tap_hint_video_tu' => 'Si puedes, colócate frente a una ventana; luego pulsa y cuenta. Tómate todo el tiempo que quieras.',
        'pause' => 'Pausa',
        'resume' => 'Continuar',
        'finish' => 'Terminar',
        'recording' => 'Grabando',
        'paused' => 'En pausa',
        'elapsed' => 'Duración: :time',
        'soft_warning' => 'Lleva diez minutos hablando. Tómese todo el tiempo que quiera; la grabación se detendrá sola a los veinte minutos.',
        'hard_stop' => 'La grabación se ha detenido a los veinte minutos. Lo que ha dicho está guardado: puede enviarlo.',
        'interrupted' => 'La grabación se ha interrumpido. Lo que ha dicho está guardado.',
        'interrupted_resume' => 'Continuar mi historia',
        'level_label' => 'Nivel del micrófono',

        // Écran 4 — vérification.
        'review_title' => '¿Quiere volver a escucharse?',
        'review_title_video' => '¿Quiere volver a verse?',
        'review_body' => 'Si le parece bien, envíela. Si no, puede volver a empezar.',
        'listen' => 'Volver a escuchar',
        'send' => 'Enviar',
        'restart' => 'Volver a empezar',
        'restart_confirm' => 'Si vuelve a empezar, se borrará lo que acaba de grabar. ¿Continuar?',

        // Écran 5 — envoi.
        'uploading' => 'Enviando',
        'uploading_notice' => 'No cierre esta página. Si ocurre de todos modos, su grabación queda guardada en su teléfono.',
        'upload_failed_title' => 'El envío no se ha completado',
        'upload_failed_body' => 'Su grabación está guardada en su teléfono. Puede volver a intentarlo.',
        'retry' => 'Reintentar',

        // Écran 6 — confirmation.
        'confirmed_title' => 'Su historia está grabada',
        'confirmed_body' => 'Gracias, :name.',
        'confirmed_next' => 'Ahora la pasamos a limpio. Usted la releerá antes de que nadie la escuche.',

        // Brouillon retrouvé au chargement.
        'draft_title' => 'Tiene una grabación sin terminar',
        'draft_body' => 'Hemos recuperado lo que había empezado a contar.',
        'draft_resume' => 'Continuar mi grabación',
        'draft_discard' => 'Volver a empezar',
        'storage_low' => 'Queda poco espacio en su teléfono. La grabación funciona, pero evite las respuestas muy largas.',

        'written_link' => 'Responder por escrito',
        'question_label' => 'Su pregunta',
    ],

    /*
     * L'aide quand c'est la caméra qui a été refusée (T-210). Un texte à
     * part, et pas une variante du précédent : envoyer quelqu'un dans le
     * réglage du micro alors qu'il a refusé la caméra le ferait tourner en
     * rond. La voix reste offerte comme issue, avant l'écrit.
     */
    'camera_help' => [
        'title' => 'La cámara no tiene permiso',
        'body' => 'Sin cámara no podemos grabarle en vídeo. Le explicamos cómo darle permiso; también puede responder solo con su voz.',
        'retry' => 'Reintentar',
        'ios' => 'En iPhone: abra Ajustes, baje hasta Safari, toque Cámara y elija «Preguntar» o «Permitir». Después vuelva a esta página.',
        'android' => 'En Android: toque el candado a la izquierda de la dirección, arriba en la pantalla, luego Permisos, luego Cámara, y elija «Permitir».',
        'samsung' => 'En Samsung Internet: toque el candado a la izquierda de la dirección, luego Permisos, luego Cámara, y elija «Permitir».',
        'other' => 'Busque el icono del candado junto a la dirección de esta página y permita la cámara.',
        'unsupported' => 'Su navegador no puede grabar vídeo. Puede responder con su voz, por escrito, o escribirnos para que le llamemos.',
    ],

    'mic_help' => [
        'title' => 'El micrófono no tiene permiso',
        'body' => 'Sin micrófono no podemos grabar su voz. Le explicamos cómo darle permiso.',
        'retry' => 'Reintentar',
        'ios' => 'En iPhone: abra Ajustes, baje hasta Safari, toque Micrófono y elija «Preguntar» o «Permitir». Después vuelva a esta página.',
        'android' => 'En Android: toque el candado a la izquierda de la dirección, arriba en la pantalla, luego Permisos, luego Micrófono, y elija «Permitir».',
        'samsung' => 'En Samsung Internet: toque el candado a la izquierda de la dirección, luego Permisos, luego Micrófono, y elija «Permitir».',
        'other' => 'Busque el icono del candado junto a la dirección de esta página y permita el micrófono.',
        'unsupported' => 'Su navegador no puede grabar sonido. Puede responder por escrito, o escribirnos para que le llamemos.',
    ],

    'written_answer' => [
        'title' => 'Responder por escrito',
        'body' => 'Escriba lo que habría contado. También es una historia.',
        'label' => 'Su respuesta',
        'counter' => ':count caracteres de :max',
        'send' => 'Enviar',
        'sent' => 'Gracias, su respuesta está guardada.',
    ],

    /*
     * Les trois choix de fin d'enregistrement (bloc 07).
     *
     * Toujours dans cet ordre, jamais présélectionnés, sans minuteur : le
     * dossier est formel, l'absence de réaction ne vaut jamais accord. Chaque
     * choix dit sa conséquence en une phrase, au présent, sans jargon.
     */
    'share_decision' => [
        'title' => '¿Qué quiere hacer con esta historia?',
        'body' => 'Es su relato. Usted decide, y podrá cambiar de opinión.',
        'share' => [
            'label' => 'Compartir con mis familiares',
            'hint' => 'Sus familiares podrán escucharla y leer el texto.',
        ],
        'keep_private' => [
            'label' => 'Guardarla para mí',
            'hint' => 'Nadie más que usted la escuchará.',
        ],
        'decide_later' => [
            'label' => 'Decidir más tarde',
            'hint' => 'Se lo volveremos a preguntar, sin insistir.',
        ],
        'recorded' => [
            'share' => 'Anotado: sus familiares podrán escucharla en cuanto el texto esté listo.',
            'keep_private' => 'Anotado: esta historia queda solo para usted.',
            'decide_later' => 'Anotado: se lo volveremos a preguntar más adelante.',
        ],
        'change' => 'Cambiar mi respuesta',
    ],

    /*
     * Relecture (bloc 07). Le mot à mot n'est pas caché : c'est la parole de
     * la personne, et elle a le droit de vérifier ce que la machine en a fait.
     */
    'review' => [
        'title' => 'Su historia está lista',
        'body' => 'Reléala, corríjala si quiere y díganos qué desea hacer con ella.',
        // Trois temps, numérotés à l'écran : on écoute, on relit, on décide.
        'steps' => [
            'listen' => 'Escuche',
            'read' => 'Relea',
            'decide' => 'Decida',
        ],
        'decide_body' => 'Es su relato. Usted decide, y podrá cambiar de opinión desde sus historias.',
        'listen' => 'Escuchar su grabación',
        'tab_fluide' => 'Texto pasado a limpio',
        'tab_verbatim' => 'Palabra por palabra',
        'edit' => 'Corregir el texto',
        'edit_label' => 'Su texto',
        'edit_help' => 'Cambie lo que quiera. Su grabación, en cambio, no se toca.',
        'save' => 'Guardar mi corrección',
        'cancel' => 'Cancelar',
        'saved' => 'Su corrección está guardada.',
        'empty' => 'El texto no puede estar vacío.',
        'no_audio' => 'La grabación todavía no está disponible para escucharla.',
        'visibility' => [
            'title' => '¿Quién puede escucharla?',
            'all_family' => 'Todos mis familiares',
            'choose' => 'Elegir quién puede escucharla',
            'book_only' => 'Solo para el libro',
            'book_only_hint' => 'Se imprimirá, pero nadie la escuchará en línea.',
        ],
        'keep_for_book' => 'Guardar esta historia para el libro',
        'keep_for_book_hint' => 'Se imprimirá, sin que nadie la escuche en línea.',
        /*
     * Opt-in (bloc 10). Le moment H0. Deux principes de rédaction : on
     * explique avant de demander, et on n'attend rien. « Non merci » est un
     * bouton de même taille que « J'accepte » — rendre le refus discret ne
     * produit pas des oui, ça produit des gens qui ne répondent pas.
     */
        'optin' => [
            'title' => ':inviter le regala un libro con sus recuerdos',
            'intro' => 'Le explicamos de qué se trata y lo que significa para usted. Tómese el tiempo de leerlo: nada empieza sin su acuerdo.',
            'what_it_means' => [
                'one' => 'Cada semana recibe una pregunta. La responde hablando, desde su teléfono, cuando quiera.',
                'two' => 'Pasamos su relato a limpio. Usted lo relee, lo corrige si quiere y **usted** decide quién puede escucharlo.',
                'three' => 'Al final, sus historias se convierten en un libro. Puede parar, hacer una pausa o borrarlo todo en cualquier momento.',
            ],
            'consents_title' => 'Sus acuerdos',
            'consents_intro' => 'Cada uno es independiente, y cada uno se puede retirar cuando usted quiera.',
            'read' => 'Leer el texto',
            'sensitive_title' => 'Temas personales',
            'preferences_title' => 'Cómo prefiere que le contactemos',
            'channel' => '¿Por qué medio?',
            'cadence' => '¿Con qué frecuencia?',
            'day' => '¿Qué día?',
            'slot' => '¿En qué momento del día?',
            'phone_confirm' => 'Su número',
            'address_form' => '¿Cómo prefiere que le hablemos?',
            'accept' => 'Acepto',
            'refuse' => 'No, gracias',
            'accepted' => 'Anotado. Le damos la bienvenida.',
            'refuse_title' => 'Prefiere no participar',
            'refuse_intro' => 'Es su decisión, y la respetamos. Si quiere, puede decirnos por qué. No es obligatorio.',
            'refuse_confirm' => 'Confirmar mi negativa',
            'welcome' => [
                'title' => 'Le damos la bienvenida, :first_name',
                'body' => 'Su primera pregunta llegará el :date. Hasta entonces, no tiene que hacer nada.',
                'body_soon' => 'Su primera pregunta llegará pronto. Hasta entonces, no tiene que hacer nada.',
                'vcard' => 'Añadir nuestro contacto a su teléfono',
                'vcard_why' => 'Sus preguntas llegarán desde este número: guardarlo evita que parezcan un mensaje desconocido.',
                'wishes_title' => 'Sus deseos para más adelante',
                'wishes_intro' => 'Puede decir desde ahora qué desea que ocurra con sus historias. O hacerlo más tarde, o nunca.',
                'wishes_later' => 'Más tarde',
            ],
            'farewell' => [
                'title' => 'Anotado',
                'body' => 'Gracias por decírnoslo. No volveremos a escribirle sobre este tema, y sus datos de contacto se borrarán en treinta días.',
            ],
        ],

        'thanks' => [
            'share' => 'Gracias. Sus familiares ya pueden escuchar esta historia.',
            'keep_private' => 'Gracias. Esta historia queda solo para usted.',
            'decide_later' => 'Gracias. Se lo volveremos a preguntar más adelante.',
        ],
    ],

    'thanks' => [
        'title' => 'Gracias',
        'body' => 'Puede cerrar esta página.',
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
        'title' => 'Sus historias',
        'empty' => 'Todavía no tiene ninguna historia grabada.',
        'empty_hint' => 'Irán apareciendo aquí semana tras semana, después de cada pregunta.',
        'actions' => 'Lo que puede hacer con esta historia',
        'pause_title' => '¿Necesita una pausa?',
        'pause_body' => 'Durante ese tiempo no le llegará ninguna pregunta. Retomará cuando quiera.',
        'pause_fewer' => 'Una semana menos',
        'pause_more' => 'Una semana más',
        'request' => [
            'title' => 'Acceder a sus historias',
            'body' => 'Indique el número de teléfono o la dirección de correo electrónico en los que recibe sus preguntas. Le enviaremos un código.',
            'label' => 'Número o correo electrónico',
            'send' => 'Recibir un código',
            // La même phrase, que la coordonnée soit connue ou non : une
            // réponse différente ferait de cette page un annuaire.
            'sent' => 'Si le conocemos, acabamos de enviarle un código. Es válido durante unos minutos.',
            'already_sent' => 'Ya le hemos enviado un código. Use ese: todavía es válido.',
            'have_code' => 'Ya tengo un código',
            'code_label' => 'Su código',
            'verify' => 'Abrir mis historias',
        ],
        'states' => [
            'recorded' => 'Grabada, transcripción en curso',
            'transcribed' => 'Guardada para usted',
            'to_review' => 'A la espera de su decisión',
            'validated' => 'Validada',
            'shared' => 'Compartida con sus familiares',
            'in_book' => 'En el libro',
            'hidden' => 'Oculta',
            'archived' => 'Archivada',
            'trashed' => 'En la papelera',
            'deleted' => 'Eliminada',
        ],
        'restorable_until' => 'Recuperable hasta el :date',
        'pause' => 'Pedir una pausa',
        'pause_weeks' => '¿Durante cuántas semanas?',
        'paused' => 'Anotado: ninguna pregunta durante :weeks semanas.',
        'paused_until' => 'Sus preguntas están en pausa hasta el :date.',
        /*
         * Le QR imprimé dans le livre (bloc 13). Le mot « désactiver » et non
         * « supprimer » : le livre reste, c'est l'écoute en ligne qui s'arrête,
         * et la distinction compte pour quelqu'un qui a le livre en main.
         */
        'qr' => [
            'title' => 'El código del libro',
            'help' => 'Cada historia impresa lleva un código que permite oír su voz. Puede apagarlo: el libro se queda, la escucha en línea se detiene.',
            'revoke' => 'Desactivar el código de esta historia',
            'restore' => 'Reactivar el código',
            'revoked' => 'El código está desactivado. El texto impreso no cambia.',
            'restored' => 'El código funciona de nuevo, el mismo de antes.',
        ],

    ],

    /*
     * Retraits (bloc 07). Cinq gestes, du plus doux au définitif. Chacun dit
     * ce qu'il fait et ce qu'il ne fait pas : « vous pourrez la remettre »
     * n'est pas une formule de politesse, c'est l'information qui permet
     * d'oser.
     */
    'withdrawals' => [
        'hide' => 'Ocultar esta historia',
        'hide_confirm' => '¿Ocultar esta historia? Podrá volver a mostrarla más tarde.',
        'hidden' => 'Esta historia está oculta. Puede volver a mostrarla cuando quiera.',
        'unhide' => 'Volver a mostrar esta historia',
        'unhidden' => 'Esta historia vuelve a estar visible.',
        'trash' => 'Enviar a la papelera',
        'trash_confirm' => '¿Enviar a la papelera? Tendrá treinta días para recuperarla.',
        'trashed' => 'Esta historia está en la papelera. Tiene treinta días para recuperarla.',
        'restore' => 'Recuperar esta historia',
        'restored' => 'Esta historia está recuperada.',
        'restore_window_closed' => 'El plazo de :days días ha pasado: esta historia ya no se puede recuperar.',
        'delete' => 'Eliminar definitivamente',
        'delete_confirm' => 'Esta eliminación es definitiva: la grabación y el texto se borrarán, y no podremos recuperarlos.',
        'delete_word' => 'ELIMINAR',
        'delete_word_label' => 'Escriba ELIMINAR para confirmar',
        'delete_word_missing' => 'Escriba :word en mayúsculas para confirmar la eliminación.',
        'deleted' => 'Esta historia está eliminada.',
        // Ce qui est imprimé est imprimé : le dire est la seule honnêteté
        // possible, et le taire serait promettre l'impossible.
        'printed_copies_warning' => 'Esta historia figura en un libro ya impreso. La retiramos del espacio en línea y de las próximas impresiones, pero no podemos cambiar nada en los ejemplares que ya tiene en casa.',
        'visibility_changed' => 'Anotado: solo los familiares que ha elegido pueden escucharla.',
    ],

    'already_recorded' => [
        'title' => 'Ya ha respondido a esta pregunta',
        'title_with_date' => 'Ya respondió a esta pregunta el :date',
        'body' => 'Puede volver a empezar si prefiere otra versión. Su primera grabación se conserva.',
        'restart' => 'Volver a empezar',
        'close' => 'Cerrar',
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
        'greeting' => 'Hola, :name,',
        'title' => ':inviter le regala algo',
        'from' => 'Un mensaje de :inviter',
        'listen_message' => 'Escuchar su mensaje',

        /*
         * L'ouverture (T-232) : un écran vide, le nom de marque, puis trois
         * phrases qui se succèdent avant la page. Le prénom reprend
         * `greeting` ; `greeting` d'ici sert quand on ne le connaît pas. Ce
         * qu'elles disent se retrouve sur la page : personne ne perd rien à
         * lire lentement.
         */
        'overture' => [
            'greeting' => 'Hola,',
            'offered' => ':inviter le ha regalado algo.',
            'promise' => 'Sus recuerdos, con su voz, para los suyos.',
        ],

        'means' => [
            'title' => 'Lo que esto significa para usted',
            'one' => 'Recibe una pregunta a la semana y la responde hablando, desde su teléfono. Con dos minutos basta.',
            'two' => 'Relee el texto antes de que nadie lo vea, y usted y solo usted decide qué se comparte. Nada sale sin su acuerdo.',
            'three' => 'Puede parar, ocultar una historia o borrarlo todo en cualquier momento, sin tener que justificarse.',
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
            'title' => 'Sus acuerdos',
            'summary' => 'Los textos completos de los cinco acuerdos, y su versión.',
            'before_accept' => 'Al pulsar «Acepto», usted da los cinco acuerdos descritos más abajo: la grabación de su voz, su transcripción, que una inteligencia artificial pase el texto a limpio, el compartirlo con sus familiares y los temas sensibles que sus relatos pueden tratar.',
            'intro' => 'Toque un acuerdo para leer su texto. Cada uno es independiente, y cada uno se puede retirar cuando quiera, sin afectar a los demás.',
            'version' => 'Versión :version',
        ],

        'settings' => [
            'title' => 'Cómo contactamos con usted',
            'hint' => 'La persona que le hace este regalo ya lo ha dejado todo configurado. Cambie solo lo que no le convenga.',
            'channel' => '¿Por qué medio?',
            'phone' => 'Su número de teléfono',
            'phone_hint' => 'Tal como lo marca, por ejemplo 612 34 56 78.',
            'phone_confirm' => 'Le escribiremos a este número: ¿es correcto?',
            'phone_required' => 'Necesitamos un número para enviarle SMS.',
            'email' => 'Su dirección de correo electrónico',
            'email_hint' => 'Allí enviaremos cada pregunta.',
            'email_required' => 'Necesitamos una dirección para enviarle correos electrónicos.',
            'cadence' => '¿Con qué frecuencia?',
            'day' => '¿Qué día?',
            'slot' => '¿En qué momento del día?',
            'address_form' => '¿Prefiere que le tratemos de «usted» o de «tú»?',
        ],

        /*
         * Les souhaits pour plus tard, repliés sous les accords (T-236) :
         * « transmettre à ma famille » est proposé d'avance parce que c'est ce
         * qui arrivera de toute façon sans directive (doc 04 §6). Rien n'est
         * écrit tant que la personne ne choisit pas autre chose ou ne désigne
         * personne. La ligne sous le titre le dit sans qu'il faille ouvrir.
         */
        'advanced' => [
            'title' => 'Ajustes avanzados',
            'summary' => 'Cuando usted ya no esté: sus historias podrán transmitirse a su familia, salvo que decida otra cosa.',
            'wishes_title' => 'Sus deseos para más adelante',
            'wishes_body' => 'Lo que habrá que hacer con sus historias tras su fallecimiento. Sus deseos prevalecen sobre lo que pidan sus familiares, y podrá cambiarlos cuando quiera.',
            'referent' => 'La persona a la que nos dirigiremos (opcional)',
            'referent_contact' => 'Cómo contactar con ella, teléfono o correo electrónico (opcional)',
        ],

        'days' => [
            '1' => 'Lunes',
            '2' => 'Martes',
            '3' => 'Miércoles',
            '4' => 'Jueves',
            '5' => 'Viernes',
            '6' => 'Sábado',
            '7' => 'Domingo',
        ],

        'accept' => 'Acepto',
        'refuse' => 'No, gracias',
        'accepted' => 'Anotado. Le damos la bienvenida.',
        'already_answered' => 'Ya ha respondido a esta invitación. Si desea cambiar de opinión, escríbanos a :email.',
        'no_password' => 'Esta página nunca le pedirá una contraseña, un pago ni un código.',

        'refusal' => [
            'title' => 'Prefiere no participar',
            'body' => 'Es su decisión, y la respetamos. ¿Quiere decirnos por qué? No es obligatorio.',
            'no_reason' => 'Prefiero no decir nada',
            'confirm' => 'Confirmar mi negativa',
            'back' => 'Volver atrás',
        ],
    ],

    'optin_welcome' => [
        'title' => 'Le damos la bienvenida, :name',
        'body' => 'Su primera pregunta llega :when. No tiene que instalar nada ni preparar nada.',
        'when_unknown' => 'muy pronto',
        'vcard' => [
            'title' => 'Añádanos a sus contactos',
            'body' => 'Nuestros mensajes llegarán siempre desde este contacto. Si le llega un mensaje desde otro sitio imitándonos, es falso.',
            'button' => 'Añadir el contacto',
        ],
        /*
         * Plus de question ici (T-236) : les souhaits se choisissent à
         * l'acceptation. On dit ce qui vaut, et où le changer.
         */
        'wishes' => [
            'title' => 'Sus deseos para más adelante',
            'default' => 'Salvo que usted decida otra cosa, sus historias podrán transmitirse a su familia. Podrá precisarlo cuando quiera, desde su espacio personal.',
            'saved' => 'Sus deseos están guardados. Podrá cambiarlos cuando quiera.',
        ],
    ],

    'optin_farewell' => [
        'title' => 'Anotado',
        'body' => 'No volveremos a escribirle sobre este tema. Sus datos de contacto se eliminarán en un plazo de treinta días.',
        'reassure' => 'La persona que le invitó está avisada, con tacto, y se le devolverá el dinero si así lo desea.',
    ],

];
