# Relecture de toutes les questions

23 septembre 2026. Les 60 questions du corpus actuel et les 54 du lot 1, relues une par une. Écrites au tutoiement, comme tous les projets de production ; le vouvoiement s'écrira à l'intégration, en suivant le même texte. Ce document remplace les textes proposés dans `02_corpus_v2_lot1.md` ; les étiquettes (conditions, sujets sensibles, ton) de ce document-là restent valables.

## 1. La grille

Trois questions posées à chaque énoncé :

1. **Est-ce clair ?** On comprend du premier coup ce qu'on attend, sans relire.
2. **Est-ce que ça touche ?** La question fait revenir quelque chose — une personne, un lieu, une sensation — plutôt que de demander un avis.
3. **Est-ce que ça donne de la matière ?** La réponse naturelle fait plusieurs minutes, pas une phrase.

## 2. Le motif retenu

Presque chaque question suit maintenant le même mouvement, en deux temps :

- **Une porte d'entrée douce**, souvent sensorielle : « Te souviens-tu… », « Ferme les yeux… », « Quel est le premier… ». Elle fait revenir le souvenir.
- **Une relance qui ouvre le récit** : « Raconte… » suivi de deux ou trois prises concrètes — qui était là, où, ce que tu ressentais, comment ça s'est terminé. Elle transforme un « oui » en histoire.

Ton exemple montre pourquoi la relance compte : « Te souviens-tu d'une odeur qui a marqué ton enfance… ? » ouvre la porte, mais laisse répondre « oui, le pain chaud ». En ajoutant « Raconte où elle t'emmène », on obtient la boulangerie, le trajet, la grand-mère qui envoyait chercher la baguette.

Quatre garde-fous :

- **Pas plus de trente mots.** Moyenne : 22, maximum : 29. La question se lit sur un téléphone.
- **Des prises, pas un questionnaire.** Deux ou trois pistes au plus, pour guider sans obliger à répondre à tout.
- **Une porte de sortie** quand la question suppose quelque chose : « si tu partais », « même s'il a disparu depuis », « ce que tu as envie d'en dire ». Personne ne doit se sentir en défaut.
- **Le « nous » de la famille**, parfois : « Raconte-nous… ». Il rappelle à qui l'on parle.

## 3. À trancher : les questions qui se recouvrent

Avec 114 questions, certaines se ressemblent. Rien n'est retiré sans ton accord :

| Sujet | Questions | Proposition |
|---|---|---|
| La cuisine | `plat-enfance`, `plat-des-grandes-occasions`, `recette-de-famille`, `geste-recette-secret` | Retirer `recette-de-famille`, que `geste-recette-secret` couvre déjà |
| Le rire | `fou-rire`, `dispute-fou-rire`, `ce-qui-fait-rire` | Garder les trois : un souvenir, un couple, aujourd'hui |
| Ce qui a changé | `ville-village-avant`, `ce-qui-a-disparu`, `changement-etonnant` | Garder, mais les espacer dans l'ordre d'envoi |
| Les bêtises | `betise-enfant`, `se-faire-gronder`, `grosse-betise-adulte` | Garder les trois : l'enfance, la punition, l'âge adulte |

Et une décision encore ouverte du lot 1 : **`service-militaire`** et sa condition — on la garde ?

## 4. Décisions et intégration (23 septembre 2026)

- **Chevauchements** : proposition retenue. `recette-de-famille` est retirée ; les questions sur le rire, les bêtises et ce qui a changé restent, espacées dans l'ordre d'envoi.
- **`service-militaire`** : retirée, et la condition `military_service` avec elle.
- **Intégré** dans `database/corpus/questions.php` : 112 questions, vouvoiement écrit à la main sur le tutoiement ci-dessous, apostrophe typographique partout. Une seule retouche après relecture : `jour-de-neige` dit « ce que tu as fait ce jour-là ».
- **L'ordre d'envoi** est recalculé. Chaque thème garde sa file, du plus facile au plus difficile ; l'envoi tourne entre les thèmes sous un plafond de difficulté qui monte (très facile les quatre premières semaines, rien de difficile avant la vingt-cinquième), avec au moins cinq semaines entre deux questions difficiles, et les questions à condition espacées. Les 52 premières : 25 très faciles, 13 faciles, 8 moyennes, 5 difficiles, 1 intime, les dix thèmes.

