# Conventions du projet

Ces conventions s'appliquent à tout le code, tous les tests et tous les documents. Elles ne se discutent pas bloc par bloc : si une convention doit changer, on la change ici d'abord, puis on met le code en conformité.

## 1. Arborescence cible du dépôt (état après le bloc 00)

```
/                               ← racine = projet Laravel (déployé tel quel par Forge)
├── app/
│   ├── Actions/                ← une classe = une action métier (ex. ValidateStory, IssueRecordToken)
│   ├── Engine/                 ← moteur de complétion : Rules/, Detectors/, EngineTick
│   ├── Enums/                  ← enums PHP 8.1+ (StoryState est géré par model-states, pas ici)
│   ├── Exceptions/Domain/      ← exceptions métier (ForbiddenTransition, TokenExpired…)
│   ├── Filament/               ← admin interne uniquement (Resources, Pages, Widgets)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Public/         ← landing, essai 60 s, tunnel
│   │   │   ├── Initiator/      ← espace Initiateur·rice (authentifié)
│   │   │   ├── Narrator/       ← pages par jeton `record`
│   │   │   ├── Family/         ← pages par jeton `listen_*` et `qr`
│   │   │   └── Webhooks/       ← Stripe, Twilio, Resend, ASR
│   │   ├── Middleware/
│   │   └── Requests/           ← Form Requests, une par action HTTP
│   ├── Jobs/                   ← jobs de queue, un par étape de pipeline
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Services/
│   │   ├── Audio/              ← ffmpeg, durée, dérivés
│   │   ├── Llm/                ← StoryRenderer (interface) + ClaudeStoryRenderer + FakeStoryRenderer
│   │   ├── Sms/                ← SmsSender (interface) + TwilioSmsSender + FakeSmsSender
│   │   ├── Storage/            ← MediaStorage (interface S3/R2) + réplication
│   │   ├── Tokens/             ← TokenService, OtpService
│   │   └── Transcription/      ← TranscriptionProvider (interface) + Gladia + Deepgram + Fake
│   ├── Settings/               ← classes spatie/laravel-settings (BrandSettings, PilotSettings)
│   └── States/Story/           ← classes d'états spatie/laravel-model-states
├── config/
│   ├── brand.php               ← valeurs par défaut de marque (surchargées par BrandSettings en base)
│   └── product.php             ← paramètres produit chiffrés (durées, seuils, plafonds)
├── database/{migrations,factories,seeders}/
├── docs/
│   ├── dossier/                ← les 5 documents produit (déplacés au bloc 00)
│   ├── reference/remento-screenshot/   ← captures, ignoré par git
│   ├── roadmap/                ← ce dossier
│   ├── runbooks/               ← restauration, incident, déploiement
│   └── spikes/                 ← comptes rendus des spikes (navigateur, ASR)
├── lang/fr/                    ← toutes les chaînes UI et notifications
├── resources/
│   ├── js/
│   │   ├── brand/              ← BrandProvider, tokens CSS
│   │   ├── components/ui/      ← shadcn/ui (généré, ne pas modifier à la main sauf tokens)
│   │   ├── components/         ← composants métier partagés
│   │   ├── hooks/
│   │   ├── layouts/            ← PublicLayout, InitiatorLayout, NarratorLayout, FamilyLayout
│   │   ├── lib/                ← utilitaires purs (testés en Vitest)
│   │   ├── pages/{public,initiator,narrator,family}/
│   │   └── recorder/           ← machine à états d'enregistrement, IndexedDB, upload
│   ├── playbooks/*.md          ← playbooks support affichés dans Filament
│   └── views/                  ← app.blade.php (racine Inertia), emails, legal/*.md
├── routes/{web,narrator,family,webhooks,console}.php
└── tests/
    ├── Feature/                ← miroir de app/ (Http, Jobs, Engine…)
    ├── Unit/
    ├── e2e/                    ← Playwright (*.spec.ts)
    └── bench/asr/              ← banc d'essai WER (corpus privé, non commité)
```

