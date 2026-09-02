# Bloc 05 — Corpus de questions et envoi des prompts SMS/email

Statut : ☐ non commencé · Dépend de : 04 · Tag de fin : `bloc-05-done`
Références dossier : PRD P0-4, P0-9, R-9 (canal), doc 04 §9 (anti-phishing : expéditeur constant, un seul domaine, jamais de raccourcisseur), annexe A ; décisions T-06, T-28.

## 1. Objectif

Chaque semaine, au jour et au créneau choisis, le narrateur reçoit par SMS et/ou email un lien d'enregistrement vers une nouvelle question, l'envoi est tracé jusqu'à la livraison, et le corpus de 60 questions est en base avec ses règles de séquencement. Horizon et Redis sont en place.

## 2. Pourquoi

Le canal est la promesse de base : « un lien par SMS/email, un clic ». Sans traçabilité de livraison, le moteur de complétion (bloc 09) ne peut pas distinguer « lien non ouvert » de « SMS non reçu ».

## 3. Livrables

- Horizon configuré, Redis pour queue/cache/session, service `horizon` dans Sail.
- `QuestionSeeder` (annexe A), table `project_question_settings`, action `PickNextQuestion`.
- Actions `ScheduleNextPrompt`, `IssueRecordToken`, `SendPrompt` ; commande `prompts:dispatch-due` (toutes les 5 min).
- Table `outbound_messages`, `TwilioSmsSender`, mailer Resend, notification `PromptNotification`.
- Webhooks `/webhooks/twilio/status` et `/webhooks/resend` avec vérification de signature.
- Route `/vcard` (fiche contact) sur le domaine des liens.
- Écouteur `NewLinkRequested` → alerte support et Initiateur·rice.

## 4. Packages

```bash
sail composer require laravel/horizon resend/resend-laravel twilio/sdk svix/svix
sail artisan horizon:install
```

## 5. Tests à écrire d'abord

- `tests/Unit/Actions/ScheduleNextPromptTest.php`
  - `it('schedules the first prompt the next day at the chosen slot after opt in')` (≤ 72 h)
  - `it('schedules weekly on the chosen day and slot in the project timezone')` (dont un cas au changement d'heure d'octobre)
  - `it('schedules biweekly when cadence is biweekly')`
  - `it('does not schedule before paused_until')`
  - `it('does not schedule after collection_ends_at')`
- `tests/Unit/Actions/PickNextQuestionTest.php` : les six règles de l'annexe A, une par test.
- `tests/Feature/Console/DispatchDuePromptsTest.php`
  - `it('creates a story, a record token and sends on the preferred channel for each due project')`
  - `it('sends on both channels when the narrator has both and preference is both')` (nouveau `preferred_channel = both`)
  - `it('is idempotent when run twice in the same minute')` (dedupe_key)
  - `it('skips paused, frozen and completed projects')`
  - `it('reschedules next_prompt_at after sending')`
- `tests/Feature/Notifications/PromptNotificationTest.php`
  - `it('renders an sms under 160 gsm7 chars or under 70 ucs2 chars with the link last')`
  - `it('uses the brand sms sender id and falls back to the number when the country forbids alphanumeric')`
  - `it('renders a branded email with the question and a single button')`
  - `it('never uses a url shortener')` (le lien commence par `https://{links_domain}/r/`)
- `tests/Feature/Webhooks/TwilioStatusWebhookTest.php` : signature valide → statut mis à jour (`sent`, `delivered`, `undelivered`, `failed`) ; signature invalide → 403 ; message inconnu → 202 sans erreur.
- `tests/Feature/Webhooks/ResendWebhookTest.php` : signature Svix valide → `delivered`/`bounced`/`complained` ; invalide → 403.
- `tests/Unit/Sms/TwilioSmsSenderTest.php` : construit la requête attendue (client Twilio mocké), transmet `statusCallback`, retourne `provider_message_id`.
- `tests/Feature/Links/VcardTest.php` : `GET /vcard` retourne `text/vcard` avec le nom de marque, le numéro de repli et l'email support.
- `tests/Feature/Listeners/NewLinkRequestedTest.php` : crée deux `outbound_messages` (support, Initiateur·rice) et un nouveau jeton `record` si l'ancien était expiré.

## 6. Étapes

### 6.1 Horizon et Redis
- [ ] `.env` : `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`.
- [ ] `config/horizon.php` : environnements `local` et `production` ; superviseur `supervisor-1` sur les files `default,notifications,engine` (2 process), `supervisor-media` sur `media,transcription,llm,exports` (2 process, `timeout 900`).
- [ ] `compose.yaml` : service `horizon` (même image que `laravel.test`, commande `php artisan horizon`, `depends_on redis`), service `scheduler` (`php artisan schedule:work`).
- [ ] Route `/horizon` protégée par `Gate::define('viewHorizon')` → `User::isStaff()`.

### 6.2 Corpus
- [ ] Migration `create_project_question_settings_table`.
- [ ] `QuestionSeeder` : les 60 lignes de l'annexe A (slug, thème, difficulté, `order_hint`, texte). Test : 60 questions, slugs uniques, première `difficulty = 1`.
- [ ] `PickNextQuestion(Project): ?Question` avec les six règles ; `ProjectQuestionSetting` pour exclusions et ordre personnalisé.

