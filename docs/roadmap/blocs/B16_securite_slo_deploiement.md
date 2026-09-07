# Bloc 16 — Sécurité, SLO, sauvegardes, déploiement Forge

Statut : ☐ non commencé · Dépend de : 15 · Tag de fin : `bloc-16-done`
Références dossier : doc 04 §8 (sous-traitants, DPA), §11 (SLO : capture < 2 % avant confirmation, zéro perte après confirmation, RTO ≤ 72 h, disponibilité 99,5 %), §12 (chiffrement, secrets, incidents, pentest), PRD §8 (hébergement UE), US-01 (page < 2 s en 4G), US-06 ; décisions T-02, T-03, T-04, T-20.

## 1. Objectif

Un environnement de staging et un environnement de production sur le serveur DigitalOcean piloté par Forge, en région européenne, avec base managée, sauvegardes testées, réplication des médias, supervision, alertes, en-têtes de sécurité, procédures d'incident et de restauration écrites et exécutées une fois.

## 2. Pourquoi

Le pilote traite des voix et des données sensibles de personnes âgées. Les slogans sont interdits ; seuls des SLO mesurés et une restauration prouvée sont acceptables.

## 3. Livrables

- Serveur Forge vérifié en région UE, deux sites (staging, production), Postgres managé DO, Redis, Horizon, scheduler, SSR, SSL, domaines.
- Buckets R2 avec juridiction UE et jetons distincts ; CORS ; cycle de vie.
- `spatie/laravel-backup` vers `r2_backups`, rétention 90 jours ; DO PITR ; `restore:drill` exécuté.
- `spatie/laravel-health` + endpoint `/health` pour Oh Dear ; page de statut publique ; Flare.
- Audit des dépendances en CI, Dependabot, en-têtes, sessions sécurisées.
- Runbooks : `deploiement.md`, `restauration.md`, `incident.md`, `sous-traitants.md`, `accessibilite.md`, `securite-checklist.md`.
- Lighthouse CI sur la page narrateur (budget mobile).

## 4. Packages

```bash
sail composer require spatie/laravel-backup spatie/laravel-health
sail artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
sail artisan vendor:publish --tag="health-migrations"
sail npm i -D @lhci/cli
```

Flare : `spatie/laravel-ignition` est déjà présent ; renseigner `FLARE_KEY` et `LOG_CHANNEL=stack` avec `flare` en production.

## 5. Tests à écrire d'abord