## 2. Nommage

- **Interface utilisateur en français, code en anglais.** Le glossaire `02_GLOSSAIRE_TECH.md` fixe la correspondance. On n'invente pas de synonyme : un `Narrator` ne devient jamais `Storyteller` dans le code.
- **Aucune occurrence du nom de marque** dans `app/`, `resources/js/`, `lang/`, `tests/`, `database/`. Le nom vient de `BrandSettings::product_name`. Le test `tests/Unit/BrandAgnosticTest.php` (bloc 01) échoue si le nom configuré dans `config/brand.php` apparaît ailleurs.
- Modèles au singulier (`Story`), tables au pluriel (`stories`), clés primaires `uuid` ordonnées (`HasUuids`) pour tout modèle exposé à l'extérieur ; `bigint` autorisé pour les tables purement internes (`engine_events`, `audit_logs`).
- Actions : verbe à l'infinitif + objet, une méthode publique `handle()` : `IssueRecordToken`, `ValidateStory`, `HideStory`.
- Jobs : verbe + objet, suffixe `Job` interdit (le namespace suffit) : `App\Jobs\TranscodeRecording`.
- Règles du moteur : préfixe par état détecté : `App\Engine\Rules\InvitationNotAccepted`.
- Événements analytics : `snake_case` préfixé par domaine, listés dans l'enum `App\Enums\AnalyticsEvent` (bloc 15).
- Routes nommées par espace : `public.*`, `initiator.*`, `narrator.*`, `family.*`, `webhooks.*`, `admin.*`.
- Clés de traduction : `<espace>.<page>.<élément>` en `snake_case` : `narrator.record.start_button`.

## 3. Style PHP

- `declare(strict_types=1);` en tête de tout fichier PHP.
- Classes `final` par défaut, `readonly` pour les DTO, promotion de constructeur, types de retour partout, jamais `mixed` sans commentaire justificatif.
- Enums PHP natifs (backed, `string`) pour toute valeur fermée.
- Contrôleurs sans logique métier : valider (Form Request), autoriser (Policy), appeler une Action, retourner une réponse Inertia ou JSON. Un contrôleur de plus de 40 lignes est un signal de refactor.
- Aucune requête Eloquent dans les composants React ni dans les vues Blade : les props Inertia sont construites par des classes `App\Http\Resources\*` ou par l'Action.
- Larastan niveau 8 sur `app/`, `config/`, `database/`, `routes/`. Les tests ne sont pas analysés par PHPStan.
- Pint avec le preset `laravel`, configuration dans `pint.json` : `declare_strict_types: true`, `final_class: true` (exclure `app/Models`, `app/Filament`, `app/Providers` de `final_class`).

## 4. Style TypeScript et React