### 6.3 Planification
- [ ] Migration : ajouter `preferred_channel` valeur `both` à `narrators` ; `projects.next_prompt_at` déjà présent.
- [ ] `ScheduleNextPrompt(Project): ?CarbonImmutable` selon T-28 : créneaux `morning 09:00`, `afternoon 14:00`, `evening 18:00` dans `project.timezone`.
- [ ] `IssueRecordToken(Story, ?string $reason = 'initial'): IssuedToken` (TTL 30 jours, scope `['record','decide_share']`).
- [ ] Commande `prompts:dispatch-due` (`routes/console.php` : `->everyFiveMinutes()->withoutOverlapping()`), pour chaque projet `status = active` avec `next_prompt_at <= now` : transaction { `PickNextQuestion` → `ProposeStory` → `IssueRecordToken` → `SendPrompt` → `ScheduleNextPrompt` }. Si `PickNextQuestion` retourne `null` (corpus épuisé), envoyer la notification `notifications.prompts.corpus_exhausted` à l'Initiateur·rice une seule fois et ne pas replanifier.

### 6.4 Messages sortants
- [ ] Migration `create_outbound_messages_table`.
- [ ] `App\Services\Sms\TwilioSmsSender` (`Twilio\Rest\Client`) : `from` = `BrandSettings::sms_sender_id` si le pays du destinataire autorise l'alphanumérique (liste dans `config/product.php` `sms.alphanumeric_countries` : `FR`, `BE`, `CH`, `LU`), sinon `TWILIO_FROM` ; `statusCallback` = `route('webhooks.twilio.status')`.
- [ ] `App\Notifications\Channels\SmsChannel` : appelle `SmsSender`, crée/actualise `OutboundMessage` (`dedupe_key`, `to_hash`, `to_masked` du type `+33 6 ** ** ** 12`).
- [ ] `App\Notifications\Channels\TrackedMailChannel` : enveloppe le canal `mail`, crée `OutboundMessage` avec l'identifiant Resend (`X-Resend-Id` via l'événement `MessageSent`).
- [ ] `PromptNotification(Story, IssuedToken)` : `via()` selon `preferred_channel` ; `toSms()` : « {Prénom}, votre question de la semaine de {Marque} vous attend : {lien} » (compter les caractères, tronquer le prénom si besoin) ; `toMail()` : `BrandedMailable`, objet « Votre question de la semaine », question en 22 px, bouton unique « Répondre en parlant », rappel « Cette page ne vous demandera jamais de mot de passe ni de paiement », adresse du support.
- [ ] `config/mail.php` : mailer `resend` (package) en production, `smtp` Mailpit en local.
- [ ] Tests verts.

### 6.5 Webhooks de livraison
- [ ] `routes/webhooks.php` : `POST /webhooks/twilio/status` (middleware `VerifyTwilioSignature` : `Twilio\Security\RequestValidator` avec `TWILIO_AUTH_TOKEN` et l'URL publique complète), `POST /webhooks/resend` (middleware `VerifyResendSignature` : `Svix\Webhook` avec `RESEND_WEBHOOK_SECRET`).
- [ ] Contrôleurs : mise à jour de `outbound_messages.status/status_detail/delivered_at/failed_at` par `provider_message_id`.
- [ ] Tests webhooks verts.

### 6.6 Fiche contact et renvoi de lien
- [ ] `GET /vcard` (domaine des liens, sans jeton, `throttle:tokens`) : vCard 3.0 `FN:{Marque}`, `TEL:{TWILIO_FROM}`, `EMAIL:{support}`, `URL:{APP_URL}`, `NOTE:Vos questions de la semaine arrivent de ce contact.`
- [ ] Écouteur `NewLinkRequested` (événement du bloc 03) : émet un nouveau jeton si nécessaire, envoie `notifications.support.new_link_requested` au support (email) et `notifications.initiator.new_link_requested` à l'Initiateur·rice (email), et renvoie le lien au narrateur sur son canal.

### 6.7 Clôture
- [ ] Annexe B : `outbound_messages`, `project_question_settings`, `narrators.preferred_channel = both`.
- [ ] `04_VERSIONS.md` : horizon, resend, twilio, svix.
- [ ] `sail composer check`, `sail npm run check`, CI verts.
- [ ] Commit `chore(bloc-05): terminé`, tag `bloc-05-done`.

## 7. Checkpoint démontrable

1. En local avec `SMS_PROVIDER=log` et Mailpit : régler le projet du seeder sur `next_prompt_at = now()`, lancer `sail artisan prompts:dispatch-due` → une histoire `proposed`, un SMS dans le log, un email dans Mailpit avec un lien `/r/…` qui ouvre la page du bloc 04.
2. Relancer la commande : rien de nouveau (idempotence), `next_prompt_at` est la semaine suivante au bon créneau.
3. Avec des identifiants Twilio et Resend de test (compte d'essai, numéro vérifié) : un vrai SMS et un vrai email arrivent sur un téléphone de l'équipe, l'expéditeur du SMS est le nom de marque, le webhook passe le message en `delivered`.
4. Importer `/vcard` sur le téléphone : le contact apparaît avec le nom de marque.

## 8. Critères de sortie

- [ ] Aucun lien envoyé ne passe par un raccourcisseur ; tous commencent par `https://{links_domain}/`.
- [ ] Chaque envoi a une ligne `outbound_messages` avec `dedupe_key`.
- [ ] Horizon tourne dans Sail et les jobs `media` du bloc 04 s'exécutent dedans.

## 9. Règle de décision par défaut

Si l'expéditeur alphanumérique est rejeté par un opérateur français lors du test réel, basculer `from` sur le numéro `TWILIO_FROM` pour ce pays et noter dans `03_DECISIONS.md` ; l'annonce « expéditeur constant » du doc 04 §9 devient alors « numéro constant ».

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
