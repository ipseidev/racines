# Audit du corpus v1 — 60 questions

23 septembre 2026. Point de départ du corpus v2 et du tunnel de personnalisation d'après-achat. Source : `database/seeders/QuestionSeeder.php`, identique à l'annexe A.

## 1. Les étiquettes proposées

Quatre familles. Les deux premières décident si une question **part ou non** ; les deux autres décident **quand** et **pour qui**.

### A. Conditions d'application — la question est retirée si la condition est fausse

| Étiquette | Sens | Question du tunnel qui la renseigne |
|---|---|---|
| `couple` | A vécu une vie de couple (en couple, veuf·ve ou séparé·e) | Situation de couple |
| `enfants` | A des enfants | Enfants |
| `petits_enfants` | A des petits-enfants | Petits-enfants |
| `vie_pro` | A exercé un métier | Vie professionnelle |
| `parents_connus` | A grandi avec ses parents ou les a connus | (défaut : vrai ; décochable) |

« Souple » dans le tableau : la question suppose la condition mais une variante la rend universelle.

### B. Sujets sensibles — la question est retirée si l'acheteur coche « à éviter »

`deuil` · `perte_enfant` · `separation` · `guerre` · `maladie` · `religion` · `fin_de_vie` (la question fait penser à la mort de la personne interrogée).

### C. Ton

`léger` (on sourit en répondant) · `tendre` (émotion douce) · `grave` (on touche à la douleur ou au bilan). Plus fin que la difficulté 1-5, qui mélange intimité et effort.

### D. Signaux d'écriture

`court` : risque de réponse en une phrase, peu de matière pour le livre · `abstrait` : appelle une opinion, pas un récit · `médian` : contient un point médian (« né·e ») · `mal classé` : thème à corriger.

## 2. Les 60 questions étiquetées

| # | Slug | Thème actuel | Diff. | Conditions | Sensibles | Ton | Remarques |
|---|---|---|---|---|---|---|---|
| 10 | `naissance-recit` | childhood | 1 | — | — | léger | médian |
| 20 | `premier-souvenir` | childhood | 1 | — | — | léger | |
| 30 | `maison-enfance` | childhood | 1 | — | — | léger | Excellente ouverture |
| 40 | `betise-enfant` | childhood | 1 | — | — | léger | |
| 50 | `apprendre-velo-nager-lire` | childhood | 1 | — | — | léger | |
| 60 | `odeur-enfance` | childhood | 1 | — | — | léger | |
| 70 | `plat-enfance` | childhood | 1 | — | fin_de_vie | tendre | « une dernière fois » à la 7e question : reformuler en « regoûter » |
| 80 | `dimanche-dix-ans` | childhood | 1 | — | — | léger | |
| 90 | `grands-parents` | family_origins | 2 | — | — | tendre | Fonctionne même si on les a peu connus |
| 100 | `nom-de-famille` | family_origins | 2 | — | — | léger | Ambigu pour une femme mariée : nom de naissance ou d'usage ? |
| 110 | `expression-famille` | family_origins | 2 | — | — | léger | |
| 120 | `conteur-famille` | family_origins | 2 | — | — | tendre | |
| 130 | `objet-transmis` | family_origins | 2 | — | — | tendre | La réponse « non » fait tomber la question : variante « un objet auquel vous tenez » |
| 140 | `tradition-gardee` | family_origins | 2 | — | — | tendre | |
| 150 | `adulte-qui-a-compte` | youth | 2 | — | — | tendre | Seule question sur l'école |
| 160 | `musique-quinze-ans` | youth | 2 | — | — | léger | court |
| 170 | `premiere-liberte` | youth | 2 | — | — | léger | |
| 180 | `reve-metier` | youth | 2 | — | — | léger | court |
| 190 | `mode-jeunesse` | youth | 2 | — | — | léger | |
| 200 | `ami-perdu-de-vue` | youth | 2 | — | deuil | tendre | L'ami peut être mort |
| 210 | `evenement-du-monde` | youth | 2 | — | guerre | tendre→grave | médian. Pour un·e 85 ans, c'est la guerre ou l'Algérie : difficulté 2 sous-estimée |
| 220 | `premier-jour-travail` | work | 2 | vie_pro | — | léger | |
| 230 | `metier-fierte` | work | 2 | vie_pro | — | tendre | Suppose plusieurs métiers ; médian |
| 240 | `travail-et-les-gens` | work | 2 | vie_pro | — | tendre | abstrait |
| 250 | `journee-de-travail` | work | 2 | vie_pro | — | léger | médian |
| 260 | `choix-professionnel` | work | 3 | vie_pro | — | tendre | court (« non ») |
| 270 | `qui-a-donne-sa-chance` | work | 2 | vie_pro (souple) | — | tendre | Vaut aussi hors travail |
| 280 | `rencontre-conjoint` | love | 3 | couple | deuil, separation | tendre | Variante veuvage/séparation à écrire |
| 290 | `mariage-ou-vie-commune` | love | 3 | couple | separation | tendre | |
| 300 | `premier-enfant` | love | 3 | enfants | perte_enfant | tendre | |
| 310 | `conseil-couple` | love | 3 | couple | separation | léger | |
| 320 | `dispute-fou-rire` | love | 3 | couple (souple) | — | léger | Doublon partiel de `fou-rire` |
| 330 | `qualite-pere-mere` | love | 3 | parents_connus | deuil | tendre | mal classé → family_origins. Seule question sur les parents |
| 340 | `avec-les-enfants` | love | 3 | enfants | — | tendre | |
| 350 | `surnoms-famille` | love | 2 | — | — | léger | mal classé → family_origins |
| 360 | `lieu-qui-manque` | places | 3 | — | — | tendre | |
| 370 | `voyage-qui-change` | places | 3 | — | — | tendre | médian. Suppose d'avoir voyagé (souple : « un départ ») |
| 380 | `ville-village-avant` | places | 2 | — | — | léger | |
| 390 | `cachette` | places | 2 | — | — | léger | médian. Plutôt childhood |
| 400 | `maison-quittee` | places | 3 | — | — | tendre | |
| 410 | `plus-beau-jour` | joys | 3 | — | — | tendre | |
| 420 | `plus-grande-fierte` | joys | 3 | — | — | tendre | médian |
| 430 | `cadeau-touchant` | joys | 3 | — | — | tendre | médian |
| 440 | `fou-rire` | joys | 2 | — | — | léger | |
| 450 | `petite-habitude-heureuse` | joys | 2 | — | — | léger | court ; médian. Seule question sur le présent |
| 460 | `epreuve-la-plus-dure` | hardships | 4 | — | deuil, maladie, guerre | grave | médian |
| 470 | `revoir-une-derniere-fois` | hardships | 4 | — | deuil | grave | |
| 480 | `grande-peur` | hardships | 4 | — | guerre, maladie | grave | |
| 490 | `decision-difficile` | hardships | 4 | — | — | grave | |
| 500 | `lecon-echec` | hardships | 4 | — | — | grave | abstrait |
| 510 | `vie-reussie` | beliefs_values | 4 | — | — | grave | abstrait : donne une opinion, pas une histoire |
| 520 | `valeur-transmise` | beliefs_values | 4 | enfants (souple) | — | tendre | |
| 530 | `croyance-changee` | beliefs_values | 4 | — | religion | grave | abstrait |
| 540 | `priere-poeme-chanson` | beliefs_values | 4 | — | religion | tendre | |
| 550 | `monde-des-petits-enfants` | beliefs_values | 4 | petits_enfants | — | grave | abstrait ; variante « les générations qui viennent » |
| 560 | `conseil-dix-huit-ans` | legacy | 5 | petits_enfants | — | tendre | Variante « à un jeune de dix-huit ans » |
| 570 | `ce-quon-retienne` | legacy | 5 | — | fin_de_vie | grave | |
| 580 | `geste-recette-secret` | legacy | 3 | — | — | léger | médian. Difficulté 3 surestimée : très bonne question précoce |
| 590 | `histoire-jamais-racontee` | legacy | 5 | — | — | grave | |
| 600 | `message-dans-cinquante-ans` | legacy | 5 | — | fin_de_vie | grave | |