## 5. Les questions

Les deux lignes `recette-de-famille` et `service-militaire` ci-dessous sont celles qui ont été retirées.

### Enfance

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `naissance-recit` | Où es-tu né{\|e}, et que t'a-t-on raconté sur le jour de ta naissance ? | Sais-tu où et comment tu es né{\|e} ? Raconte ce qu'on t'a dit de ce jour-là, et de la famille qui t'attendait. |
| `premier-souvenir` | Quel est ton tout premier souvenir ? | Quel est le tout premier souvenir qui te revient ? Où étais-tu, qui était là, et qu'est-ce que tu ressentais ? |
| `maison-enfance` | À quoi ressemblait la maison de ton enfance ? Décris une pièce que tu aimais. | Ferme les yeux et retourne dans la maison de ton enfance. Raconte-la-nous pièce par pièce : les bruits, les odeurs, le coin que tu préférais. |
| `betise-enfant` | Quel jeu ou quelle bêtise d'enfant te fait encore sourire ? | Quelle bêtise d'enfant te fait encore sourire aujourd'hui ? Raconte-la, avec tes complices et ce qui s'est passé après. |
| `apprendre-velo-nager-lire` | Qui t'a appris à faire du vélo, à nager ou à lire ? Raconte ce moment. | Qui t'a appris à faire du vélo, à nager ou à lire ? Raconte ce moment : sa patience, tes peurs, et la première fois où tu as réussi. |
| `odeur-enfance` | Quelle était ton odeur préférée quand tu étais enfant, et à quoi te ramène-t-elle ? | Te souviens-tu d'une odeur qui a marqué ton enfance, ou qui te replonge d'un coup dans tes souvenirs ? Raconte où elle t'emmène. |
| `plat-enfance` | Quel plat de ton enfance aimerais-tu goûter une dernière fois ? | Quel plat de ton enfance aimerais-tu goûter à nouveau ? Raconte qui le préparait, la cuisine où il mijotait, et les repas où on le servait. |
| `dimanche-dix-ans` | Comment se passait un dimanche ordinaire chez toi quand tu avais dix ans ? | À quoi ressemblait un dimanche chez toi quand tu avais dix ans ? Raconte-nous la journée, du matin au soir. |
| `cachette` | Quelle était ta cachette ou ton endroit à toi quand tu voulais être seul{\|e} ? | Avais-tu une cachette, un endroit rien qu'à toi quand tu voulais être seul{\|e} ? Décris-le, et raconte ce que tu y faisais. |
| `se-faire-gronder` | *nouvelle (lot 1)* | Te souviens-tu d'une fois où tu t'es fait gronder ? Raconte ce que tu avais fait, et comment ça s'est terminé. |
| `premier-jour-ecole` | *nouvelle (lot 1)* | Te souviens-tu de ton premier jour d'école ? Raconte la classe, la maîtresse ou le maître, et ce que tu ressentais en passant la porte. |
| `chemin-de-l-ecole` | *nouvelle (lot 1)* | Comment allais-tu à l'école ? Raconte le chemin, les copains que tu retrouvais, et ce qui se passait en route. |
| `cour-de-recreation` | *nouvelle (lot 1)* | À quoi jouait-on dans la cour de récréation ? Raconte tes jeux préférés, et ceux avec qui tu jouais. |
| `quel-eleve` | *nouvelle (lot 1)* | Quel genre d'élève étais-tu ? Raconte une matière que tu adorais, une que tu fuyais, et un souvenir de classe qui t'est resté. |
| `noel-enfance` | *nouvelle (lot 1)* | Comment fêtait-on Noël, ou les grandes fêtes, chez toi quand tu étais enfant ? Raconte les préparatifs, la table, les cadeaux, ceux qui étaient là. |
| `vacances-enfance` | *nouvelle (lot 1)* | Où partais-tu en vacances quand tu étais enfant, si tu partais ? Raconte un été qui t'est resté : les lieux, les jeux, les gens. |
| `jour-de-neige` | *nouvelle (lot 1)* | Te souviens-tu d'un jour de neige, d'orage ou de grosse chaleur ? Raconte ce qu'il avait de particulier, et ce que vous avez fait ce jour-là. |

