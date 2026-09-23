# Corpus v2 — lot 3 : les questions sur l'acheteur

23 septembre 2026. Les questions qui parlent de la personne qui a offert le livre. Écrites au tutoiement, comme les lots précédents ; le vouvoiement s'écrira à l'intégration.

## 1. Pourquoi ce bloc

La personne qui offre entend sa mère raconter le jour de sa naissance, et le lit dans le livre, avec un code qui rejoue sa voix. C'est la page que seul ce livre-là peut contenir. C'est aussi le bloc le plus délicat : le livre sera lu par toute la famille.

## 2. Les règles, décidées au premier point

1. **Sur l'acheteur seul, ou sur tous.** Le tunnel demande : « Des questions sur vous, ou sur tous ses enfants ? » Si c'est « sur tous », ce bloc ne part pas : le bloc Enfants du lot 2 parle déjà de chacun (`chaque-enfant-petit`, `choix-des-prenoms`). Une fille qui a deux frères ne reçoit pas un livre qui parle d'elle seule sans l'avoir demandé.
2. **Un dosage** : 4 à 6 questions sur 52, jamais la première. Le narrateur doit d'abord sentir que le livre parle de lui.
3. **Des formulations par lien** : l'enfant, le petit-enfant, puis quatre questions qui valent pour tout lien (neveu, amie, filleul…).
4. **Aucun bloc quand on achète pour soi.**

**Pas de bloc pour le conjoint.** Quand c'est le mari ou la femme qui offre, le bloc Couple du lot 2 parle déjà de lui ou d'elle (« la personne qui a partagé ta vie ») : un second bloc ferait raconter deux fois la même rencontre.

## 3. Ce que ça demande à la structure

**Un marqueur de plus.** Les accolades simples accordent déjà au genre de la narratrice (`né{|e}`). Les **accolades doubles** parlent de l'acheteur :

| Marqueur | Donne | Genre inconnu |
|---|---|---|
| `{{prénom}}` | Claire | *(la question ne part pas)* |
| `{{\|e}}` | née / né | né·e |
| `{{il\|elle\|il ou elle}}` | elle / il | il ou elle |

Une question peut mêler les deux : « ce qui t'a frappé`{|e}` chez `{{lui|elle|lui ou elle}}` » accorde *frappée* à la narratrice et *elle* à Claire.

