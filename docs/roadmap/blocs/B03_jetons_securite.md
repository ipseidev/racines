# Bloc 03 — Jetons, OTP et sécurité des liens

Statut : ☑ terminé (2026-09-02) · Dépend de : 02 · Tag de fin : `bloc-03-done`
Références dossier : doc 04 §12 (jetons porteurs, comptes et actes sensibles), doc 04 §9 (anti-phishing), PRD US-02, glossaire §4 et §8.

## 1. Objectif

Un service de jetons unique pour tous les liens du produit, des routes par jeton sûres par construction, l'OTP pour les actes sensibles, le masquage des jetons dans tous les journaux, et les pages d'erreur amicales. Aucune page produit n'existe encore : ce bloc livre l'infrastructure et une page de test.

## 2. Pourquoi

« Quiconque détient le lien peut agir à la place du narrateur. » Tout le reste du produit repose sur ces liens ; leurs propriétés doivent être prouvées avant d'écrire la première page.

## 3. Livrables

- `App\Services\Tokens\TokenService`, `App\Services\Tokens\OtpService`, modèle `AccessToken`, `OtpChallenge`.
- `App\Support\Links` : construction des URLs par type.
- Middleware `ResolveAccessToken`, `SecurityHeaders`, `NoStore`.
- Processeur Monolog `RedactTokens` ; Telescope configuré pour ignorer les chemins à jeton.
- Limiteurs `tokens` et `otp`.
- Interface `SmsSender` avec `FakeSmsSender` et `LogSmsSender` (Twilio arrive au bloc 05) ; notification `OtpCodeNotification` (mail + sms).
- Pages Inertia `narrator/LinkUnavailable`, `family/LinkUnavailable`, `narrator/OtpChallenge`.
- Tables `access_tokens`, `otp_challenges`.

## 4. Packages

Aucun. `random_bytes`, `hash`, Monolog et Laravel suffisent.

## 5. Tests à écrire d'abord

- `tests/Unit/Tokens/TokenServiceTest.php`
  - `it('issues a 43 char base64url token backed by 32 random bytes')`
  - `it('stores only the sha256 hash')` (la colonne `token_hash` ≠ jeton, longueur 64, hex)
  - `it('resolves a valid token of the expected type')`
  - `it('refuses a token of another type')` → `TokenTypeMismatch`
  - `it('refuses an expired token')` → `TokenExpired`
  - `it('refuses a revoked token')` → `TokenRevoked`
  - `it('refuses a used single use token')` → `TokenUsed`
  - `it('rotates a token: old revoked, new linked by replaced_by')`
  - `it('revokes all record tokens of a story when it is validated')` (écouteur sur la transition `Validated`)
  - `it('applies the default ttl per type from config')`
  - `it('increments use_count and last_used_at on resolve')`
- `tests/Unit/Tokens/OtpServiceTest.php`
  - `it('creates a 6 digit challenge hashed in database and sends it on the chosen channel')`
  - `it('verifies a correct code once and issues a sensitive_grant token')`
  - `it('refuses a wrong code and counts the attempt')`
  - `it('locks after 5 attempts for 15 minutes')`
  - `it('refuses an expired challenge')`
  - `it('rate limits challenge creation to 3 per hour per subject')`
- `tests/Feature/Tokens/ResolveAccessTokenMiddlewareTest.php`
  - `it('binds the token and subject to the request for a valid record token')`
  - `it('renders the friendly page with reason expired')`, `revoked`, `not_found`
  - `it('returns 404 for a malformed token without hitting the database')` (regex de route)
  - `it('adds no-store, noindex and no-referrer headers on token pages')`
  - `it('rate limits token routes at 60 per minute per ip')`
- `tests/Unit/Logging/RedactTokensProcessorTest.php`
  - `it('redacts token segments in message and nested context')` pour `/r/`, `/l/`, `/q/`, `/i/`, `/a/`, `/x/`, `/n/`