### Origines familiales

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `grands-parents` | Que sais-tu de tes grands-parents ? Raconte-les comme si on ne les avait jamais rencontrés. | Que sais-tu de tes grands-parents ? Raconte-les-nous comme si on ne les avait jamais rencontrés : leur caractère, leurs manies, un souvenir avec eux. |
| `nom-de-famille` | D'où vient ton nom de famille, et quelle histoire y est attachée ? | D'où vient le nom de famille avec lequel tu es né{\|e} ? Raconte ce que tu sais de son histoire, et des gens qui l'ont porté avant toi. |
| `expression-famille` | Quelle expression ou quel mot venait de ta famille et n'existait nulle part ailleurs ? | Y avait-il chez vous une expression, un mot ou une blague qu'on ne comprenait qu'en famille ? Raconte d'où ça venait, et quand on le disait. |
| `conteur-famille` | Qui, dans ta famille, racontait le mieux les histoires ? Laquelle te revient ? | Qui racontait le mieux les histoires dans ta famille ? Raconte-nous l'une de ses histoires, comme il ou elle la racontait. |
| `objet-transmis` | Y a-t-il un objet transmis dans la famille ? Raconte son histoire. | Y a-t-il un objet qui vient de tes parents ou de tes grands-parents ? Raconte son histoire, même s'il a disparu depuis. |
| `tradition-gardee` | Quelle tradition de ton enfance as-tu gardée, et laquelle as-tu abandonnée ? | Quelle tradition de ton enfance as-tu gardée, et laquelle as-tu laissée derrière toi ? Raconte pourquoi. |
| `qualite-pere-mere` | Quelle qualité admirais-tu le plus chez ton père ? Et chez ta mère ? | Qu'admirais-tu le plus chez ton père, et chez ta mère ? Raconte un moment qui le montre bien. |
| `surnoms-famille` | Quel surnom donnait-on dans la famille, et d'où venait-il ? | Quels surnoms se donnait-on dans la famille ? Raconte d'où ils venaient, et celui qu'on te donnait à toi. |
| `fratrie-portrait` | *nouvelle (lot 1)* | Parle-nous de tes frères et sœurs quand vous étiez petits : qui était le sage, le rebelle, le rigolo ? Et toi, quelle était ta place ? |
| `fratrie-quatre-cents-coups` | *nouvelle (lot 1)* | Avec lequel de tes frères et sœurs faisais-tu les quatre cents coups ? Raconte-nous une de vos aventures. |
| `fratrie-disputes` | *nouvelle (lot 1)* | Pour quoi vous disputiez-vous entre frères et sœurs ? Raconte une dispute mémorable, et qui finissait par gagner. |
| `fratrie-avec-les-annees` | *nouvelle (lot 1)* | Qu'est-ce qui a changé entre tes frères et sœurs et toi avec les années ? Raconte ce qui vous a rapprochés, ou éloignés. |
| `cousins-maison-commune` | *nouvelle (lot 1)* | Chez qui se retrouvait toute la famille, les cousins, les oncles, les tantes ? Raconte une de ces journées où la maison débordait de monde. |
| `metier-des-parents` | *nouvelle (lot 1)* | Que faisaient tes parents comme métier ? Raconte une de leurs journées, telle que tu la voyais avec tes yeux d'enfant. |
| `rencontre-des-parents` | *nouvelle (lot 1)* | Sais-tu comment tes parents se sont rencontrés ? Raconte ce qu'on t'en a dit, ou ce que tu as deviné. |
| `phrase-des-parents` | *nouvelle (lot 1)* | Quelle phrase entendais-tu sans arrêt dans la bouche de tes parents ? Raconte quand ils la disaient, et s'il t'arrive de la répéter à ton tour. |
| `ressembler-aux-parents` | *nouvelle (lot 1)* | En quoi ressembles-tu à ton père ou à ta mère ? Raconte ce que tu as gardé d'eux, et ce que tu as choisi de faire autrement. |
| `parents-autrement` | *nouvelle (lot 1)* | À quel moment as-tu regardé tes parents autrement qu'avec des yeux d'enfant ? Raconte ce qui s'est passé, et ce que tu as compris d'eux ce jour-là. |
| `grande-fete-de-famille` | *nouvelle (lot 1)* | Raconte une grande fête de famille, un jour où tout le monde était réuni : l'occasion, la table, les rires, ceux qui étaient là. |
| `plat-des-grandes-occasions` | *nouvelle (lot 1)* | Quel était le plat des grandes occasions dans ta famille ? Raconte qui le préparait, et les fêtes où il arrivait sur la table. |
| `argent-a-la-maison` | *nouvelle (lot 1)* | Comment vivait-on avec l'argent à la maison quand tu étais enfant ? Raconte ce qu'on pouvait s'offrir, ce dont on se passait, et comment on en parlait. |
| `recette-de-famille` | *nouvelle (lot 1)* | Donne-nous une recette de famille, avec tes mots et tes tours de main. Raconte aussi d'où elle vient, et pour quelles occasions on la faisait. |

