# Bloc 01 — Marque, réglages, admin Filament, i18n

Statut : ☑ terminé le 2026-09-02 · Dépend de : 00 · Tag de fin : `bloc-01-done`
Références dossier : doc 04 §9 (domaine unique annoncé), R-11 (vocabulaire interdit), décision T-14, T-21.

## 1. Objectif

Le nom, le domaine des liens, les couleurs, les polices, le logo et l'expéditeur SMS se changent depuis l'admin sans déploiement, et tout le front, les emails et les SMS s'y conforment instantanément. Toutes les chaînes visibles passent par les fichiers de langue. Le back-office Filament existe avec une première page « Marque ».

## 2. Pourquoi

Le nom et le domaine ne sont pas décidés. Le dossier impose un domaine unique et stable annoncé dès l'invitation ; le jour où il sera choisi, il ne doit y avoir qu'un champ à remplir.

## 3. Livrables

- Filament installé, panneau `/admin`, accès réservé aux `users.role ∈ {admin, support, support_readonly}`.
- `App\Settings\BrandSettings` (spatie/laravel-settings) avec page Filament « Marque » et aperçu en direct.
- Précédence : valeur en base > `config/brand.php` > défaut codé.
- Variables CSS de marque injectées dans `resources/views/app.blade.php` ; Tailwind 4 et shadcn/ui câblés sur ces variables.
- `BrandProvider` React + hook `useBrand()`.
- Hook `useT()` et prop Inertia `i18n`.
- Tests : précédence, injection CSS, contraste, agnosticité de marque, clés i18n, vocabulaire interdit.

## 4. Packages

```bash
sail composer require filament/filament    # dernière version stable, cf. §6bis des conventions
sail artisan filament:install --panels          # crée app/Providers/Filament/AdminPanelProvider.php, chemin /admin
sail composer require spatie/laravel-settings filament/spatie-laravel-settings-plugin
sail artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="migrations"
sail artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="settings"
```

Aucun package npm supplémentaire.

## 5. Tests à écrire d'abord

- `tests/Unit/Settings/BrandSettingsTest.php`
  - `it('falls back to config when no value is stored')`
  - `it('prefers the stored value over config')`
  - `it('rejects an sms sender id longer than 11 chars or without a letter')`
  - `it('rejects a color pair whose contrast ratio is below 4.5')`
- `tests/Feature/Brand/BrandCssInjectionTest.php`
  - `it('injects brand css variables in the inertia root view')` → GET `/` contient `--brand-primary: #…`
  - `it('shares brand and i18n props with every inertia page')`
- `tests/Unit/BrandAgnosticTest.php`
  - `it('has no brand name outside config/brand.php')` : lit `config('brand.product_name')`, cherche la chaîne (insensible à la casse) dans `app/`, `resources/js/`, `lang/`, `tests/`, `database/` ; échoue si trouvée. Le nom de test par défaut « Product » est exclu de la recherche si trop court (< 4 caractères).
- `tests/Unit/I18nKeysTest.php`
  - `it('has a translation for every key used in tsx files')` : regex `t\(['"]([a-z0-9_.]+)['"]` sur `resources/js/**/*.tsx`, vérifie `Lang::has('fr:…')`.
- `tests/Unit/ForbiddenVocabularyTest.php`
  - `it('does not use forbidden vocabulary in user facing strings')` : expressions de R-11 sur `lang/fr`, `resources/views`, `resources/playbooks`.
- `tests/Feature/Admin/BrandPageTest.php`
  - `it('forbids the brand page to initiators')`
  - `it('lets an admin update the product name and colors')`
- `resources/js/brand/BrandProvider.test.tsx`
  - `renders children with brand css variables on the root element`
- `resources/js/hooks/useT.test.tsx`
  - `returns the translation and interpolates :name`
  - `returns the key itself when missing (and logs in dev)`
- `resources/js/lib/contrast.test.ts` : ratio WCAG entre deux couleurs hex.
- `tests/e2e/brand.spec.ts` : un admin change la couleur primaire et le nom dans `/admin`, la page `/` reflète les deux sans redéploiement.

## 6. Étapes

### 6.1 Filament
- [x] Installer Filament (§4). Dans `AdminPanelProvider` : `->path('admin')`, `->login()`, `->brandName(fn () => app(BrandSettings::class)->product_name)`, `->colors(['primary' => Color::hex(app(BrandSettings::class)->color_primary)])`, `->authGuard('web')`.
- [x] `User` implémente `FilamentUser` ; `canAccessPanel()` retourne vrai pour `role ∈ {admin, support, support_readonly}`.
- [x] Migration : ajouter `role` à `users` si absent (défaut `initiator`) ; seeder `AdminUserSeeder` lisant `ADMIN_EMAIL`/`ADMIN_PASSWORD` de l'env, uniquement en `local`/`testing`.
- [x] Test `BrandPageTest` rouge → vert.

