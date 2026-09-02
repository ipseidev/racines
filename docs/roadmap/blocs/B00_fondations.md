# Bloc 00 — Fondations du dépôt et outillage

Statut : ☐ non commencé · Dépend de : rien · Tag de fin : `bloc-00-done`
Références dossier : PRD §8 (contraintes non négociables), doc 04 §12 (sécurité), CLAUDE.md.

## 1. Objectif

Un dépôt Laravel + Inertia React qui démarre avec `sail up`, dont tous les outils de qualité tournent en local et en CI, avec les documents produit rangés, et un premier test vert à chaque couche. À la fin de ce bloc, aucune fonctionnalité produit n'existe, mais tout ce qui sera écrit ensuite est testé, analysé, formaté et versionné de la même façon.

## 2. Pourquoi

Le dossier exige TDD, PHPStan, hébergement UE et une marque variable. Ces exigences coûtent dix fois plus cher à introduire après coup. Le bloc pose aussi l'organisation du dépôt (T-25) pour que Forge déploie la racine.

## 3. Livrables

- Projet Laravel à la racine du dépôt, starter kit React (Inertia 2, React 19, TypeScript, Tailwind 4, shadcn/ui, Fortify).
- Sail avec `pgsql`, `redis`, `mailpit`, `minio` ; image étendue avec `ffmpeg`.
- Pest, Larastan niveau 8, Pint, Telescope (local), Vitest + RTL, Playwright + axe, ESLint, Prettier.
- Scripts `composer check|test|analyse|lint` et `npm run check|test|e2e|typecheck|lint|format`.
- CI GitHub Actions verte.
- `docs/dossier/`, `docs/reference/`, `.gitignore` complet, `CLAUDE.md` mis à jour.
- `config/brand.php` et `config/product.php` (squelettes), `.env.example` complet.
- `04_VERSIONS.md` rempli.

## 4. Prérequis machine (vérifier avant de commencer)

```bash
php -v            # ≥ 8.3
composer -V       # ≥ 2.7
node -v           # 22.x
npm -v
docker --version && docker compose version
laravel --version # sinon : composer global require laravel/installer
gh --version      # GitHub CLI authentifié : gh auth status
```

## 5. Tests à écrire d'abord

Ce bloc crée l'outillage ; les tests servent à prouver que chaque outil tourne.

- `tests/Feature/SmokeTest.php` : `it('renders the welcome page')` → GET `/` retourne 200 et une réponse Inertia (`assertInertia(fn ($page) => $page->component('welcome'))`).
- `tests/Unit/Config/ProductConfigTest.php` : `it('exposes recording limits')` → `config('product.recording.hard_stop_seconds') === 1200`.
- `resources/js/lib/format.test.ts` : `formatDuration(65) === '1 min 05 s'` (fonction à créer dans `resources/js/lib/format.ts`).
- `tests/e2e/smoke.spec.ts` : ouvre `/`, vérifie le titre de la page et zéro violation axe `serious`/`critical`.
- Un test PHPStan « rouge » volontaire (fichier avec un type faux) pour vérifier que `composer analyse` échoue, puis supprimé.

## 6. Étapes

### 6.1 Ranger les documents produit
- [ ] `mkdir -p docs/dossier docs/reference docs/runbooks docs/spikes`
- [ ] `mv 01_EXECUTIVE_MEMO.md 02_OPPORTUNITY_ASSESSMENT.md 03_PRD_MVP.md 04_DOSSIER_CONFIANCE_CONFORMITE_OPERATIONS.md 05_REFERENTIEL_GLOSSAIRE_SOURCES.md docs/dossier/`
- [ ] `mv remento-screenshot docs/reference/remento-screenshot`
- [ ] Dans `docs/dossier/02_OPPORTUNITY_ASSESSMENT.md` et `docs/dossier/05_REFERENTIEL_GLOSSAIRE_SOURCES.md`, remplacer `remento-screenshot/` par `docs/reference/remento-screenshot/`.
- [ ] Dans `CLAUDE.md` : mettre à jour les chemins des cinq documents et du dossier de captures ; ajouter une section « Commandes » pointant vers `docs/roadmap/01_CONVENTIONS.md` §6 ; remplacer la phrase « There is no code » par la description du projet Laravel ; conserver toutes les règles produit.

