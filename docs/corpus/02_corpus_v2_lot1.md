# Corpus v2 — lot 1 : le socle universel

23 septembre 2026. Proposition à relire avant intégration dans `database/corpus/questions.php`. Seul le vouvoiement est écrit ici : le tutoiement s'écrit à l'intégration, une fois le texte validé. Les marqueurs de genre sont déjà posés (`né{|e}` se lit « né » ou « née »).

## 1. La carte du corpus v2

Cible : environ 180 questions. Le profil le plus contraint (sans couple, sans enfants, sans métier, deux sujets écartés) doit garder 52 questions **et** du choix.

| Bloc | Condition | Existant | Lot 1 | Lot 2 | Lot 3 | Total visé |
|---|---|---|---|---|---|---|
| Socle universel | — | 49 | +33 | | | ~82 |
| Réserve légère (relances) | — | — | +10 | | +10 | ~20 |
| Frères et sœurs | `siblings` *(nouvelle)* | 0 | +4 | | | 4 |
| Parents | `knew_parents` | 1 | +5 | | | 6 |
| Service militaire | `military_service` *(nouvelle)* | 0 | +1 | | | 1 |
| Couple | `partner` | 3 | | +8 | | ~11 |
| Enfants | `children` | 2 | | +8 | | ~10 |
| Petits-enfants | `grandchildren` | 2 | | +6 | | ~8 |
| Métier | `career` | 5 | +1 | +4 | | ~10 |
| Ailleurs (migration) | `migration` *(nouvelle)* | 0 | | +6 | | 6 |
| Campagne, ferme | `rural` *(nouvelle)* | 0 | | +5 | | 5 |
| Sur l'acheteur | par lien | 0 | | | +10 | ~10 |

*Existant du socle : les 47 questions v1 sans condition, plus `monde-des-petits-enfants` et `conseil-dix-huit-ans`, que les retouches du §3 rendent universelles.*

- **Lot 1** (ce document) : ce qui manque à tout le monde — frères et sœurs, parents, école, passions, fêtes, ce qu'on a vu arriver, amitiés, histoire vécue, aujourd'hui — et une première réserve légère. Plus des retouches de questions existantes (§3).
- **Lot 2** : les blocs conditionnels (couple, enfants, petits-enfants, métier, migration, campagne).
- **Lot 3** : le bloc sur l'acheteur, qui demande un marqueur de plus dans la structure (prénom, genre de l'acheteur), et la fin de la réserve légère.

**Thèmes : on garde les dix.** Ils ne servent qu'à compter la variété du livre (5 thèmes pour qu'il soit prêt) et à nourrir le choix « thèmes à mettre en avant » du tunnel. Dix suffisent, et en ajouter changerait aussi le quiz de vente, qui les liste. Les nouveaux sujets se rangent dans les thèmes existants : les frères et sœurs dans *Origines familiales*, les passions dans *Joies*, l'histoire vécue dans *Jeunesse* ou *Épreuves*, aujourd'hui dans *Joies* ou *Ce qui reste*.

**Principes d'écriture** que j'ai suivis, et que je propose de garder pour les lots suivants :

