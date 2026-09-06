# Exercices de restauration

Un par trimestre, au minimum (doc 04 §11). Chaque fichier de ce dossier est la
trace datée d'un exercice réellement exécuté — c'est la **preuve** que le
dossier réclame, et la seule chose qui distingue une promesse de restauration
d'un espoir.

```bash
sail artisan restore:drill          # sur staging, jamais en production
```

La commande prend un dump avec `pg_dump`, crée une base jetable, restaure
dedans, vérifie la chaîne d'audit **avec le même vérificateur que
`audit:verify`**, compare les comptes table par table, écrit son rapport ici,
puis efface la base d'exercice.

Ce qu'un rapport ne couvre pas, et qui est dans `restauration.md` : le
téléchargement de l'archive depuis le stockage objet, la décision humaine, et
la bascule du trafic. La durée mesurée ici est un plancher, pas le RTO.

**En échec** — un écart de comptes ou une chaîne rompue — l'exercice sort en
erreur et le rapport porte le détail. Ne pas le supprimer : un exercice raté
est l'information la plus utile du dossier.