- `tests/Feature/Security/SecurityHeadersTest.php` : CSP avec nonce, HSTS en production, `Permissions-Policy` autorise le micro sur `/r/*` uniquement.
- `resources/js/pages/narrator/LinkUnavailable.test.tsx` : affiche le message correspondant à la raison et le bouton d'action.
- `tests/e2e/token-errors.spec.ts` : ouvrir un lien révoqué et un lien expiré affiche la page amicale, sans erreur 500, zéro violation axe.

## 6. Étapes

### 6.1 Tables et modèles
- [x] Migrations `create_access_tokens_table`, `create_otp_challenges_table` (annexe B). Ajouter le type `sensitive_grant` à la liste des types (glossaire §4 mis à jour).
- [x] Enum `App\Enums\TokenType` avec `ttl(): ?CarbonInterval` lu depuis `config('product.tokens')`, `isSingleUse(): bool` (`action`, `sensitive_grant`).
- [x] Modèles `AccessToken` (relations morph `subject`, scopes `active()`, méthodes `isExpired()`, `isRevoked()`, `isUsed()`) et `OtpChallenge`.

### 6.2 TokenService
- [x] `issue(TokenType $type, Model $subject, array $scope = [], ?CarbonImmutable $expiresAt = null, ?Model $issuedBy = null, string $reason = 'initial'): IssuedToken` où `IssuedToken` est un DTO `readonly` `{ string $plain; AccessToken $token; }`. Génération : `rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')`.
- [x] `resolve(string $plain, TokenType $expected): AccessToken` : hash, recherche, vérifications type/expiration/révocation/usage, mise à jour `use_count`/`last_used_at`, marque `used_at` si `single_use`.
- [x] `revoke(AccessToken $token, string $reason): void`, `rotate(AccessToken $token, string $reason): IssuedToken`, `revokeAllFor(Model $subject, TokenType $type, string $reason): int`.
- [x] Écouteur `RevokeRecordTokensOnValidation` sur l'événement de transition vers `Validated` (spatie/laravel-model-states émet un événement ; sinon appeler explicitement dans la transition `ValidateStory`).
- [x] `App\Support\Links` : `record($plain)`, `narratorSpace($plain)`, `listen($plain)`, `qr($plain)`, `invitation($plain)`, `action($plain)`, `export($plain)` → `https://{links_domain}/{préfixe}/{plain}` (`http` en local).

### 6.3 Routes et middleware
- [x] `routes/narrator.php` et `routes/family.php` : `Route::domain(config('brand.links_domain'))`, `Route::pattern('token', '[A-Za-z0-9_-]{43}')`.
- [x] `App\Http\Middleware\ResolveAccessToken` paramétré (`resolve.token:record`) : appelle `TokenService::resolve`, place `AccessToken` et son sujet dans `$request->attributes` (`access_token`, `token_subject`), lève les exceptions domaine.
- [x] Rendu des exceptions dans `bootstrap/app.php` : `TokenExpired|TokenRevoked|TokenUsed|TokenNotFound|TokenTypeMismatch` → page Inertia `narrator/LinkUnavailable` ou `family/LinkUnavailable` selon le préfixe, avec `reason` et `can_request_new_link` (vrai pour `record` expiré ou révoqué).
- [x] Middleware `NoStore` (`Cache-Control: no-store`, `X-Robots-Tag: noindex, nofollow`, `Referrer-Policy: no-referrer`) appliqué à tous les groupes par jeton.
- [x] Middleware `SecurityHeaders` global : CSP `default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'nonce-…' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data: blob: https://*.r2.cloudflarestorage.com; media-src 'self' blob: https://*.r2.cloudflarestorage.com; connect-src 'self' https://*.r2.cloudflarestorage.com` (les hôtes R2 sont lus depuis la config), `Strict-Transport-Security` en production, `X-Content-Type-Options`, `Permissions-Policy: microphone=(self), camera=(), geolocation=()`. Nonce via `Vite::useCspNonce()`.
- [x] Limiteurs dans `AppServiceProvider` : `tokens` (60/min/IP, 20/min/jeton), `otp-request` (3/heure/sujet), `otp-verify` (10/min/défi).
- [x] Route de test `GET /r/{token}` → page Inertia `narrator/TokenProbe` affichant le type et le sujet (remplacée au bloc 04 par la vraie page ; la route reste dans les tests du middleware via une route déclarée dans le test).

