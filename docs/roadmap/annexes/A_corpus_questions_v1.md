# Annexe A — Corpus de questions v1 (60 questions, français)

Corpus original (P0-9), séquencé du facile vers l'intime. Chargé par `database/seeders/QuestionSeeder.php` au bloc 05. Colonnes : `order_hint` (ordre par défaut d'envoi), `theme`, `difficulty` (1 = très facile, 5 = intime). La première question envoyée à un narrateur est toujours de difficulté 1. La règle « silence ≥ 10 jours » du moteur choisit une question de difficulté ≤ 2 non encore proposée.

Le slug est stable : c'est lui que référencent les tests et les analyses. Ne jamais renuméroter ; ajouter en fin de liste.

| order_hint | slug | theme | difficulty | Question |
|---|---|---|---|---|
| 10 | `naissance-recit` | childhood | 1 | Où êtes-vous né·e, et que vous a-t-on raconté sur le jour de votre naissance ? |
| 20 | `premier-souvenir` | childhood | 1 | Quel est votre tout premier souvenir ? |
| 30 | `maison-enfance` | childhood | 1 | À quoi ressemblait la maison de votre enfance ? Décrivez une pièce que vous aimiez. |
| 40 | `betise-enfant` | childhood | 1 | Quel jeu ou quelle bêtise d'enfant vous fait encore sourire ? |
| 50 | `apprendre-velo-nager-lire` | childhood | 1 | Qui vous a appris à faire du vélo, à nager ou à lire ? Racontez ce moment. |
| 60 | `odeur-enfance` | childhood | 1 | Quelle était votre odeur préférée quand vous étiez enfant, et à quoi vous ramène-t-elle ? |
| 70 | `plat-enfance` | childhood | 1 | Quel plat de votre enfance aimeriez-vous goûter une dernière fois ? |
| 80 | `dimanche-dix-ans` | childhood | 1 | Comment se passait un dimanche ordinaire chez vous quand vous aviez dix ans ? |
| 90 | `grands-parents` | family_origins | 2 | Que savez-vous de vos grands-parents ? Racontez-les comme si on ne les avait jamais rencontrés. |
| 100 | `nom-de-famille` | family_origins | 2 | D'où vient votre nom de famille, et quelle histoire y est attachée ? |
| 110 | `expression-famille` | family_origins | 2 | Quelle expression ou quel mot venait de votre famille et n'existait nulle part ailleurs ? |
| 120 | `conteur-famille` | family_origins | 2 | Qui, dans votre famille, racontait le mieux les histoires ? Laquelle vous revient ? |
| 130 | `objet-transmis` | family_origins | 2 | Y a-t-il un objet transmis dans la famille ? Racontez son histoire. |
| 140 | `tradition-gardee` | family_origins | 2 | Quelle tradition de votre enfance avez-vous gardée, et laquelle avez-vous abandonnée ? |
| 150 | `adulte-qui-a-compte` | youth | 2 | Quel professeur ou adulte, en dehors de vos parents, a compté pour vous ? |
| 160 | `musique-quinze-ans` | youth | 2 | Quelle musique écoutiez-vous à quinze ans, et où l'écoutiez-vous ? |
| 170 | `premiere-liberte` | youth | 2 | Racontez votre première grande liberté : un voyage, une sortie, un départ. |
| 180 | `reve-metier` | youth | 2 | Quel était votre rêve de métier à dix-huit ans ? |
| 190 | `mode-jeunesse` | youth | 2 | Quelle mode ou quelle habitude de votre jeunesse ferait rire les jeunes d'aujourd'hui ? |
| 200 | `ami-perdu-de-vue` | youth | 2 | Racontez un ami d'enfance ou de jeunesse que vous avez perdu de vue. Que faisiez-vous ensemble ? |
| 210 | `evenement-du-monde` | youth | 2 | Quel événement du monde vous a marqué·e quand vous étiez jeune, et où étiez-vous ce jour-là ? |
| 220 | `premier-jour-travail` | work | 2 | Racontez votre premier jour de travail. |
| 230 | `metier-fierte` | work | 2 | Quel a été le métier dont vous êtes le plus fier·ère, et pourquoi ? |
| 240 | `travail-et-les-gens` | work | 2 | Qu'est-ce que votre travail vous a appris sur les gens ? |
| 250 | `journee-de-travail` | work | 2 | Racontez une journée de travail ordinaire à l'époque où vous étiez le plus occupé·e. |
| 260 | `choix-professionnel` | work | 3 | Y a-t-il un choix professionnel que vous referiez autrement ? |
| 270 | `qui-a-donne-sa-chance` | work | 2 | Qui vous a donné votre chance, et comment ? |
| 280 | `rencontre-conjoint` | love | 3 | Comment avez-vous rencontré la personne qui a partagé votre vie ? |
| 290 | `mariage-ou-vie-commune` | love | 3 | Racontez votre mariage, ou le jour où vous avez décidé de vivre ensemble. |
| 300 | `premier-enfant` | love | 3 | Qu'avez-vous ressenti en tenant votre premier enfant dans vos bras ? |
| 310 | `conseil-couple` | love | 3 | Quel conseil vous a-t-on donné sur le couple qui s'est révélé vrai ? |
| 320 | `dispute-fou-rire` | love | 3 | Racontez une dispute qui s'est terminée en fou rire. |
| 330 | `qualite-pere-mere` | love | 3 | Quelle qualité admiriez-vous le plus chez votre père ? Et chez votre mère ? |
| 340 | `avec-les-enfants` | love | 3 | Que faisiez-vous avec vos enfants quand ils étaient petits, que vous aimeriez qu'ils se rappellent ? |
| 350 | `surnoms-famille` | love | 2 | Quel surnom donnait-on dans la famille, et d'où venait-il ? |
| 360 | `lieu-qui-manque` | places | 3 | Quel lieu vous manque le plus, et que reste-t-il de lui aujourd'hui ? |
| 370 | `voyage-qui-change` | places | 3 | Racontez un voyage qui vous a changé·e. |
| 380 | `ville-village-avant` | places | 2 | À quoi ressemblait votre ville ou votre village quand vous étiez jeune ? Qu'est-ce qui a disparu ? |
| 390 | `cachette` | places | 2 | Quelle était votre cachette ou votre endroit à vous quand vous vouliez être seul·e ? |
| 400 | `maison-quittee` | places | 3 | Racontez une maison où vous avez vécu et qu'il a fallu quitter. |
| 410 | `plus-beau-jour` | joys | 3 | Quel a été le plus beau jour de votre vie, ou l'un des plus beaux ? |
| 420 | `plus-grande-fierte` | joys | 3 | De quoi êtes-vous le plus fier·ère, sans fausse modestie ? |
| 430 | `cadeau-touchant` | joys | 3 | Quel cadeau reçu vous a le plus touché·e ? |
| 440 | `fou-rire` | joys | 2 | Racontez un fou rire dont vous vous souvenez encore. |
| 450 | `petite-habitude-heureuse` | joys | 2 | Quelle petite habitude vous rend heureux·se au quotidien ? |
| 460 | `epreuve-la-plus-dure` | hardships | 4 | Quelle a été l'épreuve la plus dure, et qu'est-ce qui vous a aidé·e à la traverser ? |
| 470 | `revoir-une-derniere-fois` | hardships | 4 | Y a-t-il une personne que vous auriez aimé revoir une dernière fois ? Que lui diriez-vous ? |
| 480 | `grande-peur` | hardships | 4 | Racontez un moment où vous avez eu très peur. |
| 490 | `decision-difficile` | hardships | 4 | Quelle décision difficile avez-vous prise, et la referiez-vous ? |
| 500 | `lecon-echec` | hardships | 4 | Qu'avez-vous appris d'un échec ? |
| 510 | `vie-reussie` | beliefs_values | 4 | Qu'est-ce qui, selon vous, fait une vie réussie ? |
| 520 | `valeur-transmise` | beliefs_values | 4 | Quelle est la valeur que vous avez essayé de transmettre avant toutes les autres ? |
| 530 | `croyance-changee` | beliefs_values | 4 | Qu'est-ce que vous croyez aujourd'hui que vous ne croyiez pas à vingt ans ? |
| 540 | `priere-poeme-chanson` | beliefs_values | 4 | Y a-t-il une prière, un poème ou une chanson qui vous accompagne ? |
| 550 | `monde-des-petits-enfants` | beliefs_values | 4 | Que pensez-vous du monde que vos petits-enfants vont connaître ? |
| 560 | `conseil-dix-huit-ans` | legacy | 5 | Quel conseil donneriez-vous à votre petit-fils ou votre petite-fille pour ses dix-huit ans ? |
| 570 | `ce-quon-retienne` | legacy | 5 | Qu'aimeriez-vous que l'on retienne de vous ? |
| 580 | `geste-recette-secret` | legacy | 3 | Y a-t-il une recette, un geste ou un savoir-faire que vous seul·e savez faire ? Décrivez-le pas à pas. |
| 590 | `histoire-jamais-racontee` | legacy | 5 | Quelle histoire n'avez-vous jamais racontée à personne et pourriez-vous raconter aujourd'hui ? |
| 600 | `message-dans-cinquante-ans` | legacy | 5 | Si vous pouviez laisser un message à ceux qui écouteront ces enregistrements dans cinquante ans, que diriez-vous ? |

## Règles de séquencement (implémentées au bloc 05, `App\Actions\PickNextQuestion`)

1. Première question d'un projet : `order_hint` le plus bas parmi les `difficulty = 1` non proposées.
2. Ensuite : `order_hint` croissant parmi les questions non proposées et non exclues par l'Initiateur·rice.
3. L'Initiateur·rice peut réordonner, exclure, ou ajouter une question personnalisée (`stories.custom_question_text`), qui prend la prochaine place.
4. Règle du moteur `narrator_silence_10d` : substituer une question de `difficulty ≤ 2` non proposée.
5. Aucune question de `difficulty ≥ 4` avant la 6e histoire validée.
6. Les thèmes couverts alimentent le critère book-ready « ≥ 5 thèmes » (R-6) : un thème est couvert dès qu'une histoire validée lui est rattachée.
