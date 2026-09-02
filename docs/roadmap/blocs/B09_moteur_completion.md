# Bloc 09 — Moteur de complétion v1

Statut : ☐ non commencé · Dépend de : 08 · Tag de fin : `bloc-09-done`
Références dossier : PRD P0-11, §5.3, doc 01 §3 (« le moteur de complétion est le différenciateur n°1 »), R-7 (charge de l'Initiateur·rice ≤ 4 actions/mois), annexe C.

## 1. Objectif

Les onze règles de l'annexe C tournent toutes les heures, chacune avec son déclencheur, son destinataire, sa limite anti-culpabilisation et son événement de reprise. Les alertes à l'Initiateur·rice se résolvent en un tap. La pause et la baisse de cadence sont gérées. Chaque déclenchement et chaque reprise sont enregistrés : ce sont les données qui deviendront l'actif défendable.

## 2. Pourquoi

La plainte n°1 de la catégorie est l'abandon. Remento n'a qu'un levier, le partage instantané. Le dossier spécifie un moteur état par état ; ce bloc le rend réel et mesurable.

## 3. Livrables

- Interface `App\Engine\Rule`, onze classes dans `App\Engine\Rules\`, `App\Engine\EngineTick`, commande `engine:tick`.
- Table `engine_events`, table `support_tickets`.
- Jetons `action` et routes `/a/{token}` avec page de confirmation puis exécution.
- Actions : `RequestPause`, `ResumeFromPause`, `SwitchCadence`, `ResendPromptOnOtherChannel`, `OfferPhoneOption`, `AcknowledgeCallParent`, `OpenSupportTicket`.
- Job `MeasureResumptions` et événements `engine_rule_fired`, `engine_rule_resumed`.
- Commande `engine:report` (tableau des déclenchements des 30 derniers jours).

## 4. Packages

Aucun.

## 5. Tests à écrire d'abord

- Pour **chaque** règle, `tests/Feature/Engine/Rules/<RuleId>Test.php` avec les sept cas de l'annexe C (avant délai, au délai, pas deux fois, limite, pause/gel, événement enregistré, message et destinataire). Onze fichiers, soixante-dix-sept tests minimum.
- `tests/Feature/Engine/EngineTickTest.php` : parcourt les règles dans l'ordre ; ignore `paused`, `frozen_bereavement`, `cancelled`, `completed`, `dormant` ; tolère l'échec d'une règle sans bloquer les autres (journalisé) ; `withoutOverlapping`.
- `tests/Feature/Engine/ActionTokenTest.php` : `GET /a/{token}` affiche une confirmation sans rien exécuter ; `POST /a/{token}` exécute une seule fois (`single_use`) ; chaque action : `resend_whatsapp` (affiche le lien `record` courant et un bouton `https://wa.me/?text=…` prérempli), `switch_biweekly` (cadence changée, `next_prompt_at` recalculé), `ack_call_parent` (événement enregistré, rien d'autre), `offer_phone_option` (crée `phone_options(entry=rescue, requested)` si flag actif et plafond non atteint ; sinon page « option indisponible »), `react_heart` (réaction ❤️ du proche-Initiateur·rice sur la dernière histoire partagée).
- `tests/Feature/Engine/PauseTest.php` : `RequestPause(project, until)` pose `paused_until`, annule les envois planifiés, confirme au narrateur ; `ResumeFromPause` à l'échéance (`engine:tick`) replanifie et envoie `notifications.engine.resume`.
- `tests/Feature/Engine/DecliningCadenceTest.php` : 4 histoires puis 2 sur deux fenêtres de 4 semaines → proposition ; 1 puis 0 → pas de proposition (minimum 2 → 1 requis) ; une seule proposition par 8 semaines.
- `tests/Feature/Engine/MeasureResumptionsTest.php` : après `link_not_opened` déclenché, une ouverture dans les 7 jours pose `outcome = resumed` et émet `engine_rule_resumed` avec `delay_hours` ; après 7 jours sans ouverture, `no_effect`.
- `tests/Feature/Engine/SupportTicketTest.php` : deuxième `mic_denied` sur la même histoire → ticket `mic_denied_twice` ouvert une seule fois.
- `tests/Unit/Engine/InitiatorLoadTest.php` : compteur d'actions demandées à l'Initiateur·rice par mois (alertes + suggestions) ; le moteur n'envoie pas une cinquième sollicitation dans le mois (plafond R-7, `config('product.engine.initiator_max_requests_per_month') = 4`).
- `tests/e2e/engine-alert-one-tap.spec.ts` : ouvrir un lien `/a/…` d'alerte J+21, cliquer « Passer à une question toutes les deux semaines », vérifier la confirmation et la cadence en base.

## 6. Étapes

### 6.1 Tables
- [ ] Migrations `create_engine_events_table`, `create_support_tickets_table` (bigint : `project_id`, `story_id` nullable, `kind` varchar, `status` check `open|closed`, `payload` jsonb, `opened_at`, `closed_at`, `closed_by_user_id` nullable). Annexe B mise à jour.

### 6.2 Cœur du moteur
- [ ] `App\Engine\Rule` (annexe C), `App\Engine\Occurrence` (DTO : `project`, `story?`, `narrator?`, `key`, `attempt`).
- [ ] `App\Engine\EngineTick::run(CarbonImmutable $now)` : pour chaque règle, `detect` → filtre `dedupe_key` existant → filtre `isCapped` → `fire` dans une transaction avec `engine_events` inséré d'abord (`dedupe_key` unique protège contre les doubles ticks). Chaque règle dans un `try/catch` ; l'échec est journalisé avec `rule_id`.
- [ ] Commande `engine:tick` planifiée `cron('7 * * * *')->withoutOverlapping()`.
- [ ] `App\Engine\InitiatorLoad::requestsThisMonth(Project): int` consulté par les règles qui sollicitent l'Initiateur·rice (`invitation_not_accepted` J+14, `three_stories_no_reaction`, `narrator_silence_21d`).

### 6.3 Les onze règles
Implémenter dans l'ordre de l'annexe C. Pour chacune : `detect` = une requête Eloquent explicite (pas de boucle PHP sur tous les projets), `occurrenceKey` = `{project_id}:{story_id|-}:{attempt}`, `fire` = envoi de la notification via les canaux du bloc 05 + `engine_events`.
- [ ] `InvitationNotAccepted` : J+7 → narrateur ; J+14 → Initiateur·rice avec jeton `action:resend_whatsapp` et suggestion de message audio ; après J+14 : `projects.status` reste `awaiting_acceptance`, `narrators.contact_deletion_due_at = J+14+30j`.
- [ ] `LinkNotOpened` : J+3, `use_count = 0` sur le jeton `record` ; `ResendPromptOnOtherChannel` (si l'autre canal existe, sinon même canal une fois).
- [ ] `MicDenied` : réagit aux `client_events` `mic_denied` (pas au temps) ; 2e occurrence → `OpenSupportTicket(mic_denied_twice)`.
- [ ] `RecordingAbandoned` : `recording_started` sans `recording_confirmed` depuis 48 h ; message « Votre brouillon vous attend ».
- [ ] `RecordedNotValidated` : `to_review` ou `decide_later` depuis 4 jours ; 2 rappels puis `action_taken.awaiting_quietly = true`.
- [ ] `ValidatedNotListened` : `shared` depuis 5 jours sans `reached_30s` ; jeton `listen_story` par proche.
- [ ] `ThreeStoriesNoReaction` : 3 dernières `shared` sans réaction ; Initiateur·rice, jeton `action:react_heart` ; 1/mois.
- [ ] `NarratorSilence10d` : aucune `recorded` depuis 10 jours ; nouveau `ProposeStory` avec `PickNextQuestion` en mode « léger » (`difficulty ≤ 2`) et envoi.
- [ ] `NarratorSilence21d` : 21 jours ; Initiateur·rice, quatre jetons `action` ; 1/mois.
- [ ] `PauseRequested` : réagit à `paused_until` posé ; confirmation ; à l'échéance, `ResumeFromPause`.
- [ ] `DecliningCadence` : hebdomadaire (le tick du lundi 07:07) ; comparaison des deux fenêtres ; jeton `action:switch_biweekly`.
- [ ] Toutes les chaînes dans `lang/fr/notifications.php` sous `engine.*`, relues pour le ton (jamais « vous n'avez pas », préférer « quand vous voudrez », « votre histoire vous attend »). Test `ForbiddenVocabularyTest` étendu avec une liste de tournures culpabilisantes (`vous n'avez toujours pas`, `dernier rappel`, `il ne vous reste que`).

### 6.4 Actions 1-tap
- [ ] Routes `GET /a/{token}` (page `initiator/OneTapConfirm` : phrase, bouton unique) et `POST /a/{token}` (`resolve.token:action`, `single_use`), dispatch vers `App\Engine\Actions\{ResendWhatsapp,SwitchBiweekly,AckCallParent,OfferPhoneOption,ReactHeart}` selon `scope.action`.
- [ ] `OfferPhoneOption` : exige `Feature::active('phone-option-offer')` et `PhoneOption::count(active|requested) < PilotSettings::phone_option_cap` ; crée `phone_options(entry=rescue)` ; page « Nous vous rappelons sous 48 h pour organiser les appels » ; ticket `phone_option_requested` pour le support.

### 6.5 Mesure des reprises
- [ ] `MeasureResumptions` (`hourly()`) : pour chaque `engine_events` sans `outcome` et déclenché depuis ≤ 30 jours, évalue la condition de reprise de la règle (méthode `resumed(EngineEvent): ?bool` sur la règle) ; pose `outcome`, `outcome_at`, émet `engine_rule_resumed`.
- [ ] `engine:report` : tableau `rule_id | déclenchements | reprises | taux | délai médian` sur 30 jours.

### 6.6 Clôture
- [ ] Annexe B (`engine_events`, `support_tickets`), annexe C relue et alignée sur le code.
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts.
- [ ] Commit `chore(bloc-09): terminé`, tag `bloc-09-done`.

## 7. Checkpoint démontrable

1. Sur le projet du seeder, forcer les horodatages pour simuler : lien envoyé il y a 3 jours non ouvert ; histoire partagée il y a 5 jours non écoutée ; silence de 21 jours. `sail artisan engine:tick`.
2. Vérifier dans `outbound_messages` : un renvoi sur l'autre canal, un nudge par proche, une alerte à l'Initiateur·rice avec quatre liens `/a/…`.
3. Relancer `engine:tick` : aucun nouvel envoi.
4. Cliquer le lien « toutes les deux semaines » : confirmation, `cadence = biweekly`, `next_prompt_at` recalculé.
5. `sail artisan engine:report` affiche les trois déclenchements.

## 8. Critères de sortie

- [ ] Onze règles, soixante-dix-sept tests minimum verts.
- [ ] Aucune règle ne peut solliciter l'Initiateur·rice au-delà de 4 fois par mois (test `InitiatorLoadTest`).
- [ ] Toute notification du moteur a une clé `engine.*` et un destinataire explicite.

## 9. Règle de décision par défaut

Quand deux règles pourraient se déclencher le même jour pour le même narrateur, une seule notification part : la règle la plus haute dans l'ordre de l'annexe C gagne, l'autre est enregistrée avec `action_taken.suppressed_by`. Ne jamais envoyer deux messages au narrateur le même jour.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
