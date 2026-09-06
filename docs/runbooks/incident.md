# Runbook — incident

Bloc 16, doc 04 §12. Écrit pour être suivi par une personne seule, la nuit,
sans avoir à décider ce qui est grave.

## 1. Qualifier, en une minute

**P1 — on prévient des gens.**

- Un audio **confirmé** est perdu ou illisible. La promesse « votre histoire
  est enregistrée » est démentie.
- Un jeton porteur a fuité — dans un journal, une capture, un outil tiers.
- Un accès non autorisé à la base, au stockage, ou au back-office.
- Le journal d'audit est rompu (`audit.chain_broken`).
- Des données d'une famille ont été servies à une autre.

**P2 — on répare, on ne réveille personne.**

- Le service est indisponible ou dégradé sans perte de données.
- Les envois (SMS, courriels) sont bloqués.
- Les transcriptions ne partent plus.
- La réplication a du retard (`ReplicationLagCheck` au rouge) : rien n'est
  encore perdu, mais la promesse n'est plus garantie — **P1 si cela dure plus
  de 24 h**.

**P3 — au fil de l'eau.** Tout le reste.

En cas de doute entre deux niveaux, prendre le plus grave. Se tromper vers le
haut coûte une soirée ; se tromper vers le bas coûte la confiance d'une
famille.

## 2. Les six premières minutes

1. **Noter l'heure.** Le délai de notification CNIL court à partir de la
   *découverte*, pas de l'incident.
2. **Arrêter l'hémorragie** avant de comprendre. Un jeton fuité se révoque ;
   une file qui écrit n'importe quoi se met en pause (`php artisan
   horizon:pause`) ; un endpoint compromis se ferme.
3. **Ne rien supprimer.** Les journaux, la base, les objets : tout reste. Une
   trace effacée pendant l'urgence est une enquête impossible le lendemain.
4. **Ouvrir un fichier** dans `docs/incidents/AAAA-MM-JJ-<mot>.md` et écrire
   au fil de l'eau. On ne se souvient pas d'un incident, on le relit.

## 3. Décider si l'on notifie

**La CNIL, sous 72 heures**, dès qu'une violation de données personnelles est
probable — pas certaine, probable. Le doute penche vers la notification :
notifier pour rien coûte un formulaire, ne pas notifier coûte une sanction et
la réputation.

**Les personnes concernées**, sans délai, quand le risque pour elles est
élevé. Ici cela veut dire : leurs récits, leur voix, leurs coordonnées ont pu
être vus par quelqu'un d'autre. Le message dit ce qui s'est passé, ce qu'on a
fait, ce qu'elles doivent faire — dans cet ordre, et sans jargon. Il ne dit
jamais « par mesure de précaution » quand ce n'en est pas une.

**Un audio perdu** n'est pas une violation de données, mais c'est une
promesse rompue : on prévient la famille le jour même, on dit que le récit
n'est pas récupérable, et on propose de le réenregistrer. On ne laisse
**jamais** l'interface continuer d'afficher une histoire qui n'existe plus.

## 4. Communiquer

- **Aux familles touchées** : par le canal qu'elles ont choisi, pas par un
  bandeau sur le site qu'elles ne verront pas.
- **Aux autres** : rien. Une communication générale sur un incident qui ne
  les concerne pas inquiète sans informer.
- **Ton** : dire ce qui s'est passé, ce qui a été fait, ce qui reste à faire.
  Pas de « nous prenons la sécurité très au sérieux ». La phrase existe
  précisément pour éviter d'en dire plus.

## 5. Après, sous une semaine

Un post-mortem dans le même fichier. Trois questions, et **aucune** ne porte
sur qui a fait l'erreur :

1. Qu'est-ce qui a rendu cet incident possible ?
2. Qu'est-ce qui l'a rendu long à détecter ? (souvent la vraie réponse)
3. Quelle garde empêche qu'il se reproduise — un test, un contrôle de santé,
   une garde de démarrage ? Une décision consignée dans `03_DECISIONS.md`
   sans garde exécutable n'empêche rien.

## 6. Ce qu'on a déjà appris

Les incidents du dépôt sont dans `03_DECISIONS.md`, et deux motifs y
reviennent assez pour être vérifiés en premier quand quelque chose casse :

- **Un double qui masque la couture** : l'unité passe, la route ne l'exerce
  pas (T-154, T-178, T-181, T-191, T-197).
- **Un décor qui vise à côté de ce qu'un humain ouvre** (T-173, T-180,
  T-183, T-188, T-198).