### 6.2 BrandSettings
- [x] `app/Settings/BrandSettings.php` : propriétés `product_name`, `short_name`, `tagline`, `links_domain`, `support_email`, `support_phone` (nullable), `sms_sender_id`, `color_primary`, `color_primary_foreground`, `color_accent`, `color_accent_foreground`, `color_background`, `color_surface`, `color_text`, `color_muted`, `font_display`, `font_body`, `logo_path` (nullable), `favicon_path` (nullable), `legal_entity`, `legal_address` (nullable). `group()` retourne `brand`.
- [x] Migration de settings `create_brand_settings` : chaque propriété initialisée depuis `config('brand.*')`.
- [x] `app/Settings/Casts/` inutile ; validation dans la page Filament et dans `App\Actions\UpdateBrandSettings` (règles : hex `#RRGGBB`, sender id `^[A-Za-z0-9]{3,11}$` avec ≥ 1 lettre, contraste `text/background` et `primary/primary_foreground` ≥ 4,5 via `App\Support\Contrast::ratio()`).
- [x] `App\Support\Brand` (façade légère) : `name()`, `linksDomain()`, `cssVariables(): array`, `mailFromName()`.
- [x] Tests `BrandSettingsTest` verts.

### 6.3 Page Filament « Marque »
- [x] `app/Filament/Pages/ManageBrand.php` (`SettingsPage`, `$settings = BrandSettings::class`) : sections « Identité » (nom, nom court, slogan, entité légale), « Domaines et contacts » (domaine des liens, email support, expéditeur SMS avec aide « 3 à 11 caractères alphanumériques »), « Couleurs » (ColorPicker par couleur, aperçu d'un bouton et d'un bloc texte rendu en Blade avec les couleurs saisies), « Typographie » (select parmi une liste fermée de polices Google Fonts : Fraunces, Newsreader, Source Serif 4, Literata pour le display ; Inter, Instrument Sans, Source Sans 3 pour le corps), « Logo » (upload SVG/PNG, stocké sur le disque `public`).
- [x] Bouton « Vérifier le contraste » et refus d'enregistrement si un ratio < 4,5.
- [x] Journaliser chaque modification (préparation du bloc 11 : appel à `AuditLog::record()` s'il existe, sinon `Log::info` avec contexte).

### 6.4 Injection dans le front
- [x] `resources/views/app.blade.php` : `<style nonce="…">:root { --brand-primary: {{ $brand['colors']['primary'] }}; … }</style>` à partir de `Brand::cssVariables()` ; `<title>` = nom du produit ; `<link rel="icon">` = favicon de marque ou défaut ; `<link>` Google Fonts pour les deux polices choisies avec `font-display: swap` et une pile de repli.
- [x] `resources/css/app.css` : bloc `@theme { --color-primary: var(--brand-primary); … --font-display: var(--brand-font-display), Georgia, serif; --font-body: var(--brand-font-body), system-ui, sans-serif; }` ; réassigner les variables shadcn (`--primary`, `--primary-foreground`, `--accent`, `--background`, `--foreground`, `--muted-foreground`) sur les variables de marque.
- [x] `App\Http\Middleware\HandleInertiaRequests::share()` : `brand` (nom, nom court, slogan, domaine des liens, email support, logo URL) et `i18n` (voir 6.5).
- [x] `resources/js/brand/BrandProvider.tsx` + `useBrand()` : lit `usePage().props.brand` ; `BrandLogo` rend l'image ou le nom en texte.
- [x] Emails : layout `resources/views/emails/layout.blade.php` avec couleurs et nom de marque ; `MAIL_FROM_NAME` remplacé à l'exécution par `Brand::mailFromName()` via un `Mailable` de base `App\Mail\BrandedMailable`.
- [x] Tests `BrandCssInjectionTest`, `BrandProvider.test.tsx`, `contrast.test.ts` verts.

### 6.5 i18n
- [x] `lang/fr/public.php`, `initiator.php`, `narrator.php`, `family.php`, `notifications.php`, `admin.php`, `legal.php`, `validation.php` (publier `sail artisan lang:publish` pour les messages de validation Laravel en français).
- [x] `HandleInertiaRequests::share()` : `i18n` = fusion des fichiers de l'espace courant (déterminé par le préfixe de route : `narrator.*` → `narrator.php` + `common.php`, etc.), aplatie en clés pointées.
- [x] `resources/js/hooks/useT.ts` : `useT()` retourne `t(key, params?)` ; interpolation `:name` ; en développement, `console.warn` sur clé manquante.
- [x] Remplacer toute chaîne en dur du starter kit encore visible (pages d'auth) par des clés `initiator.auth.*`.
- [x] Tests `I18nKeysTest`, `ForbiddenVocabularyTest`, `useT.test.tsx` verts.

### 6.6 Agnosticité de marque
- [x] Test `BrandAgnosticTest` vert. Corriger toute occurrence trouvée.
- [x] Ajouter au `CLAUDE.md` la règle « aucun nom de marque dans le code ».

### 6.7 Clôture
- [x] `tests/e2e/brand.spec.ts` vert.
- [x] `sail composer check`, `sail npm run check`, CI verts.
- [x] `04_VERSIONS.md` : Filament, spatie/laravel-settings, plugin.
- [x] Commit `chore(bloc-01): terminé`, tag `bloc-01-done`.

## 7. Checkpoint démontrable

1. Se connecter à `/admin` avec l'admin seedé. Page « Marque » : changer le nom en « Essai », la couleur primaire en `#8B0000`, l'expéditeur SMS en `ESSAI`. Enregistrer.
2. Ouvrir `/` dans un autre onglet sans redéploiement : le titre de l'onglet est « Essai », le bouton principal est rouge sombre.
3. Tenter une couleur de texte `#CCCCCC` sur fond `#FFFFFF` : refus avec message de contraste.
4. Tenter un expéditeur `123456789012` : refus.
5. `sail artisan tinker` : `app(App\Settings\BrandSettings::class)->product_name` retourne « Essai ».

## 8. Critères de sortie

- [x] Livrables §3 présents.
- [x] Aucun texte visible en dur dans `resources/js/pages/**` (le test I18n couvre les clés utilisées ; une revue manuelle vérifie qu'il n'y a pas de texte hors `t()`).
- [x] Les pages d'auth du starter kit sont en français et utilisent la marque.

## 9. Règle de décision par défaut

Si Filament 4 impose une contrainte incompatible avec Inertia (conflit de Livewire ou de Vite), isoler les assets Filament (`filament:assets`) et servir Inertia via Vite sans partage de CSS. Ne pas basculer l'admin sur Inertia.

## 10. Note de checkpoint

**2026-09-02 — exécuté — résultat : conforme.** Le nom, le domaine des liens, les couleurs et les polices se changent dans `/admin/manage-brand` et s'appliquent sur le site public sans redéploiement, démontré par un test bout en bout. Porte verte : PHPStan niveau 8 sans erreur, 102 tests Pest, 16 tests Vitest, 3 tests Playwright.

**Écarts par rapport au plan :**

- Filament 5.7 au lieu du ^4.0 annoncé, en application de la règle « toujours la dernière version stable » (T-37, `01_CONVENTIONS.md` §6bis).
- Telescope reste absent : `laravel/pail`, fourni par le kit, couvre le besoin en local.
- Les traductions du framework viennent de `laravel-lang/common` ; `lang/en` a été supprimé, le français est la seule langue du MVP.
- Le fournisseur React `BrandProvider` a été supprimé au profit d'une lecture directe des propriétés Inertia : monté par `withApp`, il se retrouvait hors du contexte Inertia et ne recevait rien.
- Le sélecteur de polices ne propose que les familles réellement embarquées (Fraunces, Newsreader pour les titres ; Inter, Instrument Sans pour le texte). Elles sont auto-hébergées : aucune requête vers un tiers depuis les pages narrateur. Ajouter une famille demande une ligne dans `vite.config.ts` et une compilation.

**Défauts trouvés et corrigés en chemin :**

- Le titre de page s'accumulait à chaque navigation. Inertia remplace la balise `title`, et le repli relisait un titre déjà composé. Le nom de marque est désormais lu une seule fois dans une balise `meta` que Inertia ne touche pas.
- Le rôle n'est pas assignable en masse, pour qu'une inscription publique ne puisse pas se déclarer administratrice. La valeur par défaut est posée par le modèle à la création, pas par `$attributes`, ce qui garde le type de l'énumération lisible par l'analyse statique.
- Les libellés de couleurs étaient ambigus (« Texte », « Fond »). Ils nomment maintenant explicitement ce qu'ils colorent.
- Filament disait « Sauvegarder » là où l'interface dit « Enregistrer ». Un seul verbe désormais.

**Ce que le bloc laisse ouvert :**

- Le téléversement du logo et du favicon est modélisé (`logo_path`, `favicon_path`) mais l'interface de dépôt attend le bloc 12, qui installe la bibliothèque de médias et l'antivirus.
- L'accès à la page Marque est réservé au rôle Administration. La permission fine `brand.manage` arrive au bloc 02 avec spatie/laravel-permission.
- La double authentification obligatoire du back-office est exigée par le doc 04 §12 et planifiée au bloc 11.
