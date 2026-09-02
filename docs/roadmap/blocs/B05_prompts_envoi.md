# Bloc 05 — Corpus de questions et envoi des prompts SMS/email

Statut : ◐ en cours — code livré et éprouvé avec des doubles ; envoi réel Twilio/Resend à faire (§7.3, §7.4) · Dépend de : 04 · Tag de fin : `bloc-05-done`

**⛔ En attente de toi** — [`05_A_FAIRE_HUMAIN.md`](../05_A_FAIRE_HUMAIN.md) §1.7 : identifiants Twilio (SID, token, numéro destinataire vérifié) et Resend (clé, domaine d'envoi vérifié SPF/DKIM/DMARC, secret de webhook). Le §7.1 et le §7.2 du checkpoint sont déjà jouables en local sans rien de tout ça.

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
- [x] `.env` : `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`.
- [x] `config/horizon.php` : environnements `local` et `production` ; superviseur `supervisor-1` sur les files `default,notifications,engine` (2 process), `supervisor-media` sur `media,transcription,llm,exports` (2 process, `timeout 900`).
- [x] `compose.yaml` : service `horizon` (même image que `laravel.test`, commande `php artisan horizon`, `depends_on redis`), service `scheduler` (`php artisan schedule:work`).
- [x] Route `/horizon` protégée par `Gate::define('viewHorizon')` → `User::isStaff()`.

### 6.2 Corpus
- [x] Migration `create_project_question_settings_table`.
- [x] `QuestionSeeder` : les 60 lignes de l'annexe A (slug, thème, difficulté, `order_hint`, texte). Test : 60 questions, slugs uniques, première `difficulty = 1`.
- [x] `PickNextQuestion(Project): ?Question` avec les six règles ; `ProjectQuestionSetting` pour exclusions et ordre personnalisé.

### 6.3 Planification
- [x] Migration : ajouter `preferred_channel` valeur `both` à `narrators` ; `projects.next_prompt_at` déjà présent.
- [x] `ScheduleNextPrompt(Project): ?CarbonImmutable` selon T-28 : créneaux `morning 09:00`, `afternoon 14:00`, `evening 18:00` dans `project.timezone`.
- [x] `IssueRecordToken(Story, ?string $reason = 'initial'): IssuedToken` (TTL 30 jours, scope `['record','decide_share']`).
- [x] Commande `prompts:dispatch-due` (`routes/console.php` : `->everyFiveMinutes()->withoutOverlapping()`), pour chaque projet `status = active` avec `next_prompt_at <= now` : transaction { `PickNextQuestion` → `ProposeStory` → `IssueRecordToken` → `SendPrompt` → `ScheduleNextPrompt` }. Si `PickNextQuestion` retourne `null` (corpus épuisé), envoyer la notification `notifications.prompts.corpus_exhausted` à l'Initiateur·rice une seule fois et ne pas replanifier.

### 6.4 Messages sortants
- [x] Migration `create_outbound_messages_table`.
- [x] `App\Services\Sms\TwilioSmsSender` (`Twilio\Rest\Client`) : `from` = `BrandSettings::sms_sender_id` si le pays du destinataire autorise l'alphanumérique (liste dans `config/product.php` `sms.alphanumeric_countries` : `FR`, `BE`, `CH`, `LU`), sinon `TWILIO_FROM` ; `statusCallback` = `route('webhooks.twilio.status')`.
- [x] `App\Notifications\Channels\SmsChannel` : appelle `SmsSender`, crée/actualise `OutboundMessage` (`dedupe_key`, `to_hash`, `to_masked` du type `+33 6 ** ** ** 12`).
- [x] `App\Notifications\Channels\TrackedMailChannel` : enveloppe le canal `mail`, crée `OutboundMessage` avec l'identifiant Resend (`X-Resend-Id` via l'événement `MessageSent`).
- [x] `PromptNotification(Story, IssuedToken)` : `via()` selon `preferred_channel` ; `toSms()` : « {Prénom}, votre question de la semaine de {Marque} vous attend : {lien} » (compter les caractères, tronquer le prénom si besoin) ; `toMail()` : `BrandedMailable`, objet « Votre question de la semaine », question en 22 px, bouton unique « Répondre en parlant », rappel « Cette page ne vous demandera jamais de mot de passe ni de paiement », adresse du support.
- [x] `config/mail.php` : mailer `resend` (package) en production, `smtp` Mailpit en local.
- [x] Tests verts.

