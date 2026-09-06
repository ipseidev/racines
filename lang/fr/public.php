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
        'description' => 'Une question par semaine, sa voix qui répond, et le livre relié de ses souvenirs. Sans application ni compte à créer. Rien n\'est partagé sans son accord.',
    ],

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
        'promise' => 'Le livre de ses souvenirs, avec sa voix à chaque page.',
        'seo_title' => 'Le livre de leurs souvenirs, avec leur voix à chaque page',
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
            'lede' => 'Chaque semaine, une question. Votre proche y répond en parlant, depuis son téléphone. Au bout d’un an, le livre relié de ses histoires, et sa voix à chaque page.',
            'note' => 'Un seul paiement sécurisé, pas d’abonnement. Ses souvenirs restent privés.',
            'checks' => [
                'voice' => 'Sa voix se réécoute à chaque page du livre.',
                'no_app' => 'Aucune application, aucun mot de passe : un lien, elle parle.',
                'kept_words' => 'Ses mots sont mis au propre, jamais réécrits. Le mot à mot est conservé.',
                'she_decides' => 'C’est elle qui décide de ce que la famille entend.',
            ],
            // La carte « question de la semaine », posée sur la photo : retirée par
            // T-142, reprise le soir même à la demande du fondateur (T-144).
            'card' => [
                'aria' => 'Exemple de question de la semaine',
                'label' => 'Question de la semaine',
                'name' => 'Odette',
                'question' => 'Quelle odeur vous ramène à votre enfance ?',
                'answers' => 'Elle répond en parlant.',
                'duration' => '2 min 14',
                // La mention affichée sous le bouton quand la page la
                // demande — `product.landing.hero_sample_disclosed`. Elle est
                // décrochée depuis le 5 septembre 2026 ; le texte reste ici,
                // prêt à resservir.
                'synthetic' => 'Exemple : voix de synthèse. Les vraies histoires sont dites par de vraies voix.',
                // La transcription, pour qui n'entend pas : WCAG 2.2 AA 1.2.1
                // demande un équivalent à tout média sonore. Elle n'est pas
                // affichée — la carte est posée sur la photo et n'a pas la
                // place — mais elle est lue par les lecteurs d'écran, juste
                // après le bouton. Elle doit suivre l'audio **au mot près**.
                'transcript_label' => 'Ce qu’Odette raconte dans cet extrait',
                'transcript' => 'Oh… l’odeur du pain. Sans hésiter. Le pain qui cuit. Alors euh… ma grand-mère elle habitait à Saint-Aubin, enfin Saint-Aubin-du-Cormier, et euh chaque dimanche on y allait, on y allait en voiture avec mon père, ça faisait… je sais plus, une heure de route peut-être. Et elle faisait le pain elle-même, dans le four, le four à bois derrière la maison. Et on le sentait avant d’arriver, hein. Enfin — moi je le sentais. Mon père il disait que je racontais des histoires, mais non. Non, non. Je le sentais, dès le tournant. Et elle nous en coupait un morceau tout de suite, encore chaud, avec du beurre salé. Et… voilà. C’est ça. C’est cette odeur-là.',
            ],
            'photo_alt' => 'Une femme âgée et sa fille, enlacées sur un canapé, tiennent le livre relié qu’elles viennent de déballer.',
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
            'title' => 'Pourquoi l’offrir',
            'ask' => 'Le cadeau qu’on n’ose pas demander.',
            'voice' => 'On offre un livre. On reçoit sa voix.',
            'weekly' => 'Il s’ouvre chaque semaine, pendant un an.',
        ],

        'what' => [
            'title' => 'Qu’est-ce que :brand',
            'headline' => 'Un livre des histoires de sa vie, racontées avec sa voix.',
            'body' => ':brand transforme une année de souvenirs racontés à l’oral, une question par semaine, en un livre relié d’histoires mises au propre. Chaque chapitre porte un code à scanner qui rejoue l’enregistrement d’origine : on lit l’histoire qu’elle a racontée, et on l’entend la raconter.',
        ],

        'how' => [
            'title' => 'Comment ça marche',
            'headline' => 'Sa voix, en un simple scan.',
            // Le titre promet un scan sans dire de quoi : le chapeau nomme le
            // QR code et ce qu'il fait. On dit ce qu'il joue, jamais qu'il
            // vivrait sans nous — « QR autonomes » est interdit (R-11), et la
            // durée d'engagement se publie ailleurs (R-10).
            'lede' => 'Rien à installer, rien à écrire. Une année de questions, à son rythme, et un livre au bout : chaque chapitre porte un QR code qui rejoue sa voix.',
            'one' => [
                'title' => 'Vous choisissez les questions',
                'body' => 'Parmi soixante questions écrites pour faire remonter les histoires que la famille n’a jamais entendues. Ou vous nous laissez faire.',
                'alt' => 'Une main tient deux photographies anciennes de famille.',
            ],
            'two' => [
                'title' => 'Une question arrive. Elle parle.',
                'body' => 'Chaque semaine, par SMS ou par courriel. Ni application, ni compte, ni mot de passe. Elle ouvre le lien et elle raconte, depuis son téléphone.',
                'alt' => 'Une femme âgée, près d’une fenêtre, parle en souriant au téléphone qu’elle tient devant elle.',
            ],
            'three' => [
                'title' => 'Ses mots deviennent un chapitre',
                'body' => 'Les hésitations s’effacent, ses tournures restent. Le mot à mot est conservé à côté du texte mis au propre, et elle relit avant tout le monde.',
                'alt' => 'Une femme âgée tient devant elle un livre relié vert, titré « Récits de ma vie ».',
            ],
            'four' => [
                'title' => 'La famille l’entend aussitôt',
                'body' => 'Chaque histoire qu’elle choisit de partager arrive à ses proches. Ils la lisent, l’écoutent, lui répondent d’un mot. Pour beaucoup de familles, c’est le meilleur moment de la semaine.',
                'alt' => 'Deux personnes penchées sur un livre ouvert : l’une montre le code d’un chapitre, l’autre tient un téléphone où sourit la narratrice.',
            ],
            // Vers la page qui déroule le parcours en six étapes (T-208).
            'more' => 'Voir le parcours en détail',
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
            'title' => 'Sans mauvaise surprise',
            'no_app' => 'Ni application, ni mot de passe',
            'one_payment' => 'Un seul paiement, pas d’abonnement',
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
            'photo_alt' => 'Une femme âgée parle à son téléphone, tenu à bout de bras, sans rien d’autre à manipuler.',
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
            'photo_alt' => 'Le livre relié vert, dressé sur une table de bois parmi de vieux ouvrages, un téléphone posé à côté.',
        ],

        'review' => [
            'headline' => 'Regardez son récit prendre forme.',
            'body' => 'Le mot à mot d’un côté, le texte mis au propre de l’autre, et rien d’inventé entre les deux. Elle relit, corrige un mot si elle veut, puis décide de ce que la famille entendra.',
            'screenshot_alt' => 'La page de relecture sur un téléphone : l’enregistrement à réécouter, puis le texte mis au propre et le mot à mot, l’un à côté de l’autre.',
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
            'title' => 'Nos engagements',
            'lede' => 'Les mêmes mots ici, dans les conditions générales et dans nos courriels.',
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

    /*
     * « Comment ça marche », la page (T-208).
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
        'seo_title' => 'Comment ça marche',

        // Le bandeau de titre : le titre, la question, les deux onglets.
        'hero' => [
            'title' => 'Comment ça marche',
            'question' => 'Vous offrez ce livre, ou vous racontez vous-même ?',
            'toggle_label' => 'Pour qui est le livre ?',
            'gift' => 'Pour un proche',
            'self' => 'Pour moi',
        ],

        // L'accroche, et le média à côté : là où le leader met sa vidéo, on fait écouter.
        'intro' => [
            'gift' => [
                'headline' => 'Ses souvenirs, racontés avec sa voix.',
                'lede' => 'Rien à écrire, rien à installer : une question par semaine, et elle répond en parlant.',
            ],
            'self' => [
                'headline' => 'Votre vie, racontée avec votre voix.',
                'lede' => 'Rien à écrire, rien à installer : une question par semaine, et vous répondez en parlant.',
            ],
            'listen' => 'Écoutez',
            'caption' => 'Deux minutes avec Odette, telle qu’une famille la reçoit.',
        ],

        /*
         * Les six étapes, chacune en deux voix : « elle » quand on offre,
         * « vous » quand on raconte soi-même. L'ordre des clés est l'ordre
         * affiché, et un test le garde.
         */
        'steps' => [
            'title' => 'Les six étapes',
            'label' => 'Étape :n',
            'badge' => ':n sur 6',
            'questions' => [
                'gift' => [
                    'title' => 'Vous choisissez les questions',
                    'body' => 'Soixante questions, écrites en français pour faire remonter les histoires que la famille n’a jamais entendues : la maison d’enfance, le premier jour de travail, ce qu’on a voulu transmettre. Vous gardez celles qui lui ressemblent, vous ajoutez les vôtres, ou vous nous laissez faire. La première est toujours une question facile.',
                    'link' => 'Voir quelques questions',
                ],
                'self' => [
                    'title' => 'Vous choisissez ce que vous voulez raconter',
                    'body' => 'Soixante questions, écrites en français pour faire remonter les histoires que vos proches n’ont jamais entendues : la maison d’enfance, le premier jour de travail, ce que vous avez voulu transmettre. Vous gardez celles qui vous parlent, vous écartez les autres, vous ajoutez les vôtres. La première est toujours une question facile.',
                    'link' => 'Voir quelques questions',
                ],
                // Quatre questions du corpus (annexe A), mot pour mot : un test le vérifie.
                'samples' => [
                    'first_memory' => 'Quel est votre tout premier souvenir ?',
                    'dish' => 'Quel plat de votre enfance aimeriez-vous goûter une dernière fois ?',
                    'meeting' => 'Comment avez-vous rencontré la personne qui a partagé votre vie ?',
                    'value' => 'Quelle est la valeur que vous avez essayé de transmettre avant toutes les autres ?',
                ],
                'alt' => 'Une main tient deux photographies anciennes de famille devant une porte en bois.',
            ],
            'record' => [
                // « s'arrêter, souffler et reprendre » : la pause de l'enregistrement
                // (P0-3). Pas la reprise après un appel ou une mise en veille, que
                // le dossier interdit de promettre avant le spike navigateur.
                'gift' => [
                    'title' => 'Une question arrive. Elle parle.',
                    'body' => 'Chaque semaine, au moment qu’elle a choisi, votre proche reçoit un SMS ou un courriel : une seule question, et un lien. Elle l’ouvre, elle touche un bouton, elle raconte. Ni application, ni compte, ni mot de passe. Elle peut s’arrêter, souffler et reprendre. Et si elle préfère écrire ce jour-là, elle écrit.',
                    'link' => 'Essayer l’écran qu’elle verra',
                ],
                'self' => [
                    'title' => 'Chaque semaine, une question. Vous parlez.',
                    'body' => 'Au moment que vous avez choisi, vous recevez un SMS ou un courriel : une seule question, et un lien. Vous l’ouvrez, vous touchez un bouton, vous racontez. Ni application, ni compte, ni mot de passe. Vous pouvez vous arrêter, souffler et reprendre. Et si vous préférez écrire ce jour-là, vous écrivez.',
                    'link' => 'Essayer l’écran que vous verrez',
                ],
                'alt' => 'Une femme âgée sourit en parlant à son téléphone, devant une fenêtre.',
            ],
            'text' => [
                'gift' => [
                    'title' => 'Ses mots deviennent un texte',
                    'body' => 'En quelques minutes, l’enregistrement est transcrit, et il en sort deux textes, l’un à côté de l’autre : le mot à mot, tel qu’elle l’a dit, et le texte mis au propre, où les hésitations s’effacent et où ses tournures restent. L’IA range, elle n’invente pas. L’enregistrement d’origine est conservé, et le mot à mot n’est jamais supprimé.',
                    'link' => 'Voir la différence',
                ],
                'self' => [
                    'title' => 'Vos mots deviennent un texte',
                    'body' => 'En quelques minutes, votre enregistrement est transcrit, et il en sort deux textes, l’un à côté de l’autre : le mot à mot, tel que vous l’avez dit, et le texte mis au propre, où les hésitations s’effacent et où vos tournures restent. L’IA range, elle n’invente pas. L’enregistrement d’origine est conservé, et le mot à mot n’est jamais supprimé.',
                    'link' => 'Voir la différence',
                ],
                'alt' => 'La page de relecture sur un téléphone : l’enregistrement à réécouter, puis le texte mis au propre et le mot à mot, l’un à côté de l’autre.',
            ],
            'decide' => [
                'gift' => [
                    'title' => 'Elle relit, puis elle décide',
                    'body' => 'Avant qui que ce soit, elle relit son histoire et corrige un mot si elle veut. Puis elle choisit : partager avec ses proches, garder pour elle, ou décider plus tard. Rien n’est visible de la famille sans son accord. Et elle peut changer d’avis à tout moment : masquer une histoire, la retirer, la supprimer.',
                    'link' => 'Lire nos engagements',
                ],
                'self' => [
                    'title' => 'Vous relisez, puis vous décidez',
                    'body' => 'Avant qui que ce soit, vous relisez votre histoire et vous corrigez un mot si vous voulez. Puis vous choisissez : partager avec vos proches, garder pour vous, ou décider plus tard. Rien n’est visible de la famille sans votre accord. Et vous pouvez changer d’avis à tout moment : masquer une histoire, la retirer, la supprimer.',
                    'link' => 'Lire nos engagements',
                ],
                'alt' => 'Une femme âgée tient devant elle le livre relié de ses récits.',
            ],
            'family' => [
                'gift' => [
                    'title' => 'La famille écoute et lui répond',
                    'body' => 'Chaque histoire qu’elle a choisi de partager arrive à ses proches, sur une page privée qui rejoue sa voix et montre le texte. Ils l’écoutent, ajoutent une photo, lui répondent d’un mot. Elle sait qu’on l’a écoutée.',
                    'link' => 'Qui peut écouter ?',
                ],
                'self' => [
                    'title' => 'Vos proches écoutent et vous répondent',
                    'body' => 'Chaque histoire que vous avez choisi de partager arrive à vos proches, sur une page privée qui rejoue votre voix et montre le texte. Ils l’écoutent, ajoutent une photo, vous répondent d’un mot. Vous savez qu’ils l’ont écoutée.',
                    'link' => 'Qui peut écouter ?',
                ],
                'alt' => 'Deux proches suivent une page du livre, le téléphone à la main, la narratrice à l’écran.',
            ],
            'book' => [
                // « de quoi faire un livre » : les critères R-6, jamais un nombre
                // d'histoires. Rien sur le lieu d'impression tant que l'imprimeur
                // n'est pas contractualisé (bloc 13).
                'gift' => [
                    'title' => 'Le livre relié, avec sa voix à chaque page',
                    'body' => 'Au fil de l’année, les histoires s’assemblent en chapitres, avec les photos que la famille a ajoutées. Quand il y a de quoi faire un livre, vous relisez la maquette et vous donnez le bon à tirer. Seules les histoires qu’elle a validées y entrent. Le livre est imprimé et relié, en couleur, et chaque chapitre porte un code à scanner qui rejoue l’enregistrement d’origine. Avec le livre, vous recevez tout : les enregistrements et les textes, dans des fichiers lisibles sans nous.',
                    'link' => 'Voir le livre',
                ],
                'self' => [
                    'title' => 'Le livre relié, avec votre voix à chaque page',
                    'body' => 'Au fil de l’année, vos histoires s’assemblent en chapitres, avec les photos que vos proches ont ajoutées. Quand il y a de quoi faire un livre, vous relisez la maquette et vous donnez le bon à tirer. Seules les histoires que vous avez validées y entrent. Le livre est imprimé et relié, en couleur, et chaque chapitre porte un code à scanner qui rejoue l’enregistrement d’origine. Avec le livre, vous recevez tout : les enregistrements et les textes, dans des fichiers lisibles sans nous.',
                    'link' => 'Voir le livre',
                ],
                'alt' => 'Le livre relié, debout sur un bureau parmi des livres anciens.',
            ],
        ],

        // « Encore des questions ? » : quatre réponses lues au catalogue de l'accueil.
        'questions' => [
            'title' => 'Encore des questions ?',
            'all' => 'Toutes les questions',
        ],

        // Le bandeau d'appel, avec le prix dans le bouton comme chez le leader.
        'cta' => [
            'gift' => [
                'headline' => 'Offrez-lui le livre de sa vie, avec sa voix pour le raconter.',
                'body' => 'Une année de questions, le livre relié, et tous les enregistrements. Un seul paiement, rien à renouveler.',
            ],
            'self' => [
                'headline' => 'Votre histoire, dans vos propres mots.',
                'body' => 'Une année de questions, le livre relié de vos histoires, et tous les enregistrements. Un seul paiement, rien à renouveler.',
                'button' => 'Je commence mon livre',
            ],
        ],

        /*
         * Le texte en deux versions, en onglets, sur l'exemple d'Odette. Deux
         * et non trois : le MVP livre le mot à mot et le texte mis au propre,
         * pas de récit à la troisième personne (doc 03 §2).
         */
        'rendering' => [
            'eyebrow' => 'Le texte',
            'headline' => 'Le même enregistrement, deux textes.',
            'lede' => 'Le premier est ce qu’elle a dit, au mot près. Le second est ce qu’on lira dans le livre : les hésitations en moins, ses tournures intactes. Les deux restent accessibles, l’un à côté de l’autre, et elle corrige ce qu’elle veut dans le second.',
            'tabs_label' => 'Les deux versions du texte',
            'question_label' => 'La question d’Odette',
        ],

        // La section sombre : la citation, qui a un auteur, puis deux cartes.
        'voice' => [
            'title' => 'Pourquoi ce livre existe',
            'author' => 'Le fondateur de :brand',
            'cta' => 'Lire notre histoire',
        ],
        'more' => [
            'faq' => [
                'body' => 'Ce qui est compris, l’abonnement qu’il n’y a pas, le smartphone qui manque parfois, et ce qui se passe en cas de refus.',
                'cta' => 'Lire les réponses',
            ],
            'try' => [
                'title' => 'Essayez en 60 secondes',
                'body' => 'L’écran de la personne qui raconte, avec une vraie question de la semaine. Rien ne quitte votre téléphone.',
                'cta' => 'Faire l’essai',
            ],
        ],

        'together' => [
            'gift' => [
                'headline' => 'Un livre qui se fait à plusieurs.',
                'body' => 'Vous lancez le projet, elle raconte, la famille écoute et complète. Chacun y met quelque chose, et le livre en garde la trace.',
            ],
            'self' => [
                'headline' => 'Un livre qui se fait avec les vôtres.',
                'body' => 'Vous racontez, vos proches écoutent et complètent. Chacun y met quelque chose, et le livre en garde la trace.',
                'button' => 'Je commence mon livre',
            ],
            'points' => [
                'questions' => 'Choisir les questions',
                'photos' => 'Ajouter des photos aux histoires',
                'listen' => 'Écouter chaque histoire dès qu’elle est partagée',
                'reply' => 'Répondre d’un mot à la personne qui raconte',
            ],
            'alt' => 'Une femme âgée et sa fille s’étreignent en riant, le livre relié entre elles.',
        ],

        // L'adresse contre une réduction, en bas de page : les mêmes règles que
        // la fenêtre de bienvenue (T-141), dont elle reprend les textes.
        'newsletter' => [
            'title' => ':amount offerts pour commencer',
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
        'aria' => 'Une réduction de bienvenue',
        'eyebrow' => 'Pour commencer',
        'title' => ':amount offerts',
        'subtitle' => 'sur le livre de ses souvenirs',
        'teaser' => 'Laissez-nous votre adresse : nous vous envoyons un code de réduction de :amount, valable un an sur toute votre commande.',
        'claim' => 'Je prends ma réduction',
        'no_thanks' => 'Non merci',
        'email_label' => 'Votre adresse de courriel',
        'email_placeholder' => 'prenom@exemple.fr',
        'news' => 'Je souhaite aussi recevoir vos nouvelles, de temps en temps.',
        'send' => 'Recevoir mon code',
        'waiting' => 'Un instant…',
        'fine_print' => 'Votre adresse sert à vous envoyer le code, et à rien d’autre si vous ne cochez pas la case. Un lien pour arrêter figure dans chaque message.',
        'sent_title' => 'C’est envoyé',
        'sent_body' => 'Votre code part vers :email. S’il n’arrive pas, regardez dans les indésirables.',
        'sent_auto' => 'Si vous commandez depuis cet appareil, la réduction s’appliquera toute seule au récapitulatif.',
        'sent_cta' => 'Je commence son livre',
        'errors' => [
            'send_failed' => 'Nous n’avons pas réussi à envoyer le code. Réessayez dans un instant.',
            'closed' => 'Cette offre n’est plus proposée.',
        ],
    ],

    'legal' => [
        'terms' => 'Conditions générales de vente',
        'privacy' => 'Politique de confidentialité',
        'imprint' => 'Mentions légales',
        'consents' => 'Vos accords, dans leur version en vigueur',
        'version' => 'Version :version, en vigueur depuis le :date.',
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
        'eyebrow' => 'L’essai',
        'title' => 'Essayez en 60 secondes',
        'body' => 'Répondez à une vraie question de la semaine. Vous verrez l’écran que votre proche verra, et vous saurez si c’est à sa portée.',
        'nothing_sent' => 'Cet essai reste sur votre téléphone et disparaît quand vous fermez la page.',
        'question_label' => 'Question de la semaine',
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
        'question' => 'Quelle qualité admiriez-vous le plus chez votre père ? Et chez votre mère ?',
        'start' => 'Commencer l’essai',
        'start_hint' => 'Touchez le bouton, puis parlez. L’essai s’arrête tout seul au bout d’une minute.',
        'recording' => 'Ça tourne. Parlez, on vous écoute.',
        // Le temps écoulé, jamais un compte à rebours (PRD US-06) : voir les
        // secondes fondre coupe la parole de qui cherche ses mots.
        'elapsed' => 'Vous parlez depuis :time.',
        'stop' => 'J’ai terminé',
        'ready' => 'Réécoutez-vous.',
        'playback' => 'Votre essai',
        'again' => 'Recommencer',
        'result_title' => 'Et voici ce que ça devient.',
        'result_body' => 'Votre voix n’a pas quitté votre téléphone : nous ne l’avons pas entendue, et nous n’avons donc rien à en écrire. Voici, sur l’enregistrement d’Odette, ce que nous en faisons.',
        // L'exemple répond à la question d'Odette, pas à celle qu'on vient de
        // poser au visiteur. Il porte donc la sienne, écrite au-dessus : sans
        // elle, la page semble mettre dans une bouche une réponse qui n'y
        // était pas. Un test le garde.
        'result_question_label' => 'La question d’Odette',
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
            'intro' => 'Trois options, si vous le souhaitez. Puis trois accords, chacun sa case.',
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
                'alt' => 'Le livre relié, dressé sur une table de bois',
            ],
            'instead' => 'au lieu de :amount',
            'ebook' => [
                'title' => 'Le livre numérique',
                'body' => 'Toutes les histoires, le texte et la voix, à lire et à écouter sur téléphone, tablette ou ordinateur. Pour la famille éloignée, et pour attendre le livre relié.',
                'alt' => 'Une histoire lue sur un téléphone',
            ],
            'phone' => [
                'title' => 'Enregistrement par téléphone',
                'body' => 'Un membre de notre équipe appelle :first_name chaque semaine au créneau choisi et enregistre l’histoire. Rien à manipuler de son côté.',
                'remaining' => 'Places limitées : il en reste :remaining sur :cap.',
                'alt' => 'Une femme âgée qui parle à son téléphone',
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
            'ebook' => 'Le livre numérique',
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
            'discount' => 'Réduction de bienvenue, :percent',
            'ebook' => 'Le livre numérique',
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

        // Le code de réduction, posé au récapitulatif (T-141).
        'discount' => [
            'have_code' => 'J’ai un code de réduction',
            'label' => 'Votre code',
            'placeholder' => 'ABCD-EFGH',
            'apply' => 'Appliquer',
            'applied' => 'Réduction de :percent · :code',
            'remove' => 'Retirer',
            'errors' => [
                'unknown' => 'Ce code ne correspond à rien. Vérifiez les lettres et les chiffres.',
                'used' => 'Ce code a déjà servi.',
                'expired' => 'Ce code n’est plus valable.',
            ],
        ],

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