## 3. Ce que le tableau montre

### 3.1 La marge ne permet aucune personnalisation

13 questions ont une condition stricte : `couple` ×3, `enfants` ×2, `petits_enfants` ×2, `vie_pro` ×5, `parents_connus` ×1. Avec 52 questions vendues :

| Profil | Questions retirées | Reste | Marge |
|---|---|---|---|
| Veuve, enfants et petits-enfants, a travaillé | 0 | 60 | 8 |
| Célibataire sans enfants, a travaillé | 7 | 53 | 1 |
| Femme au foyer, sans petits-enfants | 7 | 53 | 1 |
| Célibataire sans enfants, n'a pas travaillé | 12 | 48 | **−4** |

Et c'est **avant** tout sujet sensible : cocher « deuil » retire 5 questions de plus, « guerre » 3. Dès qu'on personnalise, il manque des questions.

### 3.2 Répartition des tons

23 légères, 26 tendres, 11 graves — toutes les graves aux difficultés 4-5. La fin de parcours est une suite ininterrompue de questions graves ou abstraites : c'est là qu'un narrateur décroche, et c'est là que le corpus a le moins de réserve légère pour relancer.

### 3.3 Les trous

Aucune question, ou une seule, sur :

- **les frères et sœurs** — zéro, alors que c'est l'un des sujets les plus riches ;
- **les parents** — une seule question (`qualite-pere-mere`), mal classée ;
- **l'école** — une (`adulte-qui-a-compte`) ;
- **les passions et loisirs** — zéro : jardin, sport, cuisine, bricolage, lecture, musique jouée ;
- **les amitiés adultes**, **les animaux**, **les fêtes** (Noël, communions, fêtes de village), **l'argent** (les prix d'autrefois, le premier salaire) ;
- **ce qu'on a vu arriver** — la télévision, le téléphone, la voiture : un filon pour les 80 ans et plus ;
- **l'histoire vécue** — la guerre, l'exode, un service militaire, une migration : une seule question, qui ne dit pas son nom ;
- **le présent** — une seule (`petite-habitude-heureuse`) : rien sur la vie d'aujourd'hui, vieillir, le quartier ;
- **l'acheteur lui-même** — zéro. Le tunnel saura que c'est sa fille qui offre ; on peut demander « Racontez le jour où Claire est née », « À quoi ressemblait-elle petite ? ». C'est probablement le levier de personnalisation le plus fort, et le plus émouvant pour celui qui a offert.