### Jeunesse

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `adulte-qui-a-compte` | Quel professeur ou adulte, en dehors de tes parents, a compté pour toi ? | Quel professeur ou quel adulte, en dehors de tes parents, a compté pour toi ? Raconte qui c'était, et ce qu'il ou elle t'a apporté. |
| `musique-quinze-ans` | Quelle musique écoutais-tu à quinze ans, et où l'écoutais-tu ? | Quelle musique écoutais-tu à quinze ans ? Raconte où tu l'écoutais, avec qui, et ce qu'elle réveille en toi aujourd'hui. |
| `premiere-liberte` | Raconte ta première grande liberté : un voyage, une sortie, un départ. | Raconte ta première grande liberté : un voyage, une sortie, un départ. Qu'as-tu ressenti ce jour-là ? |
| `reve-metier` | Quel était ton rêve de métier à dix-huit ans ? | À dix-huit ans, de quoi rêvais-tu de faire ta vie ? Raconte ce rêve, d'où il venait, et ce qu'il est devenu. |
| `mode-jeunesse` | Quelle mode ou quelle habitude de ta jeunesse ferait rire les jeunes d'aujourd'hui ? | Quelle mode de ta jeunesse ferait sourire les jeunes d'aujourd'hui ? Raconte comment tu t'habillais, et ce qui était « dans le vent ». |
| `ami-perdu-de-vue` | Raconte un ami d'enfance ou de jeunesse que tu as perdu de vue. Que faisiez-vous ensemble ? | Te souviens-tu d'un ami ou d'une amie de jeunesse que tu as perdu de vue ? Raconte ce que vous faisiez ensemble, et comment vos chemins se sont séparés. |
| `evenement-du-monde` | Quel événement du monde t'a marqué{\|e} quand tu étais jeune, et où étais-tu ce jour-là ? | Quel événement du monde t'a marqué{\|e} quand tu étais jeune ? Raconte où tu étais quand tu l'as appris, et ce qu'on en disait autour de toi. |
| `quitter-l-ecole` | *nouvelle (lot 1)* | Quand as-tu quitté l'école ? Raconte comment ça s'est passé, si tu l'as choisi, et ce que tu as fait ensuite. |
| `bals-et-fetes` | *nouvelle (lot 1)* | Où allais-tu danser ou faire la fête quand tu étais jeune ? Raconte une soirée : la musique, les amis, ce qu'on portait. |
| `vetement-adore` | *nouvelle (lot 1)* | Te souviens-tu d'un vêtement que tu as adoré porter ? Raconte d'où il venait, et les moments où tu le mettais. |
| `service-militaire` | *nouvelle (lot 1)* | Raconte ton service militaire : où tu étais, avec qui, et ce que tu en as gardé. |

