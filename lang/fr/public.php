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

            /*
             * Le lien vers « Comment ça marche », que le témoin affiche sous
             * ses quatre étapes (T-220). La clé n'existait que sous `lp` : le
             * témoin appelait `public.landing.how.more` et n'obtenait rien.
             * Même libellé que la variante — deux formulations pour un même
             * lien finiraient par diverger.
             */
            'more' => 'Voir le parcours en détail',
            // Vers la page qui déroule le parcours en six étapes (T-213).
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
        'seo_title' => 'Offrez le livre de sa vie, et retrouvez sa voix',

        'cta' => [
            'buy' => 'J’offre son livre',
            'buy_price' => 'J’offre son livre · :price',
            'start' => 'Je commence son livre',
            'how' => 'Comment ça marche',
        ],

        // S00. Le bandeau et la navigation de la variante : ses propres ancres.
        'nav' => [
            'how' => 'Comment ça marche',
            'book' => 'Le livre',
            'faq' => 'Questions fréquentes',
            'menu' => 'Ouvrir le menu',
            'menu_close' => 'Fermer le menu',
            'bar' => 'Une année de questions et son livre relié : :price, sans abonnement.',
        ],

        // S01. Le héros : la promesse, l'action, la preuve visuelle.
        'hero' => [
            'title' => 'Un livre de souvenirs qui vous permet d’entendre leur voix pour toujours.',
            'lede' => 'Ils ne font que parler. :brand le capture, l’écrit et le regroupe en une belle couverture cartonnée avec leur voix sur chaque page. Aucune écriture requise. Juste leurs histoires, dans leur propre voix, dans un livre que ta famille gardera depuis des générations.',
            'checks' => [
                'voice' => 'Leur voix résonne sur chaque page',
                'no_app' => 'Fonctionne sur n’importe quel téléphone, aucune application ou connexion requise',
                'digital' => 'Livraison numérique disponible',
                'no_writing' => 'Pas besoin d’écrire, ils parlent juste',
            ],
            // La vignette posée sur la photo : le livre, pour qu'il ne
            // disparaisse pas du cadrage. Aucun faux badge d'avis.
            'thumb' => [
                'label' => 'Le livre relié',
                'body' => 'Un QR code par chapitre',
            ],
        ],

        // S02. Le bandeau sombre sous le héros : trois appréciations, cinq
        // étoiles chacune, celle du milieu plus grande.
        'trust' => [
            'title' => 'Ce qu’on en dit',
            'stars' => 'Cinq étoiles sur cinq',
            'quotes' => [
                'one' => '« Le cadeau parfait »',
                'two' => '« Un cadeau pour le futur toi »',
                'three' => '« Fabuleux »',
            ],
        ],

        // S03. Trois raisons de l'offrir. Nos phrases, sans guillemets.
        'benefits' => [
            'title' => 'Trois raisons de l’offrir',
            'discover' => [
                'title' => 'Découvrir ce que vous ne savez pas encore',
                'body' => 'Les souvenirs d’enfance, les rencontres, les petits détails : donnez-lui l’occasion de vous les raconter.',
            ],
            'voice' => [
                'title' => 'Retrouver sa façon de raconter',
                'body' => 'Le livre garde le récit. L’enregistrement permet d’en retrouver la voix, les silences et les rires.',
            ],
            'share' => [
                'title' => 'Partager bien plus qu’un cadeau',
                'body' => 'Une question chaque semaine ouvre une nouvelle conversation avec votre proche.',
            ],
            'quotes_title' => 'Ce qu’en disent les familles',
        ],

        // S04. Le concept, juste après le bandeau : l'œillet et le titre à
        // gauche, le paragraphe à droite.
        'what' => [
            'eyebrow' => 'Qu’est-ce que :brand',
            'title' => 'Un livre sur la vie de votre proche, racontée dans sa voix.',
            'body' => ':brand transforme une année de souvenirs parlés hebdomadaires en un livre relié d’histoires magnifiquement écrites. Chaque chapitre a un code QR qui lit l’enregistrement original, de sorte que vous pouvez lire à la fois l’histoire racontée par votre proche et l’entendre la raconter.',
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
            'eyebrow' => 'Comment ça marche',
            'title' => 'Histoires sur la page. Voix à seulement un scan.',
            'step' => 'Étape :number',
            'one' => [
                'title' => "Choisissez les questions.\nRendez-les personnelles.",
                // La carte posée sur la photo de la première étape.
                'card_label' => 'Question de la semaine',
                'card_question' => 'Quelle odeur vous ramène à votre enfance ?',
                'body' => 'Piochez parmi des centaines de suggestions conçues pour faire émerger des histoires que votre famille n’a jamais entendues. Ou importez vos photos pour découvrir l’histoire derrière chacune d’elles.',
            ],
            'two' => [
                'title' => "Une question arrive.\nIls n’ont plus qu’à parler.",
                'body' => 'Chaque semaine, :brand leur envoie une question par e-mail ou par SMS. Pas d’application. Pas de compte. Pas de mot de passe. Deux clics et l’enregistrement démarre, sur n’importe quel appareil.',
            ],
            'three' => [
                'title' => "Leurs mots deviennent\nune histoire écrite.",
                'body' => 'Speech-to-Story™ transforme chaque enregistrement en un chapitre soigné. Transcription mot à mot ou récit fluide. Entièrement modifiable.',
            ],
            'four' => [
                'title' => "Votre famille la découvre\ndès qu’elle est prête.",
                'body' => 'Chaque nouvelle histoire est partagée instantanément avec toute la famille. On la lit, on l’écoute, on y réagit. Pour beaucoup de familles, ça devient le meilleur moment de la semaine.',
            ],
            'more' => 'Voir le parcours en détail',
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
            'eyebrow' => 'Notre histoire',
            'title' => 'À l’origine de :brand',
            'p1' => 'Quand mes grands-parents sont partis, j’ai réalisé que je connaissais finalement peu de choses de leur vie. J’avais leurs photos, quelques souvenirs, mais tant de questions que je ne leur avais jamais posées.',
            'p2' => 'J’aurais aimé les entendre me raconter leur enfance, leurs rencontres, leurs plus beaux souvenirs. Et surtout, pouvoir réécouter leur voix aujourd’hui.',
            'p3' => 'C’est de ce regret qu’est né :brand : une façon simple de recueillir les histoires de ceux qu’on aime, sans avoir à écrire. Une question, un téléphone, et quelques minutes pour raconter.',
            'p4' => 'Parce qu’un jour, ces anecdotes, ces petits détails et cette voix que l’on connaît par cœur auront une valeur impossible à mesurer.',
            'p5' => ':brand existe pour les préserver, tant qu’il est encore temps de les raconter.',
            'name' => 'Nicolas Serra',
            'role' => 'Co-Founder & CTO, :brand',
            'photo_alt' => 'Nicolas Serra, fondateur, en extérieur dans un parc.',
        ],

        /*
         * S07. La fiche produit, dans la structure du leader : vignettes
         * verticales, grande image, carte d'écoute dessous, et à droite le
         * bandeau de notoriété, les trois bénéfices, ce que comprend l'achat,
         * le bouton et les trois réassurances.
         */
        'offer' => [
            'badge' => 'Meilleure vente',
            'rating' => '4,9',
            'stars' => 'Cinq étoiles sur cinq',
            'title' => 'Le livre de vie que l’on peut écouter',
            'lede' => 'Un an de questions qui transforment les souvenirs racontés par votre proche en un beau livre relié.',
            'gallery' => [
                'aria' => 'Vues du livre',
                'thumb' => 'Voir : :label',
                'next' => 'Vue suivante',
                'closed' => 'Le livre fermé',
                'phone' => 'Le livre et le téléphone',
                'held' => 'Le livre tenu en main',
                'photos' => 'Les photos de famille',
                'family' => 'Le livre offert en famille',
            ],
            'read' => [
                'title' => 'Lisez l’histoire.',
                'body' => 'Chaque chapitre est une histoire racontée par votre proche, transformée en un texte élégant par le Speech-to-Story™ de :brand.',
            ],
            'hear' => [
                'title' => 'Écoutez-la de vive voix.',
                'body' => 'Un QR code sur chaque page rejoue l’enregistrement d’origine. Sa voix. Pour toujours.',
            ],
            'bound' => [
                'title' => 'Fait pour durer.',
                'body' => 'Couverture rigide, tout en couleur, format 20 × 25 cm. Jusqu’à 380 pages. Impression professionnelle sur papier double épaisseur.',
            ],
            'includes' => [
                'questions' => '1 an de questions illimitées',
                'book' => '1 livre imprimé en couleur',
                'device' => 'Enregistrement depuis n’importe quel appareil',
                'qr' => 'Des QR codes qui lisent les enregistrements',
                'download' => 'Téléchargez et réécoutez les enregistrements à tout moment',
                'family' => 'Invitez la famille à participer en chemin',
            ],
            'buy' => 'Acheter • :price',
            'guarantees' => [
                'refund' => 'Garantie satisfait ou remboursé sous 30 jours',
                'yours' => 'Vos histoires vous appartiennent pour toujours',
                'download' => 'Téléchargeables à tout moment',
            ],
            'player' => [
                'label' => 'Écouter maintenant',
                'title' => 'L’odeur du pain de ma grand-mère',
                'attribution' => 'Exemple présenté sur :brand : le récit d’Odette',
                'transcript_show' => 'Lire la transcription',
                'transcript_hide' => 'Masquer la transcription',
                // Sans audio exploitable : l'extrait écrit, sans bouton de
                // lecture ni durée inventée.
                'read_instead' => 'Lire un exemple de récit',
            ],
        ],

        /*
         * S08. Ce que l'achat comprend, dans la structure du leader : titre
         * centré, chapeau, un intertitre sur filet, puis une grande carte —
         * trois colonnes à picto rond, un bandeau doré, le prix et le bouton
         * face à une image —, et une bande de confiance dessous.
         */
        'access' => [
            'title' => 'Les histoires de votre famille appartiennent à votre famille. Pour toujours.',
            'lede' => ':brand comprend une année complète de récits, un livre relié et un accès permanent aux souvenirs que vous créez. Même si vous ne renouvelez pas.',
            'includes_label' => 'Votre achat comprend',
            'forever' => [
                'title' => 'Un accès à vos histoires pour toujours',
                'body' => 'Tout ce que votre proche enregistre et crée pendant l’année vous appartient, même si vous ne renouvelez pas.',
            ],
            'download' => [
                'title' => 'Téléchargements en un clic',
                'body' => 'Enregistrez les fichiers d’origine sur votre appareil quand vous le souhaitez. Vos données ne sont jamais retenues en otage.',
            ],
            'renew' => [
                'title' => 'Renouvelez pour raconter de nouvelles histoires',
                'badge' => 'Facultatif',
                'body' => 'Achetez une année supplémentaire pour continuer à raconter de nouvelles histoires.',
                'link' => 'En savoir plus',
            ],
            'banner' => 'Vos histoires sont à vous pour toujours. Au bout d’un an, renouvelez seulement si vous souhaitez en enregistrer de nouvelles.',
            'buy' => 'Commencer son livre',
            'checks' => [
                'refund' => 'Garantie satisfait ou remboursé sous 30 jours',
                'shipping' => 'Livraison offerte sur toutes les commandes en France',
                'book' => 'Comprend un livre relié imprimé en couleur',
            ],
            'photo_alt' => 'Un livre ouvert sur une double page, et un téléphone qui rejoue l’enregistrement du chapitre.',
        ],

        // S09. La transition avant les preuves. Sans avis, elle annonce une
        // démonstration et ne parle pas de clients.
        'proof_intro' => [
            'title' => 'Découvrez un exemple avant de commencer.',
            'body' => 'Écoutez un souvenir, lisez sa mise au propre et voyez comment il peut trouver sa place dans le livre.',
            'reviews_title' => 'Ils racontent leur expérience avec :brand.',
            'reviews_body' => 'Découvrez les retours des personnes qui ont offert le livre ou commencé à raconter leur histoire.',
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
            'title' => 'Ce qu’en disent les familles',
            'stars' => 'Cinq étoiles sur cinq',
            'previous' => 'Avis précédents',
            'next' => 'Avis suivants',
            'page' => 'Page :number',
            'items' => [
                'one' => [
                    'title' => 'Tellement simple pour mon père',
                    'quote' => 'Je savais qu’il n’écrirait jamais ses souvenirs dans un carnet. Par téléphone, en revanche, il s’est pris au jeu dès la première question.',
                    'author' => 'Camille, offert à son père',
                ],
                'two' => [
                    'title' => 'Entendre sa voix dans le livre',
                    'quote' => 'Le livre est déjà précieux. Mais pouvoir scanner une page et entendre ma mère raconter l’histoire elle-même… ça change tout.',
                    'author' => 'Thomas, offert à sa mère',
                ],
                'three' => [
                    'title' => 'Mon rendez-vous préféré de la semaine',
                    'quote' => 'Chaque nouvelle réponse est devenue un petit rendez-vous. J’attends de découvrir l’histoire qu’elle va nous raconter cette fois-ci.',
                    'author' => 'Julie, offert à sa grand-mère',
                ],
                'four' => [
                    'title' => 'Rien à écrire',
                    'quote' => 'J’avais peur de ne pas savoir quoi raconter. Finalement, il suffit de répondre comme si on discutait autour d’un café.',
                    'author' => 'Michel, raconte son histoire',
                ],
                'five' => [
                    'title' => 'Des histoires que je n’avais jamais entendues',
                    'quote' => 'Je connais mon père depuis toujours et pourtant, j’ai découvert des choses sur sa jeunesse qu’il ne nous avait jamais racontées.',
                    'author' => 'Élodie, offert à son père',
                ],
                'six' => [
                    'title' => 'Les petits-enfants en redemandent',
                    'quote' => 'Maintenant, mes enfants me demandent de leur faire écouter les histoires de leur grand-père. Ils le découvrent autrement.',
                    'author' => 'Sophie, offert à son père',
                ],
                'seven' => [
                    'title' => 'Il disait qu’il n’avait rien à raconter',
                    'quote' => 'Au début, il répétait que sa vie n’avait rien d’intéressant. Quelques semaines plus tard, impossible de l’arrêter.',
                    'author' => 'Antoine, offert à son grand-père',
                ],
                'eight' => [
                    'title' => 'Un livre qui ressemble vraiment à maman',
                    'quote' => 'Ce que j’aime le plus, c’est qu’on retrouve ses expressions, ses anecdotes, sa façon de raconter. Ce n’est pas juste son histoire : c’est elle.',
                    'author' => 'Claire, offert à sa mère',
                ],
                'nine' => [
                    'title' => 'La voix vaut tout',
                    'quote' => 'Je pensais surtout offrir un beau livre à la famille. Je n’avais pas réalisé à quel point le fait de conserver sa voix serait précieux.',
                    'author' => 'Pauline, offert à son père',
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
            'title' => 'Garantie satisfait ou remboursé sous 30 jours. Si le premier enregistrement ne vous émeut pas, nous vous remboursons.',
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
            'title' => 'Pensé pour les grands-parents. Approuvé par la famille.',
            'lede' => 'Pour celles et ceux qui racontent, de 9 à 99 ans.',
            'marks' => [
                'no_writing' => 'Rien à écrire.',
                'no_app' => 'Rien à installer.',
                'no_password' => 'Aucun mot de passe.',
            ],
            'cta' => 'Essayer : ça prend 60 secondes',
        ],

        // S13. Voir l'expérience. Sans vidéo de client : une capture de
        // l'interface, et surtout aucun faux bouton de lecture.
        'experience' => [
            'title' => 'Un lien, une question, et votre proche peut raconter.',
            'body' => 'Découvrez le parcours de :brand, de l’enregistrement à la relecture du récit.',
            'cta' => 'Découvrir le parcours',
            'caption' => 'Aperçu de l’interface :brand',
            'videos_title' => 'Leur expérience, racontée avec leurs mots.',
        ],

        // S14. À l'intérieur du livre : le chapitre et son QR code.
        'book' => [
            'eyebrow' => 'À l’intérieur du livre',
            'title' => 'Lisez son histoire. Puis écoutez-la avec sa voix.',
            'body' => 'Une photo, un récit, un QR code : chaque chapitre réunit le souvenir et la possibilité de réécouter la personne qui l’a raconté.',
            'cta' => 'Voir un exemple de chapitre',
            'chapter_number' => 'Chapitre 3',
            'chapter_title' => 'L’odeur du pain de ma grand-mère',
            // Aucune destination vérifiée n'est encore imprimée : l'emplacement
            // se déclare pour ce qu'il est, et le bouton d'écoute prend le
            // relais pour qui n'a pas deux téléphones.
            'qr_placeholder' => 'Emplacement du QR code',
            'qr_body' => 'Sur le livre imprimé, ce carré ouvre l’enregistrement du chapitre.',
            'open_audio' => 'Ouvrir l’exemple audio',
            'illustrative' => 'Aperçu illustratif de mise en page',
        ],

        // S15. De la parole au texte. Les deux extraits sont ceux du projet,
        // repris tels quels depuis `landing.proof`.
        'transcript' => [
            'title' => 'Un texte plus facile à lire, sans inventer son histoire.',
            'body' => ':brand retire les hésitations et ajoute la ponctuation. Les mots de votre proche restent les siens. L’enregistrement d’origine et le mot à mot sont conservés, et la personne qui raconte peut relire et corriger le texte avant de le partager.',
            'verbatim' => 'Mot à mot',
            'fluide' => 'Texte mis au propre',
            'consent' => 'Rien n’est partagé avec les proches sans son accord.',
            'preview' => 'Aperçu : ces choix ne déclenchent rien ici.',
        ],

        // S16. Aider à choisir. Un mini-guide dans la page, sans tableau
        // comparatif ni croix rouge sur le voisin.
        'choosing' => [
            'title' => 'Quel support pour les souvenirs que vous voulez garder ?',
            'lede' => 'Écrire, enregistrer, rassembler des photos : choisissez d’abord ce que votre famille souhaite pouvoir retrouver.',
            'written' => [
                'title' => 'Pour les mots écrits',
                'body' => 'Un cahier de souvenirs laisse une place à l’écriture et à la façon dont la personne souhaite raconter.',
            ],
            'recorded' => [
                'title' => 'Pour les moments enregistrés',
                'body' => 'Des fichiers audio ou vidéo permettent de réécouter ou revoir les échanges que vous avez enregistrés.',
            ],
            'both' => [
                'title' => 'Pour relier le récit et la voix',
                'body' => ':brand accompagne les réponses orales, les met au propre et les réunit dans un livre relié avec accès aux enregistrements.',
            ],
            'cta' => 'Voir ce que comprend :brand',
        ],

        // S17. Les histoires qui pourraient remplir son livre. Une projection,
        // annoncée comme telle : ce ne sont pas des familles clientes.
        'possibilities' => [
            'eyebrow' => 'Des souvenirs à faire revenir',
            'title' => 'Son livre commence par les histoires qu’il ou elle a envie de raconter.',
            'lede' => 'Voici quelques idées de sujets pour ouvrir la conversation. Ce sont des exemples, pas des témoignages de familles clientes.',
            'places' => [
                'title' => 'Les lieux de son enfance',
                'body' => 'La maison où l’on grandit, un trajet d’école, les odeurs d’une cuisine : par quel souvenir commencerait votre proche ?',
            ],
            'people' => [
                'title' => 'Les rencontres qui comptent',
                'body' => 'Une amitié, un amour, une personne qui a changé le cours de sa vie : quelles rencontres aimerait-il ou elle raconter ?',
            ],
            'legacy' => [
                'title' => 'Ce qu’il ou elle souhaite transmettre',
                'body' => 'Une tradition, un conseil, une histoire souvent répétée : qu’aimeriez-vous retrouver dans ce livre ?',
            ],
            'stories_title' => 'Trois livres, trois familles',
        ],

        // S18. Le cadeau programmé, en fin de page.
        'gift' => [
            'title' => 'Le cadeau peut commencer le jour que vous choisissez.',
            'body' => 'Programmez l’envoi de votre message et de la première question à votre proche. Vous pouvez aussi imprimer une carte à glisser dans une enveloppe.',
            'notice' => 'Vous programmez le début du cadeau, pas la livraison immédiate d’un livre déjà écrit.',
            'card_name' => 'Odette',
            'card_preview' => 'Aperçu de la carte à imprimer',
        ],

        // S19. Deux destinataires du même cadeau, pas deux produits.
        'recipients' => [
            'title' => 'Deux façons de penser au même cadeau',
            'note' => 'Les deux mènent à la même offre :brand.',
            'parent' => [
                'title' => 'Pour votre mère ou votre père',
                'body' => 'Offrez une occasion de raconter les souvenirs que vous aimeriez mieux connaître.',
                'cta' => 'Offrir à un parent',
            ],
            'grandparent' => [
                'title' => 'Pour votre grand-mère ou votre grand-père',
                'body' => 'Rassemblez les histoires que vous aimeriez pouvoir lire et écouter en famille.',
                'cta' => 'Offrir à un grand-parent',
            ],
        ],

        // S20. Les questions fréquentes, dans l'ordre où on se les pose avant
        // d'offrir. Les engagements détaillés vivent ici.
        'faq' => [
            'title' => 'Les questions que vous vous posez avant de l’offrir.',
            'included' => [
                'q' => 'Qu’est-ce qui est compris dans les :price ?',
                'a' => 'L’achat comprend une année de questions, à raison d’une par semaine, la mise au propre des histoires avec le mot à mot conservé, un livre relié en couleur avec un QR code par chapitre, et l’accès aux enregistrements. Les proches autorisés peuvent découvrir les récits partagés. Les enregistrements et les textes sont téléchargeables.',
            ],
            'subscription' => [
                'q' => 'Est-ce un abonnement ?',
                'a' => 'Non. Vous réglez une fois l’année de questions et le livre. Il n’y a pas de renouvellement automatique. Les histoires recueillies restent accessibles après l’année et vous pouvez télécharger vos fichiers.',
            ],
            'no_app' => [
                'q' => 'Mon proche doit-il écrire ou installer une application ?',
                'a' => 'Non. Votre proche reçoit une question par SMS ou par courriel, ouvre le lien et répond en parlant. Le parcours ne nécessite pas d’application à installer ni de mot de passe à retenir.',
            ],
            'questions' => [
                'q' => 'Peut-on choisir les questions ?',
                'a' => 'Oui. :brand propose soixante questions pour faire revenir les souvenirs. Vous pouvez choisir celles qui conviennent à votre proche ou laisser :brand guider les échanges.',
            ],
            'edit' => [
                'q' => 'Peut-on corriger le texte ?',
                'a' => 'Oui. La personne qui raconte relit le texte mis au propre et peut le corriger. Le mot à mot reste accessible, et l’enregistrement d’origine est conservé.',
            ],
            'privacy' => [
                'q' => 'Qui peut lire et écouter les histoires ?',
                'a' => 'Seulement les proches autorisés par la personne qui raconte. Son accord est explicite, histoire par histoire. Elle peut aussi garder un récit pour elle, retirer son partage, le masquer ou le supprimer.',
            ],
            'after_year' => [
                'q' => 'Que se passe-t-il après l’année ?',
                'a' => 'Les histoires déjà recueillies restent accessibles sans paiement supplémentaire. Les enregistrements et les textes peuvent être téléchargés pour être conservés sur vos propres appareils. Les QR codes restent utilisables tant que le service existe.',
            ],
            'no_smartphone' => [
                'q' => 'Et si mon proche n’a pas de smartphone ?',
                'a' => 'Une option par téléphone est proposée en nombre limité. Contactez-nous pour vérifier les disponibilités et les modalités avant de choisir cette solution.',
                'link' => 'Nous écrire',
            ],
            'refuses' => [
                'q' => 'Et si mon proche ne souhaite pas participer ?',
                'a' => 'Sa décision est respectée. Contactez-nous : nous vous accompagnons dans le cadre de la garantie satisfait ou remboursé de trente jours.',
            ],
            'date' => [
                'q' => 'Puis-je choisir la date du cadeau ?',
                'a' => 'Oui. Vous pouvez programmer l’envoi de votre message et de la première question. Une carte à imprimer permet aussi de présenter le cadeau dans une enveloppe.',
            ],
            'protection' => [
                'q' => 'Comment protégez-vous les récits et la voix ?',
                'a' => 'Les enregistrements d’origine sont conservés et ne sont jamais remplacés par une voix fabriquée. :brand ne clone pas les voix et n’utilise pas les contenus de votre famille pour entraîner un modèle. Vos enregistrements et vos textes sont hébergés dans l’Union européenne. Le partage nécessite un accord explicite, et la personne qui raconte garde la possibilité de masquer, retirer ou supprimer une histoire.',
                'privacy_link' => 'Politique de confidentialité',
                'consents_link' => 'Vos accords',
            ],
            'shutdown' => [
                'q' => 'Que se passe-t-il si :brand cesse son activité ?',
                'a' => 'Nous vous prévenons au moins trois mois à l’avance, nous vous fournissons l’intégralité de vos enregistrements et de vos textes dans un format lisible sans nous, et nous vous remboursons ce qui n’a pas été livré. Nous ne promettons pas une conservation à vie : nous promettons de ne jamais vous laisser sans vos fichiers.',
            ],
            'guarantee' => [
                'q' => 'Quelle est la garantie ?',
                'a' => 'Vous bénéficiez de trente jours satisfait ou remboursé. Les modalités sont détaillées dans nos conditions générales de vente.',
                'link' => 'Conditions générales de vente',
            ],
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
        // Le même service en bandeau de bas de page, sur l'accueil et sur
        // « Comment ça marche » (T-213).
        'band_title' => ':amount offerts pour commencer',
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
     * Le pied de page (T-213) : la marque et sa phrase, les pages du site,
     * les informations légales, le contact, puis l'année et l'hébergement.
     * Les libellés des pages et des textes légaux sont lus ailleurs dans ce
     * fichier ; ici, seulement ce qui n'existe pas encore.
     */
    'footer' => [
        'discover' => 'Découvrir',
        'home' => 'Accueil',
        'try' => 'Essayer en 60 secondes',
        'information' => 'Informations',
        'contact' => 'Nous joindre',
        'copyright' => '© :year :brand',
        // Un fait, pas une promesse : la région du serveur et la juridiction
        // du stockage (T-02, T-04). La phrase complète est dans les engagements.
        'hosting' => 'Hébergé dans l’Union européenne',
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
