# Bloc 03 — Jetons, OTP et sécurité des liens

Statut : ☐ non commencé · Dépend de : 02 · Tag de fin : `bloc-03-done`
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
- [ ] Migrations `create_access_tokens_table`, `create_otp_challenges_table` (annexe B). Ajouter le type `sensitive_grant` à la liste des types (glossaire §4 mis à jour).
- [ ] Enum `App\Enums\TokenType` avec `ttl(): ?CarbonInterval` lu depuis `config('product.tokens')`, `isSingleUse(): bool` (`action`, `sensitive_grant`).
- [ ] Modèles `AccessToken` (relations morph `subject`, scopes `active()`, méthodes `isExpired()`, `isRevoked()`, `isUsed()`) et `OtpChallenge`.

### 6.2 TokenService
- [ ] `issue(TokenType $type, Model $subject, array $scope = [], ?CarbonImmutable $expiresAt = null, ?Model $issuedBy = null, string $reason = 'initial'): IssuedToken` où `IssuedToken` est un DTO `readonly` `{ string $plain; AccessToken $token; }`. Génération : `rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')`.
- [ ] `resolve(string $plain, TokenType $expected): AccessToken` : hash, recherche, vérifications type/expiration/révocation/usage, mise à jour `use_count`/`last_used_at`, marque `used_at` si `single_use`.
- [ ] `revoke(AccessToken $token, string $reason): void`, `rotate(AccessToken $token, string $reason): IssuedToken`, `revokeAllFor(Model $subject, TokenType $type, string $reason): int`.
- [ ] Écouteur `RevokeRecordTokensOnValidation` sur l'événement de transition vers `Validated` (spatie/laravel-model-states émet un événement ; sinon appeler explicitement dans la transition `ValidateStory`).
- [ ] `App\Support\Links` : `record($plain)`, `narratorSpace($plain)`, `listen($plain)`, `qr($plain)`, `invitation($plain)`, `action($plain)`, `export($plain)` → `https://{links_domain}/{préfixe}/{plain}` (`http` en local).

### 6.3 Routes et middleware
- [ ] `routes/narrator.php` et `routes/family.php` : `Route::domain(config('brand.links_domain'))`, `Route::pattern('token', '[A-Za-z0-9_-]{43}')`.
- [ ] `App\Http\Middleware\ResolveAccessToken` paramétré (`resolve.token:record`) : appelle `TokenService::resolve`, place `AccessToken` et son sujet dans `$request->attributes` (`access_token`, `token_subject`), lève les exceptions domaine.
- [ ] Rendu des exceptions dans `bootstrap/app.php` : `TokenExpired|TokenRevoked|TokenUsed|TokenNotFound|TokenTypeMismatch` → page Inertia `narrator/LinkUnavailable` ou `family/LinkUnavailable` selon le préfixe, avec `reason` et `can_request_new_link` (vrai pour `record` expiré ou révoqué).
- [ ] Middleware `NoStore` (`Cache-Control: no-store`, `X-Robots-Tag: noindex, nofollow`, `Referrer-Policy: no-referrer`) appliqué à tous les groupes par jeton.
- [ ] Middleware `SecurityHeaders` global : CSP `default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'nonce-…' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data: blob: https://*.r2.cloudflarestorage.com; media-src 'self' blob: https://*.r2.cloudflarestorage.com; connect-src 'self' https://*.r2.cloudflarestorage.com` (les hôtes R2 sont lus depuis la config), `Strict-Transport-Security` en production, `X-Content-Type-Options`, `Permissions-Policy: microphone=(self), camera=(), geolocation=()`. Nonce via `Vite::useCspNonce()`.
- [ ] Limiteurs dans `AppServiceProvider` : `tokens` (60/min/IP, 20/min/jeton), `otp-request` (3/heure/sujet), `otp-verify` (10/min/défi).
- [ ] Route de test `GET /r/{token}` → page Inertia `narrator/TokenProbe` affichant le type et le sujet (remplacée au bloc 04 par la vraie page ; la route reste dans les tests du middleware via une route déclarée dans le test).

### 6.4 Journaux
- [ ] `App\Logging\RedactTokens` (Monolog `ProcessorInterface`) : regex `#/(r|l|q|i|a|x|n)/[A-Za-z0-9_-]{43}#` → `/$1/[redacted]`, appliqué au message et récursivement au contexte et aux extras. Enregistré via `tap` sur tous les canaux de `config/logging.php`.
- [ ] `config/telescope.php` : `watchers.RequestWatcher.ignore_paths` = `['r/*','l/*','q/*','i/*','a/*','x/*','n/*','webhooks/*']` ; `ignore_http_methods` inchangé.
- [ ] Test `RedactTokensProcessorTest` vert.

