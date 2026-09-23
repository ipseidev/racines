<?php

declare(strict_types=1);

/*
 * Le corpus de questions, source unique de la table `questions`.
 *
 * Ce fichier s'applique avec `php artisan corpus:sync` (à blanc par défaut,
 * `--apply` pour écrire). Le seeder le lit aussi, pour les tests et le dev.
 *
 * Une entrée :
 *
 * - `slug` : stable, c'est l'identité de la question. Ne jamais renommer ; pour
 *   retirer une question, `'active' => false` — ne pas supprimer la ligne.
 * - `theme`, `difficulty` (1 très facile … 5 intime), `order` (ordre d'envoi
 *   par défaut).
 * - `tone` : `light` (on sourit), `tender` (émotion douce), `grave` (douleur,
 *   bilan).
 * - `conditions` : ce que la question suppose — `partner`, `children`,
 *   `grandchildren`, `career`, `knew_parents`. Fausse, la question ne part pas.
 * - `sensitive` : les sujets qu'elle touche — `bereavement`, `child_loss`,
 *   `separation`, `war`, `illness`, `religion`, `end_of_life`.
 * - `vous` et `tu` : les deux textes, écrits à la main. Le genre s'accorde par
 *   marqueur : `né{|e}` (le féminin prolonge le masculin), ou
 *   `{fier|fière|fier·ère}` (masculin, féminin, forme neutre pour un genre
 *   inconnu). Jamais de point médian en dur.
 *
 * Les soixante premières questions sont celles du corpus v1, au mot près :
 * au vouvoiement et au genre inconnu, elles rendent le texte d'origine.
 * Audit et étiquetage : docs/corpus/01_audit_corpus_v1.md.
 */

