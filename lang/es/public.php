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
        'description' => 'Una pregunta a la semana, su voz que responde y el libro encuadernado de sus recuerdos. Sin aplicación ni cuenta que crear. Nada se comparte sin su acuerdo.',
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
            'title' => ':brand — el libro de sus recuerdos, con su voz',
            'description' => 'Una pregunta a la semana, responde hablando y sus historias se vuelven un libro encuadernado con un código QR por capítulo para oír su voz. :price, sin suscripción.',
        ],
        'how' => [
            'title' => 'Cómo funciona',
            'description' => 'El recorrido en seis pasos: eliges las preguntas, tu ser querido responde hablando, sus palabras se convierten en un capítulo, relee y el libro llega.',
        ],
        'books' => [
            'title' => 'Nuestros libros: qué hay dentro',
            'description' => 'Tapa dura, impresión en color, un capítulo por historia y un código QR que reproduce la grabación original: lo que abres cuando llega el libro.',
        ],
        'faq' => [
            'title' => 'Preguntas frecuentes',
            'description' => 'Qué incluye la compra, qué pasa después del año, quién puede escuchar las historias y qué hacemos con tus grabaciones.',
        ],
        'demo' => [
            'title' => 'Probar en 60 segundos',
            'description' => 'Grábate, escúchate y mira el resultado. No se envía nada: todo se queda en tu dispositivo y desaparece cuando cierras la página.',
        ],
        // Une page légale par couple : servies par un seul composant, elles
        // partageaient sinon la même description.
        'terms' => [
            'title' => 'Condiciones generales de venta',
            'description' => 'Qué incluye la compra, la garantía de devolución de treinta días, la entrega del libro y nuestros compromisos de conservación.',
        ],
        'privacy' => [
            'title' => 'Política de privacidad',
            'description' => 'Qué datos recoge :brand, dónde se alojan, cuánto tiempo se conservan y cómo pedir su exportación o su eliminación.',
        ],
        'imprint' => [
            'title' => 'Aviso legal',
            'description' => 'El editor del sitio, su proveedor de alojamiento, sus datos de contacto y la información que la ley francesa exige a toda actividad en línea.',
        ],
        'consents' => [
            'title' => 'Tus acuerdos',
            'description' => 'Los acuerdos que la persona que narra da uno por uno — grabación, transcripción, texto escrito, compartir — y cómo retirarlos con un gesto.',
        ],
        'legal' => [
            'title' => 'Información legal',
            'description' => 'Los textos que comprometen a :brand, en su versión vigente.',
        ],
        // Le nom et la description de l'objet vendu, pour la donnée
        // structurée. Distincts de ceux d'une page : ils nomment le livre.
        'product' => [
            'title' => 'El libro de una vida que se puede escuchar',
            'description' => 'Un año de preguntas, las historias de tu ser querido pasadas a limpio y un libro encuadernado en color con un código QR por capítulo que reproduce su voz.',
        ],
        'checkout' => [
            'title' => 'Regalar el libro',
            'description' => 'El proceso de compra de :brand.',
        ],
    ],

    /*
     * Le bandeau de consentement (T-227). Deux boutons de même poids, une
     * phrase qui dit ce qu'on pose et pourquoi, et rien qui minimise le refus.
     */
    'consent' => [
        'title' => 'Tus opciones sobre las cookies',
        'body' => 'Usamos cookies para medir la audiencia del sitio y la eficacia de nuestra publicidad. Solo se instalan con tu acuerdo, y puedes cambiar de opinión en cualquier momento desde el pie de página.',
        'accept' => 'Acepto',
        'refuse' => 'Rechazo',
        'more' => 'Más información',
        'manage' => 'Gestionar las cookies',
    ],

    'vcard' => [
        'note' => 'Sus preguntas de la semana llegan desde este contacto. Nunca le pediremos una contraseña ni un pago por SMS.',
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
        'promise' => 'El libro de sus recuerdos, con su voz en cada página.',
        'seo_title' => 'El libro de sus recuerdos, con su voz en cada página',
        'cta' => 'Regalar este libro',
        'cta_start' => 'Empezar su libro',
        'cta_how' => 'Cómo funciona',
        'cta_try' => 'Pruébalo en 60 segundos',
        'cta_see_book' => 'Ver el libro',

        // Le bandeau en haut de toutes les pages publiques : l'offre en une ligne.
        'bar' => 'Un año de preguntas + el libro encuadernado: todo incluido, :price',

        'nav' => [
            'how' => 'Cómo funciona',
            'book' => 'El libro',
            'story' => 'Nuestra historia',
            'faq' => 'Preguntas',
            'login' => 'Iniciar sesión',
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
            'lede' => 'Cada semana, una pregunta. Tu ser querido responde hablando, desde su teléfono. Al cabo de un año, el libro encuadernado de sus historias, y su voz en cada página.',
            'note' => 'Un solo pago seguro, sin suscripción. Sus recuerdos siguen siendo privados.',
            'checks' => [
                'voice' => 'Su voz se vuelve a escuchar en cada página del libro.',
                'no_app' => 'Ninguna aplicación, ninguna contraseña: un enlace, y habla.',
                'kept_words' => 'Sus palabras se pasan a limpio, nunca se reescriben. La transcripción palabra por palabra se conserva.',
                'she_decides' => 'Es ella quien decide lo que escucha la familia.',
            ],
            // La carte « question de la semaine », posée sur la photo : retirée par
            // T-142, reprise le soir même à la demande du fondateur (T-144).
            'card' => [
                'aria' => 'Ejemplo de pregunta de la semana',
                'label' => 'Pregunta de la semana',
                'name' => 'Odette',
                'question' => '¿Qué olor le devuelve a su infancia?',
                'answers' => 'Responde hablando.',
                'duration' => '2 min 14',
                // La mention affichée sous le bouton quand la page la
                // demande — `product.landing.hero_sample_disclosed`. Elle est
                // décrochée depuis le 5 septembre 2026 ; le texte reste ici,
                // prêt à resservir.
                'synthetic' => 'Ejemplo: voz sintética. Las historias reales las cuentan voces reales.',
                // La transcription, pour qui n'entend pas : WCAG 2.2 AA 1.2.1
                // demande un équivalent à tout média sonore. Elle n'est pas
                // affichée — la carte est posée sur la photo et n'a pas la
                // place — mais elle est lue par les lecteurs d'écran, juste
                // après le bouton. Elle doit suivre l'audio **au mot près**.
                'transcript_label' => 'Lo que cuenta Odette en este fragmento',
                'transcript' => 'Oh… el olor del pan. Sin dudarlo. El pan que se hace. Entonces eh… mi abuela vivía en Saint-Aubin, bueno, Saint-Aubin-du-Cormier, y eh cada domingo íbamos, íbamos en coche con mi padre, se tardaba… ya no me acuerdo, una hora de camino quizá. Y ella hacía el pan ella misma, en el horno, el horno de leña detrás de la casa. Y lo olíamos antes de llegar, ¿eh? Bueno — yo lo olía. Mi padre decía que me inventaba cosas, pero no. No, no. Lo olía, desde la curva. Y ella nos cortaba un trozo enseguida, todavía caliente, con mantequilla salada. Y… ya está. Es eso. Es ese olor.',
            ],
            'photo_alt' => 'Una mujer mayor y su hija, abrazadas en un sofá, sostienen el libro encuadernado que acaban de desenvolver.',
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
            'title' => 'Por qué regalarlo',
            'ask' => 'El regalo que nadie se atreve a pedir.',
            'voice' => 'Regalas un libro. Recibes su voz.',
            'weekly' => 'Se abre cada semana, durante un año.',
        ],

        'what' => [
            'title' => 'Qué es :brand',
            'headline' => 'Un libro con las historias de su vida, contadas con su voz.',
            'body' => ':brand convierte un año de recuerdos contados de viva voz, una pregunta a la semana, en un libro encuadernado de historias pasadas a limpio. Cada capítulo lleva un código para escanear que reproduce la grabación original: se lee la historia que contó, y se la oye contarla.',
        ],

        'how' => [
            'title' => 'Cómo funciona',
            'headline' => 'Su voz, con solo escanear.',
            // Le titre promet un scan sans dire de quoi : le chapeau nomme le
            // QR code et ce qu'il fait. On dit ce qu'il joue, jamais qu'il
            // vivrait sans nous — « QR autonomes » est interdit (R-11), et la
            // durée d'engagement se publie ailleurs (R-10).
            'lede' => 'Nada que instalar, nada que escribir. Un año de preguntas, a su ritmo, y un libro al final: cada capítulo lleva un código QR que reproduce su voz.',
            'one' => [
                'title' => 'Tú eliges las preguntas',
                'body' => 'Entre sesenta preguntas escritas para hacer aflorar las historias que la familia nunca ha oído. O nos dejas hacer a nosotros.',
                'alt' => 'Una mano sostiene dos fotografías antiguas de familia.',
            ],
            'two' => [
                'title' => 'Llega una pregunta. Ella habla.',
                'body' => 'Cada semana, por SMS o por correo electrónico. Ni aplicación, ni cuenta, ni contraseña. Abre el enlace y cuenta, desde su teléfono.',
                'alt' => 'Una mujer mayor, junto a una ventana, habla sonriendo al teléfono que sostiene ante ella.',
            ],
            'three' => [
                'title' => 'Sus palabras se vuelven un capítulo',
                'body' => 'Las vacilaciones desaparecen, sus giros se quedan. La transcripción palabra por palabra se conserva junto al texto pasado a limpio, y ella lo relee antes que nadie.',
                'alt' => 'Una mujer mayor sostiene ante ella un libro encuadernado verde, titulado « Relatos de mi vida ».',
            ],
            'four' => [
                'title' => 'La familia la oye enseguida',
                'body' => 'Cada historia que ella decide compartir llega a sus familiares. La leen, la escuchan, le responden con una palabra. Para muchas familias, es el mejor momento de la semana.',
                'alt' => 'Dos personas inclinadas sobre un libro abierto: una señala el código de un capítulo, la otra sostiene un teléfono donde sonríe la narradora.',
            ],

            /*
             * Le lien vers « Comment ça marche », que le témoin affiche sous
             * ses quatre étapes (T-220). La clé n'existait que sous `lp` : le
             * témoin appelait `public.landing.how.more` et n'obtenait rien.
             * Même libellé que la variante — deux formulations pour un même
             * lien finiraient par diverger.
             */
            'more' => 'Ver el recorrido en detalle',
            // Vers la page qui déroule le parcours en six étapes (T-213).
        ],

        // Notre histoire : celle du fondateur, à la première personne, sans le nommer.
        'story' => [
            'title' => 'Nuestra historia',
            'p1' => 'Tomé conciencia de mi familia y de su historia demasiado tarde. Cuando mis abuelos se fueron, me di cuenta de que casi no sabía nada de su vida. Y de que con ellos se iba una parte de la historia de mi familia.',
            'p2' => 'Entonces busqué una manera de guardar lo que quedaba: la voz de quienes siguen aquí, y lo que tienen ganas de contar. No un cuaderno que rellenar, nadie lo rellena. Una pregunta de vez en cuando, a la que se responde hablando, como se responde al teléfono.',
            'p3' => 'De ahí viene este libro. No sustituye las conversaciones que no tuvimos. Hace que haya otras, y que se puedan volver a abrir.',
        ],

        // Le bloc produit, comme une fiche : ce qu'on achète, ce que ça contient.
        'product' => [
            'title' => 'El libro de una vida que se puede escuchar',
            'lede' => 'Un año de preguntas que convierte los recuerdos contados por tu ser querido en un libro encuadernado de historias escritas.',
            'read' => [
                'title' => 'Leer la historia.',
                'body' => 'Cada capítulo es una historia que ella contó, pasada a limpio sin inventar nada.',
            ],
            'hear' => [
                'title' => 'Oírla contarla.',
                'body' => 'Un código para escanear en cada capítulo reproduce la grabación original. Su voz, tal como la dijo.',
            ],
            'bound' => [
                'title' => 'Encuadernado para durar.',
                'body' => 'Un libro encuadernado, en color, con las fotos que la familia ha añadido. El formato se adapta a lo que se ha contado.',
            ],
            'includes' => [
                'questions' => 'Un año de preguntas, una por semana',
                'device' => 'Ella responde desde cualquier teléfono',
                'download' => 'Todas las grabaciones descargables, en cualquier momento',
                'book' => 'Un libro encuadernado, en color',
                'qr' => 'Un código para escanear por capítulo',
                'family' => 'La familia invitada a escuchar y a reaccionar',
            ],
            'guarantees' => [
                'refund' => 'Satisfecho o te devolvemos el dinero, 30 días',
                'yours' => 'Sus historias son tuyas',
                'download' => 'Descargables en cualquier momento',
            ],
            'mockup' => [
                'cover_title' => 'Las historias de Odette',
                'cover_sub' => 'contadas por ella misma',
                'chapter' => 'El olor del pan de mi abuela',
                'scan' => 'Escanea para oírla contarlo',
                'aria' => 'Maqueta del libro y de la página de escucha',
            ],
        ],

        'forever' => [
            'headline' => 'Sus recuerdos se quedan en la familia. No hay nada que renovar.',
            'lede' => ':brand incluye un año de preguntas, el libro encuadernado y el acceso a todo lo que hayas recogido, mucho después de la última pregunta.',
            'title' => 'Lo que incluye tu compra',
            'access' => [
                'title' => 'Un acceso que no se acaba con el año',
                'body' => 'Todo lo que tu ser querido grabe durante el año, y todo lo que se haga con ello, sigue accesible después, sin pagar nada más.',
            ],
            'download' => [
                'title' => 'Todo se descarga',
                'body' => 'Las grabaciones originales y los textos, en tu propio dispositivo, cuando quieras. Tus datos nunca se retienen.',
            ],
            'no_sub' => [
                'title' => 'Un solo pago',
                'body' => 'Sin suscripción, sin renovación discreta. Regalas el año, ella cuenta a su ritmo, el libro llega.',
            ],
            'banner' => 'Sus historias siguen siendo tuyas. No hay nada que renovar.',
            'per' => 'un año de preguntas y el libro encuadernado',
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
            'title' => 'Sin sorpresas desagradables',
            'no_app' => 'Ni aplicación, ni contraseña',
            'one_payment' => 'Un solo pago, sin suscripción',
            'refund' => 'Satisfecho o te devolvemos el dinero durante 30 días',
        ],

        'guarantee' => [
            'headline' => 'Satisfecho o te devolvemos el dinero durante treinta días. Si la primera grabación no te emociona, te devolvemos el dinero.',
            'body' => 'Sin tener que justificarte. Basta con decírnoslo desde tu espacio personal.',
        ],

        'tested' => [
            'title' => 'Pensado para los abuelos. Aprobado por la familia.',
            'lede' => 'Para quienes cuentan, de 9 a 99 años.',
            'no_writing' => 'Nada que escribir.',
            'no_app' => 'Nada que instalar.',
            'no_password' => 'Ninguna contraseña.',
            'cta' => 'Probar: solo lleva 60 segundos',
            'photo_alt' => 'Una mujer mayor habla a su teléfono, sostenido con el brazo extendido, sin nada más que manejar.',
        ],

        'try' => [
            'title' => 'Pruébalo en 60 segundos',
            'body' => 'Grábate, vuelve a escucharte. No se envía nada: todo se queda en tu dispositivo y desaparece cuando cierras la página.',
        ],

        'book' => [
            'title' => 'El libro',
            'headline' => 'La foto, la historia y la voz, en una misma página.',
            'body' => 'Cada capítulo lleva un código para escanear que reproduce la grabación original. Se oye cada historia exactamente como fue contada, con su voz.',
            'qr' => 'Los códigos de tu libro llevan a las grabaciones mientras el servicio exista. Si tuviéramos que cesar nuestra actividad, te avisaríamos y te entregaríamos tus archivos.',
            'photo_alt' => 'El libro encuadernado verde, de pie sobre una mesa de madera entre libros antiguos, con un teléfono al lado.',
        ],

        'review' => [
            'headline' => 'Mira cómo su relato toma forma.',
            'body' => 'La transcripción palabra por palabra a un lado, el texto pasado a limpio al otro, y nada inventado entre ambos. Ella relee, corrige una palabra si quiere, y luego decide lo que oirá la familia.',
            'screenshot_alt' => 'La página de relectura en un teléfono: la grabación para volver a escucharla, y luego el texto pasado a limpio y la transcripción palabra por palabra, uno al lado del otro.',
        ],

        'proof' => [
            'aria' => 'Ejemplo: la transcripción palabra por palabra y el texto pasado a limpio, uno al lado del otro',
            'verbatim' => 'Palabra por palabra',
            'fluide' => 'Texto pasado a limpio',
            'sample_verbatim' => 'entonces eh… mi abuela vivía en Saint-Aubin, bueno, Saint-Aubin-du-Cormier, y eh cada domingo íbamos, íbamos en coche con mi padre, se tardaba… ya no me acuerdo, una hora de camino quizá. Y ella hacía el pan ella misma, en el horno, el horno de leña detrás de la casa.',
            'sample_fluide' => 'Mi abuela vivía en Saint-Aubin-du-Cormier, y cada domingo íbamos en coche con mi padre. Se tardaba… ya no me acuerdo, una hora de camino quizá. Y ella hacía el pan ella misma, en el horno de leña detrás de la casa.',
            'then' => 'Luego ella elige:',
            'share' => 'Compartir',
            'keep' => 'Guardar para mí',
            'later' => 'Decidir más tarde',
        ],

        'gift' => [
            'headline' => 'Programa el envío del regalo',
            'body' => 'Elige la fecha: ese día, tu ser querido recibe tu mensaje y el enlace de su primera pregunta. También puedes imprimir una tarjeta para meterla en un sobre.',
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
            'title' => 'Nuestros compromisos',
            'lede' => 'Las mismas palabras aquí, en las condiciones generales y en nuestros correos electrónicos.',
            'validation' => 'La validación es explícita, nunca tácita: nada es visible para los familiares sin el acuerdo de quien lo ha contado.',
            'no_cloning' => 'Sin clonación de voz: nunca imitamos una voz, ni la fabricamos.',
            'ai_arranges' => 'La IA ordena, no inventa: quita las vacilaciones y añade la puntuación. No añade ningún hecho.',
            'source_audio' => 'La grabación original se conserva y nunca se sustituye. La transcripción palabra por palabra sigue accesible junto al texto pasado a limpio.',
            'no_training' => 'Ningún contenido de tu familia sirve para entrenar un modelo.',
            'eu_hosting' => 'Tus grabaciones y tus textos se alojan en la Unión Europea.',
            'withdrawal' => 'Quien narra puede ocultar, retirar o eliminar una historia en cualquier momento, sin justificarse.',
        ],

        'price' => [
            'title' => 'El precio',
            'prevente' => 'Preventa',
            'prevente_body' => 'Reservas ahora, el servicio empieza en la apertura. Reembolsable hasta el inicio.',
            'phone_option' => 'Grabación por teléfono',
            'phone_option_body' => 'Una persona de nuestro equipo llama a tu ser querido cada semana y graba la historia. Nada que manejar por su parte.',
            'reassurance' => 'Pago seguro · Satisfecho o te devolvemos el dinero en 30 días',
        ],

        'faq' => [
            'title' => 'Preguntas frecuentes',
            'included' => [
                'q' => '¿Qué incluye mi compra?',
                'a' => 'Un año de preguntas, una por semana. Las historias pasadas a limpio, con la transcripción palabra por palabra conservada. La escucha para toda la familia. El libro encuadernado, con un código para escanear por capítulo. Y todas las grabaciones, descargables en cualquier momento.',
            ],
            'subscription' => [
                'q' => '¿Es una suscripción?',
                'a' => 'No. Un solo pago cubre el año y el libro. Después del año, todo lo que se haya recogido sigue siendo tuyo, y puedes descargarlo todo. No hay nada que renovar.',
            ],
            'edit' => [
                'q' => '¿Se puede corregir el texto escrito?',
                'a' => 'Sí. Quien narra relee cada historia antes que nadie, y corrige una palabra si quiere. La transcripción palabra por palabra se conserva al lado, en todos los casos.',
            ],
            'no_smartphone' => [
                'q' => '¿Y si mi ser querido no tiene smartphone?',
                'a' => 'La opción por teléfono existe para eso: llamamos y grabamos la conversación. Se ofrece como opción, en número limitado.',
            ],
            'refuses' => [
                'q' => '¿Y si se niega?',
                'a' => 'Está previsto, y se respeta. Te devolvemos el dinero íntegramente, sin tener que justificarte.',
            ],
            'writing' => [
                'q' => '¿Hay que saber escribir o manejar un ordenador?',
                'a' => 'No. Todo se hace hablando, desde un teléfono, tocando un enlace recibido por mensaje.',
            ],
            'privacy' => [
                'q' => '¿Quién puede escuchar las historias?',
                'a' => 'Solo los familiares que quien narra ha autorizado. Ella decide historia por historia, y puede cambiar de opinión.',
            ],
            'refund' => [
                'q' => '¿Y si el resultado no me convence?',
                'a' => 'Tienes treinta días: si la primera grabación no te emociona, te devolvemos el dinero íntegramente, sin tener que justificarte.',
            ],
            'shutdown' => [
                'q' => '¿Qué pasa si cesáis vuestra actividad?',
                'a' => 'Te avisamos con al menos tres meses de antelación, te entregamos la totalidad de tus grabaciones y de tus textos en un formato legible sin nosotros, y te devolvemos lo que no se haya entregado. No prometemos una conservación de por vida: prometemos no dejarte nunca sin tus archivos.',
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
        'seo_title' => 'Regala el libro de su vida y recupera su voz',

        'cta' => [
            'buy' => 'Regalo su libro',
            'buy_price' => 'Regalo su libro · :price',
            'start' => 'Empiezo su libro',
            'how' => 'Cómo funciona',
        ],

        // S00. Le bandeau et la navigation de la variante : ses propres ancres.
        'nav' => [
            'how' => 'Cómo funciona',
            'book' => 'El libro',
            'faq' => 'Preguntas frecuentes',
            'login' => 'Iniciar sesión',
            'menu' => 'Abrir el menú',
            'menu_close' => 'Cerrar el menú',
            // Le bandeau de tête, en deux morceaux : la seconde moitié est en
            // gras, et un `<strong>` dans une chaîne traduite serait du
            // balisage dans le catalogue.
            'bar' => 'Grabaciones ilimitadas + Libro encuadernado:',
            'bar_strong' => 'todo por :price',
        ],

        // S01. Le héros : la promesse, l'action, la preuve visuelle.
        'hero' => [
            'title' => 'Un libro de recuerdos que te permite oír su voz para siempre.',
            'lede' => 'Ellos solo tienen que hablar. :brand lo capta, lo escribe y lo reúne en una bonita tapa dura con su voz en cada página. No hace falta escribir nada. Solo sus historias, con su propia voz, en un libro que tu familia guardará durante generaciones.',
            'checks' => [
                'voice' => 'Su voz y la transcripción en cada página',
                'no_app' => 'Funciona en cualquier teléfono, sin aplicación ni inicio de sesión',
                'digital' => 'Entrega digital disponible',
                'no_writing' => 'No hace falta escribir, solo hablan',
            ],
            // La vignette posée sur la photo : le livre, pour qu'il ne
            // disparaisse pas du cadrage. Aucun faux badge d'avis.
            'thumb' => [
                'label' => 'El libro encuadernado',
                'body' => 'Un código QR por capítulo',
            ],
        ],

        // S02. Le bandeau sombre sous le héros : trois appréciations, cinq
        // étoiles chacune, celle du milieu plus grande.
        'trust' => [
            'title' => 'Lo que dicen de él',
            'stars' => 'Cinco estrellas sobre cinco',
            'quotes' => [
                'one' => '« El regalo perfecto »',
                'two' => '« Un regalo para tu yo del futuro »',
                'three' => '« Fabuloso »',
            ],
        ],

        // S03. Trois raisons de l'offrir. Nos phrases, sans guillemets.
        'benefits' => [
            'title' => 'Tres razones para regalarlo',
            'discover' => [
                'title' => 'Descubrir lo que aún no sabes',
                'body' => 'Los recuerdos de infancia, los encuentros, los pequeños detalles: dale la ocasión de contártelos.',
            ],
            'voice' => [
                'title' => 'Recuperar su forma de contar',
                'body' => 'El libro guarda el relato. La grabación permite recuperar la voz, los silencios y las risas.',
            ],
            'share' => [
                'title' => 'Compartir mucho más que un regalo',
                'body' => 'Una pregunta cada semana abre una nueva conversación con tu ser querido.',
            ],
            'quotes_title' => 'Lo que dicen las familias',
        ],

        // S04. Le concept, juste après le bandeau : l'œillet et le titre à
        // gauche, le paragraphe à droite.
        'what' => [
            'eyebrow' => 'Qué es :brand',
            'title' => 'Un libro sobre la vida de tu ser querido, contada con su voz.',
            'body' => ':brand convierte un año de recuerdos contados de viva voz cada semana en un libro encuadernado de historias magníficamente escritas. Cada capítulo tiene un código QR que reproduce la grabación original, de modo que puedes leer la historia que contó tu ser querido y oírsela contar.',
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
            'eyebrow' => 'Cómo funciona',
            'step' => 'Paso :number',
            'one' => [
                'title' => "Elige las preguntas.\nHazlas personales.",
                // La carte posée sur la photo de la première étape.
                'card_label' => 'Pregunta de la semana',
                'card_question' => '¿Qué olor le devuelve a su infancia?',
                'body' => 'Elige entre cientos de sugerencias pensadas para hacer aflorar historias que tu familia nunca ha oído. O importa tus fotos para descubrir la historia que hay detrás de cada una.',
            ],
            'two' => [
                'title' => "Llega una pregunta.\nSolo tienen que hablar.",
                'body' => 'Cada semana, :brand les envía una pregunta por correo electrónico o por SMS. Sin aplicación. Sin cuenta. Sin contraseña. Dos clics y la grabación empieza, en cualquier dispositivo.',
            ],
            'three' => [
                'title' => "Sus palabras se convierten\nen una historia escrita.",
                'body' => 'Speech-to-Story™ convierte cada grabación en un capítulo cuidado. Transcripción palabra por palabra o relato fluido. Totalmente modificable.',
            ],
            'four' => [
                'title' => "Tu familia la descubre\nen cuanto está lista.",
                'body' => 'Cada nueva historia se comparte al instante con toda la familia. Se lee, se escucha, se reacciona. Para muchas familias, se convierte en el mejor momento de la semana.',
            ],
            'more' => 'Ver el recorrido en detalle',
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
            'eyebrow' => 'Nuestra historia',
            'title' => 'El origen de :brand',
            'p1' => 'Cuando mis abuelos se fueron, me di cuenta de que al final sabía muy poco de su vida. Tenía sus fotos, algunos recuerdos, pero tantas preguntas que nunca les había hecho.',
            'p2' => 'Me habría gustado oírles contarme su infancia, sus encuentros, sus recuerdos más bonitos. Y sobre todo, poder volver a escuchar su voz hoy.',
            'p3' => 'De ese arrepentimiento nació :brand: una manera sencilla de recoger las historias de quienes queremos, sin tener que escribir. Una pregunta, un teléfono y unos minutos para contar.',
            'p4' => 'Porque un día, esas anécdotas, esos pequeños detalles y esa voz que conocemos de memoria tendrán un valor imposible de medir.',
            'p5' => ':brand existe para preservarlas, mientras todavía haya tiempo de contarlas.',
            'name' => 'Nicolas Serra',
            'role' => 'Fundador',
            'photo_alt' => 'Nicolas Serra, fundador, al aire libre en un parque.',
        ],

        /*
         * S07. La fiche produit, dans la structure du leader : vignettes
         * verticales, grande image, carte d'écoute dessous, et à droite le
         * bandeau de notoriété, les trois bénéfices, ce que comprend l'achat,
         * le bouton et les trois réassurances.
         */
        'offer' => [
            'badge' => 'Más vendido',
            'rating' => '4,9',
            'stars' => 'Cinco estrellas sobre cinco',
            'title' => 'El libro de una vida que se puede escuchar',
            'lede' => 'Un año de preguntas que convierten los recuerdos contados por tu ser querido en un bonito libro encuadernado.',
            'gallery' => [
                'aria' => 'Vistas del libro',
                'thumb' => 'Ver: :label',
                'next' => 'Vista siguiente',
                'closed' => 'El libro cerrado',
                'phone' => 'El libro y el teléfono',
                'held' => 'El libro sostenido en la mano',
                'photos' => 'Las fotos de familia',
                'family' => 'El libro regalado en familia',
            ],
            'read' => [
                'title' => 'Lee la historia.',
                'body' => 'Cada capítulo es una historia contada por tu ser querido, convertida en un texto elegante por el Speech-to-Story™ de :brand.',
            ],
            'hear' => [
                'title' => 'Escúchala de viva voz.',
                'body' => 'Un código QR en cada página reproduce la grabación original. Su voz. Para siempre.',
            ],
            'bound' => [
                'title' => 'Hecho para durar.',
                'body' => 'Tapa dura, todo en color, formato 20 × 25 cm. Hasta 380 páginas. Impresión profesional en papel de doble grosor.',
            ],
            'includes' => [
                'questions' => '1 año de preguntas ilimitadas',
                'book' => '1 libro impreso en color',
                'device' => 'Grabación desde cualquier dispositivo',
                'qr' => 'Códigos QR que reproducen las grabaciones',
                'download' => 'Descarga y vuelve a escuchar las grabaciones en cualquier momento',
                'family' => 'Invita a la familia a participar por el camino',
            ],
            'buy' => 'Comprar • :price',
            'guarantees' => [
                'refund' => 'Garantía de satisfacción o devolución en 30 días',
                'yours' => 'Tus historias son tuyas para siempre',
                'download' => 'Descargables en cualquier momento',
            ],
            'player' => [
                'label' => 'Escuchar ahora',
                'title' => 'El olor del pan de mi abuela',
                'attribution' => 'Ejemplo presentado en :brand: el relato de Odette',
                'transcript_show' => 'Leer la transcripción',
                'transcript_hide' => 'Ocultar la transcripción',
                // Sans audio exploitable : l'extrait écrit, sans bouton de
                // lecture ni durée inventée.
                'read_instead' => 'Leer un ejemplo de relato',
            ],
        ],

        /*
         * S08. Ce que l'achat comprend, dans la structure du leader : titre
         * centré, chapeau, un intertitre sur filet, puis une grande carte —
         * trois colonnes à picto rond, un bandeau doré, le prix et le bouton
         * face à une image —, et une bande de confiance dessous.
         */
        'access' => [
            'title' => 'Las historias de tu familia pertenecen a tu familia. Para siempre.',
            'lede' => ':brand incluye un año completo de relatos, un libro encuadernado y un acceso permanente a los recuerdos que crees. Aunque no renueves.',
            'includes_label' => 'Tu compra incluye',
            'forever' => [
                'title' => 'Un acceso a tus historias para siempre',
                'body' => 'Todo lo que tu ser querido grabe y cree durante el año es tuyo, aunque no renueves.',
            ],
            'download' => [
                'title' => 'Descargas en un clic',
                'body' => 'Guarda los archivos originales en tu dispositivo cuando quieras. Tus datos nunca se retienen como rehenes.',
            ],
            'renew' => [
                'title' => 'Renueva para contar nuevas historias',
                'badge' => 'Opcional',
                'body' => 'Compra un año más para seguir contando nuevas historias.',
                'link' => 'Saber más',
            ],
            'banner' => 'Tus historias son tuyas para siempre. Al cabo de un año, renueva solo si quieres grabar nuevas.',
            'buy' => 'Empezar su libro',
            'checks' => [
                'refund' => 'Garantía de satisfacción o devolución en 30 días',
                'shipping' => 'Envío gratuito en todos los pedidos en Francia',
                'book' => 'Incluye un libro encuadernado impreso en color',
            ],
            'photo_alt' => 'Un libro abierto por una doble página, y un teléfono que reproduce la grabación del capítulo.',
        ],

        // S09. La transition avant les preuves. Sans avis, elle annonce une
        // démonstration et ne parle pas de clients.
        'proof_intro' => [
            'title' => 'Descubre un ejemplo antes de empezar.',
            'body' => 'Escucha un recuerdo, lee su versión pasada a limpio y mira cómo puede encontrar su sitio en el libro.',
            'reviews_title' => 'Cuentan su experiencia con :brand.',
            'reviews_body' => 'Descubre las opiniones de quienes han regalado el libro o han empezado a contar su historia.',
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
            'title' => 'Lo que dicen las familias',
            'stars' => 'Cinco estrellas sobre cinco',
            'previous' => 'Opiniones anteriores',
            'next' => 'Opiniones siguientes',
            'page' => 'Página :number',
            'items' => [
                'one' => [
                    'title' => 'Tan sencillo para mi padre',
                    'quote' => 'Sabía que nunca escribiría sus recuerdos en un cuaderno. Por teléfono, en cambio, se enganchó desde la primera pregunta.',
                    'author' => 'Camille, se lo regaló a su padre',
                ],
                'two' => [
                    'title' => 'Oír su voz en el libro',
                    'quote' => 'El libro ya es valioso. Pero poder escanear una página y oír a mi madre contar la historia ella misma… lo cambia todo.',
                    'author' => 'Thomas, se lo regaló a su madre',
                ],
                'three' => [
                    'title' => 'Mi cita preferida de la semana',
                    'quote' => 'Cada nueva respuesta se ha convertido en una pequeña cita. Espero con ganas descubrir la historia que nos va a contar esta vez.',
                    'author' => 'Julie, se lo regaló a su abuela',
                ],
                'four' => [
                    'title' => 'Nada que escribir',
                    'quote' => 'Tenía miedo de no saber qué contar. Al final, basta con responder como si estuviéramos charlando en un café.',
                    'author' => 'Michel, cuenta su historia',
                ],
                'five' => [
                    'title' => 'Historias que nunca había oído',
                    'quote' => 'Conozco a mi padre de toda la vida y, aun así, he descubierto cosas de su juventud que nunca nos había contado.',
                    'author' => 'Élodie, se lo regaló a su padre',
                ],
                'six' => [
                    'title' => 'Los nietos piden más',
                    'quote' => 'Ahora mis hijos me piden que les ponga las historias de su abuelo. Lo descubren de otra manera.',
                    'author' => 'Sophie, se lo regaló a su padre',
                ],
                'seven' => [
                    'title' => 'Decía que no tenía nada que contar',
                    'quote' => 'Al principio repetía que su vida no tenía nada de interesante. Unas semanas después, imposible pararlo.',
                    'author' => 'Antoine, se lo regaló a su abuelo',
                ],
                'eight' => [
                    'title' => 'Un libro que se parece de verdad a mamá',
                    'quote' => 'Lo que más me gusta es que se reconocen sus expresiones, sus anécdotas, su forma de contar. No es solo su historia: es ella.',
                    'author' => 'Claire, se lo regaló a su madre',
                ],
                'nine' => [
                    'title' => 'La voz lo vale todo',
                    'quote' => 'Pensaba sobre todo en regalar un bonito libro a la familia. No me había dado cuenta de lo valioso que sería conservar su voz.',
                    'author' => 'Pauline, se lo regaló a su padre',
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
            'title' => 'Garantía de satisfacción o devolución en 30 días. Si la primera grabación no te emociona, te devolvemos el dinero.',
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
            'title' => 'Pensado para los abuelos. Aprobado por la familia.',
            'lede' => 'Para quienes cuentan, de 9 a 99 años.',
            'marks' => [
                'no_writing' => 'Nada que escribir.',
                'no_app' => 'Nada que instalar.',
                'no_password' => 'Ninguna contraseña.',
            ],
            'cta' => 'Probar: solo lleva 60 segundos',
            'photo_alt' => 'Una mujer mayor y su hija se abrazan en un sofá, con el libro encuadernado que acaban de desenvolver entre ellas.',
        ],

        // S13. Voir l'expérience. Sans vidéo de client : une capture de
        // l'interface, et surtout aucun faux bouton de lecture.
        'experience' => [
            'title' => 'Un enlace, una pregunta, y tu ser querido puede contar.',
            'body' => 'Descubre el recorrido de :brand, de la grabación a la relectura del relato.',
            'cta' => 'Descubrir el recorrido',
            'caption' => 'Vista previa de la interfaz de :brand',
            'videos_title' => 'Su experiencia, contada con sus palabras.',
        ],

        // S14. À l'intérieur du livre : le chapitre et son QR code.
        'book' => [
            'eyebrow' => 'Dentro del libro',
            'title' => 'Lee su historia. Y luego escúchala con su voz.',
            'body' => 'Una foto, un relato, un código QR: cada capítulo reúne el recuerdo y la posibilidad de volver a escuchar a quien lo contó.',
            'cta' => 'Ver un ejemplo de capítulo',
            'chapter_number' => 'Capítulo 3',
            'chapter_title' => 'El olor del pan de mi abuela',
            // Aucune destination vérifiée n'est encore imprimée : l'emplacement
            // se déclare pour ce qu'il est, et le bouton d'écoute prend le
            // relais pour qui n'a pas deux téléphones.
            'qr_placeholder' => 'Lugar del código QR',
            'qr_body' => 'En el libro impreso, este cuadrado abre la grabación del capítulo.',
            'open_audio' => 'Abrir el ejemplo de audio',
            'illustrative' => 'Vista previa ilustrativa de la maquetación',
        ],

        // S15. De la parole au texte. Les deux extraits sont ceux du projet,
        // repris tels quels depuis `landing.proof`.
        'transcript' => [
            'title' => 'Un texto más fácil de leer, sin inventar su historia.',
            'body' => ':brand quita las vacilaciones y añade la puntuación. Las palabras de tu ser querido siguen siendo suyas. La grabación original y la transcripción palabra por palabra se conservan, y quien narra puede releer y corregir el texto antes de compartirlo.',
            'verbatim' => 'Palabra por palabra',
            'fluide' => 'Texto pasado a limpio',
            'consent' => 'Nada se comparte con los familiares sin su acuerdo.',
            'preview' => 'Vista previa: aquí estas opciones no activan nada.',
        ],

        // S16. Aider à choisir. Un mini-guide dans la page, sans tableau
        // comparatif ni croix rouge sur le voisin.
        'choosing' => [
            'title' => '¿Qué soporte para los recuerdos que quieres guardar?',
            'lede' => 'Escribir, grabar, reunir fotos: elige primero lo que tu familia quiere poder recuperar.',
            'written' => [
                'title' => 'Para las palabras escritas',
                'body' => 'Un cuaderno de recuerdos deja sitio a la escritura y a la forma en que la persona quiere contar.',
            ],
            'recorded' => [
                'title' => 'Para los momentos grabados',
                'body' => 'Los archivos de audio o de vídeo permiten volver a escuchar o volver a ver los intercambios que has grabado.',
            ],
            'both' => [
                'title' => 'Para unir el relato y la voz',
                'body' => ':brand acompaña las respuestas orales, las pasa a limpio y las reúne en un libro encuadernado con acceso a las grabaciones.',
            ],
            'cta' => 'Ver lo que incluye :brand',
        ],

        // S17. Les histoires qui pourraient remplir son livre. Une projection,
        // annoncée comme telle : ce ne sont pas des familles clientes.
        'possibilities' => [
            'eyebrow' => 'Recuerdos por hacer volver',
            'title' => 'Su libro empieza por las historias que él o ella tiene ganas de contar.',
            'lede' => 'Aquí tienes algunas ideas de temas para abrir la conversación. Son ejemplos, no testimonios de familias clientes.',
            'places' => [
                'title' => 'Los lugares de su infancia',
                'body' => 'La casa donde se crece, un camino al colegio, los olores de una cocina: ¿por qué recuerdo empezaría tu ser querido?',
            ],
            'people' => [
                'title' => 'Los encuentros que cuentan',
                'body' => 'Una amistad, un amor, una persona que cambió el rumbo de su vida: ¿qué encuentros le gustaría contar?',
            ],
            'legacy' => [
                'title' => 'Lo que él o ella quiere transmitir',
                'body' => 'Una tradición, un consejo, una historia repetida muchas veces: ¿qué te gustaría encontrar en este libro?',
            ],
            'stories_title' => 'Tres libros, tres familias',
        ],

        // S18. Le cadeau programmé, en fin de page.
        'gift' => [
            'title' => 'El regalo puede empezar el día que tú elijas.',
            'body' => 'Programa el envío de tu mensaje y de la primera pregunta a tu ser querido. También puedes imprimir una tarjeta para meterla en un sobre.',
            'notice' => 'Programas el comienzo del regalo, no la entrega inmediata de un libro ya escrito.',
            'card_name' => 'Odette',
            'card_preview' => 'Vista previa de la tarjeta para imprimir',
        ],

        // S19. Deux destinataires du même cadeau, pas deux produits.
        'recipients' => [
            'title' => 'Dos formas de pensar el mismo regalo',
            'note' => 'Las dos llevan a la misma oferta de :brand.',
            'parent' => [
                'title' => 'Para tu madre o tu padre',
                'body' => 'Regala una ocasión de contar los recuerdos que te gustaría conocer mejor.',
                'cta' => 'Regalar a un padre o una madre',
            ],
            'grandparent' => [
                'title' => 'Para tu abuela o tu abuelo',
                'body' => 'Reúne las historias que te gustaría poder leer y escuchar en familia.',
                'cta' => 'Regalar a un abuelo o una abuela',
            ],
        ],

        // S20. Les questions fréquentes, dans l'ordre où on se les pose avant
        // d'offrir. Les engagements détaillés vivent ici.
        'faq' => [
            'title' => 'Las preguntas que te haces antes de regalarlo.',
            'included' => [
                'q' => '¿Qué incluyen los :price?',
                'a' => 'La compra incluye un año de preguntas, a razón de una por semana, las historias pasadas a limpio con la transcripción palabra por palabra conservada, un libro encuadernado en color con un código QR por capítulo, y el acceso a las grabaciones. Los familiares autorizados pueden descubrir los relatos compartidos. Las grabaciones y los textos se pueden descargar.',
            ],
            'subscription' => [
                'q' => '¿Es una suscripción?',
                'a' => 'No. Pagas una sola vez el año de preguntas y el libro. No hay renovación automática. Las historias recogidas siguen accesibles después del año y puedes descargar tus archivos.',
            ],
            'no_app' => [
                'q' => '¿Mi ser querido tiene que escribir o instalar una aplicación?',
                'a' => 'No. Tu ser querido recibe una pregunta por SMS o por correo electrónico, abre el enlace y responde hablando. El recorrido no requiere ninguna aplicación que instalar ni contraseña que recordar.',
            ],
            'questions' => [
                'q' => '¿Se pueden elegir las preguntas?',
                'a' => 'Sí. :brand propone sesenta preguntas para hacer volver los recuerdos. Puedes elegir las que le convengan a tu ser querido o dejar que :brand guíe los intercambios.',
            ],
            'edit' => [
                'q' => '¿Se puede corregir el texto?',
                'a' => 'Sí. Quien narra relee el texto pasado a limpio y puede corregirlo. La transcripción palabra por palabra sigue accesible, y la grabación original se conserva.',
            ],
            'privacy' => [
                'q' => '¿Quién puede leer y escuchar las historias?',
                'a' => 'Solo los familiares autorizados por quien narra. Su acuerdo es explícito, historia por historia. También puede guardar un relato para ella, retirar lo que ha compartido, ocultarlo o eliminarlo.',
            ],
            'after_year' => [
                'q' => '¿Qué pasa después del año?',
                'a' => 'Las historias ya recogidas siguen accesibles sin pago adicional. Las grabaciones y los textos se pueden descargar para conservarlos en tus propios dispositivos. Los códigos QR siguen siendo utilizables mientras el servicio exista.',
            ],
            'no_smartphone' => [
                'q' => '¿Y si mi ser querido no tiene smartphone?',
                'a' => 'Se ofrece una opción por teléfono en número limitado. Escríbenos para comprobar la disponibilidad y las condiciones antes de elegir esta solución.',
                'link' => 'Escríbenos',
            ],
            'refuses' => [
                'q' => '¿Y si mi ser querido no quiere participar?',
                'a' => 'Su decisión se respeta. Escríbenos: te acompañamos en el marco de la garantía de satisfacción o devolución de treinta días.',
            ],
            'date' => [
                'q' => '¿Puedo elegir la fecha del regalo?',
                'a' => 'Sí. Puedes programar el envío de tu mensaje y de la primera pregunta. Una tarjeta para imprimir permite además presentar el regalo en un sobre.',
            ],
            'protection' => [
                'q' => '¿Cómo protegéis los relatos y la voz?',
                'a' => 'Las grabaciones originales se conservan y nunca se sustituyen por una voz fabricada. :brand no clona las voces y no utiliza los contenidos de tu familia para entrenar un modelo. Tus grabaciones y tus textos se alojan en la Unión Europea. Compartir requiere un acuerdo explícito, y quien narra conserva la posibilidad de ocultar, retirar o eliminar una historia.',
                'privacy_link' => 'Política de privacidad',
                'consents_link' => 'Tus acuerdos',
            ],
            'shutdown' => [
                'q' => '¿Qué pasa si :brand cesa su actividad?',
                'a' => 'Te avisamos con al menos tres meses de antelación, te entregamos la totalidad de tus grabaciones y de tus textos en un formato legible sin nosotros, y te devolvemos lo que no se haya entregado. No prometemos una conservación de por vida: prometemos no dejarte nunca sin tus archivos.',
            ],
            'guarantee' => [
                'q' => '¿Cuál es la garantía?',
                'a' => 'Tienes treinta días de satisfacción o devolución. Las condiciones se detallan en nuestras condiciones generales de venta.',
                'link' => 'Condiciones generales de venta',
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
        'seo_title' => 'Nuestros libros',
        'title' => 'Lo que hay dentro de un libro :brand',
        'lede' => 'Un año de historias, encuadernado. Esto es lo que abres el día en que llega el libro.',
        'price_note' => 'Un año de preguntas y el libro encuadernado. Un solo pago.',
        'photo_alt' => 'Tres libros :brand: dos portadas cerradas, una color crema y otra verde, y un ejemplar abierto por un capítulo ilustrado con una fotografía de familia.',

        // Les trois onglets : ce qu'on lit, ce qu'on entend, ce qu'elle décide.
        'tabs' => [
            'words' => [
                'tab' => 'Sus palabras',
                'title' => 'Sus palabras, pasadas a limpio sin reescribirlas',
                'body' => 'Cada capítulo es una historia que ella contó. Los titubeos desaparecen, la puntuación se coloca, sus giros siguen siendo los suyos. La transcripción palabra por palabra se conserva junto al texto pasado a limpio: siempre se puede volver a lo que se dijo.',
            ],
            'voice' => [
                'tab' => 'Su voz',
                'title' => 'Su voz, a un escaneo de la página',
                'body' => 'Un código QR acompaña cada capítulo y reproduce la grabación original. Se lee la historia y luego se la oye contarla, con sus silencios y sus risas. Es lo que un libro escrito no puede guardar.',
            ],
            'control' => [
                'tab' => 'Ella decide',
                'title' => 'Ella relee antes que nadie',
                'body' => 'Quien narra relee cada texto antes que nadie, corrige una palabra si quiere y elige lo que la familia puede leer y escuchar. Nada se comparte sin su acuerdo, y puede volver atrás en su decisión.',
            ],
        ],

        // Ce que comprend l'achat, en une colonne de six points.
        'includes_title' => 'Lo que incluye tu libro',
        'includes' => [
            'questions' => 'Un año de preguntas, una por semana, escritas para hacer volver los recuerdos.',
            'record' => 'Respuestas grabadas desde cualquier teléfono, sin aplicación ni contraseña.',
            'text' => 'El paso a limpio de cada historia, con la transcripción palabra por palabra conservada al lado.',
            'download' => 'Las grabaciones originales y los textos, descargables en cualquier momento.',
            'book' => 'Un libro encuadernado en color, con tapa dura y un código QR por capítulo.',
            'family' => 'La familia invitada a escuchar y a reaccionar, dentro de lo que ella autoriza.',
        ],

        // Le bloc de fond : ce qui compte n'est pas l'objet.
        'why' => [
            'title' => 'Lo que cuenta no es el libro. Es el año que lo llena.',
            'p1' => 'El libro es lo que queda, pero no es lo que ocurre. Lo que ocurre es una pregunta hecha el domingo, una respuesta que se escucha en el coche, un detalle que no se conocía y que se vuelve a pedir en Navidad.',
            'p2' => 'Al cabo de un año, la familia ha oído historias que nunca se le habría ocurrido pedir. El libro llega después: las reúne y las hace fáciles de encontrar.',
        ],

        // L'appel final.
        'closing' => [
            'title' => 'Empieza su libro hoy.',
            'body' => 'La primera pregunta sale el día que tú elijas. El libro llega al cabo del año.',
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
        'seo_title' => 'Preguntas frecuentes',
        'title' => 'Preguntas frecuentes',
        'lede' => 'Encuentra las respuestas a las preguntas sobre cómo :brand ayuda a tu familia a preservar sus recuerdos y sus historias como nunca antes.',
        'jump' => 'Ir a',

        'categories' => [
            'common' => 'Las preguntas más frecuentes',
            'about' => 'Sobre :brand',
            'pricing' => 'Compra y renovación',
            'recording' => 'Grabar las historias',
            'customizing' => 'Personalizar la experiencia',
            'book' => 'Crear y pedir el libro',
            'gifting' => 'Regalar y compartir',
            'privacy' => 'Privacidad y ayuda',
            'other' => 'Otras preguntas',
        ],

        'common' => [
            'renewal' => [
                'q' => '¿Por qué hay una renovación?',
                'a' => 'La oferta incluye un año de acceso a la plataforma y todas las funciones de recogida de historias. La renovación permite seguir grabando nuevas historias los años siguientes. Aunque no renueves, siempre puedes consultar y escuchar todas las grabaciones de tu familia.',
            ],
            'shutdown' => [
                'q' => '¿Qué pasa si :brand cesa su actividad?',
                'a' => 'Puedes descargar tus grabaciones, tus fotos y tus historias escritas en cualquier momento, para conservarlas en tus dispositivos o subirlas al servicio de almacenamiento que prefieras. Te avisamos con al menos tres meses de antelación y te reembolsamos lo que no se haya entregado.',
            ],
            'printed' => [
                'q' => '¿Qué se imprime exactamente en el libro?',
                'a' => ':brand convierte la transcripción de las grabaciones en texto escrito, según tu preferencia: la transcripción pasada a limpio o una historia redactada por el Speech-to-Story™.',
            ],
            'shipping' => [
                'q' => '¿Dónde envía :brand?',
                'a' => 'El envío en Francia está incluido en el precio de compra. El envío a otros países es posible, con gastos adicionales.',
            ],
            'language' => [
                'q' => '¿Qué idiomas admite :brand?',
                'a' => 'El Speech-to-Story™ funciona en francés. Otros idiomas llegarán más adelante.',
            ],
            'seniors' => [
                'q' => '¿Es :brand fácil de usar para las personas mayores de la familia?',
                'a' => 'No hay ninguna aplicación que descargar ni ningún identificador que recordar. Todo está pensado para que sea sencillo: muchas de las personas que narran con nosotros tienen 70, 80 o 90 años.',
            ],
        ],

        'about' => [
            'who' => [
                'q' => '¿A quién se dirige :brand?',
                'a' => ':brand se dirige a quienes quieren preservar las historias de sus padres, de sus abuelos o de un ser querido. También sirve para grabar la propia historia, los propios valores, lo que se ha aprendido en el oficio o lo que se desea transmitir.',
            ],
            'how' => [
                'q' => '¿Cómo funciona :brand?',
                'a' => 'Cada semana, quien narra recibe una pregunta por correo electrónico o por SMS. Responde hablando, desde cualquier dispositivo conectado, sin identificador ni aplicación que instalar. La grabación se convierte en una historia escrita, compartida con los familiares autorizados. Se pueden añadir fotos. Al cabo de un año, las historias se reúnen en un libro encuadernado, con un código QR por capítulo que reproduce la grabación original.',
            ],
            'shutdown' => [
                'q' => '¿Qué pasa si :brand cesa su actividad?',
                'a' => 'Mantienes un acceso sencillo para descargar todas tus grabaciones, tus fotos y tus historias escritas, y conservarlas en tu casa o en el servicio de almacenamiento que prefieras.',
            ],
            'two_people' => [
                'q' => '¿Pueden dos personas compartir un mismo :brand?',
                'a' => ':brand está pensado para una sola persona que narra por libro, para que su voz y su mirada destaquen del todo. Puedes comprar varias ofertas para varias personas que narran.',
            ],
        ],

        'pricing' => [
            'included' => [
                'q' => '¿Qué incluye mi compra?',
                'a' => 'Una cuenta para quien narra, todos los familiares que quieras invitar, un año de preguntas, un libro encuadernado en color (hasta 200 páginas) y el envío en Francia.',
            ],
            'why_renewal' => [
                'q' => '¿Por qué hay una renovación?',
                'a' => 'La oferta cubre un año de acceso para recoger las historias. La renovación sirve para seguir grabando después. Sin renovación, siempre puedes consultar, escuchar y descargar todo lo que se haya recogido.',
            ],
            'no_renewal' => [
                'q' => '¿Qué pasa si no renuevo?',
                'a' => 'Incluso después del primer año, las historias de tu familia siguen estando totalmente accesibles. Puedes escuchar las grabaciones, leer las historias y pedir ejemplares adicionales del libro. Todo se descarga en cualquier momento. Grabar **nuevas** historias, en cambio, requiere una renovación, que puedes contratar cuando quieras.',
            ],
        ],

        'recording' => [
            'submit' => [
                'q' => '¿Cómo envío mi historia?',
                'a' => 'Es sencillo: basta con tocar el enlace recibido por correo electrónico o por mensaje. Ninguna aplicación que descargar, ninguna contraseña. Cualquier dispositivo conectado sirve, y el Speech-to-Story™ se encarga del resto.',
            ],
            'writing' => [
                'q' => '¿Cómo convierte :brand una grabación en texto?',
                'a' => 'Existen dos versiones: la transcripción palabra por palabra, libre de titubeos, o una versión fluida. El ajuste se aplica a una historia o a todo el proyecto, y cada texto se puede modificar antes de la impresión.',
            ],
            'more_than_one' => [
                'q' => '¿Se puede compartir más de una historia por semana?',
                'a' => 'Sí. Por defecto sale una pregunta cada semana, pero el ritmo se ajusta de diario a mensual. También se pueden grabar varias respuestas cuando apetece.',
            ],
            'missed' => [
                'q' => '¿Qué pasa si me salto una pregunta?',
                'a' => 'Siempre puedes volver atrás y responder a las preguntas anteriores cuando quieras. Todas siguen accesibles en tu espacio privado.',
            ],
            'length' => [
                'q' => '¿Cuánto puede durar una grabación?',
                'a' => 'Cada grabación puede durar hasta treinta minutos. No hay duración mínima: algunas de las historias más memorables solo duran unos minutos.',
            ],
            'languages' => [
                'q' => '¿Qué idiomas se admiten?',
                'a' => 'El Speech-to-Story™ funciona en francés. Las grabaciones se transcriben fielmente, se imprimen en el libro encuadernado, y el código QR de cada capítulo reproduce la grabación original.',
            ],
        ],

        'customizing' => [
            'choose' => [
                'q' => '¿Se pueden elegir o cambiar las preguntas?',
                'a' => 'Tienes total libertad sobre las preguntas: escoger entre las que hemos escrito, escribir las tuyas, o partir de una foto para que se cuente la historia que la acompaña. Los familiares también pueden proponer preguntas.',
            ],
            'change_weekly' => [
                'q' => '¿Se puede cambiar la pregunta de la semana?',
                'a' => 'Sí. La invitación semanal permite cambiar de pregunta con un gesto: elegir otra en la biblioteca o escribir una uno mismo.',
            ],
            'seniors' => [
                'q' => '¿Es sencillo para tus padres o tus abuelos?',
                'a' => ':brand se ha diseñado para que sea sencillo, pensando ante todo en los padres y los abuelos a quienes la tecnología no entusiasma. Ninguna aplicación que instalar, ningún identificador que recordar.',
            ],
            'not_tech' => [
                'q' => '¿Es sencillo para alguien que no se maneja con la tecnología?',
                'a' => 'Sí. Quien narra toca un enlace recibido por correo electrónico o por mensaje: nada que descargar, ninguna contraseña que crear. Muchas de las personas que narran con nosotros tienen entre 70 y 90 años.',
            ],
        ],

        'book' => [
            'create' => [
                'q' => '¿Cómo creo mi libro :brand?',
                'a' => 'Las historias se acumulan a lo largo del año, a medida que se graban. Después confirmas la versión que quieres para cada una, el orden de los capítulos y la portada, y ves el libro entero antes de la impresión. El primer libro encuadernado (hasta 200 páginas) está incluido en la oferta.',
            ],
            'printed' => [
                'q' => '¿Qué se imprime exactamente?',
                'a' => 'Eliges, para cada historia, entre la transcripción pasada a limpio y la historia redactada por el Speech-to-Story™.',
            ],
            'edit' => [
                'q' => '¿Se pueden corregir o completar las historias?',
                'a' => 'Por supuesto. Puedes modificar la versión escrita de tus historias en cualquier momento antes de imprimir el libro. También se pueden añadir fotos después de la grabación.',
            ],
            'photos' => [
                'q' => '¿Se pueden incluir fotos?',
                'a' => 'Sí. Las fotos se añaden como pregunta de la semana, o después de grabar una historia.',
            ],
            'looks_like' => [
                'q' => '¿Cómo es el libro impreso?',
                'a' => 'Un bonito libro encuadernado de 20 × 25 cm, impreso profesionalmente, todo en color. Cada historia es un capítulo con su título, su texto, sus fotos y un código QR que abre la grabación original.',
            ],
            'preview' => [
                'q' => '¿Se puede ver una vista previa antes de la impresión?',
                'a' => 'Sí. Desde la primera historia grabada, puedes ver cómo quedará impresa.',
            ],
            'limits' => [
                'q' => '¿Hay un límite de palabras o de páginas?',
                'a' => 'Cada grabación puede durar hasta treinta minutos, y no hay límite de palabras en los textos. Un libro :brand puede llegar hasta 380 páginas. La mayoría tienen menos de 200 páginas, que es lo que incluye la oferta. A partir de 380 páginas, el libro se edita en dos volúmenes.',
            ],
            'extra' => [
                'q' => '¿Se pueden pedir ejemplares adicionales?',
                'a' => 'Sí. Un ejemplar encuadernado adicional cuesta :extra_copy. Hay una versión digital por :ebook. Para una cantidad grande, pide primero un ejemplar.',
            ],
            'family_order' => [
                'q' => '¿Pueden mis familiares pedir un ejemplar?',
                'a' => 'Sí. Un ejemplar adicional cuesta :extra_copy e incluye todas las historias, las fotos y los códigos QR.',
            ],
            'shipping' => [
                'q' => '¿Dónde hacéis los envíos?',
                'a' => 'El envío en Francia está incluido en el precio de compra. El envío a otros países conlleva gastos adicionales.',
            ],
        ],

        'gifting' => [
            'when' => [
                'q' => '¿Puedo elegir la fecha en que se envía el regalo a su destinatario?',
                'a' => 'Sí. Al comprar, eliges el día exacto en que tu ser querido recibe el correo electrónico. También hay una tarjeta para imprimir y entregar el regalo en mano.',
            ],
            'gift_card' => [
                'q' => '¿Se puede comprar una tarjeta regalo :brand?',
                'a' => 'Sí. La tarjeta regalo lleva un código que da acceso a la oferta completa. Quien la recibe crea su espacio personal, elige sus ajustes y la fecha de la primera pregunta. La tarjeta se envía por correo electrónico o se imprime en casa.',
            ],
            'printable' => [
                'q' => '¿Hay algo para imprimir y regalar?',
                'a' => 'Sí. Después de tu compra, recibes un correo electrónico con el enlace de una tarjeta regalo cuidada, lista para imprimir.',
            ],
            'delivery' => [
                'q' => '¿Cuánto tarda la entrega?',
                'a' => ':brand es un regalo de última hora estupendo: el año de historias se puede entregar el mismo día, por correo electrónico. El libro encuadernado, en cambio, se imprime y se envía al final del año de preguntas.',
            ],
        ],

        'privacy' => [
            'private' => [
                'q' => '¿Es privado :brand?',
                'a' => 'Sí. Todo lo que se crea con :brand es privado por defecto: tú decides quién ve qué. Solo las personas autorizadas tienen acceso. Tus contenidos siguen siendo tuyos y se descargan en cualquier momento. Tus grabaciones y tus textos se alojan en la Unión Europea.',
            ],
            'download' => [
                'q' => '¿Se pueden descargar todas las historias y todas las grabaciones?',
                'a' => 'Sí. Puedes exportar tus historias en texto o en PDF, y descargar los archivos de audio en cualquier momento desde tu espacio personal.',
            ],
            'training' => [
                'q' => '¿Mis historias sirven para entrenar una IA?',
                'a' => 'No. Tus contenidos no sirven para entrenar ningún modelo. Se tratan para crear tu libro, y para nada más.',
            ],
            'delete' => [
                'q' => '¿Se puede eliminar la cuenta y los datos?',
                'a' => 'Sí. Puedes eliminar tu cuenta y todos los datos asociados en cualquier momento: grabaciones, transcripciones, fotos e información de la cuenta.',
            ],
            'returns' => [
                'q' => '¿Cuál es vuestra política de devoluciones?',
                'a' => 'Ofrecemos una garantía de devolución del dinero de treinta días después de la compra, si el resultado no te convence.',
            ],
            'help' => [
                'q' => '¿Y si necesito ayuda?',
                'a' => 'Nuestro equipo responde en esta dirección:',
            ],
        ],

        'other' => [
            'storyworth' => [
                'q' => '¿En qué se diferencia :brand de Storyworth?',
                'a' => ':brand capta la voz y la personalidad a través de recuerdos grabados, mientras que Storyworth recoge respuestas escritas. El Speech-to-Story™ convierte las grabaciones en historias escritas, y un código QR reproduce la voz junto al texto impreso.',
            ],
            'my_life' => [
                'q' => '¿En qué se diferencia :brand de My Life In A Book?',
                'a' => ':brand se basa en el relato hablado y no en un cuestionario que rellenar. Las grabaciones se convierten en historias cuidadas, y el código QR conserva la voz original junto al texto.',
            ],
            'storykeeper' => [
                'q' => '¿En qué se diferencia :brand de StoryKeeper?',
                'a' => ':brand entrega a la vez las historias escritas y las grabaciones originales. Los códigos QR del libro impreso abren el audio: la familia puede leer **y** oír.',
            ],
            'no_story_lost' => [
                'q' => '¿En qué se diferencia :brand de No Story Lost?',
                'a' => ':brand no exige largas entrevistas ni un redactor profesional. La persona cuenta sus recuerdos a su ritmo, guiada por preguntas, y sus respuestas se convierten en historias escritas acompañadas del código QR de su voz.',
            ],
        ],

        'closing' => [
            'title' => 'Cada familia tiene historias que merecen guardarse.',
            'body' => 'Las pruebas superadas, los momentos de alegría, lo que se ha aprendido con los años. :brand capta esas conversaciones tal como llegan y las reúne en un bonito libro encuadernado, donde un código QR permite a las generaciones siguientes leer la historia y oír la voz de quien la contó.',
        ],

        'still' => [
            'title' => '¿Alguna pregunta más?',
            'body' => 'Escríbenos: te responde una persona.',
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
        'seo_title' => 'Cómo funciona',

        // Le bandeau de titre : le titre, la question, les deux onglets.
        'hero' => [
            'title' => 'Cómo funciona',
            'question' => '¿Regalas este libro o lo cuentas tú?',
            'toggle_label' => '¿Para quién es el libro?',
            'gift' => 'Para un ser querido',
            'self' => 'Para mí',
        ],

        // L'accroche, et le média à côté : là où le leader met sa vidéo, on fait écouter.
        'intro' => [
            'gift' => [
                'headline' => 'Sus recuerdos, contados con su voz.',
                'lede' => 'Nada que escribir, nada que instalar: una pregunta por semana, y ella responde hablando.',
            ],
            'self' => [
                'headline' => 'Tu vida, contada con tu voz.',
                'lede' => 'Nada que escribir, nada que instalar: una pregunta por semana, y respondes hablando.',
            ],
            'listen' => 'Escucha',
            'caption' => 'Dos minutos con Odette, tal como los recibe una familia.',
        ],

        /*
         * Les six étapes, chacune en deux voix : « elle » quand on offre,
         * « vous » quand on raconte soi-même. L'ordre des clés est l'ordre
         * affiché, et un test le garde.
         */
        'steps' => [
            'title' => 'Los seis pasos',
            'label' => 'Paso :n',
            'badge' => ':n de 6',
            'questions' => [
                'gift' => [
                    'title' => 'Tú eliges las preguntas',
                    'body' => 'Sesenta preguntas, escritas en francés para hacer aflorar las historias que la familia nunca ha oído: la casa de la infancia, el primer día de trabajo, lo que se quiso transmitir. Te quedas con las que se le parecen, añades las tuyas, o nos dejas hacer a nosotros. La primera es siempre una pregunta fácil.',
                    'link' => 'Ver algunas preguntas',
                ],
                'self' => [
                    'title' => 'Tú eliges lo que quieres contar',
                    'body' => 'Sesenta preguntas, escritas en francés para hacer aflorar las historias que tus seres queridos nunca han oído: la casa de la infancia, el primer día de trabajo, lo que quisiste transmitir. Te quedas con las que te dicen algo, descartas las demás, añades las tuyas. La primera es siempre una pregunta fácil.',
                    'link' => 'Ver algunas preguntas',
                ],
                // Quatre questions du corpus (annexe A), mot pour mot : un test le vérifie.
                'samples' => [
                    'first_memory' => '¿Cuál es su primer recuerdo?',
                    'dish' => '¿Qué plato de su infancia le gustaría probar una última vez?',
                    'meeting' => '¿Cómo conoció a la persona que compartió su vida?',
                    'value' => '¿Cuál es el valor que ha intentado transmitir antes que todos los demás?',
                ],
                'alt' => 'Una mano sostiene dos fotografías antiguas de familia delante de una puerta de madera.',
            ],
            'record' => [
                // « s'arrêter, souffler et reprendre » : la pause de l'enregistrement
                // (P0-3). Pas la reprise après un appel ou une mise en veille, que
                // le dossier interdit de promettre avant le spike navigateur.
                'gift' => [
                    'title' => 'Llega una pregunta. Ella habla.',
                    'body' => 'Cada semana, en el momento que ella ha elegido, tu ser querido recibe un SMS o un correo electrónico: una sola pregunta y un enlace. Lo abre, toca un botón y cuenta. Ni aplicación, ni cuenta, ni contraseña. Puede parar, respirar y seguir. Y si ese día prefiere escribir, escribe.',
                    'link' => 'Probar la pantalla que ella verá',
                ],
                'self' => [
                    'title' => 'Cada semana, una pregunta. Tú hablas.',
                    'body' => 'En el momento que hayas elegido, recibes un SMS o un correo electrónico: una sola pregunta y un enlace. Lo abres, tocas un botón y cuentas. Ni aplicación, ni cuenta, ni contraseña. Puedes parar, respirar y seguir. Y si ese día prefieres escribir, escribes.',
                    'link' => 'Probar la pantalla que verás',
                ],
                'alt' => 'Una mujer mayor sonríe mientras habla a su teléfono, delante de una ventana.',
            ],
            'text' => [
                'gift' => [
                    'title' => 'Sus palabras se convierten en un texto',
                    'body' => 'En unos minutos, la grabación se transcribe y salen dos textos, uno al lado del otro: la transcripción palabra por palabra, tal como ella lo dijo, y el texto pasado a limpio, donde los titubeos desaparecen y sus giros se quedan. La IA ordena, no inventa. La grabación original se conserva, y la transcripción palabra por palabra nunca se borra.',
                    'link' => 'Ver la diferencia',
                ],
                'self' => [
                    'title' => 'Tus palabras se convierten en un texto',
                    'body' => 'En unos minutos, tu grabación se transcribe y salen dos textos, uno al lado del otro: la transcripción palabra por palabra, tal como lo dijiste, y el texto pasado a limpio, donde los titubeos desaparecen y tus giros se quedan. La IA ordena, no inventa. La grabación original se conserva, y la transcripción palabra por palabra nunca se borra.',
                    'link' => 'Ver la diferencia',
                ],
                'alt' => 'La página de relectura en un teléfono: la grabación para volver a escucharla, y luego el texto pasado a limpio y la transcripción palabra por palabra, uno al lado del otro.',
            ],
            'decide' => [
                'gift' => [
                    'title' => 'Ella relee y luego decide',
                    'body' => 'Antes que nadie, ella relee su historia y corrige una palabra si quiere. Después elige: compartirla con sus seres queridos, guardarla para ella, o decidir más tarde. Nada es visible para la familia sin su acuerdo. Y puede cambiar de opinión en cualquier momento: ocultar una historia, retirarla, eliminarla.',
                    'link' => 'Leer nuestros compromisos',
                ],
                'self' => [
                    'title' => 'Tú relees y luego decides',
                    'body' => 'Antes que nadie, relees tu historia y corriges una palabra si quieres. Después eliges: compartirla con tus seres queridos, guardarla para ti, o decidir más tarde. Nada es visible para la familia sin tu acuerdo. Y puedes cambiar de opinión en cualquier momento: ocultar una historia, retirarla, eliminarla.',
                    'link' => 'Leer nuestros compromisos',
                ],
                'alt' => 'Una mujer mayor sostiene ante sí el libro encuadernado de sus historias.',
            ],
            'family' => [
                'gift' => [
                    'title' => 'La familia escucha y le responde',
                    'body' => 'Cada historia que ella ha elegido compartir llega a sus seres queridos, en una página privada que reproduce su voz y muestra el texto. La escuchan, añaden una foto, le responden con una palabra. Ella sabe que la han escuchado.',
                    'link' => '¿Quién puede escuchar?',
                ],
                'self' => [
                    'title' => 'Tus seres queridos escuchan y te responden',
                    'body' => 'Cada historia que has elegido compartir llega a tus seres queridos, en una página privada que reproduce tu voz y muestra el texto. La escuchan, añaden una foto, te responden con una palabra. Sabes que la han escuchado.',
                    'link' => '¿Quién puede escuchar?',
                ],
                'alt' => 'Dos familiares siguen una página del libro, teléfono en mano, con la narradora en la pantalla.',
            ],
            'book' => [
                // « de quoi faire un livre » : les critères R-6, jamais un nombre
                // d'histoires. Rien sur le lieu d'impression tant que l'imprimeur
                // n'est pas contractualisé (bloc 13).
                'gift' => [
                    'title' => 'El libro encuadernado, con su voz en cada página',
                    'body' => 'A lo largo del año, las historias se organizan en capítulos, con las fotos que la familia ha añadido. Cuando hay con qué hacer un libro, relees la maqueta y das la prueba final (BAT). Solo entran las historias que ella ha validado. El libro se imprime y se encuaderna, en color, y cada capítulo lleva un código para escanear que reproduce la grabación original. Con el libro lo recibes todo: las grabaciones y los textos, en archivos legibles sin nosotros.',
                    'link' => 'Ver el libro',
                ],
                'self' => [
                    'title' => 'El libro encuadernado, con tu voz en cada página',
                    'body' => 'A lo largo del año, tus historias se organizan en capítulos, con las fotos que tus seres queridos han añadido. Cuando hay con qué hacer un libro, relees la maqueta y das la prueba final (BAT). Solo entran las historias que has validado. El libro se imprime y se encuaderna, en color, y cada capítulo lleva un código para escanear que reproduce la grabación original. Con el libro lo recibes todo: las grabaciones y los textos, en archivos legibles sin nosotros.',
                    'link' => 'Ver el libro',
                ],
                'alt' => 'El libro encuadernado, de pie sobre un escritorio entre libros antiguos.',
            ],
        ],

        // « Encore des questions ? » : quatre réponses lues au catalogue de l'accueil.
        'questions' => [
            'title' => '¿Más preguntas?',
            'all' => 'Todas las preguntas',
        ],

        // Le bandeau d'appel, avec le prix dans le bouton comme chez le leader.
        'cta' => [
            'gift' => [
                'headline' => 'Regálale el libro de su vida, con su voz para contarlo.',
                'body' => 'Un año de preguntas, el libro encuadernado y todas las grabaciones. Un solo pago, nada que renovar.',
            ],
            'self' => [
                'headline' => 'Tu historia, con tus propias palabras.',
                'body' => 'Un año de preguntas, el libro encuadernado de tus historias y todas las grabaciones. Un solo pago, nada que renovar.',
                'button' => 'Empiezo mi libro',
            ],
        ],

        /*
         * Le texte en deux versions, en onglets, sur l'exemple d'Odette. Deux
         * et non trois : le MVP livre le mot à mot et le texte mis au propre,
         * pas de récit à la troisième personne (doc 03 §2).
         */
        'rendering' => [
            'eyebrow' => 'El texto',
            'headline' => 'La misma grabación, dos textos.',
            'lede' => 'El primero es lo que ella dijo, palabra por palabra. El segundo es lo que se leerá en el libro: sin los titubeos, con sus giros intactos. Los dos siguen accesibles, uno al lado del otro, y ella corrige lo que quiere en el segundo.',
            'tabs_label' => 'Las dos versiones del texto',
            'question_label' => 'La pregunta de Odette',
        ],

        // La section sombre : la citation, qui a un auteur, puis deux cartes.
        'voice' => [
            'title' => 'Por qué existe este libro',
            'author' => 'El fundador de :brand',
            'cta' => 'Leer nuestra historia',
        ],
        'more' => [
            'faq' => [
                'body' => 'Lo que está incluido, la suscripción que no existe, el smartphone que a veces falta, y qué pasa en caso de negativa.',
                'cta' => 'Leer las respuestas',
            ],
            'try' => [
                'title' => 'Pruébalo en 60 segundos',
                'body' => 'La pantalla de quien narra, con una pregunta de la semana de verdad. Nada sale de tu teléfono.',
                'cta' => 'Hacer la prueba',
            ],
        ],

        'together' => [
            'gift' => [
                'headline' => 'Un libro que se hace entre varios.',
                'body' => 'Tú pones en marcha el proyecto, ella cuenta, la familia escucha y completa. Cada cual aporta algo, y el libro guarda su huella.',
            ],
            'self' => [
                'headline' => 'Un libro que se hace con los tuyos.',
                'body' => 'Tú cuentas, tus seres queridos escuchan y completan. Cada cual aporta algo, y el libro guarda su huella.',
                'button' => 'Empiezo mi libro',
            ],
            'points' => [
                'questions' => 'Elegir las preguntas',
                'photos' => 'Añadir fotos a las historias',
                'listen' => 'Escuchar cada historia en cuanto se comparte',
                'reply' => 'Responder con una palabra a quien narra',
            ],
            'alt' => 'Una mujer mayor y su hija se abrazan entre risas, con el libro encuadernado entre ellas.',
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
        'aria' => 'Un descuento de bienvenida',
        'eyebrow' => 'Para empezar',
        'title' => ':amount de regalo',
        'subtitle' => 'en el libro de sus recuerdos',
        // Le même service en bandeau de bas de page, sur l'accueil et sur
        // « Comment ça marche » (T-213).
        'band_title' => ':amount de regalo para empezar',
        'teaser' => 'Déjanos tu correo electrónico: te enviamos un código de descuento de :amount, válido un año para todo tu pedido.',
        'claim' => 'Quiero mi descuento',
        'no_thanks' => 'No, gracias',
        'email_label' => 'Tu correo electrónico',
        'email_placeholder' => 'nombre@ejemplo.es',
        'news' => 'También quiero recibir vuestras novedades, de vez en cuando.',
        'send' => 'Recibir mi código',
        'waiting' => 'Un momento…',
        'fine_print' => 'Tu dirección sirve para enviarte el código, y para nada más si no marcas la casilla. En cada mensaje hay un enlace para darte de baja.',
        'sent_title' => 'Enviado',
        'sent_body' => 'Tu código va camino de :email. Si no llega, mira en el correo no deseado.',
        'sent_auto' => 'Si haces el pedido desde este dispositivo, el descuento se aplicará solo en el resumen.',
        'sent_cta' => 'Empiezo su libro',
        'errors' => [
            'send_failed' => 'No hemos podido enviar el código. Inténtalo de nuevo en un momento.',
            'closed' => 'Esta oferta ya no está disponible.',
        ],
    ],

    'legal' => [
        'terms' => 'Condiciones generales de venta',
        'privacy' => 'Política de privacidad',
        'imprint' => 'Aviso legal',
        'consents' => 'Tus acuerdos, en su versión vigente',
        'version' => 'Versión :version, vigente desde el :date.',
    ],

    /*
     * Le pied de page (T-213) : la marque et sa phrase, les pages du site,
     * les informations légales, le contact, puis l'année et l'hébergement.
     * Les libellés des pages et des textes légaux sont lus ailleurs dans ce
     * fichier ; ici, seulement ce qui n'existe pas encore.
     */
    'footer' => [
        'discover' => 'Descubrir',
        'home' => 'Inicio',
        'try' => 'Probar en 60 segundos',
        'information' => 'Información',
        'contact' => 'Contacto',
        'copyright' => '© :year :brand',
        // Un fait, pas une promesse : la région du serveur et la juridiction
        // du stockage (T-02, T-04). La phrase complète est dans les engagements.
        'hosting' => 'Alojado en la Unión Europea',
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
        'eyebrow' => 'La prueba',
        'title' => 'Pruébalo en 60 segundos',
        'body' => 'Responde a una pregunta de la semana de verdad. Verás la pantalla que verá tu ser querido, y sabrás si está a su alcance.',
        'nothing_sent' => 'Esta prueba se queda en tu teléfono y desaparece cuando cierras la página.',
        'question_label' => 'Pregunta de la semana',
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
        'question' => '¿Qué cualidad admiraba más de su padre? ¿Y de su madre?',
        'start' => 'Empezar la prueba',
        'start_hint' => 'Toca el botón y habla. La prueba se detiene sola al cabo de un minuto.',
        'recording' => 'Está grabando. Habla, te escuchamos.',
        // Le temps écoulé, jamais un compte à rebours (PRD US-06) : voir les
        // secondes fondre coupe la parole de qui cherche ses mots.
        'elapsed' => 'Llevas hablando :time.',
        'stop' => 'He terminado',
        'ready' => 'Escúchate.',
        'playback' => 'Tu prueba',
        'again' => 'Volver a empezar',
        'result_title' => 'Y esto es en lo que se convierte.',
        'result_body' => 'Tu voz no ha salido de tu teléfono: no la hemos oído, así que no tenemos nada que escribir. Esto es lo que hacemos, sobre la grabación de Odette.',
        // L'exemple répond à la question d'Odette, pas à celle qu'on vient de
        // poser au visiteur. Il porte donc la sienne, écrite au-dessus : sans
        // elle, la page semble mettre dans une bouche une réponse qui n'y
        // était pas. Un test le garde.
        'result_question_label' => 'La pregunta de Odette',
        'unsupported' => 'Este navegador no puede grabar. Prueba con Safari en iPhone o Chrome en Android.',
        'refused' => 'No se ha dado permiso al micrófono. Tiene arreglo: en los ajustes de tu navegador, autoriza el micrófono para este sitio y vuelve a cargar la página.',
        'cta' => 'Regalar a un ser querido',
    ],

    'checkout' => [
        'title' => 'Regalar',
        'step_of' => 'Paso :step de :total',
        'progress' => 'Progreso del pedido',
        'next' => 'Continuar',
        'back' => 'Volver',
        'waiting' => 'Un momento…',
        'edit' => 'Modificar',
        'secure' => 'Pago seguro',
        'refund' => 'Garantía de devolución 30 días',

        // Les titres des étapes, puis leur forme courte pour la progression.
        'steps' => [
            'for' => '¿Para quién?',
            'narrator' => 'Quien narra',
            'gift' => 'El regalo',
            'gift_self' => 'El comienzo',
            'account' => 'Tu cuenta',
            'options' => 'Opciones y acuerdos',
            'summary' => 'Resumen',
        ],
        'labels' => [
            'for' => 'Para quién',
            'narrator' => 'Quien narra',
            'gift' => 'El regalo',
            'account' => 'Tu cuenta',
            'options' => 'Opciones',
            'summary' => 'Resumen',
        ],

        'for' => [
            'intro' => 'El libro se hace entre dos: alguien lo regala, alguien cuenta.',
            'relative' => 'Un ser querido',
            'relative_hint' => 'Un padre, una madre, un abuelo, alguien a quien quieres. Tú regalas, esa persona cuenta.',
            'self' => 'Para ti',
            'self_hint' => 'Cuentas tus propios recuerdos.',
        ],

        // Deux jeux de libellés : « son » quand on offre à un proche, « votre »
        // quand on raconte soi-même. La forme suit le choix de l'étape 1.
        'narrator' => [
            'intro' => 'La persona que va a contar. Le escribiremos una sola vez, para invitarla, y no enviaremos ninguna pregunta hasta que haya aceptado.',
            'intro_self' => 'Vas a contar tú. Te escribiremos una vez para empezar, y luego una pregunta a la semana.',
            'first_name' => 'Su nombre',
            'first_name_self' => 'Tu nombre',
            'last_name' => 'Sus apellidos (opcional)',
            'last_name_self' => 'Tus apellidos (opcional)',
            'relationship' => 'Tu relación con esa persona',
            'relationship_hint' => 'Mi madre, mi abuelo, una amiga de toda la vida.',
            'contact_hint' => 'Basta con un correo electrónico o un número.',
            'contact_hint_self' => 'Basta con un correo electrónico o un número: ahí llegarán las preguntas.',
            'email' => 'Su correo electrónico',
            'email_self' => 'Tu correo electrónico',
            'phone' => 'Su número de teléfono',
            'phone_self' => 'Tu número de teléfono',
            'channel' => '¿Cómo contactamos con esa persona?',
            'channel_self' => '¿Cómo contactamos contigo?',
            'address_form' => '¿La tratamos de «usted» o de «tú»?',
            'address_form_self' => '¿Prefieres que te tratemos de «usted» o de «tú»?',
            'tech_comfort' => '¿Esa persona se maneja bien con el teléfono?',
            'tech_comfort_hint' => 'Adaptamos la ayuda y las opciones a tu respuesta.',
        ],

        'gift' => [
            'intro' => 'La invitación se enviará el día y a la hora que elijas, con tu mensaje.',
            'intro_self' => 'Tu primera pregunta se enviará el día y a la hora que elijas.',
            'send_at' => '¿Qué día?',
            'send_time' => '¿A qué hora?',
            'message' => 'Tu mensaje personal',
            'message_hint' => 'Ese mensaje lo decide todo: uno tuyo vale por diez nuestros.',
            'message_counter' => ':count caracteres de :max',
            'message_default' => 'Me gustaría guardar tus historias. Solo tienes que hablar, una pregunta a la semana, cuando quieras. Si no te apetece, dímelo sin más.',
        ],

        'account' => [
            'intro' => 'Para seguir el proyecto, añadir fotos y encontrar tu pedido. No se envía nada antes del último paso.',
            'signed_in' => 'Has iniciado sesión como :email.',
            'create' => 'Crear una cuenta',
            'have' => 'Ya tengo una cuenta',
            'name' => 'Tu nombre',
            'email' => 'Tu correo electrónico',
            'password' => 'Una contraseña',
            'password_hint' => 'Ocho caracteres como mínimo. Puedes mostrarla para comprobarla.',
            'show' => 'Mostrar',
            'hide' => 'Ocultar',
            'register' => 'Crear mi cuenta y continuar',
            'login' => 'Iniciar sesión y continuar',
            'forgot' => '¿Has olvidado la contraseña?',
        ],

        // Les options, présentées comme chez le leader : une carte, une image,
        // un prix, « Ajouter ». Puis les trois accords, chacun sa case.
        'options' => [
            'intro' => 'Tres opciones, si te apetece. Y luego tres acuerdos, cada uno con su casilla.',
            'add' => 'Añadir',
            'remove' => 'Quitar',
            'added' => 'Añadido',
            'closed' => 'Completo por ahora',
            'recommended' => 'Recomendado para ella',
            'copies' => [
                'title' => 'Ejemplares adicionales',
                'body' => 'Para que hermanos, hermanas, hijos y nietos se queden cada uno con el suyo.',
                'each' => ':amount por ejemplar',
                'count' => 'Número de ejemplares',
                'fewer' => 'Un ejemplar menos',
                'more' => 'Un ejemplar más',
                'alt' => 'El libro encuadernado, de pie sobre una mesa de madera',
            ],
            'instead' => 'en vez de :amount',
            'ebook' => [
                'title' => 'El libro digital',
                'body' => 'Todas las historias, el texto y la voz, para leer y escuchar en el teléfono, la tableta o el ordenador. Para la familia que vive lejos, y para la espera del libro encuadernado.',
                'alt' => 'Una historia leída en un teléfono',
            ],
            'phone' => [
                'title' => 'Grabación por teléfono',
                'body' => 'Una persona de nuestro equipo llama a :first_name cada semana en la franja elegida y graba la historia. No tiene que manejar nada.',
                'remaining' => 'Plazas limitadas: quedan :remaining de :cap.',
                'alt' => 'Una mujer mayor hablando por teléfono',
            ],
        ],

        'summary' => [
            'title' => 'Resumen',
            'intro' => 'Repasa y luego paga. El pago se hace en la página de nuestro proveedor y vuelves aquí.',
            'narrator' => 'Quien narra',
            'gift' => 'La invitación',
            'gift_self' => 'La primera pregunta',
            'gift_line' => 'El :date a las :time',
            'options' => 'Las opciones',
            'none' => 'Ninguna opción',
            'copies_one' => 'Un ejemplar adicional',
            'copies_many' => ':count ejemplares adicionales',
            'phone' => 'La opción de teléfono',
            'ebook' => 'El libro digital',
            'total' => 'Total a pagar',
            'notice' => 'El pago se realiza en la página segura de nuestro proveedor. Nunca vemos el número de tu tarjeta.',
        ],

        // La colonne de droite : ce qu’on achète, et ce qu’on promet.
        'aside' => [
            'title' => 'Tu pedido',
            'for' => 'Para :name',
            'for_self' => 'Para ti',
            'main' => 'El libro encuadernado y un año de preguntas',
            'copies_one' => 'Un ejemplar adicional',
            'copies_many' => ':count ejemplares adicionales',
            'phone' => 'La opción de teléfono',
            'discount' => 'Descuento de bienvenida, :percent',
            'ebook' => 'El libro digital',
            'total' => 'Total',
            'one_payment' => 'Un solo pago, sin suscripción.',
            'secure' => 'Pago seguro en la página de nuestro proveedor.',
            'refund' => 'Garantía de devolución durante treinta días.',
            'help' => '¿Alguna duda?',
        ],

        'terms' => 'Acepto las condiciones generales de venta y la política de privacidad.',
        'early_start' => 'Solicito que el servicio digital empiece de inmediato, sin esperar a que termine el plazo de desistimiento de catorce días.',
        'early_start_notice' => 'En ese caso, si desistes, podremos retener una parte correspondiente a lo que ya se haya prestado.',
        'marketing' => 'Quiero recibir novedades.',
        'pay' => 'Pagar :amount',

        // Le code de réduction, posé au récapitulatif (T-141).
        'discount' => [
            'have_code' => 'Tengo un código de descuento',
            'label' => 'Tu código',
            'placeholder' => 'ABCD-EFGH',
            'apply' => 'Aplicar',
            'applied' => 'Descuento de :percent · :code',
            'remove' => 'Quitar',
            'errors' => [
                'unknown' => 'Este código no corresponde a nada. Comprueba las letras y los números.',
                'used' => 'Este código ya se ha usado.',
                'expired' => 'Este código ya no es válido.',
            ],
        ],

        'thanks' => [
            'title' => 'Gracias',
            'headline' => 'Gracias. El libro :of empieza aquí.',
            'headline_anonymous' => 'Gracias. El libro empieza aquí.',
            'headline_self' => 'Gracias. Tu libro empieza aquí.',
            'body' => 'Tu pago se ha realizado. Recibirás un correo electrónico con el detalle, y la invitación se enviará el día que has elegido.',
            'next_title' => 'Qué pasa ahora',
            'next' => [
                'email' => 'Recibirás un correo de confirmación en unos minutos.',
                'invite' => 'La invitación se envía el :date a las :time, con tu mensaje.',
                'invite_soon' => 'La invitación se envía el día y a la hora que has elegido, con tu mensaje.',
                'invite_self' => 'Tu primera pregunta llega el :date a las :time.',
                'invite_self_soon' => 'Tu primera pregunta llega el día y a la hora que has elegido.',
                'first' => 'La semana en que acepte, recibirá su primera pregunta y responderá hablando.',
                'first_self' => 'Respondes hablando, desde tu teléfono, cuando quieras a lo largo de la semana.',
                'space' => 'Lo sigues todo desde tu espacio personal: las preguntas, los familiares, las fotos.',
            ],
            'book_aria' => 'Un libro que se abre',
            'book_cover' => 'Las historias :of',
            'book_cover_anonymous' => 'Sus historias',
            'book_cover_self' => 'Tus historias',
            'book_sub' => 'Primer capítulo, próximamente',
            'orders' => 'Ir a mi espacio personal',
        ],
    ],
];
