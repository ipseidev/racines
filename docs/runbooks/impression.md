# Runbook — l'impression du livre, au pilote

Bloc 13. **Tout est manuel**, et c'est la décision §9 du bloc : l'imprimeur
n'est pas choisi, le devis 0A n'est pas fait, et une dizaine de familles ne
justifie pas d'intégrer une API. Passer les premières commandes à la main est
aussi le meilleur moyen de découvrir ce qu'un imprimeur demande réellement —
profil colorimétrique, fonds perdus, dos calculé — avant de l'automatiser.

## 1. Ce qui arrive

Quand une famille approuve son bon à tirer, l'application :

1. verrouille la sélection des chapitres ;
2. fait passer chaque histoire incluse à `INCLUSE AU LIVRE` et pose
   `printed_in_book` ;
3. ouvre un ticket support `print_order` portant le lien du PDF ;
4. inscrit `approved BookProof` au journal d'audit.

Le ticket apparaît dans **Les tickets** du panneau, et le livre dans
**Les livres**.

## 2. Ce que tu fais

1. Ouvre `/admin/books`, trouve la ligne au statut **Commandé**.
2. **Ouvrir le bon à tirer** : l'URL est temporaire, une heure, régénérée à
   chaque affichage. Télécharge le PDF.
3. Vérifie trois choses avant d'envoyer quoi que ce soit :
   - le format est **160 × 240 mm** (T-179) ;
   - la pagination est celle qu'annonce la colonne **Pages** ;
   - les QR sont présents sur les pages de chapitre et **s'ouvrent** — scanne
     au moins le premier et le dernier.
4. Passe la commande chez l'imprimeur, en RVB, sans passe PDF/X tant que
   l'imprimeur n'en demande pas (§9 du bloc).
5. Reviens sur la fiche et note la référence de l'imprimeur.
6. À la réception de la confirmation d'impression : **Marquer imprimé**.
7. À la livraison confirmée : **Marquer livré**. Ce geste déclenche l'export
   proactif promis en R-10 — ne le fais pas « pour avancer ».

## 3. Exemplaires supplémentaires

Ils arrivent par un **second** ticket `print_order`, portant `extra_copies` et
`session_id`. Ils ne remplacent pas la commande initiale : un livre commandé
en janvier et deux exemplaires en juin sont deux tirages, et les fondre ferait
réimprimer le mauvais.

## 4. Défaut d'impression

**Réimpression gratuite et sans condition** (doc 04 §10). Le motif sert à
savoir ce qui s'est mal passé chez l'imprimeur, jamais à juger la demande :
un livre abîmé coûte moins cher à réimprimer qu'à discuter, et discuter serait
de toute façon la mauvaise réponse à une famille qui attendait ce livre depuis
un an.

Le bouton **Réimprimer** ouvre un ticket `print_defect` et repasse le livre en
`reprint`. **Le bon à tirer n'est pas regénéré** : on réimprime ce qui a été
approuvé. Un nouveau rendu pourrait produire un livre différent — une histoire
validée depuis, une photo ajoutée — et le second exemplaire ne serait plus le
même livre que le premier.

## 5. Ce qu'on ne fait jamais

- **Modifier la sélection des chapitres après l'accord.** Ce que la famille a
  approuvé est ce qui s'imprime, y compris ses erreurs. Si elle veut changer
  quelque chose, elle regénère un bon à tirer — ce qui annule l'accord
  précédent, et c'est le point.
- **Approuver à sa place.** Aucun bouton ne le permet, et c'est délibéré.
- **Annoncer un délai de livraison** avant le devis (doc 03 P0-14).

## 6. Le rendu, côté technique

Le BAT est fabriqué par `RenderBookPdf` sur la file `exports` (900 s de
délai). Il demande **Chromium** et **Node** :

```bash
# Une fois, sur la machine qui rend :
npx puppeteer browsers install chrome
# puis dans .env
BROWSERSHOT_CHROME_PATH=/chemin/vers/chrome
BROWSERSHOT_NODE_PATH=/usr/bin/node
PDF_DRIVER=browsershot   # `fake` en test seulement
```

Le HTML rendu ne charge **aucune ressource distante** : polices, Paged.js, QR
et photos y sont incrustés. Un test l'interdit, et la raison est qu'un rendu
qui va chercher une police sur le réseau produit un livre différent selon
l'humeur du réseau — une police manquante décale toute la pagination, et
personne ne s'en aperçoit avant de recevoir les exemplaires.

Si un rendu échoue : le job réessaie une fois, puis le livre reste au statut
précédent et rien n'est annoncé à la famille. Regarde `book.proof_rendered`
dans les journaux, puis `storage/logs` pour la trace de Chromium.
