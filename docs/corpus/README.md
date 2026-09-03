# Corpus d'essai du rendu « Fluide »

Cinq mots à mot écrits pour la **lecture humaine** que demande le bloc 06. Ce
ne sont pas des tests automatiques : un test ne peut pas juger qu'une phrase
sonne encore comme la personne. Il faut des yeux, et ces cinq textes sont
choisis pour que ces yeux voient les cinq façons dont le rendu peut trahir.

```
sail artisan fluide:try --file=docs/corpus/essai-01-pain.txt \
    --question="Quelle odeur vous ramène à votre enfance ?"
```

| Fichier | Ce qu'il éprouve | Ce qui serait une faute |
|---|---|---|
| `essai-01-pain.txt` | Le cas ordinaire : hésitations, faux départs, un nom propre dit deux fois de deux façons | Inventer un détail ; « corriger » Saint-Aubin en Saint-Aubin-du-Cormier de son propre chef |
| `essai-02-sante.txt` | Un sujet **sensible** (santé), et une personne qui dit ne pas avoir compris ce qu'on lui a fait | Ne pas signaler le sujet sensible ; expliquer l'opération à sa place ; lui prêter un sentiment qu'elle n'exprime pas |
| `essai-03-conflit.txt` | Un **conflit familial**, avec une belle-sœur mise en cause et un frère mort | Adoucir « sa femme y était pour beaucoup » ; ne pas signaler le conflit ; ajouter une morale de réconciliation |
| `essai-04-court.txt` | Une réponse **très courte** — treize mots | Rallonger. Un rendu plus long que le mot à mot a inventé, par définition |
| `essai-05-date-impossible.txt` | Une **impossibilité factuelle** : né en 1870, mort en 1965, la personne avait douze ans | « Corriger » la date. Le prompt l'interdit : on ne corrige pas les souvenirs, même faux |

## Ce qu'on regarde, dans cet ordre

1. **A-t-il inventé ?** Chaque élément du rendu doit se retrouver dans le mot à
   mot. C'est le seul critère éliminatoire.
2. **Est-ce encore sa voix ?** Les tournures orales — « ma grand-mère, elle
   habitait », « je sais plus », « mais bon » — doivent rester. Un rendu qui
   remet la langue au propre remet aussi quelqu'un d'autre à la place.
3. **Les signalements** : les sujets sensibles doivent apparaître, parce que la
   famille doit être prévenue **avant** l'impression.
4. **La longueur** : au plus 20 % plus court. En dessous, il a résumé.
5. **Le titre** : court, tiré de ses mots, sans lyrisme.

## Première lecture, `fluide-v1` (3 septembre 2026)

Le critère éliminatoire est tenu : **rien d'inventé dans aucun des cinq**. Les
dates impossibles d'`essai-05` ne sont pas corrigées, `essai-04` n'est pas
rallongé, « sa femme y était pour beaucoup » d'`essai-03` n'est pas adouci, et
l'opération d'`essai-02` n'est pas expliquée à sa place. Deux défauts, tous
deux dans le prompt et non dans le code.

**1. La négation rétablie, une fois sur trois.** « je sais pas » devient « je
ne sais pas » (`essai-04`), « j'ai jamais bien compris » devient « je n'ai
jamais bien compris » (`essai-02`, deux fois) — mais « je voulais pas » et
« on s'est pas parlé » (`essai-03`), « il disait rien » (`essai-05`) et « je
sais plus » (`essai-01`) restent intacts. Trois corrections sur une dizaine
d'occasions, sans règle : c'est le pire résultat possible, parce que personne
ne peut raisonner dessus. Et c'est le point qui compte le plus pour « c'est
encore sa voix » : sur un récit de trois cents mots, dix « ne » réinsérés font
que la personne parle mieux dans le livre que dans la vie.

**2. Un « conflit familial » signalé là où il n'y en a pas.** `essai-05` — un
grand-père qui a fait la première guerre, qui criait la nuit, qui taillait des
sifflets — ressort avec le drapeau `conflict`. Or `conflict` veut dire brouille
entre des personnes de la famille, et il n'y en a aucune ici. La conséquence
n'est pas cosmétique : un drapeau sensible non arbitré **bloque le livre**
(`ComputeBookReadiness::isSensitiveUndecided`), et il annonce à la famille un
conflit imaginaire dans un souvenir tendre. Quelques fausses alertes de ce
genre et plus personne ne lit les vraies.

## Seconde lecture, `fluide-v2` (3 septembre 2026)

`fluide-v2` ajoute la règle de négation avec ses exemples, et définit chaque
drapeau sensible par **qui pourrait être exposé** au lieu de le nommer par son
sujet. Les cinq textes relus :

| | Négation | Drapeaux | Longueur | Verdict |
|---|---|---|---|---|
| `essai-01` | conservée | — | −7 % | bon ; avec un lexique, « à Saint-Aubin, enfin Saint-Aubin-du-Cormier » se replie sur la graphie retenue |
| `essai-02` | **corrigé** : les deux « ne » ont disparu | `health` tenu | −1 % | bon |
| `essai-03` | conservée | `conflict, money` tenus | +0 % | bon ; le reproche à la belle-sœur reste mot pour mot |
| `essai-04` | **corrigé** : « je sais pas » | — | +0 % | bon |
| `essai-05` | conservée | **corrigé** : plus aucun drapeau | +0 % | bon ; dates impossibles intactes |

Deux effets de bord, tous deux du côté sûr :

- `essai-04` garde maintenant le « Ben… » d'ouverture, que `v1` retirait comme
  tic de langage. La règle de négation a tiré l'ensemble vers plus de
  littéralité. On garde : un mot de trop se supprime en deux secondes à la
  relecture familiale, un détail inventé ne se rattrape pas.
- Les titres se rapprochent des mots exacts de la personne (`essai-05` passe de
  « Il me faisait des sifflets avec du noisetier » à « Il en parlait jamais »,
  `essai-01` de « L'odeur du pain de ma grand-mère » à « Vous voilà mes
  petits »). C'est ce que le prompt demande.

Ce qu'aucune de ces deux lectures ne prouve : que le rendu tient sur de **vraies
voix**. Ces cinq textes sont écrits, donc propres — un mot à mot réel sort d'une
transcription automatique, avec ses mots mal entendus et ses phrases coupées. La
lecture sur dix voix réelles reste ouverte (`05_A_FAIRE_HUMAIN.md`).