### Métier

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `premier-jour-travail` | Raconte ton premier jour de travail. | Te souviens-tu de ton premier jour de travail ? Raconte où c'était, les gens que tu as rencontrés, et ce que tu ressentais. |
| `metier-fierte` | Quel a été le métier dont tu es le plus {fier\|fière\|fier·ère}, et pourquoi ? | Raconte ce dont tu es le plus {fier\|fière\|fier·ère} dans ton travail : un moment, une réussite, quelqu'un que tu as aidé. |
| `travail-et-les-gens` | Qu'est-ce que ton travail t'a appris sur les gens ? | Raconte quelqu'un que tu as rencontré par ton travail et que tu n'as jamais oublié. Qu'est-ce qui le rendait inoubliable ? |
| `journee-de-travail` | Raconte une journée de travail ordinaire à l'époque où tu étais le plus occupé{\|e}. | Raconte une journée de travail ordinaire, à l'époque où tu étais le plus occupé{\|e} : du réveil au retour à la maison. |
| `choix-professionnel` | Y a-t-il un choix professionnel que tu referais autrement ? | Y a-t-il un choix professionnel que tu referais autrement ? Raconte ce qui s'est passé, et ce que tu en penses aujourd'hui. |
| `qui-a-donne-sa-chance` | Qui t'a donné ta chance, et comment ? | Qui t'a donné ta chance un jour ? Raconte comment c'est arrivé, et ce que ça a changé pour toi. |
| `premier-salaire` | *nouvelle (lot 1)* | Te souviens-tu de ton tout premier salaire ? Raconte ce que tu en as fait, et ce que tu as ressenti en le recevant. |

### Amour

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `rencontre-conjoint` | Comment as-tu rencontré la personne qui a partagé ta vie ? | Comment as-tu rencontré la personne qui a partagé ta vie ? Raconte ce jour-là, ta première impression, et ce qui t'a plu. |
| `mariage-ou-vie-commune` | Raconte ton mariage, ou le jour où vous avez décidé de vivre ensemble. | Raconte ton mariage, ou le jour où vous avez décidé de vivre ensemble : les préparatifs, les invités, un détail que tu n'as jamais oublié. |
| `premier-enfant` | Qu'as-tu ressenti en tenant ton premier enfant dans tes bras ? | Qu'as-tu ressenti en tenant ton premier enfant dans tes bras ? Raconte ce jour-là, et ceux qui ont suivi. |
| `conseil-couple` | Quel conseil t'a-t-on donné sur le couple qui s'est révélé vrai ? | Quel conseil t'a-t-on donné sur la vie à deux qui s'est révélé vrai ? Raconte qui te l'a donné, et quand tu l'as compris. |
| `dispute-fou-rire` | Raconte une dispute qui s'est terminée en fou rire. | Te souviens-tu d'une dispute qui s'est terminée en fou rire ? Raconte-la, du début à la fin. |
| `avec-les-enfants` | Que faisais-tu avec tes enfants quand ils étaient petits, que tu aimerais qu'ils se rappellent ? | Que faisais-tu avec tes enfants quand ils étaient petits, que tu aimerais qu'ils se rappellent ? Raconte un de ces moments. |

