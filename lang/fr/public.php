<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pages publiques
|--------------------------------------------------------------------------
|
| L'ordre des sections de la page d'accueil vient du dossier 01 §4 et n'est
| pas négociable : la promesse, comment ça marche, l'essai, le livre, les
| engagements, le prix, les questions. On explique avant de demander — la même
| règle que sur la page d'enregistrement, où l'on explique avant de demander
| le micro.
|
| Les engagements (R-10) sont en formulation **canonique** : ce sont des
| phrases qu'on peut nous opposer, et elles doivent être identiques partout.
|
*/

return [

    'vcard' => [
        'note' => 'Vos questions de la semaine arrivent de ce contact. Nous ne vous demanderons jamais de mot de passe ni de paiement par SMS.',
    ],

    'landing' => [
        'promise' => 'Le livre de souvenirs de vos parents qui va réellement au bout.',
        'subtitle' => 'Sans application, et sans leur demander d’écrire.',
        'cta' => 'J’offre ce livre',
        'cta_try' => 'Essayez en 60 secondes',
        'cta_how' => 'Comment ça marche',

        /*
         * Le héros : la question de la semaine comme objet, parce que c'est le
         * rituel qu'on vend — pas le livre. Les quatre points sont le fond du
         * produit, dans les mots de l'acheteur.
         */
        'hero' => [
            'lede' => 'Chaque semaine, une question. Elle répond en parlant, depuis son téléphone. À la fin de l’année, un livre imprimé de ses histoires — et sa voix à chaque page.',
            'note' => 'Un seul paiement, pas d’abonnement. Ses souvenirs restent privés.',
            'checks' => [
                'no_app' => 'Aucune application, aucun mot de passe : un lien, elle parle.',
                'kept_words' => 'Ses mots sont mis au propre, jamais réécrits — le mot à mot est conservé à côté.',
                'she_decides' => 'C’est elle qui décide de ce que la famille entend.',
                'book' => 'Un livre relié, avec un code à scanner pour l’entendre raconter.',
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

        'how' => [
            'title' => 'Comment ça marche',
            'headline' => 'Une question par semaine. Le reste, on s’en occupe.',
            'lede' => 'Rien à installer, rien à écrire. Une année de questions, à son rythme, et un livre au bout.',
            'one' => [
                'title' => 'Votre proche reçoit un lien',
                'body' => 'Une question par semaine, par SMS ou par courriel. Rien à installer, aucun mot de passe.',
                'alt' => 'Une main tient deux photographies anciennes de famille.',
            ],
            'two' => [
                'title' => 'Il ou elle parle',
                'body' => 'Une réponse orale, depuis son téléphone, quand il ou elle veut. Deux minutes suffisent.',
                'alt' => 'Une femme âgée sourit, en cardigan gris.',
            ],
            'three' => [
                'title' => 'Le texte est relu et validé',
                'body' => 'Nous mettons le récit au propre. Votre proche le relit, le corrige, et décide seul·e de le partager.',
                'alt' => 'Un livre ouvert, photographié de près.',
            ],
            'four' => [
                'title' => 'La famille écoute',
                'body' => 'Les proches autorisés écoutent la voix et lisent le texte. À la fin, tout devient un livre.',
                'alt' => 'Une famille réunie autour d’une table, sur une photographie ancienne.',
            ],
        ],

        'try' => [
            'title' => 'Essayez en 60 secondes',
            'body' => 'Enregistrez-vous, réécoutez-vous. Rien n’est envoyé : tout reste sur votre appareil et disparaît quand vous fermez la page.',
        ],

        'book' => [
            'title' => 'Le livre',
            'headline' => 'Sur chaque page, la photo, l’histoire — et sa voix.',
            'body' => 'Un livre imprimé, avec un QR par chapitre qui ramène à la voix. Le format s’adapte à la matière recueillie : un livre, un livret, ou un chapitre fondateur.',
            'points' => [
                'photos' => 'Un livre relié, avec les photos que la famille a ajoutées.',
                'proof' => 'Vous relisez et validez chaque page avant l’impression.',
                'lasting' => 'Les enregistrements restent écoutables des années après, code ou pas.',
            ],
            'photo_alt' => 'Un homme âgé regarde deux photographies anciennes qu’il tient dans ses mains.',
            'qr' => 'Les codes de votre livre mènent aux enregistrements aussi longtemps que le service existe. Si nous devions cesser notre activité, nous vous préviendrions et vous fournirions vos fichiers.',
        ],

        /*
         * R-10, en formulation canonique. Ce sont des phrases qu'on peut nous
         * opposer : elles doivent être identiques ici, dans les CGV et dans
         * les courriels.
         */
        'commitments' => [
            'title' => 'Nos engagements',
            'headline' => 'Ses souvenirs lui appartiennent.',
            'lede' => 'Des phrases qu’on peut nous opposer : elles sont les mêmes ici, dans les conditions de vente et dans nos courriels.',
            'validation' => 'La validation est explicite, jamais tacite : rien n’est visible des proches sans l’accord de la personne qui a raconté.',
            'no_cloning' => 'Pas de clonage vocal : nous n’imitons jamais une voix, et nous n’en fabriquons pas.',
            'ai_arranges' => 'L’IA range, elle n’invente pas : elle enlève les hésitations et ajoute la ponctuation. Elle n’ajoute aucun fait.',
            'source_audio' => 'L’enregistrement d’origine est conservé et n’est jamais remplacé. Le mot à mot reste accessible à côté du texte mis au propre.',
            'no_training' => 'Aucun contenu de votre famille ne sert à entraîner un modèle.',
            'eu_hosting' => 'Vos enregistrements et vos textes sont hébergés dans l’Union européenne.',
            'withdrawal' => 'La personne qui raconte peut masquer, retirer ou supprimer une histoire à tout moment, sans se justifier.',
        ],

        /*
         * La preuve à côté de la promesse : le mot à mot d'un essai du corpus
         * (docs/corpus/essai-01-pain.txt) et son rendu, tels qu'ils sortent.
         */
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

        'price' => [
            'title' => 'Le prix',
            'headline' => 'Un paiement. Une année. Un livre.',
            'lede' => 'Pas d’abonnement à surveiller, pas de renouvellement discret. Vous offrez l’année, elle raconte à son rythme, et le livre arrive.',
            'per' => 'une année de questions, et le livre imprimé',
            'includes' => [
                'questions' => 'Une question par semaine, pendant un an',
                'text' => 'Ses histoires mises au propre, mot à mot conservé',
                'family' => 'L’écoute pour toute la famille, et les réponses d’un mot',
                'book' => 'Un livre relié, avec un code à scanner par chapitre',
                'download' => 'Tous les enregistrements téléchargeables, à jamais',
            ],
            'cta_start' => 'Je commence son livre',
            'reassurance' => 'Paiement sécurisé · Remboursable',
            'pilot' => 'Offre pilote',
            'pilot_body' => 'Douze semaines d’accompagnement, un livrable réduit, et un statut expérimental assumé. Remboursable.',
            'prevente' => 'Prévente',
            'prevente_body' => 'Vous réservez maintenant, le service démarre à l’ouverture. Remboursable jusqu’au démarrage.',
            'phone_option' => 'Enregistrement par téléphone',
            'phone_option_body' => 'Un membre de notre équipe appelle votre proche chaque semaine et enregistre l’histoire. Rien à manipuler de son côté.',
        ],

        'faq' => [
            'title' => 'Questions fréquentes',
            'no_smartphone' => [
                'q' => 'Et si mon proche n’a pas de smartphone ?',
                'a' => 'L’option « enregistrement par téléphone » existe pour ça : nous appelons, et nous enregistrons la conversation. Elle est limitée à quelques familles au pilote.',
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
        'draft_banner' => 'Ce texte n’est pas encore validé par notre conseil juridique. Il est publié pour transparence pendant la phase pilote.',
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
        'next' => 'Continuer',
        'back' => 'Revenir',

        'steps' => [
            'for' => 'Pour qui ?',
            'narrator' => 'Le narrateur',
            'gift' => 'Le cadeau',
            'account' => 'Vos coordonnées',
            'options' => 'Options et accords',
            'summary' => 'Récapitulatif',
        ],

        'for' => [
            'relative' => 'Un proche',
            'self' => 'Vous-même',
            'self_notice' => 'Au pilote, nous accompagnons un proche. Voulez-vous continuer pour un proche ?',
            'self_continue' => 'Continuer pour un proche',
        ],

        'narrator' => [
            'intro' => 'La personne qui racontera. Nous ne lui écrirons qu’une fois, pour l’inviter — et nous n’enverrons aucune question avant qu’elle ait accepté.',
            'first_name' => 'Son prénom',
            'last_name' => 'Son nom (facultatif)',
            'relationship' => 'Votre lien avec elle',
            'email' => 'Son courriel',
            'phone' => 'Son numéro de téléphone',
            'contact_hint' => 'Un courriel ou un numéro suffit. Au format international : +33 6 12 34 56 78.',
            'channel' => 'Par quel moyen préfère-t-elle être jointe ?',
            'address_form' => 'Faut-il lui dire « vous » ou « tu » ?',
        ],

        'gift' => [
            'intro' => 'L’invitation partira à la date que vous choisissez, à neuf heures du matin.',
            'send_at' => 'Quand faut-il envoyer l’invitation ?',
            'message' => 'Votre message personnel',
            'message_hint' => 'C’est ce mot qui décide : un message de vous vaut dix des nôtres.',
            'message_default' => 'J’aimerais garder tes histoires. Il suffit de parler, une question par semaine, quand tu veux. Si ça ne te dit pas, dis-le-moi simplement.',
            'variant' => 'Comment présenter le cadeau ?',
            'variant_ecard' => 'Une carte à l’écran',
            'variant_printed_card' => 'Une carte à imprimer',
            'variant_audio_message' => 'Un message vocal de votre part',
        ],

        'account' => [
            'intro' => 'Pour suivre le projet et retrouver votre commande.',
            'signed_in' => 'Vous êtes connecté·e en tant que :email.',
            'register' => 'Créer mon compte',
            'login' => 'J’ai déjà un compte',
        ],

        'options' => [
            'intro' => 'Deux options, et trois accords. Aucun des trois n’est groupé avec un autre.',
            'extra_copies' => 'Exemplaires supplémentaires du livre',
            'extra_copies_hint' => ':amount par exemplaire.',
            'phone_option_remaining' => 'Il reste :remaining places sur :cap.',
            'phone_option_closed' => 'L’option téléphone est complète pour le pilote.',
        ],

        'summary' => [
            'title' => 'Récapitulatif',
            'narrator' => 'Le narrateur',
            'gift' => 'L’invitation',
            'total' => 'Total à payer',
            'notice' => 'Le paiement se fait sur la page sécurisée de notre prestataire. Nous ne voyons jamais votre numéro de carte.',
        ],

        'terms' => 'J’accepte les conditions générales de vente et la politique de confidentialité.',
        'early_start' => 'Je demande que le service numérique démarre immédiatement, sans attendre la fin du délai de rétractation de quatorze jours.',
        'early_start_notice' => 'Dans ce cas, si vous vous rétractez, nous pourrons retenir une part correspondant à ce qui aura déjà été fourni.',
        'marketing' => 'Je souhaite recevoir des nouvelles.',
        'phone_option_label' => 'Un membre de notre équipe appelle :first_name chaque semaine au créneau choisi et enregistre l’histoire. Offre limitée aux :cap premières familles du pilote.',
        'pay' => 'Payer',

        'thanks' => [
            'title' => 'Merci',
            'body' => 'Votre paiement est passé. Vous recevez un courriel avec le détail, et l’invitation partira à la date que vous avez choisie.',
            'orders' => 'Voir ma commande',
        ],
    ],
];