return [
    // Enfance
    ['slug' => 'naissance-recit', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 10, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Où êtes-vous né{|e}, et que vous a-t-on raconté sur le jour de votre naissance ?',
        'tu' => 'Où es-tu né{|e}, et que t\'a-t-on raconté sur le jour de ta naissance ?'],
    ['slug' => 'premier-souvenir', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 20, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel est votre tout premier souvenir ?',
        'tu' => 'Quel est ton tout premier souvenir ?'],
    ['slug' => 'maison-enfance', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 30, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'À quoi ressemblait la maison de votre enfance ? Décrivez une pièce que vous aimiez.',
        'tu' => 'À quoi ressemblait la maison de ton enfance ? Décris une pièce que tu aimais.'],
    ['slug' => 'betise-enfant', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 40, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel jeu ou quelle bêtise d\'enfant vous fait encore sourire ?',
        'tu' => 'Quel jeu ou quelle bêtise d\'enfant te fait encore sourire ?'],
    ['slug' => 'apprendre-velo-nager-lire', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 50, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Qui vous a appris à faire du vélo, à nager ou à lire ? Racontez ce moment.',
        'tu' => 'Qui t\'a appris à faire du vélo, à nager ou à lire ? Raconte ce moment.'],
    ['slug' => 'odeur-enfance', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 60, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle était votre odeur préférée quand vous étiez enfant, et à quoi vous ramène-t-elle ?',
        'tu' => 'Quelle était ton odeur préférée quand tu étais enfant, et à quoi te ramène-t-elle ?'],
    ['slug' => 'plat-enfance', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 70, 'tone' => 'tender', 'conditions' => [], 'sensitive' => ['end_of_life'],
        'vous' => 'Quel plat de votre enfance aimeriez-vous goûter une dernière fois ?',
        'tu' => 'Quel plat de ton enfance aimerais-tu goûter une dernière fois ?'],
    ['slug' => 'dimanche-dix-ans', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 80, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Comment se passait un dimanche ordinaire chez vous quand vous aviez dix ans ?',
        'tu' => 'Comment se passait un dimanche ordinaire chez toi quand tu avais dix ans ?'],

    // Origines familiales
    ['slug' => 'grands-parents', 'theme' => 'family_origins', 'difficulty' => 2, 'order' => 90, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Que savez-vous de vos grands-parents ? Racontez-les comme si on ne les avait jamais rencontrés.',
        'tu' => 'Que sais-tu de tes grands-parents ? Raconte-les comme si on ne les avait jamais rencontrés.'],
    ['slug' => 'nom-de-famille', 'theme' => 'family_origins', 'difficulty' => 2, 'order' => 100, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'D\'où vient votre nom de famille, et quelle histoire y est attachée ?',
        'tu' => 'D\'où vient ton nom de famille, et quelle histoire y est attachée ?'],
    ['slug' => 'expression-famille', 'theme' => 'family_origins', 'difficulty' => 2, 'order' => 110, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle expression ou quel mot venait de votre famille et n\'existait nulle part ailleurs ?',
        'tu' => 'Quelle expression ou quel mot venait de ta famille et n\'existait nulle part ailleurs ?'],
    ['slug' => 'conteur-famille', 'theme' => 'family_origins', 'difficulty' => 2, 'order' => 120, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Qui, dans votre famille, racontait le mieux les histoires ? Laquelle vous revient ?',
        'tu' => 'Qui, dans ta famille, racontait le mieux les histoires ? Laquelle te revient ?'],
    ['slug' => 'objet-transmis', 'theme' => 'family_origins', 'difficulty' => 2, 'order' => 130, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Y a-t-il un objet transmis dans la famille ? Racontez son histoire.',
        'tu' => 'Y a-t-il un objet transmis dans la famille ? Raconte son histoire.'],
    ['slug' => 'tradition-gardee', 'theme' => 'family_origins', 'difficulty' => 2, 'order' => 140, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle tradition de votre enfance avez-vous gardée, et laquelle avez-vous abandonnée ?',
        'tu' => 'Quelle tradition de ton enfance as-tu gardée, et laquelle as-tu abandonnée ?'],

    // Jeunesse
    ['slug' => 'adulte-qui-a-compte', 'theme' => 'youth', 'difficulty' => 2, 'order' => 150, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel professeur ou adulte, en dehors de vos parents, a compté pour vous ?',
        'tu' => 'Quel professeur ou adulte, en dehors de tes parents, a compté pour toi ?'],
    ['slug' => 'musique-quinze-ans', 'theme' => 'youth', 'difficulty' => 2, 'order' => 160, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle musique écoutiez-vous à quinze ans, et où l\'écoutiez-vous ?',
        'tu' => 'Quelle musique écoutais-tu à quinze ans, et où l\'écoutais-tu ?'],
    ['slug' => 'premiere-liberte', 'theme' => 'youth', 'difficulty' => 2, 'order' => 170, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Racontez votre première grande liberté : un voyage, une sortie, un départ.',
        'tu' => 'Raconte ta première grande liberté : un voyage, une sortie, un départ.'],
    ['slug' => 'reve-metier', 'theme' => 'youth', 'difficulty' => 2, 'order' => 180, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel était votre rêve de métier à dix-huit ans ?',
        'tu' => 'Quel était ton rêve de métier à dix-huit ans ?'],
    ['slug' => 'mode-jeunesse', 'theme' => 'youth', 'difficulty' => 2, 'order' => 190, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle mode ou quelle habitude de votre jeunesse ferait rire les jeunes d\'aujourd\'hui ?',
        'tu' => 'Quelle mode ou quelle habitude de ta jeunesse ferait rire les jeunes d\'aujourd\'hui ?'],
    ['slug' => 'ami-perdu-de-vue', 'theme' => 'youth', 'difficulty' => 2, 'order' => 200, 'tone' => 'tender', 'conditions' => [], 'sensitive' => ['bereavement'],
        'vous' => 'Racontez un ami d\'enfance ou de jeunesse que vous avez perdu de vue. Que faisiez-vous ensemble ?',
        // « Vous » reste : c'est le pluriel, l'ami et elle.
        'tu' => 'Raconte un ami d\'enfance ou de jeunesse que tu as perdu de vue. Que faisiez-vous ensemble ?'],
    ['slug' => 'evenement-du-monde', 'theme' => 'youth', 'difficulty' => 2, 'order' => 210, 'tone' => 'tender', 'conditions' => [], 'sensitive' => ['war'],
        'vous' => 'Quel événement du monde vous a marqué{|e} quand vous étiez jeune, et où étiez-vous ce jour-là ?',
        'tu' => 'Quel événement du monde t\'a marqué{|e} quand tu étais jeune, et où étais-tu ce jour-là ?'],

    // Métier
    ['slug' => 'premier-jour-travail', 'theme' => 'work', 'difficulty' => 2, 'order' => 220, 'tone' => 'light', 'conditions' => ['career'], 'sensitive' => [],
        'vous' => 'Racontez votre premier jour de travail.',
        'tu' => 'Raconte ton premier jour de travail.'],
    ['slug' => 'metier-fierte', 'theme' => 'work', 'difficulty' => 2, 'order' => 230, 'tone' => 'tender', 'conditions' => ['career'], 'sensitive' => [],
        'vous' => 'Quel a été le métier dont vous êtes le plus {fier|fière|fier·ère}, et pourquoi ?',
        'tu' => 'Quel a été le métier dont tu es le plus {fier|fière|fier·ère}, et pourquoi ?'],
    ['slug' => 'travail-et-les-gens', 'theme' => 'work', 'difficulty' => 2, 'order' => 240, 'tone' => 'tender', 'conditions' => ['career'], 'sensitive' => [],
        'vous' => 'Qu\'est-ce que votre travail vous a appris sur les gens ?',
        'tu' => 'Qu\'est-ce que ton travail t\'a appris sur les gens ?'],
    ['slug' => 'journee-de-travail', 'theme' => 'work', 'difficulty' => 2, 'order' => 250, 'tone' => 'light', 'conditions' => ['career'], 'sensitive' => [],
        'vous' => 'Racontez une journée de travail ordinaire à l\'époque où vous étiez le plus occupé{|e}.',
        'tu' => 'Raconte une journée de travail ordinaire à l\'époque où tu étais le plus occupé{|e}.'],
    ['slug' => 'choix-professionnel', 'theme' => 'work', 'difficulty' => 3, 'order' => 260, 'tone' => 'tender', 'conditions' => ['career'], 'sensitive' => [],
        'vous' => 'Y a-t-il un choix professionnel que vous referiez autrement ?',
        'tu' => 'Y a-t-il un choix professionnel que tu referais autrement ?'],
    ['slug' => 'qui-a-donne-sa-chance', 'theme' => 'work', 'difficulty' => 2, 'order' => 270, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Qui vous a donné votre chance, et comment ?',
        'tu' => 'Qui t\'a donné ta chance, et comment ?'],

    // Amour et famille
    ['slug' => 'rencontre-conjoint', 'theme' => 'love', 'difficulty' => 3, 'order' => 280, 'tone' => 'tender', 'conditions' => ['partner'], 'sensitive' => ['bereavement', 'separation'],
        'vous' => 'Comment avez-vous rencontré la personne qui a partagé votre vie ?',
        'tu' => 'Comment as-tu rencontré la personne qui a partagé ta vie ?'],
    ['slug' => 'mariage-ou-vie-commune', 'theme' => 'love', 'difficulty' => 3, 'order' => 290, 'tone' => 'tender', 'conditions' => ['partner'], 'sensitive' => ['separation'],
        'vous' => 'Racontez votre mariage, ou le jour où vous avez décidé de vivre ensemble.',
        // « Vous » reste : c'est le couple qui décide.
        'tu' => 'Raconte ton mariage, ou le jour où vous avez décidé de vivre ensemble.'],
    ['slug' => 'premier-enfant', 'theme' => 'love', 'difficulty' => 3, 'order' => 300, 'tone' => 'tender', 'conditions' => ['children'], 'sensitive' => ['child_loss'],
        'vous' => 'Qu\'avez-vous ressenti en tenant votre premier enfant dans vos bras ?',
        'tu' => 'Qu\'as-tu ressenti en tenant ton premier enfant dans tes bras ?'],
    ['slug' => 'conseil-couple', 'theme' => 'love', 'difficulty' => 3, 'order' => 310, 'tone' => 'light', 'conditions' => ['partner'], 'sensitive' => ['separation'],
        'vous' => 'Quel conseil vous a-t-on donné sur le couple qui s\'est révélé vrai ?',
        'tu' => 'Quel conseil t\'a-t-on donné sur le couple qui s\'est révélé vrai ?'],
    ['slug' => 'dispute-fou-rire', 'theme' => 'love', 'difficulty' => 3, 'order' => 320, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Racontez une dispute qui s\'est terminée en fou rire.',
        'tu' => 'Raconte une dispute qui s\'est terminée en fou rire.'],
    ['slug' => 'qualite-pere-mere', 'theme' => 'love', 'difficulty' => 3, 'order' => 330, 'tone' => 'tender', 'conditions' => ['knew_parents'], 'sensitive' => ['bereavement'],
        'vous' => 'Quelle qualité admiriez-vous le plus chez votre père ? Et chez votre mère ?',
        'tu' => 'Quelle qualité admirais-tu le plus chez ton père ? Et chez ta mère ?'],
    ['slug' => 'avec-les-enfants', 'theme' => 'love', 'difficulty' => 3, 'order' => 340, 'tone' => 'tender', 'conditions' => ['children'], 'sensitive' => [],
        'vous' => 'Que faisiez-vous avec vos enfants quand ils étaient petits, que vous aimeriez qu\'ils se rappellent ?',
        'tu' => 'Que faisais-tu avec tes enfants quand ils étaient petits, que tu aimerais qu\'ils se rappellent ?'],
    ['slug' => 'surnoms-famille', 'theme' => 'love', 'difficulty' => 2, 'order' => 350, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel surnom donnait-on dans la famille, et d\'où venait-il ?',
        'tu' => 'Quel surnom donnait-on dans la famille, et d\'où venait-il ?'],

    // Lieux
    ['slug' => 'lieu-qui-manque', 'theme' => 'places', 'difficulty' => 3, 'order' => 360, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel lieu vous manque le plus, et que reste-t-il de lui aujourd\'hui ?',
        'tu' => 'Quel lieu te manque le plus, et que reste-t-il de lui aujourd\'hui ?'],
    ['slug' => 'voyage-qui-change', 'theme' => 'places', 'difficulty' => 3, 'order' => 370, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Racontez un voyage qui vous a changé{|e}.',
        'tu' => 'Raconte un voyage qui t\'a changé{|e}.'],
    ['slug' => 'ville-village-avant', 'theme' => 'places', 'difficulty' => 2, 'order' => 380, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'À quoi ressemblait votre ville ou votre village quand vous étiez jeune ? Qu\'est-ce qui a disparu ?',
        'tu' => 'À quoi ressemblait ta ville ou ton village quand tu étais jeune ? Qu\'est-ce qui a disparu ?'],
    ['slug' => 'cachette', 'theme' => 'places', 'difficulty' => 2, 'order' => 390, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle était votre cachette ou votre endroit à vous quand vous vouliez être seul{|e} ?',
        'tu' => 'Quelle était ta cachette ou ton endroit à toi quand tu voulais être seul{|e} ?'],
    ['slug' => 'maison-quittee', 'theme' => 'places', 'difficulty' => 3, 'order' => 400, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Racontez une maison où vous avez vécu et qu\'il a fallu quitter.',
        'tu' => 'Raconte une maison où tu as vécu et qu\'il a fallu quitter.'],

    // Joies
    ['slug' => 'plus-beau-jour', 'theme' => 'joys', 'difficulty' => 3, 'order' => 410, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel a été le plus beau jour de votre vie, ou l\'un des plus beaux ?',
        'tu' => 'Quel a été le plus beau jour de ta vie, ou l\'un des plus beaux ?'],
    ['slug' => 'plus-grande-fierte', 'theme' => 'joys', 'difficulty' => 3, 'order' => 420, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'De quoi êtes-vous le plus {fier|fière|fier·ère}, sans fausse modestie ?',
        'tu' => 'De quoi es-tu le plus {fier|fière|fier·ère}, sans fausse modestie ?'],
    ['slug' => 'cadeau-touchant', 'theme' => 'joys', 'difficulty' => 3, 'order' => 430, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel cadeau reçu vous a le plus touché{|e} ?',
        'tu' => 'Quel cadeau reçu t\'a le plus touché{|e} ?'],
    ['slug' => 'fou-rire', 'theme' => 'joys', 'difficulty' => 2, 'order' => 440, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Racontez un fou rire dont vous vous souvenez encore.',
        'tu' => 'Raconte un fou rire dont tu te souviens encore.'],
    ['slug' => 'petite-habitude-heureuse', 'theme' => 'joys', 'difficulty' => 2, 'order' => 450, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle petite habitude vous rend heureu{x|se|x·se} au quotidien ?',
        'tu' => 'Quelle petite habitude te rend heureu{x|se|x·se} au quotidien ?'],

    // Épreuves
    ['slug' => 'epreuve-la-plus-dure', 'theme' => 'hardships', 'difficulty' => 4, 'order' => 460, 'tone' => 'grave', 'conditions' => [], 'sensitive' => ['bereavement', 'illness', 'war'],
        'vous' => 'Quelle a été l\'épreuve la plus dure, et qu\'est-ce qui vous a aidé{|e} à la traverser ?',
        'tu' => 'Quelle a été l\'épreuve la plus dure, et qu\'est-ce qui t\'a aidé{|e} à la traverser ?'],
    ['slug' => 'revoir-une-derniere-fois', 'theme' => 'hardships', 'difficulty' => 4, 'order' => 470, 'tone' => 'grave', 'conditions' => [], 'sensitive' => ['bereavement'],
        'vous' => 'Y a-t-il une personne que vous auriez aimé revoir une dernière fois ? Que lui diriez-vous ?',
        'tu' => 'Y a-t-il une personne que tu aurais aimé revoir une dernière fois ? Que lui dirais-tu ?'],
    ['slug' => 'grande-peur', 'theme' => 'hardships', 'difficulty' => 4, 'order' => 480, 'tone' => 'grave', 'conditions' => [], 'sensitive' => ['war', 'illness'],
        'vous' => 'Racontez un moment où vous avez eu très peur.',
        'tu' => 'Raconte un moment où tu as eu très peur.'],
    ['slug' => 'decision-difficile', 'theme' => 'hardships', 'difficulty' => 4, 'order' => 490, 'tone' => 'grave', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle décision difficile avez-vous prise, et la referiez-vous ?',
        'tu' => 'Quelle décision difficile as-tu prise, et la referais-tu ?'],
    ['slug' => 'lecon-echec', 'theme' => 'hardships', 'difficulty' => 4, 'order' => 500, 'tone' => 'grave', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Qu\'avez-vous appris d\'un échec ?',
        'tu' => 'Qu\'as-tu appris d\'un échec ?'],

    // Convictions et valeurs
    ['slug' => 'vie-reussie', 'theme' => 'beliefs_values', 'difficulty' => 4, 'order' => 510, 'tone' => 'grave', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Qu\'est-ce qui, selon vous, fait une vie réussie ?',
        'tu' => 'Qu\'est-ce qui, selon toi, fait une vie réussie ?'],
    ['slug' => 'valeur-transmise', 'theme' => 'beliefs_values', 'difficulty' => 4, 'order' => 520, 'tone' => 'tender', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle est la valeur que vous avez essayé de transmettre avant toutes les autres ?',
        'tu' => 'Quelle est la valeur que tu as essayé de transmettre avant toutes les autres ?'],
    ['slug' => 'croyance-changee', 'theme' => 'beliefs_values', 'difficulty' => 4, 'order' => 530, 'tone' => 'grave', 'conditions' => [], 'sensitive' => ['religion'],
        'vous' => 'Qu\'est-ce que vous croyez aujourd\'hui que vous ne croyiez pas à vingt ans ?',
        'tu' => 'Qu\'est-ce que tu crois aujourd\'hui que tu ne croyais pas à vingt ans ?'],
    ['slug' => 'priere-poeme-chanson', 'theme' => 'beliefs_values', 'difficulty' => 4, 'order' => 540, 'tone' => 'tender', 'conditions' => [], 'sensitive' => ['religion'],
        'vous' => 'Y a-t-il une prière, un poème ou une chanson qui vous accompagne ?',
        'tu' => 'Y a-t-il une prière, un poème ou une chanson qui t\'accompagne ?'],
    ['slug' => 'monde-des-petits-enfants', 'theme' => 'beliefs_values', 'difficulty' => 4, 'order' => 550, 'tone' => 'grave', 'conditions' => ['grandchildren'], 'sensitive' => [],
        'vous' => 'Que pensez-vous du monde que vos petits-enfants vont connaître ?',
        'tu' => 'Que penses-tu du monde que tes petits-enfants vont connaître ?'],

    // Ce qui reste
    ['slug' => 'conseil-dix-huit-ans', 'theme' => 'legacy', 'difficulty' => 5, 'order' => 560, 'tone' => 'tender', 'conditions' => ['grandchildren'], 'sensitive' => [],
        'vous' => 'Quel conseil donneriez-vous à votre petit-fils ou votre petite-fille pour ses dix-huit ans ?',
        'tu' => 'Quel conseil donnerais-tu à ton petit-fils ou ta petite-fille pour ses dix-huit ans ?'],
    ['slug' => 'ce-quon-retienne', 'theme' => 'legacy', 'difficulty' => 5, 'order' => 570, 'tone' => 'grave', 'conditions' => [], 'sensitive' => ['end_of_life'],
        'vous' => 'Qu\'aimeriez-vous que l\'on retienne de vous ?',
        'tu' => 'Qu\'aimerais-tu que l\'on retienne de toi ?'],
    ['slug' => 'geste-recette-secret', 'theme' => 'legacy', 'difficulty' => 3, 'order' => 580, 'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Y a-t-il une recette, un geste ou un savoir-faire que vous seul{|e} savez faire ? Décrivez-le pas à pas.',
        'tu' => 'Y a-t-il une recette, un geste ou un savoir-faire que toi seul{|e} sais faire ? Décris-le pas à pas.'],
    ['slug' => 'histoire-jamais-racontee', 'theme' => 'legacy', 'difficulty' => 5, 'order' => 590, 'tone' => 'grave', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quelle histoire n\'avez-vous jamais racontée à personne et pourriez-vous raconter aujourd\'hui ?',
        'tu' => 'Quelle histoire n\'as-tu jamais racontée à personne et pourrais-tu raconter aujourd\'hui ?'],
    ['slug' => 'message-dans-cinquante-ans', 'theme' => 'legacy', 'difficulty' => 5, 'order' => 600, 'tone' => 'grave', 'conditions' => [], 'sensitive' => ['end_of_life'],
        'vous' => 'Si vous pouviez laisser un message à ceux qui écouteront ces enregistrements dans cinquante ans, que diriez-vous ?',
        'tu' => 'Si tu pouvais laisser un message à ceux qui écouteront ces enregistrements dans cinquante ans, que dirais-tu ?'],
];
