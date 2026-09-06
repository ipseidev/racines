# Runbook — restaurer

Bloc 16. RTO visé : **72 heures** (doc 04 §11). Ce document existe pour être
lu un jour de panne, par quelqu'un qui n'a pas dormi. Il est donc court, et
l'ordre des sections est celui dans lequel on en a besoin.

## 0. Qui décide

Le fondateur, seul, décide de restaurer. Une restauration **écrase** ce qui
existe : si la base actuelle contient trois heures d'enregistrements postés
après l'incident, elles disparaissent. Personne ne prend cette décision à sa
place, et surtout pas dans l'urgence.

## 1. Deux sauvegardes, et ce que chacune couvre

Il y en a **deux**, et c'est délibéré (T-202).

| | Sauvegarde Forge | `laravel-backup` |
|---|---|---|
| Contenu | La base | La base **et** `storage/app/private` |
| Chiffrée | Non | Oui (`BACKUP_ARCHIVE_PASSWORD`) |
| Destination | Le stockage configuré dans Forge | Le bucket `r2_backups`, jeton distinct |
| Fréquence | Selon Forge | 01:30, après nettoyage à 01:00 |
| Rétention | Selon Forge | 7 jours pleins, puis 90 jours de quotidiennes |
| Éprouvée | **Non** | Oui, par `restore:drill` |

**On garde les deux.** Elles échouent différemment : un jeton R2 révoqué tue
la seconde et pas la première ; une erreur de configuration Forge tue la
première et pas la seconde. Deux sauvegardes qui tomberaient ensemble ne
vaudraient qu'une.

**Celle qui compte est la seconde**, parce qu'elle est chiffrée et parce
qu'on l'a déjà restaurée. Celle de Forge est le filet du filet, et sa faiblesse
est nommée : une archive de base **en clair** sur un stockage tiers. Ne pas y
mettre plus que ce qu'elle contient déjà.

## 2. Ce qui n'est dans aucune des deux

- **Les médias** — audio et photos. Ils vivent sur R2 et sont copiés vers un
  second bucket par `ReplicateRecording` (bloc 04). Les réempaqueter chaque
  nuit doublerait le coût sans rien ajouter. Leur perte se traite en §5.
- **Le code** — il est dans Git.
- **Les secrets** — dans Forge, et dans le gestionnaire de secrets du
  fondateur. **Sans `BACKUP_ARCHIVE_PASSWORD`, aucune archive ne se
  restaure.** C'est le seul secret dont la perte est définitive.

## 3. Restaurer la base

```bash
# 1. Fermer la porte : personne n'écrit pendant une restauration.
php artisan down --secret=<jeton connu de vous seul>

# 2. Choisir l'archive. Elles sont datées, la plus récente n'est pas
#    toujours la bonne : si l'incident date de la veille, prendre celle
#    d'avant.
php artisan backup:list

# 3. Restaurer. `laravel-backup` ne restaure pas lui-même — il n'y a pas de
#    commande magique, et c'est bien ainsi : décompresser à la main oblige
#    à regarder ce qu'on écrase.
#    Télécharger l'archive depuis r2_backups, la déchiffrer, extraire le
#    dump, puis :
psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f dump.sql

# 4. Vérifier avant de rouvrir.
php artisan audit:verify
php artisan migrate --force        # aucune migration en attente attendue

# 5. Rouvrir.
php artisan up
```

**Ne jamais sauter l'étape 4.** Une restauration qui rend 90 % des lignes est
un échec silencieux — c'est exactement ce que `restore:drill` attrape chaque
trimestre, et ce qu'il faut revérifier ici.

## 4. Restaurer par le point-dans-le-temps

Si la base managée du fournisseur offre le PITR, c'est **la première option à
essayer** : elle ramène à l'instant précédant l'incident, pas à la nuit
précédente, et la différence est une journée d'enregistrements.

La procédure est celle du fournisseur. Ce qui nous concerne : à la fin, la
base restaurée est une **nouvelle** base, et il faut pointer `DB_DATABASE`
dessus dans Forge, puis redéployer. Refaire l'étape 4 ci-dessus.

## 5. Perdre des médias

Le cas le plus grave, et le seul qui casse une promesse écrite (« zéro perte
après “votre histoire est enregistrée” »).

1. Vérifier l'ampleur : `recordings` dont `confirmed_at` est posé et dont
   l'objet ne répond plus en `HeadObject`.
2. Pour chacun, le second bucket a la copie : `replica_path`. La recopier
   vers le bucket principal.
3. Un enregistrement confirmé **sans** réplique est une perte réelle. Elle se
   traite en incident P1 : `docs/runbooks/incident.md`, et l'on prévient la
   famille — le dossier interdit de laisser croire que le récit existe encore.

## 6. Après

- Archiver ce qui s'est passé dans `docs/runbooks/drills/` avec la mention
  « incident réel » : un vrai incident vaut trois exercices.
- Mesurer le temps écoulé et le comparer aux 72 heures. S'il est dépassé,
  la cause est dans la procédure, pas dans la personne : corriger ce document.