### Lieux

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `lieu-qui-manque` | Quel lieu te manque le plus, et que reste-t-il de lui aujourd'hui ? | Quel lieu te manque le plus ? Raconte-le comme si on y était, et dis-nous ce qu'il en reste aujourd'hui. |
| `voyage-qui-change` | Raconte un voyage qui t'a changé{\|e}. | Raconte un voyage, ou un départ, qui t'a changé{\|e} : où tu allais, ce que tu y as trouvé, et ce que tu en as rapporté. |
| `ville-village-avant` | À quoi ressemblait ta ville ou ton village quand tu étais jeune ? Qu'est-ce qui a disparu ? | À quoi ressemblait ta ville ou ton village quand tu étais jeune ? Raconte les rues, les commerces, les gens, et ce qui a disparu depuis. |
| `maison-quittee` | Raconte une maison où tu as vécu et qu'il a fallu quitter. | Raconte une maison où tu as vécu et qu'il a fallu quitter. Qu'est-ce que tu y as laissé, et qu'est-ce que tu as emporté ? |
| `premiere-television` | *nouvelle (lot 1)* | Te souviens-tu de la première télévision que tu as vue ? Raconte chez qui c'était, ce qu'on regardait, et l'effet que ça faisait. |
| `telephone-a-la-maison` | *nouvelle (lot 1)* | Quand le téléphone est-il arrivé chez vous ? Raconte comment on s'en servait, et un appel que tu n'as jamais oublié. |
| `premiere-voiture` | *nouvelle (lot 1)* | Raconte ta première voiture, ou le premier voyage en voiture dont tu te souviens : la route, les passagers, les pannes peut-être. |
| `ce-qui-a-disparu` | *nouvelle (lot 1)* | Qu'est-ce qui faisait partie de ta vie d'enfant et qui a complètement disparu aujourd'hui ? Raconte-le à ceux qui ne l'ont jamais connu. |
| `prix-d-autrefois` | *nouvelle (lot 1)* | Combien coûtaient le pain, une place de cinéma ou un timbre quand tu étais jeune ? Raconte ce qu'on pouvait s'offrir, et ce qui était un luxe. |
| `changement-etonnant` | *nouvelle (lot 1)* | Quel changement du monde t'a le plus étonné{\|e} au cours de ta vie ? Raconte comment tu l'as vu arriver. |
| `voisins-inoubliables` | *nouvelle (lot 1)* | Te souviens-tu de voisins inoubliables ? Raconte qui ils étaient, et ce qui se passait entre vos maisons. |
| `ou-je-vis` | *nouvelle (lot 1)* | Qu'aimes-tu dans l'endroit où tu vis aujourd'hui ? Raconte-nous ton coin préféré, et comment tu y es arrivé{\|e}. |
| `objet-qui-a-une-histoire` | *nouvelle (lot 1)* | Quel objet de ta maison a une histoire ? Raconte-la : d'où il vient, et pourquoi tu y tiens. |
| `premier-train-ou-avion` | *nouvelle (lot 1)* | Te souviens-tu de la première fois où tu as pris le train ou l'avion ? Raconte le départ, le voyage, et ce que tu as ressenti. |