### 6.5 OTP
- [ ] Interface `App\Services\Sms\SmsSender { public function send(string $toE164, string $body, ?string $dedupeKey = null): SmsResult; }` ; `FakeSmsSender` (collecte en mémoire, assertions `assertSentTo`), `LogSmsSender` (local). Liaison dans `AppServiceProvider` selon `SMS_PROVIDER`.
- [ ] `OtpService::challenge(Narrator|FamilyMember $subject, OtpPurpose $purpose, Channel $channel): OtpChallenge` : code 6 chiffres (`random_int(0, 999999)` formaté), `code_hash = hash('sha256', code.':'.challenge_id)`, envoi par `OtpCodeNotification` (mail branded ou SMS « {Marque} : votre code est {code}. Il expire dans 10 minutes. Ne le communiquez à personne. »).
- [ ] `OtpService::verify(OtpChallenge $challenge, string $code): IssuedToken` : vérifie expiration, verrou, incrémente `attempts`, verrouille à 5, émet un `sensitive_grant` (15 min) ou un `narrator_space` (30 jours) selon `purpose`.
- [ ] Page Inertia `narrator/OtpChallenge` : saisie à 6 cases, gros caractères, bouton « Renvoyer le code » (limité), message d'erreur en langage simple.
- [ ] Middleware `RequireSensitiveGrant` : exige un `sensitive_grant` valide (cookie signé `sg` contenant le jeton, `HttpOnly`, `SameSite=Strict`, 15 min) pour les routes marquées sensibles ; sinon redirige vers le défi OTP puis retour.
- [ ] Documenter dans `docs/roadmap/02_GLOSSAIRE_TECH.md` la frontière : depuis un lien `record`, les actions sur **cette** histoire (`keep_private`, masquer juste après enregistrement) ne demandent pas d'OTP ; toute action sur une autre histoire, une suppression, un réglage durable ou une directive post-mortem exige un `sensitive_grant`.

### 6.6 Pages d'erreur amicales
- [ ] `narrator/LinkUnavailable.tsx` : titre selon `reason` (« Ce lien n'est plus valable », « Ce lien a expiré », « Ce lien a déjà été utilisé »), phrase d'explication, bouton « Demander un nouveau lien » (POST `/r/{token}/request-new-link`, limité à 1/heure, crée un `OutboundMessage` d'alerte vers le support et l'Initiateur·rice via `App\Actions\RequestNewLink` ; le contenu de l'alerte arrive au bloc 05, ici la ligne `outbound_messages` n'existe pas encore : journaliser et lever un événement `NewLinkRequested` que le bloc 05 écoutera).
- [ ] `family/LinkUnavailable.tsx` : variante sans bouton de renvoi (« Demandez un nouveau lien à la personne qui vous a invité·e »).
- [ ] Tests Vitest et Playwright verts.

### 6.7 Clôture
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts.
- [ ] Commit `chore(bloc-03): terminé`, tag `bloc-03-done`.

## 7. Checkpoint démontrable

1. `sail artisan tinker` : émettre un jeton `record` pour une histoire du seeder, ouvrir `http://localhost/r/{jeton}` → page `TokenProbe` affiche « record · Story … ».
2. Révoquer le jeton dans tinker, recharger → page « Ce lien n'est plus valable » avec le bouton de renvoi.
3. Modifier un caractère du jeton → 404 (pas de page amicale, pas de requête SQL visible dans Telescope).
4. `tail -f storage/logs/laravel.log` pendant les requêtes : aucune occurrence du jeton, uniquement `/r/[redacted]`.
5. Ouvrir Telescope : aucune entrée pour les requêtes `/r/*`.
6. Lancer un défi OTP dans tinker pour le narrateur du seeder : le code apparaît dans le log (`LogSmsSender`) ; 5 mauvais codes verrouillent ; le bon code après déverrouillage émet un `sensitive_grant`.

## 8. Critères de sortie

- [ ] Aucun endroit du code ne compare un jeton en clair (revue : `grep -rn "token ==" app` vide ; seul `TokenService::resolve` lit `token_hash`).
- [ ] Toutes les routes des fichiers `narrator.php` et `family.php` portent `resolve.token:*`, `throttle:tokens`, `no-store`.
- [ ] Glossaire §4 et §8 à jour (`sensitive_grant`).

## 9. Règle de décision par défaut

En cas de doute sur le caractère « sensible » d'une action, elle est sensible et exige un `sensitive_grant`. On assouplit ensuite avec un test qui documente l'exception.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
