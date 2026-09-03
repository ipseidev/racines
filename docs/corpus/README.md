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

## Observations de la première lecture (3 septembre 2026)

- `essai-01` : rien d'inventé, tournures orales conservées, les deux graphies
  du village proposées au lexique sans qu'aucune ne soit imposée. −3 % de
  longueur. **Bon.**
- `essai-04` : n'a rien rallongé (13 mots → 13 mots). Mais **« je sais pas »
  est devenu « je ne sais pas »** — une correction de niveau de langue, alors
  que `essai-01` avait conservé « je sais plus ». Le rendu est donc
  inconstant sur ce point précis, et c'est celui qui compte le plus pour
  « c'est encore sa voix ». À reprendre dans le prompt.