### 6.4 Journaux
- [x] `App\Logging\RedactTokens` (Monolog `ProcessorInterface`) : regex `#/(r|l|q|i|a|x|n)/[A-Za-z0-9_-]{43}#` → `/$1/[redacted]`, appliqué au message et récursivement au contexte et aux extras. Enregistré via `tap` sur tous les canaux de `config/logging.php`.
- [x] ~~`config/telescope.php`~~ → sans objet : Telescope n'est pas installé (T-29, T-48). Le masquage vit dans le processeur Monolog, donc dans tous les journaux, et non dans la configuration d'un outil.
- [x] Test `RedactTokensProcessorTest` vert.

### 6.5 OTP
- [x] Interface `App\Services\Sms\SmsSender { public function send(string $toE164, string $body, ?string $dedupeKey = null): SmsResult; }` ; `FakeSmsSender` (collecte en mémoire, assertions `assertSentTo`), `LogSmsSender` (local). Liaison dans `AppServiceProvider` selon `SMS_PROVIDER`.
- [x] `OtpService::challenge(Narrator|FamilyMember $subject, OtpPurpose $purpose, Channel $channel): OtpChallenge` : code 6 chiffres (`random_int(0, 999999)` formaté), `code_hash = hash('sha256', code.':'.challenge_id)`, envoi par `OtpCodeNotification` (mail branded ou SMS « {Marque} : votre code est {code}. Il expire dans 10 minutes. Ne le communiquez à personne. »).
- [x] `OtpService::verify(OtpChallenge $challenge, string $code): IssuedToken` : vérifie expiration, verrou, incrémente `attempts`, verrouille à 5, émet un `sensitive_grant` (15 min) ou un `narrator_space` (30 jours) selon `purpose`.
- [x] Page Inertia `narrator/OtpChallenge` : saisie à 6 cases, gros caractères, bouton « Renvoyer le code » (limité), message d'erreur en langage simple.
- [x] Middleware `RequireSensitiveGrant` : exige un `sensitive_grant` valide (cookie signé `sg` contenant le jeton, `HttpOnly`, `SameSite=Strict`, 15 min) pour les routes marquées sensibles ; sinon redirige vers le défi OTP puis retour.
- [x] Documenter dans `docs/roadmap/02_GLOSSAIRE_TECH.md` la frontière : depuis un lien `record`, les actions sur **cette** histoire (`keep_private`, masquer juste après enregistrement) ne demandent pas d'OTP ; toute action sur une autre histoire, une suppression, un réglage durable ou une directive post-mortem exige un `sensitive_grant`.

