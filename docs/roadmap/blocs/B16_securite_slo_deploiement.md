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

- `tests/Feature/Health/HealthEndpointTest.php` : `/health` (secret Oh Dear) rapporte DB, Redis, Horizon, scheduler (battement ≤ 10 min), espace disque, joignabilité R2 (`head` d'un objet sentinelle), ClamAV, `audit:verify` du jour ; sans secret → 403.
- `tests/Feature/Security/HeadersProductionTest.php` : en `APP_ENV=production` simulé, HSTS `max-age ≥ 31536000; includeSubDomains`, cookies `Secure`, `SameSite=Lax` (Strict pour `sg`), `APP_DEBUG=false` vérifié par un test de configuration.
- `tests/Feature/Backup/BackupConfigTest.php` : destination `r2_backups`, inclut la base et `storage/app/private`, exclut `storage/logs`, rétention `keepAllBackupsForDays 7`, `keepDailyBackupsForDays 90`, chiffrement d'archive activé (`BACKUP_ARCHIVE_PASSWORD`).
- `tests/Feature/Console/RestoreDrillTest.php` : `restore:drill` sur un dump de test restaure dans une base `drill_*`, exécute `audit:verify`, compare les comptes (`projects`, `stories`, `recordings`, `transcripts`), vérifie `head` sur 5 enregistrements au hasard, écrit un rapport markdown, supprime la base.
- `tests/Feature/Ops/ReplicationCheckTest.php` : `media:verify-replicas` détecte un `recordings.replicated_at` nul de plus de 1 h (incident P1, alerte Flare).
- `tests/e2e/perf-narrator.spec.ts` : TTFB < 800 ms en local, JS ≤ 150 Ko gzip ; Lighthouse CI configuré pour la page `/r/…` (profil mobile, réseau 4G simulé) avec budget performance ≥ 90 et accessibilité = 100 (exécuté en `@slow`, pas en CI par défaut).

## 6. Étapes

### 6.1 Infrastructure
- [ ] Vérifier la région du droplet (`doctl compute droplet get <id> --format Region` ou console) : doit être `ams3` ou `fra1`. Sinon : créer un droplet UE, provisionner via Forge, migrer. Consigner dans `03_DECISIONS.md`.
- [ ] Forge : PHP 8.3+, extensions `pgsql pdo_pgsql redis intl gd imagick bcmath`, Node 22, `ffmpeg`, `clamav-daemon`, `poppler-utils`, Chromium pour Puppeteer (`npx puppeteer browsers install chrome --path /home/forge/.cache/puppeteer`) ; `BROWSERSHOT_CHROME_PATH` renseigné.
- [ ] Postgres managé DigitalOcean même région, `sslmode=require`, pare-feu limité au droplet, sauvegardes quotidiennes + PITR activés ; deux bases `app_staging`, `app_production`.
- [ ] Redis Forge (mot de passe), `maxmemory-policy noeviction` pour la file.
- [ ] R2 : trois buckets par environnement (`{env}-media`, `{env}-media-replica`, `{env}-backups`) créés avec juridiction UE, jetons API séparés par bucket et par environnement, CORS sur `media` (origines = domaine des liens et `APP_URL`, méthodes `PUT,GET`, en-têtes exposés `ETag`), règle de cycle de vie sur `backups` (expiration 100 jours) et sur les uploads multipart incomplets (7 jours).
- [ ] Sites Forge : `staging.<domaine app>` et `<domaine app>`, plus le domaine des liens en alias sur chaque site ; SSL Let's Encrypt ; HSTS.
- [ ] Daemons Forge : `php artisan horizon` (production et staging), `php artisan inertia:start-ssr` ; scheduler cron `* * * * *`.
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
- [ ] `config/backup.php` : destination `r2_backups`, `db_dump_compressor gzip`, `encryption default`, notifications Flare + email admin en cas d'échec ; planification `backup:clean` 01:00 et `backup:run` 01:30.
- [ ] `media:verify-replicas` (`hourly()`), `backup:monitor` quotidien.
- [ ] `restore:drill` et `docs/runbooks/restauration.md` (RTO cible 72 h ; étapes DO PITR ; restauration `laravel-backup` ; vérifications ; qui décide). Exécuter le drill sur staging et archiver le rapport dans `docs/runbooks/drills/`.
- [ ] Politique de rétention publiée (`confidentialite.md`) alignée : sauvegardes 90 jours.

### 6.3 Supervision et alertes
- [ ] `spatie/laravel-health` : checks `DatabaseCheck`, `RedisCheck`, `HorizonCheck`, `ScheduleCheck`, `UsedDiskSpaceCheck`, `CacheCheck`, checks maison `R2ReachableCheck`, `ClamavCheck`, `AuditChainCheck`, `ReplicationLagCheck` ; endpoint `/health` protégé par `OH_DEAR_HEALTH_CHECK_SECRET`.
- [ ] Oh Dear : uptime sur `/` et `/health`, certificats, page de statut publique (URL dans le pied de page), alertes vers l'email et le téléphone du fondateur.
- [ ] Flare : erreurs, `audit:verify` rupture, échec de sauvegarde, `ReplicationLag`.

### 6.4 Sécurité
- [ ] CI : `composer audit`, `npm audit --audit-level=high` bloquants ; Dependabot hebdomadaire.
- [ ] `docs/runbooks/securite-checklist.md` : la liste doc 04 §12 point par point avec l'emplacement de la preuve (test, config, capture) ; revue d'accès trimestrielle (`access:review`) ; rotation des secrets (procédure) ; pentest externe planifié avant Noël 2027 (placeholder daté).
- [ ] `docs/runbooks/incident.md` : détection, qualification (P1 = perte d'un audio confirmé ou fuite de jeton), communication, notification CNIL sous 72 h et personnes concernées si risque élevé, journal d'incident, post-mortem.
- [ ] `docs/runbooks/sous-traitants.md` `[À VALIDER PAR CONSEIL]` : Cloudflare R2 (UE), DigitalOcean (AMS/FRA), Twilio, Resend, Anthropic, Gladia, Deepgram, Stripe, PostHog (UE), Flare, Oh Dear ; pour chacun : rôle, données, région, DPA (lien), transferts, option de sortie.

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