### 3.4 Trois défauts d'écriture, vérifiés dans le code

1. **Le tutoiement n'est jamais appliqué aux questions.** Le tunnel d'achat demande « tu » ou « vous » (`projects.address_form`) et la page d'enregistrement reçoit bien `addressForm`, mais `Story::questionText()` renvoie `questions.text` tel quel, écrit au vouvoiement. Une mère qu'on a choisi de tutoyer reçoit « Où êtes-vous né·e ».
2. **Le point médian** est dans 11 questions (« né·e », « fier·ère », « heureux·se »). Lu par une personne de 85 ans, c'est une gêne ; le tunnel peut savoir s'il s'agit de sa mère ou de son père et accorder.
3. **Le corpus n'existe qu'en français** (`questions.locale` vaut `fr` partout) alors que l'interface parle aussi italien et espagnol.

Ces trois points appellent la même réponse : une question porte **plusieurs textes** (vous/tu × féminin/masculin, par langue), et on choisit le bon au moment de l'envoi.

### 3.5 Petites corrections

- Reclasser `qualite-pere-mere` et `surnoms-famille` en `family_origins`, `cachette` en `childhood`.
- Descendre `geste-recette-secret` en difficulté 1-2, monter `evenement-du-monde` en 3.
- `dispute-fou-rire` et `fou-rire` se recouvrent : en garder une, ou ouvrir la première à toute dispute.
- Réancrer les questions abstraites sur un moment : « Racontez le moment où vous avez compris ce qui compte vraiment » plutôt que « Qu'est-ce qui fait une vie réussie ? ».

## 4. Ce que ça implique

**Pour le tunnel**, les questions à poser s'en déduisent directement : une femme ou un homme (pour l'accord, souvent déduit du lien : « ma mère ») · la situation de couple · des enfants · des petits-enfants · un métier ou non · où elle a grandi (ville, campagne, autre pays) · les sujets à éviter · les thèmes à mettre en avant · le ton souhaité. Le tutoiement est déjà demandé à l'achat.

**Pour le corpus v2**, la règle de taille : le profil le plus contraint (sans couple, sans enfants, sans métier, deux sujets sensibles cochés) doit encore disposer de 52 questions **et** d'un choix. Soit :

- un **socle universel** d'environ 90 questions, sans aucune condition ;
- des **blocs conditionnels** de 10 à 15 questions chacun (couple, enfants, petits-enfants, métier, migration, campagne…) ;
- un **bloc « sur l'acheteur »** paramétré par le lien et le prénom ;
- une **réserve légère** d'une vingtaine de questions pour les relances.

Environ **180 questions**, chacune avec ses variantes de texte.

## 5. À valider

1. Les quatre familles d'étiquettes, et la liste des conditions et des sujets sensibles.
2. Le bloc « sur l'acheteur » : on le fait ?
3. Les variantes tu/vous × féminin/masculin : quatre textes écrits à la main, ou un texte avec marqueurs résolus à l'envoi ?

## 6. Décisions (23 septembre 2026)

1. **Étiquettes validées** telles quelles. En code : `QuestionCondition` (`partner`, `children`, `grandchildren`, `career`, `knew_parents`), `SensitiveTopic` (`bereavement`, `child_loss`, `separation`, `war`, `illness`, `religion`, `end_of_life`), `QuestionTone` (`light`, `tender`, `grave`). Les conditions « souples » du tableau ne sont pas posées : elles attendent leur variante universelle.
2. **Bloc sur l'acheteur : oui**, avec quatre règles — sur un enfant ou sur tous (le tunnel demande), 4 à 6 questions sur 52 et jamais la première, formulations par lien, aucun bloc quand on achète pour soi.
3. **Variantes hybrides** : deux textes écrits à la main (`vous`, `tu`), le genre par marqueur (`né{|e}`, `{fier|fière|fier·ère}`), point médian seulement au genre inconnu.

## 7. Mise en production du corpus

La source est `database/corpus/questions.php`. Après chaque déploiement qui la modifie, en SSH :

```bash
ssh fenomn
cd ~/narrae.fr/current
php artisan corpus:sync            # à blanc : lire le rapport
php artisan corpus:sync --apply    # appliquer
```

La commande ne supprime jamais rien, ignore les questions absentes du fichier, refuse un fichier invalide, et dit combien d'histoires en attente liront un texte modifié. Les histoires enregistrées ont photographié leur intitulé (`stories.question_text`) et ne bougent plus.