- `strict: true`, `noUncheckedIndexedAccess: true`, `any` interdit (ESLint `@typescript-eslint/no-explicit-any: error`).
- Composants fonction, hooks, pas de classes. Un composant par fichier, nom de fichier en `PascalCase.tsx`, tests à côté en `PascalCase.test.tsx`.
- Formulaires avec `useForm` d'Inertia, validation côté serveur par Form Request ; pas de bibliothèque de formulaire supplémentaire.
- shadcn/ui pour les primitives (Button, Dialog, Input…). Les tokens de couleur passent par les variables CSS de marque (bloc 01). Aucune couleur en dur dans les composants métier.
- Toute logique non triviale (machine à états du recorder, calculs d'affichage, formatage) vit dans `resources/js/lib/` ou `resources/js/recorder/` en fonctions pures testées par Vitest.
- Les pages narrateur et famille n'importent aucune dépendance lourde : budget 150 Ko gzip de JavaScript par page, vérifié par `npm run build -- --report` et un test Playwright qui mesure la taille des ressources.

## 5. Protocole TDD

**Cycle obligatoire pour chaque étape d'un bloc :**

1. Écrire le test décrit en §5 du bloc (ou plus fin). Le nommer d'après le comportement : `it('refuses to share a story that is not validated')`.
2. Lancer le test, constater l'échec pour la bonne raison (pas une erreur de syntaxe).
3. Écrire le minimum de code qui le fait passer.
4. Refactorer sans changer le comportement, tests verts.
5. Commit.

**Ce qu'on teste à chaque couche :**

| Couche | Outil | Obligatoire pour |
|---|---|---|
| Unitaire PHP | Pest, dossier `tests/Unit` | Toute Action, Service, Rule du moteur, transition d'état, Value Object |
| Feature PHP | Pest, `tests/Feature` | Toute route (succès, refus d'autorisation, validation), tout Job (avec fakes), tout webhook (signature valide et invalide) |
| Unitaire front | Vitest (via `vp test`) + Testing Library, fichiers `*.test.tsx` / `*.test.ts` | Tout composant qui a un état ou une condition, toute fonction de `lib/` et `recorder/` |
| Bout en bout | Playwright, `tests/e2e/*.spec.ts` | Le scénario nominal de chaque bloc et son scénario d'échec principal |
| Accessibilité | `@axe-core/playwright` dans les specs des pages narrateur et famille | Zéro violation `serious` ou `critical` |

**Règles :**

- Les fournisseurs externes (Twilio, Resend, Stripe, Gladia, Deepgram, Anthropic, R2) ont chacun une implémentation `Fake*` de leur interface, utilisée dans les tests. Aucun test n'appelle le réseau. `Http::preventStrayRequests()` est activé dans `tests/Pest.php`.
- Le temps est contrôlé : `$this->travelTo(...)` ou `Carbon::setTestNow()` ; aucune assertion ne dépend de l'heure réelle.
- Les factories couvrent tous les états : `Story::factory()->recorded()`, `->validated()`, etc.
- Une régression corrigée s'accompagne toujours d'un test qui la reproduisait.
- Les tests Playwright tournent contre `sail` avec la base `testing` réinitialisée (`sail artisan migrate:fresh --seed --env=testing`) et les fournisseurs en mode `fake` (`ASR_PROVIDER=fake`, `SMS_PROVIDER=fake`, `LLM_PROVIDER=fake`).

## 6. Commandes canoniques

Définies dans `composer.json` (`scripts`) et `package.json` (`scripts`) au bloc 00. On n'en invente pas d'autres.

| Commande | Fait |
|---|---|
| `sail up -d` | Démarre l'environnement local : app, pgsql, redis, mailpit, minio (clamav au bloc 12) |
| `sail composer check` | Alias de `ci:check` : contrôle front puis PHP, la porte complète |
| `sail composer test` | Enchaîne `lint:check`, `types:check` et les tests PHP |
| `sail composer lint` | Pint, corrige |
| `sail composer lint:check` | Pint, vérifie sans corriger |
| `sail composer types:check` | PHPStan niveau 8 |
| `sail npm run check` | Vite+ : format (oxfmt) et lint (oxlint) |
| `sail npm run check:fix` | Vite+ : corrige format et lint |
| `sail npm run types:check` | `tsc --noEmit` |
| `sail npm run test` | Vitest, une passe |
| `sail npm run test:watch` | Vitest en continu |
| `sail npx playwright test` | Tests bout en bout, depuis le conteneur |
| `sail npm run build` | Compile les assets |
| `sail artisan migrate:fresh --seed` | Base locale propre avec le corpus et un projet de démonstration |

L'application locale répond sur `http://localhost:8001`, Mailpit sur `http://localhost:8027`, la console MinIO sur `http://localhost:8901` (ports décalés, décision T-34). Depuis le Mac, Playwright a besoin de `E2E_BASE_URL=http://localhost:8001` ; dans le conteneur, la valeur par défaut suffit.

## 6bis. Versions des dépendances

- **On installe toujours la dernière version stable.** `composer require <paquet>` et `npm i <paquet>` sans contrainte de version ; on laisse le gestionnaire résoudre. Les numéros écrits dans les blocs de la roadmap sont indicatifs et datent de sa rédaction : quand ils divergent de ce qui est publié, la dernière version gagne et l'écart est noté dans `03_DECISIONS.md`.
- **On vérifie avant de clore un bloc** : `sail composer outdated --direct` et `sail npm outdated` ne doivent lister aucune montée possible, ou chaque exception doit être justifiée par écrit.
- **Une montée majeure passe par la porte qualité complète** avant d'être commitée, et seule, pour que l'échec soit attribuable.
- Dependabot ouvre des demandes hebdomadaires sur Composer, npm et les actions GitHub ; la CI les valide.

## 7. Git et commits

- Dépôt GitHub privé, branche `main` protégée par la CI (les checks doivent passer). Travail en solo : commits directs sur `main` autorisés ; une branche par bloc si le bloc dure plus d'une journée.
- Conventional Commits avec le bloc en scope : `feat(bloc-04): resumable multipart upload to R2`, `test(bloc-04): recorder survives page reload`, `chore(bloc-04): terminé`.
- Un commit = un cycle TDD ou une étape cochée. Pas de commit « WIP » sur `main`.
- Tag annoté `bloc-XX-done` à la fin de chaque bloc.
- Jamais commités : `.env*` sauf `.env.example`, `docs/reference/remento-screenshot/`, `tests/bench/asr/corpus/`, `storage/`, `node_modules/`, `vendor/`.

## 8. Variables d'environnement

Toutes dans `.env.example` avec une valeur d'exemple ou vide et un commentaire d'une ligne. Toute variable nouvelle est ajoutée ici dans le même commit.

| Variable | Rôle | Exemple local |
|---|---|---|
| `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Standard Laravel | `local`, généré, `true`, `http://localhost` |
| `APP_NAME` | Repli uniquement si `BrandSettings` indisponible (migrations) | `Product` |
| `APP_TIMEZONE` | Toujours `Europe/Paris` | `Europe/Paris` |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Français partout | `fr`, `fr`, `fr_FR` |
| `LINKS_DOMAIN` | Domaine court des liens `/r`, `/l`, `/q`, `/i` ; identique à l'hôte de `APP_URL` en local | `localhost` |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE` | Postgres | `pgsql`, `pgsql`, `5432`, `app`, `sail`, `password`, `prefer` |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` | Redis via extension phpredis | `phpredis`, `redis`, vide, `6379` |
| `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER` | Tous `redis` à partir du bloc 05 | `redis` |
| `FILESYSTEM_DISK` | Disque par défaut | `r2` (local : `r2` pointant sur MinIO du Sail, voir bloc 04) |
| `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY` | Identifiants R2 (jeton limité aux buckets du projet) | — |
| `R2_ENDPOINT` | `https://<account>.eu.r2.cloudflarestorage.com` pour la juridiction UE | — |
| `R2_BUCKET_MEDIA`, `R2_BUCKET_MEDIA_REPLICA`, `R2_BUCKET_BACKUPS` | Trois buckets distincts | `media`, `media-replica`, `backups` |
| `MAIL_MAILER`, `RESEND_API_KEY`, `RESEND_WEBHOOK_SECRET`, `MAIL_FROM_ADDRESS` | Email via Resend ; local : `smtp` vers Mailpit | `resend` / `smtp` |
| `SMS_PROVIDER` | `twilio` ou `fake` | `fake` |
| `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM` | SMS ; `TWILIO_FROM` est le numéro de repli si l'expéditeur alphanumérique est refusé par l'opérateur | — |
| `ASR_PROVIDER` | `gladia`, `deepgram` ou `fake` | `fake` |
| `GLADIA_API_KEY`, `DEEPGRAM_API_KEY` | Clés ASR | — |
| `ASR_CALLBACK_SECRET` | Secret HMAC ajouté aux URLs de callback ASR | généré |
| `LLM_PROVIDER` | `claude` ou `fake` | `fake` |
| `ANTHROPIC_API_KEY`, `LLM_MODEL`, `LLM_EFFORT`, `LLM_MAX_TOKENS` | Rendu Fluide | —, `claude-opus-5`, `medium`, `8000` |
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Cashier | clés de test |
| `STRIPE_PRICE_PILOT`, `STRIPE_PRICE_PREVENTE_99`, `STRIPE_PRICE_PREVENTE_129`, `STRIPE_PRICE_EXTRA_COPY`, `STRIPE_PRICE_PHONE_OPTION` | Identifiants `price_…` créés dans Stripe | — |
| `POSTHOG_KEY`, `POSTHOG_HOST` | Analytics ; hôte UE obligatoire | —, `https://eu.i.posthog.com` |
| `FLARE_KEY` | Suivi d'erreurs en production | — |
| `OH_DEAR_HEALTH_CHECK_SECRET` | Endpoint de santé | — |
| `TELESCOPE_ENABLED` | `true` en local seulement | `true` |
| `CLAMAV_HOST`, `CLAMAV_PORT` | Antivirus (bloc 12) | `clamav`, `3310` |
| `FFMPEG_BINARIES`, `FFPROBE_BINARIES` | Chemins ffmpeg | `/usr/bin/ffmpeg`, `/usr/bin/ffprobe` |
| `BROWSERSHOT_NODE_BINARY`, `BROWSERSHOT_CHROME_PATH` | Génération PDF (bloc 13) | — |

## 9. Sécurité, règles permanentes

- Les jetons ne sont jamais stockés en clair : on stocke `sha256(token)`. La comparaison se fait sur le hash.
- Aucune donnée personnelle dans une URL, ni en chemin ni en query : ni nom, ni email, ni téléphone, ni identifiant séquentiel.
- Toute route par jeton passe par le middleware de masquage des journaux (bloc 03) et par le limiteur `tokens` (60 requêtes/minute/IP).
- Tout webhook vérifie sa signature avant de lire le corps ; un test couvre la signature invalide.
- Les secrets vivent dans l'environnement Forge, jamais dans le code ni dans les tests.
- En-têtes HTTP : `Content-Security-Policy` stricte (nonce pour Inertia), `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`, `Permissions-Policy: microphone=(self)` (le micro doit rester autorisé sur les pages narrateur).
- Tout accès en lecture à une donnée sensible depuis le back-office est journalisé (bloc 11).

## 10. Internationalisation

- Toutes les chaînes visibles vivent dans `lang/fr/*.php`, un fichier par espace : `public.php`, `initiator.php`, `narrator.php`, `family.php`, `notifications.php`, `admin.php`, `legal.php`.
- Le front reçoit l'objet de traduction de la page courante via la prop Inertia partagée `i18n` et l'utilise avec le hook `useT()` (`resources/js/hooks/useT.ts`, bloc 01). Pas de bibliothèque tierce.
- Test `tests/Unit/I18nKeysTest.php` : toute clé appelée dans `resources/js/**/*.tsx` existe dans `lang/fr`. Test `tests/Unit/ForbiddenVocabularyTest.php` : aucune expression de R-11 dans `lang/fr`, `resources/views`, `resources/playbooks`.
- Pluriels et genres : utiliser la syntaxe `trans_choice` et la forme inclusive « Initiateur·rice », « Narrateur·rice » dans l'interface Initiateur ; l'interface narrateur tutoie-vouvoie selon le réglage du projet (`Project::address_form` : `vous` par défaut).

## 11. Accessibilité

- WCAG 2.2 AA. Zones tactiles ≥ 44 × 44 px sur toutes les pages narrateur et famille. Taille de police minimale 18 px sur les pages narrateur, respect de l'agrandissement système (unités `rem`, jamais de `maximum-scale`).
- Aucun compte à rebours visible. Aucune animation qui ne puisse être coupée (`prefers-reduced-motion`).
- Chaque bouton a un libellé texte ; les icônes seules sont interdites sur les pages narrateur.
- Erreurs récupérables : un message en langage simple et une action de reprise, jamais un code d'erreur seul.
- Contraste ≥ 4,5:1 vérifié par un test Vitest sur les tokens de marque (bloc 01) : l'admin refuse une combinaison de couleurs qui casse le contraste.

## 12. Journalisation et observabilité

- Canal `stack` en local, `flare` en production. Niveau `info` en production.
- Contexte structuré : `project_id`, `story_id`, `actor_type`, `actor_id`, `rule_id`. Jamais `token`, `phone`, `email` en clair.
- Le processeur Monolog `RedactTokens` (bloc 03) remplace tout segment `/r/…`, `/l/…`, `/q/…`, `/i/…` par `/[type]/[redacted]` dans tous les journaux.
- Telescope : activé en local seulement, `hideRequestParameters(['token', 'code'])`, `hideRequestHeaders(['authorization', 'cookie'])`.

## 13. Données et migrations

- Postgres uniquement. Aucune fonctionnalité qui ne marche pas sur Postgres n'est acceptée.
- `timestampsTz()` partout. Fuseau applicatif `Europe/Paris`, stockage en UTC.
- Migrations toujours réversibles jusqu'au bloc 16 ; après le premier déploiement en production, plus jamais de modification d'une migration existante.
- Soft delete uniquement là où le dossier le prévoit (état `trashed` des histoires = colonne `trashed_at`, pas `SoftDeletes` global).
- Contraintes en base et pas seulement en code : clés étrangères, `check` sur les enums stockés en texte, index uniques sur les hash de jetons.
- Le modèle complet est dans `annexes/B_modele_donnees.md`. Toute nouvelle table y est ajoutée dans le même commit.

## 14. Jobs et queues

- Horizon dès le bloc 05. Files : `default`, `media` (ffmpeg, réplication), `transcription`, `llm`, `notifications`, `engine`, `exports`.
- Tout job est idempotent : clé d'idempotence en base (`engine_events.dedupe_key`, `outbound_messages.dedupe_key`) ou vérification d'état avant action.
- `tries` explicite, `backoff` exponentiel, `failed()` qui journalise et, pour les jobs de pipeline média, remet l'histoire dans un état cohérent.
- Aucun job ne supprime un objet R2 sauf `PurgeDeletedStory` (bloc 07) et `EraseProject` (bloc 14).

## 15. Feature flags (Laravel Pennant)

Tous déclarés dans `app/Features/`, portée par projet sauf indication.

| Flag | Portée | Valeurs | Bloc |
|---|---|---|---|
| `validation-variant` | projet | `immediate` (A) / `deferred` (B) | 07 |
| `mandate-delegation` | projet | bool | 07 |
| `reaction-notification-timing` | projet | `immediate` / `next-morning` | 08 |
| `prevente-price` | visiteur anonyme (cookie) | `99` / `129` | 10 |
| `gift-experience` | projet | `ecard` / `printed-card` / `audio-message` | 10 |
| `phone-option-offer` | global | bool, désactivé quand le plafond est atteint | 17 |

## 16. Erreurs

- Exceptions métier dans `App\Exceptions\Domain\`, une par cas : `TokenExpired`, `TokenRevoked`, `ForbiddenTransition`, `StoryNotVisible`, `PhoneOptionCapReached`…
- Elles sont rendues par `bootstrap/app.php` : pages Inertia dédiées pour les espaces narrateur et famille (langage simple, action de reprise), JSON pour les webhooks et l'API interne.
- Jamais de `500` non maîtrisé sur une page narrateur : un test Playwright ouvre un lien révoqué et un lien expiré et vérifie la page amicale.

## 17. Documents à maintenir à chaque bloc

- `docs/roadmap/blocs/BXX_*.md` : cases cochées, note de checkpoint en bas.
- `docs/roadmap/04_VERSIONS.md` : versions figées.
- `docs/roadmap/03_DECISIONS.md` : toute décision prise faute d'information.
- `docs/roadmap/annexes/B_modele_donnees.md` : toute table ou colonne nouvelle.
- `.env.example` et la table §8 ci-dessus.
- `CLAUDE.md` à la racine : chemins et commandes si ils changent.