**Trois conditions nouvelles** : `about_buyer` (le tunnel a dit « sur moi », et ce n'est pas un achat pour soi), `buyer_is_child`, `buyer_is_grandchild`. Toutes les questions du bloc portent `about_buyer`.

**Un garde-fou** : sans prénom de l'acheteur, une question qui contient `{{prénom}}` ne part jamais. Jusqu'à ce que le tunnel existe, aucun projet n'a de profil : ces questions seront en base mais ne partiront chez personne — pas même chez la narratrice de production.

## 4. Les questions

Chaque ligne montre le texte tel qu'il est écrit, puis tel qu'il se lirait pour une narratrice dont la fille Claire a offert le livre.


### L'acheteur est son enfant — `buyer_is_child`

| Slug | Question | Lu pour Claire | Thème | Diff. | Ton |
|---|---|---|---|---|---|
| `prenom-naissance` | Raconte le jour où {{prénom}} est né{{\|e}} : l'attente, l'arrivée, et la première fois que tu l'as vu{{\|e}}. | Raconte le jour où Claire est née : l'attente, l'arrivée, et la première fois que tu l'as vue. | love | 2 | tendre |
| `prenom-le-choix-du-prenom` | Pourquoi avoir choisi le prénom {{prénom}} ? Raconte les idées écartées, et ce que ce prénom voulait dire pour toi. | Pourquoi avoir choisi le prénom Claire ? Raconte les idées écartées, et ce que ce prénom voulait dire pour toi. | love | 1 | léger |
| `prenom-enfant` | À quoi ressemblait {{prénom}} quand {{il\|elle\|il ou elle}} était petit{{\|e}} ? Raconte une manie, un mot d'enfant ou une bêtise qui te fait encore sourire. | À quoi ressemblait Claire quand elle était petite ? Raconte une manie, un mot d'enfant ou une bêtise qui te fait encore sourire. | love | 1 | léger |
| `prenom-ce-qu-il-ignore` | Qu'est-ce que {{prénom}} ne sait pas de sa petite enfance, et que tu pourrais lui raconter aujourd'hui ? | Qu'est-ce que Claire ne sait pas de sa petite enfance, et que tu pourrais lui raconter aujourd'hui ? | legacy | 2 | tendre |
| `prenom-fierte` | Raconte un moment où tu as été particulièrement {fier\|fière\|fier·ère} de {{prénom}}. Qu'est-ce que tu ne lui as peut-être jamais dit ? | Raconte un moment où tu as été particulièrement fière de Claire. Qu'est-ce que tu ne lui as peut-être jamais dit ? | love | 3 | tendre |
| `prenom-souhait-d-un-parent` | Que souhaites-tu à {{prénom}} pour la suite de sa vie ? Parle-lui comme si {{il\|elle\|il ou elle}} était devant toi. | Que souhaites-tu à Claire pour la suite de sa vie ? Parle-lui comme si elle était devant toi. | legacy | 5 | tendre |

### L'acheteur est son petit-enfant — `buyer_is_grandchild`

| Slug | Question | Lu pour Claire | Thème | Diff. | Ton |
|---|---|---|---|---|---|
| `prenom-naissance-petit-enfant` | Te souviens-tu du jour où {{prénom}} est né{{\|e}} ? Raconte comment tu l'as appris, et la première fois que tu l'as tenu{{\|e}} dans tes bras. | Te souviens-tu du jour où Claire est née ? Raconte comment tu l'as appris, et la première fois que tu l'as tenue dans tes bras. | love | 2 | tendre |
| `prenom-petit-avec-moi` | Qu'aimais-tu faire avec {{prénom}} quand {{il\|elle\|il ou elle}} était petit{{\|e}} ? Raconte un moment que tu gardes précieusement. | Qu'aimais-tu faire avec Claire quand elle était petite ? Raconte un moment que tu gardes précieusement. | love | 1 | léger |
| `prenom-ses-parents-enfants` | Raconte à {{prénom}} l'enfant qu'était son père ou sa mère : ses bêtises, ses manies, ce qui te faisait rire. | Raconte à Claire l'enfant qu'était son père ou sa mère : ses bêtises, ses manies, ce qui te faisait rire. | family_origins | 1 | léger |
| `prenom-souhait-d-un-grand-parent` | Que souhaites-tu à {{prénom}} pour sa vie ? Parle-lui comme si {{il\|elle\|il ou elle}} était devant toi. | Que souhaites-tu à Claire pour sa vie ? Parle-lui comme si elle était devant toi. | legacy | 5 | tendre |

### Quel que soit le lien — `about_buyer`

| Slug | Question | Lu pour Claire | Thème | Diff. | Ton |
|---|---|---|---|---|---|
| `prenom-premier-souvenir` | Quel est ton tout premier souvenir de {{prénom}} ? Raconte où c'était, et ce qui t'a frappé{\|e} chez {{lui\|elle\|lui ou elle}}. | Quel est ton tout premier souvenir de Claire ? Raconte où c'était, et ce qui t'a frappée chez elle. | joys | 1 | léger |
| `prenom-moment-ensemble` | Raconte un moment passé avec {{prénom}} que tu n'oublieras jamais. Où étiez-vous, et qu'est-ce qui le rend si précieux ? | Raconte un moment passé avec Claire que tu n'oublieras jamais. Où étiez-vous, et qu'est-ce qui le rend si précieux ? | joys | 2 | tendre |
| `prenom-ce-qu-il-m-a-apporte` | Qu'est-ce que {{prénom}} t'a apporté, sans peut-être le savoir ? Raconte un moment où tu l'as senti. | Qu'est-ce que Claire t'a apporté, sans peut-être le savoir ? Raconte un moment où tu l'as senti. | joys | 3 | tendre |
| `prenom-ce-que-je-veux-qu-il-sache` | Qu'aimerais-tu que {{prénom}} sache de toi, et que tu ne lui as jamais dit ? | Qu'aimerais-tu que Claire sache de toi, et que tu ne lui as jamais dit ? | legacy | 5 | tendre |

## 5. Ce que chacun recevra

| Lien | Questions disponibles | Dosage |
|---|---|---|
| Enfant (« ma mère », « mon père ») | 6 + 4 = 10 | 4 à 6 retenues |
| Petit-enfant | 4 + 4 = 8 | 4 à 6 retenues |
| Autre lien (neveu, amie…) | 4 | 4 |
| Conjoint | — | le bloc Couple |
| Achat pour soi | — | — |

**L'ordre** : une première question sur l'acheteur vers la sixième semaine — le souvenir le plus doux, `prenom-premier-souvenir` ou `prenom-enfant` —, puis une toutes les huit semaines environ, les souhaits (`difficulté 5`) en fin de parcours. C'est le choix des questions, au lot suivant, qui tiendra ce rythme projet par projet ; l'ordre du corpus ne fait que le préparer.

## 6. À décider

1. Les questions à retirer ou reformuler — un slug suffit.
2. Pas de bloc conjoint : d'accord ?
3. La syntaxe `{{…}}` pour l'acheteur : d'accord ?

## 7. Intégration (23 septembre 2026)

Validé sans retouche : pas de bloc conjoint, syntaxe `{{…}}` retenue. Le corpus compte 164 questions.

- `QuestionWording` résout les accolades doubles avant les simples (`{{|e}}` contient `{|e}`), et laisse `{{prénom}}` tel quel quand le prénom est inconnu. `problems()` refuse une variable inconnue et un marqueur d'acheteur mal formé ; un test vérifie que `{{prénom}}` et la condition `about_buyer` vont toujours ensemble.
- **Garde-fou** : `Question::scopeSendableWithoutProfile()` écarte toute question `about_buyer`. Le choix des questions (`PickNextQuestion`, la file de l'espace) et l'aperçu du quiz passent par lui : aucune des quatorze questions ne peut partir tant que le choix selon le profil n'existe pas.
- **L'ordre** : les questions existantes gardent le leur ; les quatorze s'intercalent (ordres en 5 : 65, 145, 225…), du plus doux au souhait, en alternant les liens pour que chaque parcours progresse. Le dosage — 4 à 6 sur 52, la première vers la sixième semaine — sera tenu par le choix selon le profil.
