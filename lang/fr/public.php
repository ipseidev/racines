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

    'vcard' => [
        'note' => 'Vos questions de la semaine arrivent de ce contact. Nous ne vous demanderons jamais de mot de passe ni de paiement par SMS.',
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
        'promise' => 'Le livre de leurs souvenirs, avec leur voix à chaque page.',
        'cta' => 'J’offre ce livre',
        'cta_start' => 'Je commence son livre',
        'cta_how' => 'Comment ça marche',
        'cta_try' => 'Essayez en 60 secondes',
        'cta_see_book' => 'Voir le livre',

        // Le bandeau en haut de toutes les pages publiques : l'offre en une ligne.
        'bar' => 'Une année de questions + le livre relié : tout compris, :price',

        'nav' => [
            'how' => 'Comment ça marche',
            'book' => 'Le livre',
            'story' => 'Notre histoire',
            'faq' => 'Questions',
            'login' => 'Se connecter',
        ],

        'hero' => [
            'lede' => 'Chaque semaine, une question. Elle répond en parlant, depuis son téléphone. À la fin de l’année, un livre relié de ses histoires, et sa voix à chaque page.',
            'note' => 'Un seul paiement sécurisé, pas d’abonnement. Ses souvenirs restent privés.',
            'checks' => [
                'voice' => 'Sa voix se réécoute à chaque page du livre.',
                'no_app' => 'Aucune application, aucun mot de passe : un lien, elle parle.',
                'kept_words' => 'Ses mots sont mis au propre, jamais réécrits. Le mot à mot est conservé.',
                'she_decides' => 'C’est elle qui décide de ce que la famille entend.',
            ],
            'card' => [
                'aria' => 'Exemple de question de la semaine',
                'label' => 'Question de la semaine',
                'name' => 'Odette',
                'question' => 'Quelle odeur vous ramène à votre enfance ?',
                'answers' => 'Elle répond en parlant.',
                'duration' => '2 min 14',
            ],
            'photo_alt' => 'Une femme assise à la table de sa cuisine, le téléphone à la main, songeuse.',
        ],

        // Le bandeau sombre : trois engagements, en tête courte puis en formulation canonique.
        'promises' => [
            'title' => 'Ce qui ne change pas',
            'validation' => 'Rien n’est partagé sans son accord.',
            'ai_arranges' => 'L’IA range, elle n’invente pas.',
            'withdrawal' => 'Elle peut tout retirer, à tout moment.',
        ],

        'what' => [
            'title' => 'Qu’est-ce que :brand',
            'headline' => 'Un livre des histoires de sa vie, racontées avec sa voix.',
            'body' => ':brand transforme une année de souvenirs racontés à l’oral, une question par semaine, en un livre relié d’histoires mises au propre. Chaque chapitre porte un code à scanner qui rejoue l’enregistrement d’origine : on lit l’histoire qu’elle a racontée, et on l’entend la raconter.',
        ],

        'how' => [
            'title' => 'Comment ça marche',
            'headline' => 'Les histoires sur la page. La voix, à un scan de là.',
            'lede' => 'Rien à installer, rien à écrire. Une année de questions, à son rythme, et un livre au bout.',
            'one' => [
                'title' => 'Vous choisissez les questions',
                'body' => 'Parmi soixante questions écrites pour faire remonter les histoires que la famille n’a jamais entendues. Ou vous nous laissez faire.',
                'alt' => 'Une main tient deux photographies anciennes de famille.',
            ],
            'two' => [
                'title' => 'Une question arrive. Elle parle.',
                'body' => 'Chaque semaine, par SMS ou par courriel. Ni application, ni compte, ni mot de passe. Elle ouvre le lien et elle raconte, depuis son téléphone.',
                'alt' => 'Une femme âgée sourit, en cardigan gris.',
            ],
            'three' => [
                'title' => 'Ses mots deviennent un chapitre',
                'body' => 'Les hésitations s’effacent, ses tournures restent. Le mot à mot est conservé à côté du texte mis au propre, et elle relit avant tout le monde.',
                'alt' => 'Un livre ouvert, photographié de près.',
            ],
            'four' => [
                'title' => 'La famille l’entend aussitôt',
                'body' => 'Chaque histoire qu’elle choisit de partager arrive à ses proches. Ils la lisent, l’écoutent, lui répondent d’un mot. Pour beaucoup de familles, c’est le meilleur moment de la semaine.',
                'alt' => 'Une famille réunie autour d’une table, sur une photographie ancienne.',
            ],
        ],

        // Notre histoire : celle du fondateur, à la première personne, sans le nommer.
        'story' => [
            'title' => 'Notre histoire',
            'p1' => 'J’ai pris conscience de ma famille et de son histoire bien trop tard. Quand mes grands-parents sont partis, je me suis rendu compte que je ne savais presque rien de leur vie. Et qu’avec eux, c’est une partie de l’histoire de ma famille qui s’en allait.',
            'p2' => 'Alors j’ai cherché un moyen de garder ce qui restait : la voix de ceux qui sont encore là, et ce qu’ils ont envie de raconter. Pas un cahier à remplir, personne ne le remplit. Une question de temps en temps, à laquelle on répond en parlant, comme on répond au téléphone.',
            'p3' => 'C’est de là que vient ce livre. Il ne remplace pas les conversations qu’on n’a pas eues. Il fait qu’il y en aura d’autres, et qu’on pourra les rouvrir.',
        ],

        // Le bloc produit, comme une fiche : ce qu'on achète, ce que ça contient.
        'product' => [
            'title' => 'Le livre de vie qu’on peut écouter',
            'lede' => 'Une année de questions qui transforme les souvenirs racontés de votre proche en un livre relié d’histoires écrites.',
            'read' => [
                'title' => 'Lire l’histoire.',
                'body' => 'Chaque chapitre est une histoire qu’elle a racontée, mise au propre sans rien inventer.',
            ],
            'hear' => [
                'title' => 'L’entendre la raconter.',
                'body' => 'Un code à scanner sur chaque chapitre rejoue l’enregistrement d’origine. Sa voix, telle qu’elle l’a dite.',
            ],
            'bound' => [
                'title' => 'Relié pour durer.',
                'body' => 'Un livre relié, en couleur, avec les photos que la famille a ajoutées. Le format s’adapte à ce qui a été raconté.',
            ],
            'includes' => [
                'questions' => 'Une année de questions, une par semaine',
                'device' => 'Elle répond depuis n’importe quel téléphone',
                'download' => 'Tous les enregistrements téléchargeables, à tout moment',
                'book' => 'Un livre relié, en couleur',
                'qr' => 'Un code à scanner par chapitre',
                'family' => 'La famille invitée à écouter et à réagir',
            ],
            'guarantees' => [
                'refund' => 'Satisfait ou remboursé 30 jours',
                'yours' => 'Ses histoires vous appartiennent',
                'download' => 'Téléchargeables à tout moment',
            ],
            'mockup' => [
                'cover_title' => 'Les histoires d’Odette',
                'cover_sub' => 'racontées par elle-même',
                'chapter' => 'L’odeur du pain de ma grand-mère',
                'scan' => 'Scannez pour l’entendre raconter',
                'aria' => 'Maquette du livre et de la page d’écoute',
            ],
        ],

        'forever' => [
            'headline' => 'Ses souvenirs restent dans la famille. Il n’y a rien à renouveler.',
            'lede' => ':brand comprend une année de questions, le livre relié, et l’accès à tout ce que vous avez recueilli, bien après la dernière question.',
            'title' => 'Ce que comprend votre achat',
            'access' => [
                'title' => 'Un accès qui ne s’arrête pas avec l’année',
                'body' => 'Tout ce que votre proche enregistre pendant l’année, et tout ce qui en est fait, reste accessible ensuite, sans rien payer de plus.',
            ],
            'download' => [
                'title' => 'Tout se télécharge',
                'body' => 'Les enregistrements d’origine et les textes, sur votre propre appareil, quand vous voulez. Vos données ne sont jamais retenues.',
            ],
            'no_sub' => [
                'title' => 'Un seul paiement',
                'body' => 'Pas d’abonnement, pas de renouvellement discret. Vous offrez l’année, elle raconte à son rythme, le livre arrive.',
            ],
            'banner' => 'Ses histoires restent à vous. Il n’y a rien à renouveler.',
            'per' => 'une année de questions, et le livre relié',
        ],

        // La bande de confiance : trois faits, tous déjà écrits dans nos engagements.
        'trust' => [
            'eu' => 'Hébergé dans l’Union européenne',
            'no_training' => 'Aucune IA entraînée sur vos souvenirs',
            'refund' => 'Satisfait ou remboursé pendant 30 jours',
        ],

        'guarantee' => [
            'headline' => 'Satisfait ou remboursé pendant trente jours. Si le premier enregistrement ne vous touche pas, nous vous remboursons.',
            'body' => 'Sans justification à donner. Il suffit de nous le dire depuis votre espace.',
        ],

        'tested' => [
            'title' => 'Pensé pour les grands-parents. Approuvé par la famille.',
            'lede' => 'Pour celles et ceux qui racontent, de 9 à 99 ans.',
            'no_writing' => 'Rien à écrire.',
            'no_app' => 'Rien à installer.',
            'no_password' => 'Aucun mot de passe.',
            'cta' => 'Essayer : ça prend 60 secondes',
            'photo_alt' => 'Une femme âgée sourit, en cardigan gris.',
        ],

        'try' => [
            'title' => 'Essayez en 60 secondes',
            'body' => 'Enregistrez-vous, réécoutez-vous. Rien n’est envoyé : tout reste sur votre appareil et disparaît quand vous fermez la page.',
        ],

        'book' => [
            'title' => 'Le livre',
            'headline' => 'La photo, l’histoire et la voix, sur une même page.',
            'body' => 'Chaque chapitre porte un code à scanner qui rejoue l’enregistrement d’origine. On entend chaque histoire exactement comme elle a été racontée, avec sa voix.',
            'qr' => 'Les codes de votre livre mènent aux enregistrements aussi longtemps que le service existe. Si nous devions cesser notre activité, nous vous préviendrions et vous fournirions vos fichiers.',
            'photo_alt' => 'Un homme âgé regarde deux photographies anciennes qu’il tient dans ses mains.',
        ],

        'review' => [
            'headline' => 'Regardez son récit prendre forme.',
            'body' => 'Le mot à mot d’un côté, le texte mis au propre de l’autre, et rien d’inventé entre les deux. Elle relit, corrige un mot si elle veut, puis décide de ce que la famille entendra.',
            'screenshot_alt' => 'La page de relecture : le texte mis au propre, le mot à mot, et les trois choix de partage.',
        ],

        'proof' => [
            'aria' => 'Exemple : le mot à mot et le texte mis au propre, côte à côte',
            'verbatim' => 'Mot à mot',
            'fluide' => 'Texte mis au propre',
            'sample_verbatim' => 'alors euh… ma grand-mère elle habitait à Saint-Aubin, enfin Saint-Aubin-du-Cormier, et euh chaque dimanche on y allait, on y allait en voiture avec mon père, ça faisait… je sais plus, une heure de route peut-être. Et elle faisait le pain elle-même, dans le four, le four à bois derrière la maison.',
            'sample_fluide' => 'Ma grand-mère, elle habitait à Saint-Aubin-du-Cormier, et chaque dimanche on y allait en voiture avec mon père. Ça faisait… je sais plus, une heure de route peut-être. Et elle faisait le pain elle-même, dans le four à bois derrière la maison.',
            'then' => 'Puis elle choisit :',
            'share' => 'Partager',
            'keep' => 'Garder pour moi',
            'later' => 'Décider plus tard',
        ],

        'gift' => [
            'headline' => 'Programmez l’envoi du cadeau',
            'body' => 'Choisissez la date : ce jour-là, votre proche reçoit votre message et le lien de sa première question. Vous pouvez aussi imprimer une carte à glisser dans une enveloppe.',
        ],

        'tiles' => [
            'phone' => [
                'title' => 'Option téléphone',
                'body' => 'Elle préfère qu’on l’appelle ? Une personne de notre équipe lui pose la question de vive voix, chaque semaine, et enregistre l’histoire.',
            ],
            'copies' => [
                'title' => 'Exemplaires supplémentaires',
                'body' => 'Pour que chaque enfant, chaque petit-enfant, ait le sien.',
                'each' => 'l’exemplaire',
            ],
        ],

        /*
         * R-10, en formulation canonique. Ce sont des phrases qu'on peut nous
         * opposer : elles doivent être identiques ici, dans les CGV et dans
         * les courriels.
         */
        'commitments' => [
            'title' => 'Nos engagements',
            'validation' => 'La validation est explicite, jamais tacite : rien n’est visible des proches sans l’accord de la personne qui a raconté.',
            'no_cloning' => 'Pas de clonage vocal : nous n’imitons jamais une voix, et nous n’en fabriquons pas.',
            'ai_arranges' => 'L’IA range, elle n’invente pas : elle enlève les hésitations et ajoute la ponctuation. Elle n’ajoute aucun fait.',
            'source_audio' => 'L’enregistrement d’origine est conservé et n’est jamais remplacé. Le mot à mot reste accessible à côté du texte mis au propre.',
            'no_training' => 'Aucun contenu de votre famille ne sert à entraîner un modèle.',
            'eu_hosting' => 'Vos enregistrements et vos textes sont hébergés dans l’Union européenne.',
            'withdrawal' => 'La personne qui raconte peut masquer, retirer ou supprimer une histoire à tout moment, sans se justifier.',
        ],

        'price' => [
            'title' => 'Le prix',
            'prevente' => 'Prévente',
            'prevente_body' => 'Vous réservez maintenant, le service démarre à l’ouverture. Remboursable jusqu’au démarrage.',
            'phone_option' => 'Enregistrement par téléphone',
            'phone_option_body' => 'Un membre de notre équipe appelle votre proche chaque semaine et enregistre l’histoire. Rien à manipuler de son côté.',
            'reassurance' => 'Paiement sécurisé · Satisfait ou remboursé 30 jours',
        ],

        'faq' => [
            'title' => 'Questions fréquentes',
            'included' => [
                'q' => 'Qu’est-ce qui est compris dans mon achat ?',
                'a' => 'Une année de questions, une par semaine. Les histoires mises au propre, avec le mot à mot conservé. L’écoute pour toute la famille. Le livre relié, avec un code à scanner par chapitre. Et tous les enregistrements, téléchargeables à tout moment.',
            ],
            'subscription' => [
                'q' => 'Est-ce un abonnement ?',
                'a' => 'Non. Un seul paiement couvre l’année et le livre. Après l’année, tout ce qui a été recueilli reste à vous, et vous pouvez tout télécharger. Il n’y a rien à renouveler.',
            ],
            'edit' => [
                'q' => 'Peut-on corriger le texte qui est écrit ?',
                'a' => 'Oui. La personne qui raconte relit chaque histoire avant qui que ce soit, et corrige un mot si elle veut. Le mot à mot reste conservé à côté, dans tous les cas.',
            ],
            'no_smartphone' => [
                'q' => 'Et si mon proche n’a pas de smartphone ?',
                'a' => 'L’option téléphone existe pour ça : nous appelons, et nous enregistrons la conversation. Elle est proposée en option, en nombre limité.',
            ],
            'refuses' => [
                'q' => 'Et s’il ou elle refuse ?',
                'a' => 'C’est prévu, et c’est respecté. Nous vous remboursons intégralement, sans justification à donner.',
            ],
            'writing' => [
                'q' => 'Faut-il savoir écrire ou se servir d’un ordinateur ?',
                'a' => 'Non. Tout se fait en parlant, depuis un téléphone, en touchant un lien reçu par message.',
            ],
            'privacy' => [
                'q' => 'Qui peut écouter les histoires ?',
                'a' => 'Seuls les proches que la personne qui raconte a autorisés. Elle décide histoire par histoire, et peut changer d’avis.',
            ],
            'refund' => [
                'q' => 'Et si le résultat ne me convient pas ?',
                'a' => 'Vous avez trente jours : si le premier enregistrement ne vous touche pas, nous vous remboursons intégralement, sans justification à donner.',
            ],
            'shutdown' => [
                'q' => 'Que se passe-t-il si vous cessez votre activité ?',
                'a' => 'Nous vous prévenons au moins trois mois à l’avance, nous vous fournissons l’intégralité de vos enregistrements et de vos textes dans un format lisible sans nous, et nous vous remboursons ce qui n’a pas été livré. Nous ne promettons pas une conservation à vie : nous promettons de ne jamais vous laisser sans vos fichiers.',
            ],
        ],
    ],

    'legal' => [
        'terms' => 'Conditions générales de vente',
        'privacy' => 'Politique de confidentialité',
        'imprint' => 'Mentions légales',
        'consents' => 'Vos accords, dans leur version en vigueur',
        'version' => 'Version :version, en vigueur depuis le :date.',
        'draft_banner' => 'Ce texte n’est pas encore validé par notre conseil juridique. Il est publié pour transparence, et sera mis à jour après sa relecture.',
    ],

    'demo' => [
        'title' => 'Essayez en 60 secondes',
        'body' => 'Parlez, puis réécoutez-vous. Rien ne part de votre appareil.',
        'nothing_sent' => 'Cet essai reste sur votre téléphone et disparaît quand vous fermez la page.',
        'start' => 'Commencer l’essai',
        'recording' => 'C’est parti. Il reste :seconds secondes.',
        'stop' => 'J’ai terminé',
        'ready' => 'Réécoutez-vous.',
        'playback' => 'Votre essai',
        'again' => 'Recommencer',
        'unsupported' => 'Ce navigateur ne sait pas enregistrer. Essayez depuis Safari sur iPhone, ou Chrome sur Android.',
        'refused' => 'Le micro n’a pas été autorisé. C’est réversible : dans les réglages de votre navigateur, autorisez le micro pour ce site, puis rechargez la page.',
        'cta' => 'Offrir à un proche',
    ],

    'checkout' => [
        'title' => 'Offrir',
        'step_of' => 'Étape :step sur :total',
        'progress' => 'Progression de la commande',
        'next' => 'Continuer',
        'back' => 'Revenir',
        'waiting' => 'Un instant…',
        'edit' => 'Modifier',
        'secure' => 'Paiement sécurisé',
        'refund' => 'Satisfait ou remboursé 30 jours',

        // Les titres des étapes, puis leur forme courte pour la progression.
        'steps' => [
            'for' => 'Pour qui ?',
            'narrator' => 'Le narrateur',
            'gift' => 'Le cadeau',
            'gift_self' => 'Le début',
            'account' => 'Votre compte',
            'options' => 'Options et accords',
            'summary' => 'Récapitulatif',
        ],
        'labels' => [
            'for' => 'Pour qui',
            'narrator' => 'Le narrateur',
            'gift' => 'Le cadeau',
            'account' => 'Votre compte',
            'options' => 'Options',
            'summary' => 'Récapitulatif',
        ],

        'for' => [
            'intro' => 'Le livre se fait à deux : quelqu’un l’offre, quelqu’un raconte.',
            'relative' => 'Un proche',
            'relative_hint' => 'Un parent, un grand-parent, quelqu’un que vous aimez. Vous offrez, la personne raconte.',
            'self' => 'Vous-même',
            'self_hint' => 'Vous racontez vos propres souvenirs.',
        ],

        // Deux jeux de libellés : « son » quand on offre à un proche, « votre »
        // quand on raconte soi-même. La forme suit le choix de l'étape 1.
        'narrator' => [
            'intro' => 'La personne qui racontera. Nous ne lui écrirons qu’une fois, pour l’inviter, et nous n’enverrons aucune question avant qu’elle ait accepté.',
            'intro_self' => 'Vous raconterez vous-même. Nous vous écrirons une fois pour commencer, puis une question par semaine.',
            'first_name' => 'Son prénom',
            'first_name_self' => 'Votre prénom',
            'last_name' => 'Son nom (facultatif)',
            'last_name_self' => 'Votre nom (facultatif)',
            'relationship' => 'Votre lien avec elle',
            'relationship_hint' => 'Ma mère, mon grand-père, une amie de toujours.',
            'contact_hint' => 'Un courriel ou un numéro suffit.',
            'contact_hint_self' => 'Un courriel ou un numéro suffit : c’est là que les questions arriveront.',
            'email' => 'Son courriel',
            'email_self' => 'Votre courriel',
            'phone' => 'Son numéro de téléphone',
            'phone_self' => 'Votre numéro de téléphone',
            'channel' => 'Comment la joindre ?',
            'channel_self' => 'Comment vous joindre ?',
            'address_form' => 'Faut-il lui dire « vous » ou « tu » ?',
            'address_form_self' => 'Préférez-vous « vous » ou « tu » ?',
            'tech_comfort' => 'Cette personne est-elle à l’aise avec un téléphone ?',
            'tech_comfort_hint' => 'Nous adaptons l’aide et les options à votre réponse.',
        ],

        'gift' => [
            'intro' => 'L’invitation partira à la date et à l’heure que vous choisissez, avec votre mot.',
            'intro_self' => 'Votre première question partira à la date et à l’heure que vous choisissez.',
            'send_at' => 'Quel jour ?',
            'send_time' => 'À quelle heure ?',
            'message' => 'Votre message personnel',
            'message_hint' => 'C’est ce mot qui décide : un message de vous vaut dix des nôtres.',
            'message_counter' => ':count caractères sur :max',
            'message_default' => 'J’aimerais garder tes histoires. Il suffit de parler, une question par semaine, quand tu veux. Si ça ne te dit pas, dis-le-moi simplement.',
        ],

        'account' => [
            'intro' => 'Pour suivre le projet, ajouter des photos et retrouver votre commande. Rien ne part avant la dernière étape.',
            'signed_in' => 'Vous êtes connecté·e en tant que :email.',
            'create' => 'Créer un compte',
            'have' => 'J’ai déjà un compte',
            'name' => 'Votre nom',
            'email' => 'Votre courriel',
            'password' => 'Un mot de passe',
            'password_hint' => 'Huit caractères au moins. Vous pouvez l’afficher pour vérifier.',
            'show' => 'Afficher',
            'hide' => 'Masquer',
            'register' => 'Créer mon compte et continuer',
            'login' => 'Me connecter et continuer',
            'forgot' => 'Mot de passe oublié ?',
        ],

        // Les options, présentées comme chez le leader : une carte, une image,
        // un prix, « Ajouter ». Puis les trois accords, chacun sa case.
        'options' => [
            'intro' => 'Deux options, si vous le souhaitez. Puis trois accords, chacun sa case.',
            'add' => 'Ajouter',
            'remove' => 'Retirer',
            'added' => 'Ajouté',
            'closed' => 'Complet pour le moment',
            'recommended' => 'Recommandé pour elle',
            'copies' => [
                'title' => 'Exemplaires supplémentaires',
                'body' => 'Pour que frères, sœurs, enfants et petits-enfants gardent chacun le leur.',
                'each' => ':amount l’exemplaire',
                'count' => 'Nombre d’exemplaires',
                'fewer' => 'Un exemplaire de moins',
                'more' => 'Un exemplaire de plus',
                'alt' => 'Le livre relié, ouvert sur une double page',
            ],
            'phone' => [
                'title' => 'Enregistrement par téléphone',
                'body' => 'Un membre de notre équipe appelle :first_name chaque semaine au créneau choisi et enregistre l’histoire. Rien à manipuler de son côté.',
                'remaining' => 'Places limitées : il en reste :remaining sur :cap.',
                'alt' => 'Une femme âgée qui répond au téléphone',
            ],
        ],

        'summary' => [
            'title' => 'Récapitulatif',
            'intro' => 'Relisez, puis payez. Le paiement se fait chez notre prestataire, et vous revenez ici.',
            'narrator' => 'Le narrateur',
            'gift' => 'L’invitation',
            'gift_self' => 'La première question',
            'gift_line' => 'Le :date à :time',
            'options' => 'Les options',
            'none' => 'Aucune option',
            'copies_one' => 'Un exemplaire supplémentaire',
            'copies_many' => ':count exemplaires supplémentaires',
            'phone' => 'L’option téléphone',
            'total' => 'Total à payer',
            'notice' => 'Le paiement se fait sur la page sécurisée de notre prestataire. Nous ne voyons jamais votre numéro de carte.',
        ],

        // La colonne de droite : ce qu’on achète, et ce qu’on promet.
        'aside' => [
            'title' => 'Votre commande',
            'for' => 'Pour :name',
            'for_self' => 'Pour vous',
            'main' => 'Le livre relié et une année de questions',
            'copies_one' => 'Un exemplaire supplémentaire',
            'copies_many' => ':count exemplaires supplémentaires',
            'phone' => 'L’option téléphone',
            'total' => 'Total',
            'one_payment' => 'Un seul paiement, pas d’abonnement.',
            'secure' => 'Paiement sécurisé sur la page de notre prestataire.',
            'refund' => 'Satisfait ou remboursé pendant trente jours.',
            'help' => 'Une question ?',
        ],

        'terms' => 'J’accepte les conditions générales de vente et la politique de confidentialité.',
        'early_start' => 'Je demande que le service numérique démarre immédiatement, sans attendre la fin du délai de rétractation de quatorze jours.',
        'early_start_notice' => 'Dans ce cas, si vous vous rétractez, nous pourrons retenir une part correspondant à ce qui aura déjà été fourni.',
        'marketing' => 'Je souhaite recevoir des nouvelles.',
        'pay' => 'Payer :amount',

        'thanks' => [
            'title' => 'Merci',
            'headline' => 'Merci. Le livre :of commence ici.',
            'headline_anonymous' => 'Merci. Le livre commence ici.',
            'headline_self' => 'Merci. Votre livre commence ici.',
            'body' => 'Votre paiement est passé. Vous recevez un courriel avec le détail, et l’invitation partira à la date que vous avez choisie.',
            'next_title' => 'Ce qui se passe maintenant',
            'next' => [
                'email' => 'Vous recevez un courriel de confirmation dans quelques minutes.',
                'invite' => 'L’invitation part le :date à :time, avec votre mot.',
                'invite_soon' => 'L’invitation part à la date et à l’heure que vous avez choisies, avec votre mot.',
                'invite_self' => 'Votre première question arrive le :date à :time.',
                'invite_self_soon' => 'Votre première question arrive à la date et à l’heure que vous avez choisies.',
                'first' => 'La semaine où elle accepte, elle reçoit sa première question et répond en parlant.',
                'first_self' => 'Vous répondez en parlant, depuis votre téléphone, quand vous voulez dans la semaine.',
                'space' => 'Vous suivez tout depuis votre espace : les questions, les proches, les photos.',
            ],
            'book_aria' => 'Un livre qui s’ouvre',
            'book_cover' => 'Les histoires :of',
            'book_cover_anonymous' => 'Ses histoires',
            'book_cover_self' => 'Vos histoires',
            'book_sub' => 'Premier chapitre à venir',
            'orders' => 'Aller dans mon espace',
        ],
    ],
];