### 6.5 Webhooks de livraison
- [x] `routes/webhooks.php` : `POST /webhooks/twilio/status` (middleware `VerifyTwilioSignature` : `Twilio\Security\RequestValidator` avec `TWILIO_AUTH_TOKEN` et l'URL publique complète), `POST /webhooks/resend` (middleware `VerifyResendSignature` : `Svix\Webhook` avec `RESEND_WEBHOOK_SECRET`).
- [x] Contrôleurs : mise à jour de `outbound_messages.status/status_detail/delivered_at/failed_at` par `provider_message_id`.
- [x] Tests webhooks verts.

### 6.6 Fiche contact et renvoi de lien
- [x] `GET /vcard` (domaine des liens, sans jeton, `throttle:tokens`) : vCard 3.0 `FN:{Marque}`, `TEL:{TWILIO_FROM}`, `EMAIL:{support}`, `URL:{APP_URL}`, `NOTE:Vos questions de la semaine arrivent de ce contact.`
- [x] Écouteur `NewLinkRequested` (événement du bloc 03) : émet un nouveau jeton si nécessaire, envoie `notifications.support.new_link_requested` au support (email) et `notifications.initiator.new_link_requested` à l'Initiateur·rice (email), et renvoie le lien au narrateur sur son canal.

### 6.7 Clôture
- [x] Annexe B : `outbound_messages`, `project_question_settings`, `narrators.preferred_channel = both`.
- [x] `04_VERSIONS.md` : horizon, resend, twilio, svix.
- [x] `sail composer check`, `sail npm run check`, CI verts.
- [ ] Commit `chore(bloc-05): terminé`, tag `bloc-05-done` — **après** l'envoi réel.

## 7. Checkpoint démontrable

1. En local avec `SMS_PROVIDER=log` et Mailpit : régler le projet du seeder sur `next_prompt_at = now()`, lancer `sail artisan prompts:dispatch-due` → une histoire `proposed`, un SMS dans le log, un email dans Mailpit avec un lien `/r/…` qui ouvre la page du bloc 04.
2. Relancer la commande : rien de nouveau (idempotence), `next_prompt_at` est la semaine suivante au bon créneau.
3. Avec des identifiants Twilio et Resend de test (compte d'essai, numéro vérifié) : un vrai SMS et un vrai email arrivent sur un téléphone de l'équipe, l'expéditeur du SMS est le nom de marque, le webhook passe le message en `delivered`.
4. Importer `/vcard` sur le téléphone : le contact apparaît avec le nom de marque.

## 8. Critères de sortie

- [x] Aucun lien envoyé ne passe par un raccourcisseur ; tous commencent par `https://{links_domain}/`.
- [x] Chaque envoi a une ligne `outbound_messages` avec `dedupe_key`.
- [x] Horizon tourne dans Sail et les jobs `media` du bloc 04 s'exécutent dedans.

## 9. Règle de décision par défaut

Si l'expéditeur alphanumérique est rejeté par un opérateur français lors du test réel, basculer `from` sur le numéro `TWILIO_FROM` pour ce pays et noter dans `03_DECISIONS.md` ; l'annonce « expéditeur constant » du doc 04 §9 devient alors « numéro constant ».

## 10. Note de checkpoint

**2026-09-02 — code livré et éprouvé — bloc non clos : l'envoi réel demande
des identifiants Twilio et Resend et un téléphone.**

### Ce qui est démontré

1. **Checkpoint §7.1.** `next_prompt_at` réglé sur maintenant, puis
   `sail artisan prompts:dispatch-due` : une histoire `proposed` (séquence 6,
   « Comment était la cuisine de votre enfance ? »), un SMS dans le journal
   avec un lien `/r/…`, une ligne `outbound_messages` en `sent` portant sa clé
   `prompt:{story}:sms`, et le numéro conservé **masqué** (`+336••••••00`).
2. **Checkpoint §7.2.** Relance : rien de nouveau. `next_prompt_at` est passé
   au mercredi suivant à 09:00, jour et créneau du projet.
3. Les six règles de séquencement de l'annexe A ont chacune leur test ; le
   corpus est extrait du document plutôt que recopié, et un test vérifie qu'il
   compte bien soixante questions, dix thèmes, aucun slug en double.
4. Les deux webhooks vérifient leur signature avant de lire le corps, refusent
   une signature inventée, acceptent sans broncher un message inconnu, et ne
   font **jamais redescendre** un message déjà reçu.
5. Horizon est protégé par la permission `admin.access` et porte les deux
   superviseurs prévus ; les jobs média du bloc 04 sont bien sur la file
   `media`, avec un délai de 900 s qui ne retarde pas les notifications.
6. Porte verte : Pint, PHPStan niveau 8, **438 tests Pest**, 76 Vitest,
   17 Playwright.

### Ce qui reste, et qui ne peut pas être fait sans toi

**Checkpoint §7.3 et §7.4** : un vrai SMS et un vrai courriel sur un téléphone
de l'équipe, avec des identifiants Twilio et Resend d'essai — donc un compte,
un numéro vérifié et un domaine d'envoi. C'est là que se joue la règle de
décision §9 : si un opérateur français refuse l'expéditeur alphanumérique,
`from` bascule sur `TWILIO_FROM` et l'engagement du doc 04 §9 devient
« numéro constant » plutôt que « expéditeur constant ». Le code sait déjà
faire les deux et le choix est testé ; c'est le comportement réel de
l'opérateur qui manque. Importer `/vcard` sur un téléphone en fait partie.

Tant que ce n'est pas fait, le bloc reste `◐ en cours` et n'est pas taggé.

**Écarts par rapport au plan :**

- **Arbitrage rendu (T-63)** : une question avancée explicitement par
  l'Initiateur·rice passe outre la règle 5, qui interdit l'intime avant la
  sixième histoire validée. Les deux règles de l'annexe A se contredisaient.
- `PromptNotification` compte le SMS en **segments** et non en caractères :
  une seule apostrophe typographique fait tomber la limite de 160 à 70.
  `App\Support\SmsLength` porte ce calcul, avec ses tests.
- `TrackedMailChannel` enveloppe le canal `mail` de Laravel plutôt que de le
  remplacer : l'identifiant Resend n'est connu qu'après l'envoi, récupéré par
  un écouteur posé le temps de l'appel.
- Le rappel de statut Twilio est passé en `statusCallback` sur chaque message,
  et non configuré une fois pour toutes : c'est ce qui permet de router les
  rappels vers l'URL publique courante, y compris depuis un tunnel de test.
- `FamilyMember` reçoit un canal préféré **déduit** de ses coordonnées : les
  proches n'ont pas de préférence enregistrée, et le typage de
  `OtpService::channelFor()` demandait une réponse pour eux aussi.
- En local, le lien d'un SMS ressort masqué du journal (T-67). Pour suivre un
  vrai parcours, on utilise les liens déterministes de `E2ELinksSeeder`.

**Défauts trouvés et corrigés en chemin :**

- **Les écouteurs tournaient deux fois.** Laravel découvre ceux de
  `app/Listeners` ; les enregistrer en plus dans `AppServiceProvider` doublait
  leur exécution, et une demande de nouveau lien émettait **deux** jetons. La
  double révocation, idempotente, avait masqué le problème depuis le bloc 03
  (T-65).
- **`config/services.php` portait deux clés `resend`**, la seconde écrasant la
  première : le secret du webhook disparaissait en silence, et la vérification
  de signature aurait refusé tous les rappels en production.
- **Un narrateur ayant choisi les deux canaux faisait échouer l'envoi d'un
  code** : la contrainte de `otp_challenges.channel` n'accepte que `sms` ou
  `email` (T-66).
- **Une date convertie en UTC avant enregistrement se décalait de deux
  heures.** Trouvé par un test de planification. Corrigé à la racine : les
  dates s'écrivent avec leur décalage (T-64).
- **Les aides de test partagées se percutaient** dans l'espace global de Pest,
  déclarées dans deux fichiers. Elles vivent désormais dans `tests/Pest.php`.

**Ce que le bloc laisse ouvert :**

- L'envoi réel et la règle de décision §9 (voir ci-dessus).
- L'événement `NewLinkRequested` est écouté et alerte les trois parties ; les
  gabarits de courriel restent sobres, leur mise en forme de marque arrivera
  avec le reste des messages au bloc 10.
- `project_question_settings` est en base et respecté par le séquencement,
  mais aucun écran ne permet encore d'exclure ni de réordonner : c'est
  l'espace Initiateur·rice du bloc 10.