### 6.2 Créer le projet Laravel à la racine
- [ ] Depuis le dossier parent : `laravel new app-tmp --react --pest --database=pgsql` (répondre « non » à l'installation npm et au démarrage, ils se feront dans Sail).
- [ ] `rsync -a app-tmp/ remento-clone/ && rm -rf app-tmp` (aucun fichier du starter kit n'entre en conflit avec `CLAUDE.md` ni `docs/`).
- [ ] Vérifier la présence de `laravel/fortify` dans `composer.json`. Sinon : `composer require laravel/fortify && php artisan fortify:install --inertia` puis vérifier que les pages d'auth React existent dans `resources/js/pages/auth/`.
- [ ] Vérifier `composer.json` : `inertiajs/inertia-laravel` ^2, `pestphp/pest` ^3 ou supérieur ; `package.json` : `react` ^19, `@inertiajs/react` ^2, `tailwindcss` ^4, `typescript` ^5.

### 6.3 Git et GitHub
- [ ] `git init -b main`
- [ ] Compléter `.gitignore` :
  ```
  docs/reference/remento-screenshot/
  tests/bench/asr/corpus/
  .env.testing
  /storage/app/private/*
  playwright-report/
  test-results/
  ```
- [ ] `git add -A && git commit -m "chore(bloc-00): laravel react starter kit + dossier produit"`
- [ ] `gh repo create <compte>/<nom-du-depot> --private --source=. --remote=origin --push` (le nom du dépôt n'est pas le nom de marque ; utiliser un nom de code neutre, ex. `memoir-platform`).

### 6.4 Sail
- [ ] `composer require laravel/sail --dev`
- [ ] `php artisan sail:install --with=pgsql,redis,mailpit,minio`
- [ ] `php artisan sail:publish` puis, dans `docker/8.x/Dockerfile` (dossier publié), ajouter `ffmpeg` à la liste `apt-get install` ; dans `compose.yaml`, pointer `build.context` vers ce dossier.
- [ ] Ajouter dans `compose.yaml` un service `minio` déjà présent ; créer le bucket local au démarrage : script `docker/minio/create-buckets.sh` appelé par un service `minio-init` (`mc alias set local http://minio:9000 sail password && mc mb -p local/media local/media-replica local/backups`).
- [ ] `./vendor/bin/sail up -d` puis `alias sail='./vendor/bin/sail'` (documenté dans `CLAUDE.md`).
- [ ] `sail composer install && sail npm install`
- [ ] `sail artisan key:generate && sail artisan migrate`

### 6.5 Configuration de base
- [ ] `config/app.php` : `timezone` et `locale` lus depuis l'env (`APP_TIMEZONE`, `APP_LOCALE`), `fallback_locale` `fr`, `faker_locale` `fr_FR`.
- [ ] Créer `config/brand.php` :
  ```php
  return [
      'product_name' => env('BRAND_PRODUCT_NAME', 'Product'),
      'short_name' => env('BRAND_SHORT_NAME', 'Product'),
      'tagline' => 'Le livre de souvenirs de vos parents qui va réellement au bout.',
      'links_domain' => env('LINKS_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
      'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@example.test'),
      'sms_sender_id' => env('BRAND_SMS_SENDER_ID', 'PRODUCT'),
      'colors' => [
          'primary' => '#1F3D2B', 'primary_foreground' => '#FFFFFF',
          'accent' => '#D9E76C', 'accent_foreground' => '#1F3D2B',
          'background' => '#F7F5EF', 'surface' => '#FFFFFF',
          'text' => '#1B1B1B', 'muted' => '#6B6B6B',
      ],
      'fonts' => ['display' => 'Fraunces', 'body' => 'Inter'],
      'legal_entity' => env('BRAND_LEGAL_ENTITY', ''),
  ];
  ```
  Les couleurs par défaut sont provisoires : registre chaleureux et éditorial, distinct de la charte Remento. Elles seront remplacées dans l'admin (bloc 01).
- [ ] Créer `config/product.php` avec les sections `recording` (`soft_warning_seconds` 600, `hard_stop_seconds` 1200, `max_bytes` 209715200, `accepted_mimes`), `tokens` (durées du glossaire §4), `otp` (`length` 6, `ttl_minutes` 10, `max_attempts` 5, `lockout_minutes` 15), `engine` (annexe C), `book_ready` (R-6 : `min_words` 12000, `min_audio_minutes` 90, `min_pages` 60, `min_themes` 5), `offer` (`pilot_weeks` 12, `core_months` 12, `finalization_months` 3), `family` (`listen_threshold_seconds` 30, `comment_max_chars` 280), `pilot` (`phone_option_cap` 10, `phone_option_price_cents` 2500).
- [ ] Remplir `.env.example` avec toutes les lignes de `01_CONVENTIONS.md` §8 (valeurs locales), y compris MinIO comme endpoint R2 local : `R2_ENDPOINT=http://minio:9000`, `R2_ACCESS_KEY_ID=sail`, `R2_SECRET_ACCESS_KEY=password`, `AWS_USE_PATH_STYLE_ENDPOINT=true`.
- [ ] Créer `routes/narrator.php`, `routes/family.php`, `routes/webhooks.php` (vides, avec un commentaire d'en-tête) et les enregistrer dans `bootstrap/app.php` via `then:` ; `webhooks` hors middleware `web` (pas de CSRF) ; `narrator` et `family` sur le domaine `config('brand.links_domain')`.

### 6.6 Qualité PHP
- [ ] `composer require --dev larastan/larastan`
- [ ] `phpstan.neon` : includes larastan, `level: 8`, `paths: [app, config, database, routes]`, `tmpDir: storage/phpstan`.
- [ ] `pint.json` : preset `laravel`, règles `declare_strict_types: true`, `final_class: true`, exclusions `app/Models`, `app/Filament`, `app/Providers`, `database/migrations`.
- [ ] `composer.json` scripts :
  ```json
  "check": ["@lint-check", "@analyse", "@test"],
  "lint": "pint",
  "lint-check": "pint --test",
  "analyse": "phpstan analyse --memory-limit=1G",
  "test": "pest --parallel"
  ```
- [ ] `tests/Pest.php` : `uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature');` et `Http::preventStrayRequests()` dans `beforeEach` global.
- [ ] `composer require --dev laravel/telescope && sail artisan telescope:install` ; dans `TelescopeServiceProvider::register()`, `Telescope::night()` retiré, `hideRequestParameters(['token','code','password'])`, `hideRequestHeaders(['authorization','cookie'])` ; `TELESCOPE_ENABLED` lu dans `config/telescope.php` ; provider chargé seulement en `local` (`bootstrap/providers.php` conditionnel via `App\Providers\AppServiceProvider::register()`).

### 6.7 Qualité front
- [ ] `sail npm i -D vitest @testing-library/react @testing-library/jest-dom @testing-library/user-event jsdom @playwright/test @axe-core/playwright`
- [ ] `vitest.config.ts` : `environment: 'jsdom'`, `setupFiles: ['resources/js/test/setup.ts']` (importe `@testing-library/jest-dom/vitest`), `include: ['resources/js/**/*.test.{ts,tsx}']`, alias `@` → `resources/js`.
- [ ] `playwright.config.ts` : `testDir: 'tests/e2e'`, `baseURL: process.env.E2E_BASE_URL ?? 'http://localhost'`, projet `chromium` seul, `use.locale: 'fr-FR'`, `use.timezoneId: 'Europe/Paris'`, `reporter: [['list'], ['html', { open: 'never' }]]`.
- [ ] `sail npx playwright install --with-deps chromium` (dans le conteneur, ou en local si Playwright tourne hors Sail ; documenter le choix dans `CLAUDE.md`).
- [ ] `tsconfig.json` : `strict`, `noUncheckedIndexedAccess`, `noImplicitOverride`.
- [ ] `eslint.config.js` : ajouter `@typescript-eslint/no-explicit-any: error`.
- [ ] `package.json` scripts :
  ```json
  "typecheck": "tsc --noEmit",
  "lint": "eslint .",
  "format": "prettier --write .",
  "format:check": "prettier --check .",
  "test": "vitest run",
  "e2e": "playwright test",
  "check": "npm run typecheck && npm run lint && npm run format:check && npm run test"
  ```

### 6.8 CI
- [ ] `.github/workflows/ci.yml` : déclencheurs `push` et `pull_request` sur `main` ; services `postgres:16` et `redis:7` ; étapes : checkout, PHP 8.3 avec extensions `pgsql pdo_pgsql redis intl gd`, `composer install --no-interaction --prefer-dist`, copie `.env.example` → `.env` + `key:generate`, `composer lint-check`, `composer analyse`, `composer test`, Node 22, `npm ci`, `npm run check`, `npm run build`, `npx playwright install --with-deps chromium`, démarrage `php artisan serve --port=8000 &` avec `E2E_BASE_URL=http://localhost:8000`, `npm run e2e`, upload du rapport Playwright en artefact.
- [ ] Protection de branche `main` : « Require status checks » sur le job `ci`.

### 6.9 Premiers tests et versions
- [ ] Écrire et faire passer les cinq tests de §5.
- [ ] `sail composer check && sail npm run check && sail npm run e2e` verts.
- [ ] Remplir `docs/roadmap/04_VERSIONS.md` avec `sail composer show` et `sail npm ls --depth=0`.
- [ ] Commit `chore(bloc-00): terminé`, tag `bloc-00-done`, push.

## 7. Checkpoint démontrable

1. Sur une machine propre : `git clone`, `cp .env.example .env`, `sail up -d`, `sail composer install`, `sail npm install`, `sail artisan key:generate`, `sail artisan migrate`, `sail npm run dev` → `http://localhost` affiche la page d'accueil du starter kit.
2. `sail composer check` et `sail npm run check` verts en moins de 3 minutes.
3. `sail npm run e2e` vert.
4. La CI du dernier commit sur `main` est verte.
5. `http://localhost:8025` (Mailpit) et `http://localhost:8900` (console MinIO) répondent.

## 8. Critères de sortie

- [ ] Tous les livrables de §3 existent.
- [ ] Aucun fichier du dossier produit n'est resté à la racine.
- [ ] `grep -ri "racines" app resources/js lang tests database` ne retourne rien (le nom de marque provisoire ne doit être que dans `config/brand.php` et les docs).
- [ ] `04_VERSIONS.md` rempli.

## 9. Règle de décision par défaut

Si une commande du starter kit a changé de forme (options de `laravel new`, structure des pages d'auth), suivre la documentation officielle Laravel du moment et noter l'écart dans `03_DECISIONS.md`. Ne pas remplacer le starter kit par une installation manuelle.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