1. **Un moment, pas une opinion.** « Racontez… », une scène, un jour, une personne. Une question qui appelle un avis donne trois phrases générales ; une question qui appelle un souvenir donne une histoire.
2. **Des prises concrètes.** Un lieu, un objet, une odeur, un prix : ce qui fait revenir le reste.
3. **Une porte de sortie** quand la question suppose quelque chose (« si vous partiez », « même s'il a disparu »), pour que personne ne se sente en défaut.
4. **Courte à lire.** Une phrase, deux au plus : elle est lue sur un téléphone, parfois à voix haute par quelqu'un d'autre.

## 2. Les nouvelles questions

Colonnes : difficulté (1 très facile … 5 intime) · ton (L léger, T tendre, G grave) · conditions · sujets sensibles.

### Frères, sœurs, cousins

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `fratrie-portrait` | Racontez vos frères et sœurs quand vous étiez petits : qui était qui, à la maison ? | family_origins | 2 | T | siblings | |
| `fratrie-quatre-cents-coups` | Avec lequel de vos frères et sœurs faisiez-vous les quatre cents coups ? Racontez-en un. | family_origins | 1 | L | siblings | |
| `fratrie-disputes` | Pour quoi vous disputiez-vous entre frères et sœurs, et qui finissait par gagner ? | family_origins | 1 | L | siblings | |
| `fratrie-avec-les-annees` | Qu'est-ce qui a changé, avec les années, entre vous et vos frères et sœurs ? | family_origins | 3 | T | siblings | bereavement |
| `cousins-maison-commune` | Chez qui se retrouvait toute la famille, les cousins, les oncles et les tantes ? Racontez une de ces journées. | family_origins | 1 | L | | |

### Les parents

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `metier-des-parents` | Quel était le métier de vos parents ? Racontez une de leurs journées, telle que vous la voyiez enfant. | family_origins | 2 | L | knew_parents | |
| `rencontre-des-parents` | Comment vos parents se sont-ils rencontrés ? Que vous en a-t-on raconté ? | family_origins | 2 | T | knew_parents | |
| `phrase-des-parents` | Quelle phrase entendiez-vous sans cesse dans la bouche de vos parents ? Vous arrive-t-il de la répéter ? | family_origins | 1 | L | knew_parents | |
| `ressembler-aux-parents` | En quoi ressemblez-vous à votre père ou à votre mère, et en quoi avez-vous voulu être différent{\|e} ? | family_origins | 3 | T | knew_parents | |
| `parents-autrement` | À quel moment avez-vous regardé vos parents autrement qu'avec des yeux d'enfant ? | family_origins | 4 | T | knew_parents | bereavement |
| `se-faire-gronder` | Racontez une fois où vous vous êtes fait gronder. Qu'aviez-vous fait ? | childhood | 1 | L | | |

### L'école

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `premier-jour-ecole` | Vous souvenez-vous de votre premier jour d'école ? Racontez la classe, le maître ou la maîtresse. | childhood | 1 | L | | |
| `chemin-de-l-ecole` | Comment alliez-vous à l'école, et que se passait-il en chemin ? | childhood | 1 | L | | |
| `cour-de-recreation` | À quoi jouait-on dans la cour de récréation ? | childhood | 1 | L | | |
| `quel-eleve` | Quel genre d'élève étiez-vous ? Racontez une matière que vous aimiez et une que vous fuyiez. | childhood | 1 | L | | |
| `quitter-l-ecole` | Quand avez-vous quitté l'école, et l'avez-vous choisi ? | youth | 3 | T | | |

### Passions et loisirs

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `passion-d-une-vie` | Quelle passion vous a accompagné{\|e} toute votre vie ? Racontez comment elle est née. | joys | 2 | T | | |
| `fait-de-mes-mains` | Qu'avez-vous aimé faire de vos mains : jardiner, cuisiner, coudre, bricoler ? Racontez ce dont vous êtes le plus {fier\|fière\|fier·ère}. | joys | 1 | L | | |
| `livre-film-marquant` | Quel livre, quel film ou quelle émission vous a marqué{\|e}, et pourquoi ? | joys | 2 | L | | |
| `sport-ou-jeu` | Quel sport ou quel jeu avez-vous pratiqué avec passion ? Racontez une partie mémorable. | joys | 1 | L | | |
| `bals-et-fetes` | Où alliez-vous danser ou faire la fête quand vous étiez jeune ? Racontez une soirée. | youth | 2 | L | | |
| `chanson-par-coeur` | Quelle chanson connaissez-vous encore par cœur ? Chantez-en un bout, si le cœur vous en dit. | joys | 1 | L | | |
| `animal-qui-a-compte` | Racontez un animal qui a compté dans votre vie. | joys | 1 | T | | |

### Fêtes et traditions

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `noel-enfance` | Comment fêtait-on Noël, ou les grandes fêtes de l'année, chez vous quand vous étiez enfant ? | childhood | 1 | L | | |
| `grande-fete-de-famille` | Racontez une grande fête de famille, un jour où tout le monde était là. | family_origins | 2 | L | | |
| `plat-des-grandes-occasions` | Quel était le plat des grandes occasions dans votre famille, et qui le préparait ? | family_origins | 1 | L | | |
| `vacances-enfance` | Où partiez-vous en vacances quand vous étiez enfant, si vous partiez ? Racontez un été. | childhood | 1 | L | | |

### Ce qu'on a vu arriver

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `premiere-television` | Vous souvenez-vous de la première télévision que vous avez vue ? Chez qui, et que regardait-on ? | places | 1 | L | | |
| `telephone-a-la-maison` | Quand le téléphone est-il arrivé chez vous ? Racontez un appel que vous n'avez pas oublié. | places | 1 | L | | |
| `premiere-voiture` | Racontez votre première voiture, ou le premier voyage en voiture dont vous vous souvenez. | places | 1 | L | | |
| `ce-qui-a-disparu` | Qu'est-ce qui faisait partie de votre enfance et a complètement disparu aujourd'hui ? | places | 1 | L | | |
| `prix-d-autrefois` | Combien coûtaient le pain, le cinéma ou un timbre quand vous étiez jeune ? Que pouvait-on s'offrir ? | places | 1 | L | | |
| `changement-etonnant` | Quel changement du monde vous a le plus étonné{\|e} au cours de votre vie ? | places | 2 | T | | |
| `argent-a-la-maison` | Comment parlait-on d'argent chez vous quand vous étiez enfant ? | family_origins | 3 | T | | |
| `premier-salaire` | Qu'avez-vous fait de votre tout premier salaire ? | work | 2 | L | career | |

### Amitiés et voisinage

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `amitie-fidele` | Racontez l'amitié la plus fidèle de votre vie d'adulte : comment est-elle née ? | joys | 2 | T | | bereavement |
| `voisins-inoubliables` | Racontez des voisins dont on se souvient encore, des années après. | places | 1 | L | | |

### L'histoire vécue

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `la-guerre` | Qu'avez-vous vécu de la guerre, ou que vous en a-t-on raconté dans la famille ? | hardships | 4 | G | | war |
| `service-militaire` | Racontez votre service militaire : où, avec qui, et ce que vous en avez gardé. | youth | 3 | T | military_service | war |

### Aujourd'hui

| Slug | Question | Thème | Diff. | Ton | Conditions | Sensibles |
|---|---|---|---|---|---|---|
| `journee-d-aujourd-hui` | Racontez une journée ordinaire d'aujourd'hui, du réveil au coucher. | joys | 1 | L | | |
| `ou-je-vis` | Qu'aimez-vous dans l'endroit où vous vivez aujourd'hui ? | places | 1 | L | | |
| `apprecier-avec-l-age` | Qu'est-ce que l'âge vous a appris à apprécier, que vous ne voyiez pas avant ? | beliefs_values | 3 | T | | |
| `encore-envie` | Qu'avez-vous encore envie de faire, de voir ou d'apprendre ? | legacy | 2 | T | | |
| `vieillir` | Qu'y a-t-il de plus dur, et de plus doux, dans le fait de vieillir ? | beliefs_values | 4 | G | | illness |

### Réserve légère

Des questions faciles et gaies, de difficulté 1, que le moteur envoie quand quelqu'un s'essouffle (règle des dix jours de silence). Elles n'ont aucune condition, pour convenir à tout le monde.

| Slug | Question | Thème | Diff. | Ton |
|---|---|---|---|---|
| `photo-preferee` | Décrivez une photo de vous que vous aimez : où, quand, et qui l'a prise ? | joys | 1 | L |
| `objet-qui-a-une-histoire` | Quel objet de votre maison a une histoire ? Racontez-la. | places | 1 | L |
| `premier-train-ou-avion` | Racontez la première fois que vous avez pris le train ou l'avion. | places | 1 | L |
| `vetement-adore` | Racontez un vêtement que vous avez adoré porter. | youth | 1 | L |
| `saison-preferee` | Quelle est votre saison préférée, et quel souvenir s'y attache ? | joys | 1 | L |
| `rencontre-celebre` | Avez-vous déjà croisé quelqu'un de célèbre ? Racontez. | joys | 1 | L |
| `grosse-betise-adulte` | Racontez la plus grosse bêtise que vous ayez faite une fois adulte. | joys | 1 | L |
| `ce-qui-fait-rire` | Qu'est-ce qui vous fait rire, aujourd'hui ? | joys | 1 | L |
| `recette-de-famille` | Donnez-nous une recette de famille, avec vos mots et vos tours de main. | family_origins | 1 | L |
| `jour-de-neige` | Racontez un jour de neige, d'orage ou de grande chaleur dont vous vous souvenez. | childhood | 1 | L |

## 3. Retouches des questions existantes

La reformulation est sans risque depuis le lot précédent : les histoires déjà enregistrées gardent l'intitulé qu'elles ont lu.

| Slug | Aujourd'hui | Proposé | Pourquoi |
|---|---|---|---|
| `plat-enfance` | Quel plat de votre enfance aimeriez-vous goûter une dernière fois ? | Quel plat de votre enfance aimeriez-vous goûter à nouveau, et qui le préparait ? | « une dernière fois » évoque la mort à la 7e question ; l'étiquette `end_of_life` tombe |
| `objet-transmis` | Y a-t-il un objet transmis dans la famille ? Racontez son histoire. | Y a-t-il un objet qui vient de vos parents ou de vos grands-parents ? Racontez son histoire, même s'il a disparu. | « non » faisait tomber la question |
| `nom-de-famille` | D'où vient votre nom de famille, et quelle histoire y est attachée ? | D'où vient le nom de famille avec lequel vous êtes né{\|e}, et quelle histoire y est attachée ? | ambigu pour une femme mariée |
| `metier-fierte` | Quel a été le métier dont vous êtes le plus fier·ère, et pourquoi ? | Racontez ce dont vous êtes le plus {fier\|fière\|fier·ère} dans votre travail. | ne suppose plus plusieurs métiers |
| `travail-et-les-gens` | Qu'est-ce que votre travail vous a appris sur les gens ? | Racontez quelqu'un que vous avez rencontré par votre travail et que vous n'avez jamais oublié. | un souvenir plutôt qu'un avis |
| `choix-professionnel` | Y a-t-il un choix professionnel que vous referiez autrement ? | Y a-t-il un choix professionnel que vous referiez autrement ? Racontez ce qui s'est passé. | évite le « non » sec |
| `voyage-qui-change` | Racontez un voyage qui vous a changé·e. | Racontez un voyage, ou un départ, qui vous a changé{\|e}. | tout le monde n'a pas voyagé |
| `lecon-echec` | Qu'avez-vous appris d'un échec ? | Racontez un échec qui vous a finalement servi. | un souvenir plutôt qu'un avis |
| `vie-reussie` | Qu'est-ce qui, selon vous, fait une vie réussie ? | Racontez un moment où vous vous êtes dit : « Ça, je l'ai réussi. » | un souvenir plutôt qu'un avis |
| `monde-des-petits-enfants` | Que pensez-vous du monde que vos petits-enfants vont connaître ? | Que pensez-vous du monde que les générations qui viennent vont connaître ? | la condition `grandchildren` tombe ; la version aux petits-enfants ira au lot 2 |
| `conseil-dix-huit-ans` | Quel conseil donneriez-vous à votre petit-fils ou votre petite-fille pour ses dix-huit ans ? | Quel conseil donneriez-vous à quelqu'un qui a dix-huit ans aujourd'hui ? | idem |

Et quatre corrections de rangement : `qualite-pere-mere` et `surnoms-famille` passent en *Origines familiales*, `cachette` en *Enfance* ; `geste-recette-secret` descend en difficulté 2, `evenement-du-monde` monte en 3.

## 4. Bilan du lot

- **54 questions nouvelles** : 33 dans le socle sans condition, 11 dans des blocs à condition (frères et sœurs, parents, service militaire, premier salaire), 10 dans la réserve légère. Le corpus passerait de 60 à 114 questions.
- **Deux conditions nouvelles** : `siblings` (a des frères ou des sœurs) et `military_service`. Le tunnel devra les demander ; la seconde peut se déduire en partie (« a-t-il fait son service ? »), ou disparaître si tu la trouves trop pointue.
- **Le profil le plus contraint** (sans couple, sans enfants, sans métier, sans frères et sœurs) passe de 48 questions à 98 : le socle suffit déjà à tenir 52 envois avec une vraie marge.
- **Tons** : le lot est volontairement léger (38 L, 14 T, 2 G). Il rééquilibre un corpus v1 qui finissait sur onze questions graves d'affilée.

## 5. À décider

1. Les questions à retirer, reformuler ou garder — un slug suffit.
2. Les retouches du §3 : toutes, certaines, aucune ?
3. `military_service` : on garde la question et la condition, ou on la retire ?