### 6.6 Pages d'erreur amicales
- [x] `narrator/LinkUnavailable.tsx` : titre selon `reason` (« Ce lien n'est plus valable », « Ce lien a expiré », « Ce lien a déjà été utilisé »), phrase d'explication, bouton « Demander un nouveau lien » (POST `/r/{token}/request-new-link`, limité à 1/heure, crée un `OutboundMessage` d'alerte vers le support et l'Initiateur·rice via `App\Actions\RequestNewLink` ; le contenu de l'alerte arrive au bloc 05, ici la ligne `outbound_messages` n'existe pas encore : journaliser et lever un événement `NewLinkRequested` que le bloc 05 écoutera).
- [x] `family/LinkUnavailable.tsx` : variante sans bouton de renvoi (« Demandez un nouveau lien à la personne qui vous a invité·e »).
- [x] Tests Vitest et Playwright verts.

### 6.7 Clôture
- [x] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts.
- [x] Commit `chore(bloc-03): terminé`, tag `bloc-03-done`.

## 7. Checkpoint démontrable

1. `sail artisan tinker` : émettre un jeton `record` pour une histoire du seeder, ouvrir `http://localhost/r/{jeton}` → page `TokenProbe` affiche « record · Story … ».
2. Révoquer le jeton dans tinker, recharger → page « Ce lien n'est plus valable » avec le bouton de renvoi.
3. Modifier un caractère du jeton → 404 (pas de page amicale, pas de requête SQL visible dans Telescope).
4. `tail -f storage/logs/laravel.log` pendant les requêtes : aucune occurrence du jeton, uniquement `/r/[redacted]`.
5. Ouvrir Telescope : aucune entrée pour les requêtes `/r/*`.
6. Lancer un défi OTP dans tinker pour le narrateur du seeder : le code apparaît dans le log (`LogSmsSender`) ; 5 mauvais codes verrouillent ; le bon code après déverrouillage émet un `sensitive_grant`.

## 8. Critères de sortie

- [x] Aucun endroit du code ne compare un jeton en clair (revue : `grep -rn "token ==" app` vide ; seul `TokenService::resolve` lit `token_hash`).
- [x] Toutes les routes des fichiers `narrator.php` et `family.php` portent `resolve.token:*`, `throttle:tokens`, `no-store`.
- [x] Glossaire §4 et §8 à jour (`sensitive_grant`).

## 9. Règle de décision par défaut

En cas de doute sur le caractère « sensible » d'une action, elle est sensible et exige un `sensitive_grant`. On assouplit ensuite avec un test qui documente l'exception.

## 10. Note de checkpoint

**2026-09-02 — exécuté — résultat : conforme, avec deux écarts assumés.**

1. **Lien valable.** `http://localhost:8001/r/demo-record-linkxxx…` répond `200` et sert `narrator/TokenProbe` avec `tokenType: record` et le sujet. La page n'affiche ni nom, ni téléphone : un test bout en bout vérifie que ni `Delaunay` ni `+33600000000` n'apparaissent.
2. **Lien révoqué et lien expiré.** `410 Gone`, composant `narrator/LinkUnavailable`, `reason: expired`, `canRequestNewLink: true`, bouton « Demander un nouveau lien » présent et fonctionnel. Zéro violation d'accessibilité `serious` ou `critical`.
3. **Jeton bricolé.** `/r/{jeton}z` (44 caractères) et `/r/trop-court` reçoivent un `404`, sans page amicale et **sans aucune requête sur `access_tokens`** — vérifié par un test qui inspecte le journal de requêtes.
4. **Journaux.** Une ligne journalisée avec un lien complet et une clé `token` en contexte ressort en `/r/[redacted]` et `"token":"[redacted]"`. Aucun jeton de 43 caractères ne subsiste dans `storage/logs/laravel.log`.
5. **Telescope** : sans objet, Telescope n'est pas installé (T-48).
6. **OTP.** Défi créé pour le narrateur du seeder, code envoyé au `+336••••••00`. Cinq mauvais codes : cinq refus, `attempts = 5`, verrou posé à quinze minutes, et le verrou refuse ensuite même le bon code. Un nouveau code, lu dans le journal (`LogSmsSender`), émet un `sensitive_grant` à usage unique de quinze minutes.

Porte verte : Pint sans correction en attente, PHPStan niveau 8 sans erreur, **281 tests Pest**, 23 tests Vitest, 9 tests Playwright. `composer outdated --direct` et `npm outdated` sans montée possible. Aucun paquet installé par ce bloc.

**Écarts par rapport au plan :**

- **Telescope.** §6.4 demandait de configurer `ignore_paths` et le checkpoint d'ouvrir Telescope. Telescope n'est pas installé depuis T-29 ; la protection est portée par le processeur Monolog, donc par tous les journaux, ce qui est plus fort qu'une exclusion d'outil. Consigné en T-48, à reprendre au bloc 11 si Telescope entre.
- **Politique de contenu.** Deux ajustements sur la chaîne écrite au §6.3 : `style-src-attr 'unsafe-inline'` est indispensable, React posant des styles en attribut qui ne peuvent pas porter de `nonce` ; les hôtes `fonts.googleapis.com` et `fonts.gstatic.com` sont retirés, les polices étant auto-hébergées (T-40). Le back-office reçoit une politique distincte, assouplie, qu'Alpine exige (T-47).
- **Verrou OTP.** Le bloc annonçait « le bon code après déverrouillage émet un `sensitive_grant` ». Le code vaut dix minutes et le verrou quinze : c'est un **nouveau** code qui l'émet. Le message de la page le dit en clair (T-53).
- **Codes dans le journal.** Le processeur masque aussi les codes à six chiffres, ce que le bloc ne demandait pas — sauf en local, où le journal est la passerelle d'envoi et où le checkpoint exige de pouvoir lire le code (T-49).
- **Route de test.** La page `narrator/TokenProbe` est livrée comme prévu. En revanche `RequireSensitiveGrant` est éprouvé par une route déclarée dans son test : aucune action sensible du produit n'existe encore, et le bloc 03 n'avait pas à en inventer une. Le bloc 07 branchera les vrais retraits.
- **`family/LinkUnavailable`** est livrée et testée (Vitest + test de fonctionnalité sur une route déclarée dans le test), mais aucune route `/l/` de production n'existe encore : elle arrive au bloc 08.
- Trois énumérations et une aide ajoutées, non listées au bloc : `TokenType`, `TokenIssuedReason`, `OtpPurpose` et `App\Support\SensitiveGrant` (le cookie).

**Défauts trouvés et corrigés en chemin :**

- **La prop `flash` n'était pas partagée par Inertia.** Le bouton « Demander un nouveau lien » enregistrait bien la demande, et la page ne le disait pas : le message de confirmation n'arrivait jamais au front. Découvert par le test bout en bout, pas par un test de fonctionnalité — c'est précisément ce que le bout en bout doit attraper.
- **L'absence de message se disait de deux façons.** Le serveur envoie `null`, le composant testait `undefined` : le bouton disparaissait dès que la prop existait. Les deux absences sont désormais ramenées à une seule.
- **Le panneau Filament n'avait aucun en-tête de sécurité.** Il n'emprunte pas le groupe `web` : il faut lui donner `SecurityHeaders` explicitement, ce qui était passé inaperçu jusqu'au test.
- **Repli silencieux de l'expéditeur SMS.** Un `SMS_PROVIDER` inconnu retombait sur `LogSmsSender`, y compris en production : les codes seraient partis dans `storage/logs` au lieu d'arriver aux narrateurs. La liaison lève désormais (T-51).
- **Clé étrangère auto-référencée.** `access_tokens.replaced_by_token_id` pointe la même table ; Postgres refuse la contrainte dans le `CREATE TABLE`. Elle est posée par un `ALTER` séparé, juste après.

**Ce que le bloc laisse ouvert :**

- Aucune route `/n/`, `/i/`, `/l/`, `/q/`, `/a/`, `/x/` de production : les types de jetons existent, les espaces arrivent aux blocs 04, 07, 08, 09, 10, 13 et 14.
- `NewLinkRequested` n'a pas d'écouteur : le bloc 05 alertera le support et l'Initiateur·rice par un vrai message. En attendant, la demande est journalisée et rattrapable à la main.
- Twilio arrive au bloc 05 comme une implémentation de plus de `SmsSender`.
- La double authentification obligatoire du back-office reste planifiée au bloc 11 ; c'est elle qui justifiera de revoir la politique de contenu assouplie du panneau.