### Joies

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `plus-beau-jour` | Quel a été le plus beau jour de ta vie, ou l'un des plus beaux ? | Quel a été l'un des plus beaux jours de ta vie ? Raconte-le du matin au soir, avec ceux qui étaient là. |
| `plus-grande-fierte` | De quoi es-tu le plus {fier\|fière\|fier·ère}, sans fausse modestie ? | De quoi es-tu le plus {fier\|fière\|fier·ère}, sans fausse modestie ? Raconte comment tu y es arrivé{\|e}. |
| `cadeau-touchant` | Quel cadeau reçu t'a le plus touché{\|e} ? | Quel cadeau t'a le plus touché{\|e} ? Raconte qui te l'a offert, à quelle occasion, et pourquoi il comptait autant. |
| `fou-rire` | Raconte un fou rire dont tu te souviens encore. | Te souviens-tu d'un fou rire qui te fait encore rire aujourd'hui ? Raconte-nous ce qui s'est passé. |
| `petite-habitude-heureuse` | Quelle petite habitude te rend heureu{x\|se\|x·se} au quotidien ? | Quelle petite habitude te rend heureu{x\|se\|x·se} au quotidien ? Raconte comment elle est entrée dans ta vie. |
| `passion-d-une-vie` | *nouvelle (lot 1)* | Quelle passion t'a accompagné{\|e} toute ta vie ? Raconte comment elle est née, et ce qu'elle t'a apporté. |
| `fait-de-mes-mains` | *nouvelle (lot 1)* | Qu'as-tu aimé faire de tes mains : jardiner, cuisiner, coudre, bricoler ? Raconte ce dont tu es le plus {fier\|fière\|fier·ère}. |
| `livre-film-marquant` | *nouvelle (lot 1)* | Quel livre, quel film ou quelle émission t'a marqué{\|e} ? Raconte quand tu l'as découvert, et ce qu'il a remué en toi. |
| `sport-ou-jeu` | *nouvelle (lot 1)* | Quel sport ou quel jeu as-tu pratiqué avec passion ? Raconte une partie ou un match dont tu te souviens encore. |
| `chanson-par-coeur` | *nouvelle (lot 1)* | Quelle chanson connais-tu encore par cœur ? Raconte ce qu'elle te rappelle, et chantes-en un bout si le cœur t'en dit. |
| `animal-qui-a-compte` | *nouvelle (lot 1)* | Raconte un animal qui a compté dans ta vie : son nom, son caractère, et un souvenir avec lui. |
| `amitie-fidele` | *nouvelle (lot 1)* | Raconte l'amitié la plus fidèle de ta vie d'adulte : comment elle est née, et ce qui l'a fait durer. |
| `journee-d-aujourd-hui` | *nouvelle (lot 1)* | Raconte-nous une journée ordinaire d'aujourd'hui, du réveil au coucher : tes habitudes, tes petits plaisirs. |
| `photo-preferee` | *nouvelle (lot 1)* | Décris une photo de toi que tu aimes particulièrement. Raconte où et quand elle a été prise, et ce qui se passait juste avant. |
| `saison-preferee` | *nouvelle (lot 1)* | Quelle est ta saison préférée ? Raconte un souvenir qui s'y attache, avec ses couleurs et ses odeurs. |
| `rencontre-celebre` | *nouvelle (lot 1)* | As-tu déjà croisé quelqu'un de célèbre ? Raconte la rencontre, même si elle n'a duré qu'une minute. |
| `grosse-betise-adulte` | *nouvelle (lot 1)* | Quelle est la plus grosse bêtise que tu aies faite une fois adulte ? Raconte-nous, on ne le répétera à personne. |
| `ce-qui-fait-rire` | *nouvelle (lot 1)* | Qu'est-ce qui te fait rire, aujourd'hui ? Raconte la dernière fois que tu as ri de bon cœur. |

### Épreuves

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `epreuve-la-plus-dure` | Quelle a été l'épreuve la plus dure, et qu'est-ce qui t'a aidé{\|e} à la traverser ? | Quelle a été l'épreuve la plus dure de ta vie ? Raconte ce que tu as envie d'en dire, et ce qui t'a aidé{\|e} à la traverser. |
| `revoir-une-derniere-fois` | Y a-t-il une personne que tu aurais aimé revoir une dernière fois ? Que lui dirais-tu ? | Y a-t-il une personne que tu aurais aimé revoir une dernière fois ? Raconte qui elle était, et ce que tu lui dirais. |
| `grande-peur` | Raconte un moment où tu as eu très peur. | Raconte un moment où tu as eu très peur. Où étais-tu, et comment t'en es-tu sorti{\|e} ? |
| `decision-difficile` | Quelle décision difficile as-tu prise, et la referais-tu ? | Quelle décision difficile as-tu dû prendre ? Raconte ce qui t'a fait choisir, et si tu la reprendrais aujourd'hui. |
| `lecon-echec` | Qu'as-tu appris d'un échec ? | Raconte un échec qui t'a finalement servi. Qu'est-ce qu'il t'a appris ? |
| `la-guerre` | *nouvelle (lot 1)* | Qu'as-tu vécu de la guerre, ou que t'en a-t-on raconté dans la famille ? Raconte ce que tu as envie de transmettre. |

