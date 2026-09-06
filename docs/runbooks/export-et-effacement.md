# Runbook — export et effacement

Bloc 14, doc 04 §4 et §7, R-10.2.

## 1. Ce que la famille peut faire seule

Depuis `/espace/donnees` : demander un export (trois formes), et demander
l'effacement. Rien de tout cela ne passe par le support en temps normal, et
c'est le but — **une porte qu'on ne voit pas n'est pas une porte.**

| Forme | Contenu |
|---|---|
| `full` | Voix, mot à mot, textes mis au propre, photos, livre, lexique |
| `offline_pack` | Tout cela, **plus** `hors-ligne/index.html`, une page qui joue les voix sans réseau |
| `gdpr_access` | Tout cela, **plus** les consentements |

Le lien vaut **sept jours**. Un nouveau se demande gratuitement, autant de
fois qu'on veut : c'est écrit dans le courriel et sur la page.

## 2. Ce qui part sans qu'on le demande

Deux moments, choisis pour ce que l'oubli y coûte :

- **À la livraison du livre** (`Marquer livré` dans `/admin/books`) : un
  export complet **et** un pack hors-ligne. Le projet se termine, tout le
  monde passe à autre chose, et personne ne pensera à télécharger quoi que ce
  soit.
- **Soixante jours avant `hosting_ends_at`** : `exports:proactive`, tous les
  jours à 06:00. Soixante jours, c'est le temps qu'il faut à une personne de
  quatre-vingts ans pour demander de l'aide à quelqu'un.

Deux gardes empêchent le harcèlement : un export des quatre-vingt-dix
derniers jours suffit, et un projet effacé n'en reçoit pas.

## 3. Confirmer un effacement

1. Le ticket apparaît dans **Les tickets**, genre « Effacement demandé », avec
   sa date limite dans le contenu.
2. **Vérifier qui demande.** Le ticket porte `requested_by_type`. Une demande
   arrivée par un autre canal — téléphone, courriel — se vérifie avant : une
   personne qui perd ses récits parce que quelqu'un s'est fait passer pour
   elle n'a aucun recours.
3. **Confirmer l'effacement** sur le ticket. Le motif est obligatoire : c'est
   la trace de la demande, et sans elle l'effacement ressemble à une décision
   de notre part.
4. **Prévenir la personne.** Elle attend une réponse, pas un silence.

**Le droit `rgpd.erase` n'est pas dans le rôle support.** C'est délibéré :
c'est le seul acte du back-office qu'aucune sauvegarde ne rattrape passé
quatre-vingt-dix jours.

### Quand c'est refusé

Un livre en cours d'impression bloque l'effacement, et le message le dit avec
la sortie : livrer, ou annuler la commande. Effacer des récits pendant qu'une
machine les imprime produirait un objet dont plus rien ne dit d'où il vient.

### Priorité

**Le narrateur gagne** (doc 04 §3). Sa demande ferme celle de
l'Initiateur·rice ; l'inverse ne se produit pas. Il n'y a pas d'arbitrage à
rendre, et surtout pas d'appel à négocier : c'est sa voix.

## 4. Ce qui reste après un effacement

| | |
|---|---|
| Commandes | Conservées — durée légale comptable, ce n'est pas nous qui la fixons |
| Consentements | Conservés, **sujet haché** : la preuve subsiste, elle ne désigne plus personne |
| Journal d'audit | Conservé, déjà masqué par `Redactor`. L'effacer serait effacer le moyen de répondre à qui réclame ses données |
| Lignes de projet, narrateur, proches | Conservées, champs personnels vidés, `contact_deleted_at` posé |
| Prénom du narrateur | Remplacé par `[effacé]` — la colonne n'est pas nullable, et un marqueur distingue « a demandé l'effacement » de « jamais rempli » |

## 5. Vérifier après coup

```bash
# Plus aucun objet dans le stockage pour ce projet
sail artisan tinker --execute="…"   # head() sur les clés connues

# La chaîne d'audit tient toujours
sail artisan audit:verify
```

Si `audit:verify` échoue après un effacement, c'est un **incident P1** :
`docs/runbooks/incident.md`. Cela voudrait dire que l'effacement a touché le
journal, ce qu'il n'a pas le droit de faire.

## 6. Le test de performance qui reste dû

Le dossier promet **moins de trente minutes pour cinq gigaoctets** (doc 04
§7). Il n'a pas encore été mesuré : le décor local ne pèse que quelques
mégaoctets.

À faire sur staging, avec un projet synthétique : noter la durée ici, et si
elle dépasse, chercher du côté du téléchargement des objets — c'est lui qui
domine, pas la compression.

_Résultat : `[à mesurer]`_