- `tests/Feature/Health/HealthEndpointTest.php` : `/health` (secret Oh Dear) rapporte DB, Redis, Horizon, scheduler (battement ≤ 10 min), espace disque, joignabilité R2 (`head` d'un objet sentinelle), ClamAV, `audit:verify` du jour ; sans secret → 403. **Le battement du scheduler demande une commande planifiée** (`health:schedule-check-heartbeat`) : sans elle le contrôle est rouge à vie même quand le planificateur tourne, ce qu'il a été jusqu'au 2026-09-07 (T-217).
- `tests/Feature/Security/HeadersProductionTest.php` : en `APP_ENV=production` simulé, HSTS `max-age ≥ 31536000; includeSubDomains`, cookies `Secure`, `SameSite=Lax` (Strict pour `sg`), `APP_DEBUG=false` vérifié par un test de configuration.
- `tests/Feature/Backup/BackupConfigTest.php` : destination `r2_backups`, inclut la base et `storage/app/private`, exclut `storage/logs`, rétention `keepAllBackupsForDays 7`, `keepDailyBackupsForDays 90`, chiffrement d'archive activé (`BACKUP_ARCHIVE_PASSWORD`).
- `tests/Feature/Console/RestoreDrillTest.php` : `restore:drill` sur un dump de test restaure dans une base `drill_*`, exécute `audit:verify`, compare les comptes (`projects`, `stories`, `recordings`, `transcripts`), vérifie `head` sur 5 enregistrements au hasard, écrit un rapport markdown, supprime la base.
- `tests/Feature/Ops/ReplicationCheckTest.php` : `media:verify-replicas` détecte un `recordings.replicated_at` nul de plus de 1 h (incident P1, alerte Flare).
- `tests/e2e/perf-narrator.spec.ts` : TTFB < 800 ms en local, JS ≤ 150 Ko gzip ; Lighthouse CI configuré pour la page `/r/…` (profil mobile, réseau 4G simulé) avec budget performance ≥ 90 et accessibilité = 100 (exécuté en `@slow`, pas en CI par défaut).

## 6. Étapes

### 6.1 Infrastructure
- [x] **Hébergement en Irlande**, juridiction UE : le critère de sortie est tenu. Réserve consignée en T-202 — la feuille disait « droplet DigitalOcean », or DigitalOcean n'a pas de région irlandaise ; le fournisseur réel reste à nommer dans `sous-traitants.md`, qui porte « à confirmer » plutôt qu'un nom inventé.
- [ ] Forge : PHP 8.3+, extensions `pgsql pdo_pgsql redis intl gd imagick bcmath`, Node 22, `ffmpeg`, ~~`clamav-daemon`~~ (**retiré le 2026-09-07 par D-12** : le démon n'a jamais été installé, et comme le scan refuse tout fichier lorsqu'il ne joint personne, aucune photo ne pouvait être déposée en production ; le contrôle est débranché par `ANTIVIRUS_SCANNER=off`, qui journalise chaque fichier admis — T-216. Rebrancher un jour reste ouvert dans R-12), `poppler-utils`, Chromium pour Puppeteer (`npx puppeteer browsers install chrome --path /home/forge/.cache/puppeteer`) ; `BROWSERSHOT_CHROME_PATH` renseigné.
- [ ] Postgres managé DigitalOcean même région, `sslmode=require`, pare-feu limité au droplet, sauvegardes quotidiennes + PITR activés ; deux bases `app_staging`, `app_production`.
- [ ] Redis Forge (mot de passe), `maxmemory-policy noeviction` pour la file.
- [ ] R2 : trois buckets par environnement (`{env}-media`, `{env}-media-replica`, `{env}-backups`) créés avec juridiction UE, jetons API séparés par bucket et par environnement, CORS sur `media` (origines = domaine des liens et `APP_URL`, méthodes `PUT,GET`, en-têtes exposés `ETag`), règle de cycle de vie sur `backups` (expiration 100 jours) et sur les uploads multipart incomplets (7 jours).
- [ ] Sites Forge : `staging.<domaine app>` et `<domaine app>`, plus le domaine des liens en alias sur chaque site ; SSL Let's Encrypt ; HSTS.
- [x] Daemons Forge : Horizon, SSR Inertia et le planificateur tournent (confirmé par le fondateur le 2026-09-06).
- [ ] Script de déploiement Forge :
  ```bash
  cd $FORGE_SITE_PATH && git pull origin $FORGE_SITE_BRANCH
  $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
  npm ci && npm run build
  ( flock -w 10 9 || exit 1; $FORGE_PHP artisan migrate --force ) 9>/tmp/fsmaintenance.lock
  $FORGE_PHP artisan optimize
  $FORGE_PHP artisan inertia:stop-ssr
  $FORGE_PHP artisan horizon:terminate
  ```
  (les daemons redémarrent SSR et Horizon).
- [ ] Déploiement automatique de staging sur push `main` (hook Forge appelé par la CI après succès) ; production par bouton Forge uniquement.
- [ ] Variables d'environnement : toutes celles de `01_CONVENTIONS.md` §8, valeurs production ; `APP_DEBUG=false`, `TELESCOPE_ENABLED=false`, `LOG_LEVEL=info`, `SESSION_SECURE_COOKIE=true`.
- [ ] Resend : domaine d'envoi vérifié (SPF, DKIM, DMARC `p=quarantine`), webhook enregistré ; Twilio : expéditeur alphanumérique testé en France, `statusCallback` en HTTPS ; Stripe : clés live et webhook live (activation au bloc 17 seulement).

### 6.2 Sauvegardes et restauration
- [x] `config/backup.php` : destination `r2_backups`, chiffrement, rétention 90 jours, notifications ; `backup:clean` 01:00 puis `backup:run` 01:30 — on nettoie **avant**, l'inverse effacerait parfois l'archive de la nuit même. L'application **refuse de démarrer en production sans `BACKUP_ARCHIVE_PASSWORD`** : sans lui, l'archive part en clair et tout paraît normal.
- [x] Le retard de réplication est surveillé par `ReplicationLagCheck` — un contrôle de santé plutôt qu'une commande dédiée : il tourne déjà toutes les minutes, et une commande de plus n'aurait rien dit que celui-ci ne dise. `backup:monitor` quotidien à 07:00 : il répond à la question que `backup:run` ne pose pas — la dernière archive est-elle récente **et de taille plausible** ? Une sauvegarde qui réussit chaque soir en écrivant trois kilo-octets est le pire des cas.
- [x] `restore:drill` — dump réel, base jetable, chaîne d'audit vérifiée par **le même vérificateur que `audit:verify`**, comptes comparés table par table, rapport daté dans `docs/runbooks/drills/`, base effacée. Reste `docs/runbooks/restauration.md` (RTO cible 72 h ; étapes DO PITR ; restauration `laravel-backup` ; vérifications ; qui décide). Exécuter le drill sur staging et archiver le rapport dans `docs/runbooks/drills/`.
- [ ] Politique de rétention publiée (`confidentialite.md`) alignée : sauvegardes 90 jours.

### 6.3 Supervision et alertes
- [x] `spatie/laravel-health` : les six contrôles génériques et les quatre du produit — `R2ReachableCheck`, `ClamavCheck`, `AuditChainCheck`, `ReplicationLagCheck`. L'endpoint `/health` **n'existe que si** `OH_DEAR_HEALTH_CHECK_SECRET` est posé, et le secret voyage dans un en-tête : en paramètre d'URL il finirait dans les journaux. Les résultats vont au cache et non en base — une table qui grossit chaque minute pour une donnée que personne ne relit.
- [x] ~~Oh Dear~~ **écarté sur son coût (T-201)**, remplacé en deux moitiés : « un contrôle est au rouge » part par courriel depuis le serveur (`laravel-health`, un message par heure, avertissements compris) ; « le serveur ne répond plus » demande un appel de l'extérieur sur `/up`, que fait une offre gratuite (UptimeRobot, Better Stack). Détail dans `docs/runbooks/supervision.md`. **Manque assumé** : la page de statut publique, reportée au bloc 17.
- [ ] Flare : erreurs, `audit:verify` rupture, échec de sauvegarde, `ReplicationLag`.

### 6.4 Sécurité
- [x] CI : `composer audit` et `npm audit --audit-level=high` bloquants, avec trois essais espacés sur le second — le registre npm rend parfois 503, et une panne de registre ne doit pas passer pour une vulnérabilité. Dependabot reste à activer.
- [ ] `docs/runbooks/securite-checklist.md` : la liste doc 04 §12 point par point avec l'emplacement de la preuve (test, config, capture) ; revue d'accès trimestrielle (`access:review`) ; rotation des secrets (procédure) ; pentest externe planifié avant Noël 2027 (placeholder daté).
- [x] `docs/runbooks/incident.md`. Deux choses y sont écrites qui n'étaient pas prévues : le retard de réplication devient **P1 au-delà de 24 h** — rien n'est perdu, mais la promesse ne tient plus — et le post-mortem se termine par « quelle **garde exécutable** empêche la récidive », une décision consignée sans test n'empêchant rien.
- [x] `docs/runbooks/sous-traitants.md` `[À VALIDER PAR CONSEIL]` : Cloudflare R2 (UE), DigitalOcean (AMS/FRA), Twilio, Resend, Anthropic, Gladia, Deepgram, Stripe, PostHog (UE), Flare, Oh Dear ; pour chacun : rôle, données, région, DPA (lien), transferts, option de sortie.

### 6.5 Performance et accessibilité
- [ ] SSR activé aussi pour les pages narrateur et famille (premier rendu sans JavaScript).
- [ ] `lighthouserc.json` : URL `/r/{token de test}` sur staging, `preset: mobile`, budgets ; commande `npm run lhci`.
- [ ] `docs/runbooks/accessibilite.md` : parcours VoiceOver (iOS) et TalkBack (Android) sur enregistrement, relecture, écoute ; liste de vérification WCAG 2.2 AA ; agrandissement 200 % ; résultats datés.

### 6.6 Clôture
- [ ] `docs/runbooks/deploiement.md` complet (prérequis, script, rollback = redéployer le commit précédent + `migrate:rollback` si migration réversible).
- [ ] `04_VERSIONS.md`, `.env.example`.
- [ ] CI verte ; staging déployé ; commit `chore(bloc-16): terminé`, tag `bloc-16-done`.

## 7. Checkpoint démontrable

1. `https://staging.<domaine>` répond, HSTS présent, Oh Dear vert, `/health` vert.
2. Enregistrer une histoire sur staging depuis un téléphone en 4G réelle : page chargée < 2 s, audio confirmé, `replicated_at` posé dans l'heure.
3. `sail artisan restore:drill` sur staging : rapport généré, `audit:verify` intact, RTO mesuré noté.
4. Couper Redis sur staging : alerte Oh Dear/Flare reçue dans les 5 minutes.
5. Lighthouse CI sur la page narrateur : performance ≥ 90, accessibilité 100.

## 8. Critères de sortie

- [ ] Région UE prouvée (capture dans `docs/runbooks/deploiement.md`).
- [ ] Un drill de restauration exécuté et archivé.
- [ ] Tous les runbooks existent et sont datés.
- [ ] Aucune clé Stripe live ni envoi réel avant le bloc 17.

## 9. Règle de décision par défaut

Si un fournisseur ne propose pas de région ou de juridiction UE, il n'est pas utilisé pour des données personnelles ; on cherche l'équivalent UE et on note la décision. Pour R2, la restriction de juridiction est obligatoire, pas optionnelle.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