### Convictions et valeurs

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `vie-reussie` | Qu'est-ce qui, selon toi, fait une vie réussie ? | Raconte un moment où tu t'es dit : « Ça, je l'ai réussi. » Qu'est-ce qui rendait ce moment si important ? |
| `valeur-transmise` | Quelle est la valeur que tu as essayé de transmettre avant toutes les autres ? | Quelle valeur as-tu essayé de transmettre avant toutes les autres ? Raconte d'où elle te vient, et un moment où tu l'as vue vivre chez les tiens. |
| `croyance-changee` | Qu'est-ce que tu crois aujourd'hui que tu ne croyais pas à vingt ans ? | Qu'est-ce que tu crois aujourd'hui que tu ne croyais pas à vingt ans ? Raconte ce qui t'a fait changer d'avis. |
| `priere-poeme-chanson` | Y a-t-il une prière, un poème ou une chanson qui t'accompagne ? | Y a-t-il une prière, un poème ou une chanson qui t'accompagne ? Raconte-nous d'où ça vient, et dans quels moments tu y reviens. |
| `monde-des-petits-enfants` | Que penses-tu du monde que tes petits-enfants vont connaître ? | Que penses-tu du monde que les générations qui viennent vont connaître ? Raconte ce qui t'inquiète, et ce qui te donne de l'espoir. |
| `apprecier-avec-l-age` | *nouvelle (lot 1)* | Qu'est-ce que l'âge t'a appris à apprécier, que tu ne voyais pas avant ? Raconte un moment où tu t'en es rendu compte. |
| `vieillir` | *nouvelle (lot 1)* | Qu'y a-t-il de plus dur, et de plus doux, dans le fait de vieillir ? Raconte-nous ce que tu as envie d'en partager. |

### Ce qui reste

| Slug | Aujourd'hui | Proposé |
|---|---|---|
| `geste-recette-secret` | Y a-t-il une recette, un geste ou un savoir-faire que toi seul{\|e} sais faire ? Décris-le pas à pas. | Y a-t-il une recette, un geste ou un savoir-faire que toi seul{\|e} sais faire ? Explique-le-nous pas à pas, comme si on était à côté de toi. |
| `encore-envie` | *nouvelle (lot 1)* | Qu'as-tu encore envie de faire, de voir ou d'apprendre ? Raconte ce qui te fait envie, et pourquoi. |
| `conseil-dix-huit-ans` | Quel conseil donnerais-tu à ton petit-fils ou ta petite-fille pour ses dix-huit ans ? | Quel conseil donnerais-tu à quelqu'un qui a dix-huit ans aujourd'hui ? Raconte ce que toi, tu aurais aimé entendre à cet âge. |
| `ce-quon-retienne` | Qu'aimerais-tu que l'on retienne de toi ? | Qu'aimerais-tu que l'on retienne de toi ? Raconte un souvenir qui te ressemble. |
| `histoire-jamais-racontee` | Quelle histoire n'as-tu jamais racontée à personne et pourrais-tu raconter aujourd'hui ? | Y a-t-il une histoire que tu n'as jamais racontée à personne, et que tu pourrais raconter aujourd'hui ? Si tu le veux, c'est le moment. |
| `message-dans-cinquante-ans` | Si tu pouvais laisser un message à ceux qui écouteront ces enregistrements dans cinquante ans, que dirais-tu ? | Si tu pouvais laisser un message à ceux qui écouteront ces enregistrements dans cinquante ans, que leur dirais-tu ? Parle-leur comme s'ils étaient devant toi. |
