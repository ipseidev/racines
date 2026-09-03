# Annexe B — Modèle de données

Postgres. Toutes les colonnes `*_at` sont `timestamptz`. Clés primaires `uuid` sauf mention `bigint`. Les enums sont stockés en `varchar` avec contrainte `check`, posée par `App\Support\Database\EnumCheck` et nommée `<table>_<colonne>_check`. Toute table nouvelle est ajoutée ici dans le même commit que sa migration. Le bloc qui crée la table est indiqué.

Toutes les dates s'écrivent **avec leur décalage** (`Y-m-d H:i:sP`, trait `App\Concerns\StoresDatesWithOffset`) : sans lui, Eloquent écrit une chaîne sans décalage que Postgres interprète dans le fuseau de la session, et une date convertie en UTC avant enregistrement se retrouvait décalée de deux heures (décision T-64).

Les relations polymorphes stockent un **alias court** et non un nom de classe (`user`, `project`, `narrator`, `family_member`, `story`), déclaré par `Relation::morphMap()` dans `AppServiceProvider` : renommer une classe ne réécrit pas la base.

Le fuseau de la session Postgres est aligné sur `APP_TIMEZONE` (`config/database.php`). Le stockage reste en UTC : c'est la conversion en texte qui suit l'application, sans quoi les dates écrites par Eloquent — sans décalage — se décalaient de deux heures.

## users (bloc 00, complété bloc 02)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint | Clé séquentielle conservée du squelette (décision T-41) : `users` n'est jamais exposé par un jeton |
| name, email (unique), email_verified_at, password, remember_token | standard | |
| two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at | Fortify | |
| role | varchar check | `admin`, `support`, `support_readonly`, `initiator` (défaut) |
| locale | varchar | `fr` |
| created_at, updated_at | | |

## projects (bloc 02)
| Colonne | Type | Notes |
|---|---|---|
| id | uuid | |
| owner_user_id | bigint FK users | Initiateur·rice propriétaire |
| cohort_id | uuid FK cohorts nullable | |
| status | check | `draft`, `awaiting_acceptance`, `active`, `paused`, `dormant`, `completed`, `cancelled`, `frozen_bereavement` |
| offer | check | `pilot`, `core`, `prevente` |
| address_form | check | `vous` (défaut), `tu` |
| cadence | check | `weekly` (défaut), `biweekly` |
| prompt_day | smallint 1-7 | 1 = lundi |
| prompt_slot | check | `morning`, `afternoon`, `evening` |
| timezone | varchar | `Europe/Paris` |
| next_prompt_at | timestamptz nullable | calculé par `ScheduleNextPrompt` |
| paused_until | timestamptz nullable | |
| collection_started_at, collection_ends_at, finalization_ends_at | nullable | pilote : +12 semaines ; cœur : +12 mois puis +3 mois |
| validation_variant | check | `immediate`, `deferred` (copie du flag Pennant pour le reporting) |
| gift_message | text nullable | |
| gift_audio_recording_id | uuid nullable | variante « message audio » ; la contrainte de clé étrangère est posée au bloc 04, avec `recordings` |
| gift_send_at, gift_sent_at | nullable | |
| accepted_at, refused_at | nullable | H0 |
| refusal_reason | text nullable | |
| family_code_hash | varchar nullable | code optionnel des pages QR (D-8) |
| created_at, updated_at | | |
Index : `(owner_user_id)`, `(status, next_prompt_at)`. Contrainte `projects_prompt_day_check` : `prompt_day between 1 and 7`. Défauts en base repris côté modèle (`Project::$attributes`) pour qu'une instance fraîche dise la vérité sans relire la base.

## project_members (bloc 02) — bigint
`id`, `project_id` FK projects, `user_id` FK users, `role` check (`initiator`, `editor`), timestamps. Unique `(project_id, user_id)`.

## narrators (bloc 02, complété blocs 05 et 10)
`id`, `project_id` FK, `first_name`, `last_name` nullable, `display_name`, `email` nullable, `phone_e164` nullable, `preferred_channel` check (`sms`, `email`, `both` depuis le bloc 05), `is_primary` bool, `birth_year` smallint nullable, `opted_in_at`, `opted_out_at`, `contact_deletion_due_at` nullable, `contact_deleted_at` nullable (bloc 10), timestamps. Index unique partiel `narrators_one_primary (project_id) where is_primary`. `preferred_channel` n'accepte que `sms` et `email` : le téléphone n'est jamais un canal d'envoi automatique (R-9).

Contrainte `narrators_reachable_check`, **révisée au bloc 10** : `contact_deleted_at` non nul **ou** `email` non nul **ou** `phone_e164` non nul. La version d'origine exigeait une coordonnée, et rendait donc impossible l'obligation du doc 04 §2 — effacer les coordonnées d'une personne qui n'a jamais dit oui, trente jours après la dernière relance. On ne retire pas la garde, on nomme l'exception, et sa date l'accompagne : « quand ces coordonnées ont-elles été effacées ? » est exactement ce qu'une demande RGPD demande (écart T-109, même forme que T-80 pour l'audio d'origine).

## family_members (bloc 02)
`id`, `project_id` FK, `invited_by_user_id` FK users, `display_name`, `relationship` nullable, `email` nullable, `phone_e164` nullable, `can_contribute` bool défaut false, `invited_at` nullable, `first_seen_at` nullable, `removed_at` nullable, timestamps. Index `(project_id)`.

## questions (bloc 02, données bloc 05)
`id`, `slug` unique, `text`, `theme` check (`childhood`, `family_origins`, `youth`, `work`, `love`, `places`, `joys`, `hardships`, `beliefs_values`, `legacy`), `difficulty` smallint défaut 1, contrainte `questions_difficulty_check` 1-5, `order_hint` int défaut 0, `is_active` bool défaut true, `locale` défaut `fr`, timestamps. Index `(theme, order_hint)`.

## project_question_settings (bloc 05) — bigint
`id`, `project_id` FK, `question_id` FK, `excluded` bool défaut false, `custom_order` int nullable, timestamps. Unique `(project_id, question_id)`, index `(project_id, excluded)`.

Ce que l'Initiateur·rice change au corpus pour **son** projet (annexe A, règle 3). Le corpus lui-même n'est pas modifié : il est partagé et sert de référence aux analyses. Une question portant un `custom_order` passe devant, **y compris intime et avant la sixième histoire validée** : la règle 5 protège du séquencement automatique, pas d'un choix délibéré (décision T-63).

## stories (bloc 02)
| Colonne | Type | Notes |
|---|---|---|
| id | uuid | |
| project_id, narrator_id | uuid FK | |
| question_id | uuid FK nullable | null si question personnalisée |
| custom_question_text | text nullable | |
| sequence | int | n-ième histoire proposée du projet |
| state | varchar | classes `App\States\Story\*`, valeur stockée voir glossaire §3 ; contrainte `check` sur les onze états, écrits en dur dans la migration et vérifiés par test contre `StoryState::all()` |
| previous_state | varchar nullable | pour restaurer depuis `hidden`/`archived`/`trashed` |
| proposed_at, recorded_at, transcribed_at, validated_at, shared_at | nullable | |
| validated_via | check nullable | `recording_end`, `post_transcription`, `mandate`, `phone_operator` |
| share_decision | check nullable | `share`, `keep_private`, `decide_later` |
| share_decided_at | nullable | |
| visibility | check | `all_family` (défaut), `restricted`, `book_only` |
| answer_type | check nullable | `audio`, `text`, `phone` |
| written_answer | text nullable | P0-5 |
| title | varchar nullable | proposé par le rendu Fluide, modifiable |
| hidden_at, archived_at, trashed_at, deleted_at | nullable | |
| deletion_requested_by | check nullable | `narrator`, `mandate`, `admin` |
| printed_in_book | bool | vrai après commande d'impression |
| created_at, updated_at | | |
Index : `(project_id, state)`, `(narrator_id)`, `(trashed_at) where trashed_at is not null`, unique `(project_id, sequence)`.

Contraintes : `stories_question_present_check` (`question_id is not null or custom_question_text is not null`) — une histoire vient toujours d'une question, du corpus ou personnalisée ; `check` sur `state`, `previous_state`, `visibility`, `share_decision`, `validated_via`, `answer_type`, `deletion_requested_by`. La colonne `state` n'est **jamais** écrite hors des transitions de `App\States\Story\Transitions` : le test `tests/Unit/States/NoDirectStateWriteTest.php` échoue si un fichier de `app/` ou de `database/seeders/` l'écrit, et `state` est hors de l'assignation de masse.

## story_visibility_family_members (bloc 07) — bigint
`id`, `story_id` uuid FK (`cascadeOnDelete`), `family_member_id` uuid FK (`cascadeOnDelete`), `created_at` posé par la base (`useCurrent` : `sync()` n'écrit pas d'horodatage, et la date à laquelle un accès a été ouvert vaut d'être gardée). Unique `(story_id, family_member_id)`.

Une **liste blanche**, jamais une liste noire : le narrateur désigne les personnes à qui il confie un souvenir, pas celles à qui il le refuse. La table n'a de sens que pour `visibility = restricted` ; ailleurs elle est vide, et `SetStoryVisibility` la purge à chaque changement — rouvrir à tous puis restreindre à nouveau ne ressuscite pas d'anciens invités.

`Story::isVisibleTo(FamilyMember)` pose les deux questions dans cet ordre : l'**état** dit *si* l'histoire s'écoute, la **liste** dit *qui* l'écoute. Une liste blanche ne rend donc jamais visible une histoire non partagée.

## mandates (bloc 07)
`id` uuid, `project_id` uuid FK (`cascadeOnDelete`), `narrator_id` uuid FK (`cascadeOnDelete`), `holder_type`/`holder_id` varchar(64) (un `User` ou un `FamilyMember`), `scope` jsonb (liste fermée d'actes, `["validate"]` aujourd'hui), `consent_id` uuid FK **non nullable** (`restrictOnDelete`), `granted_at`, `revoked_at` nullable, timestamps. Index `(narrator_id, revoked_at)`, `(holder_type, holder_id)`.

La forme de la table dit l'exception qu'elle représente. `consent_id` n'est pas nullable : un mandat sans consentement journalisé du narrateur n'existe pas, et `GrantMandate` exige en plus que ce consentement soit **en vigueur** et recueilli par un canal qui laisse une trace — le web ou le téléphone, jamais l'administration. `scope` est une liste fermée, jamais un blanc-seing.

`revoked_at` plutôt qu'une suppression : savoir qu'un mandat a existé, qui le détenait et quand il a cessé fait partie de l'audit (bloc 11). Un seul mandat vivant par mandataire : accorder à nouveau révoque le précédent, pour qu'il n'y ait jamais deux périmètres à comparer.

`Mandate::covers($story, $act)` réunit quatre conditions, et aucune n'est superflue : le mandat vit, l'acte est dans son périmètre, l'histoire est celle de *son* narrateur, et elle est `to_review`. Le mandat débloque une relecture que le narrateur ne fait pas ; il ne remplace ni sa décision de partage en fin d'enregistrement, ni aucun retrait.

## recordings (bloc 04)
| Colonne | Type | Notes |
|---|---|---|
| id | uuid | |
| story_id | uuid FK | |
| source | check | `browser`, `phone_operator`, `upload_admin` |
| original_disk, original_path | varchar | jamais modifiés après `confirmed_at` (trigger `recordings_original_immutable`) |
| original_mime | varchar | |
| original_bytes | bigint | |
| duration_seconds | numeric(8,2) nullable | ffprobe |
| derived_mp3_path | varchar nullable | |
| replica_path, replicated_at | nullable | bucket `media-replica` |
| upload_id | varchar nullable | identifiant multipart R2 |
| upload_status | check | `initiated`, `uploading`, `completed`, `failed`, `aborted` |
| confirmed_at | nullable | posé après `HeadObject` réussi |
| checksum_sha256 | char(64) nullable | |
| is_current | bool | un seul courant par histoire (index unique partiel) |
| segments | jsonb | une entrée par continuité de flux : `{number, upload_id, key, bytes, etag}`. Un appel entrant ou une veille en ajoute une |
| device_info | jsonb | plateforme, navigateur, version, durée annoncée par le client |
| created_at, updated_at | | |

Index : `(story_id)`, unique partiel `recordings_one_current (story_id) where is_current`. Contraintes `check` sur `source` et `upload_status`.

Déclencheur `recordings_original_immutable` : une fois `confirmed_at` posé **et** `original_path` renseigné, `original_path` ne peut plus changer. Il laisse en revanche le renseigner une première fois après confirmation — un enregistrement interrompu est confirmé sur ses segments, qui sont ce qui est en sécurité, et son fichier recollé n'arrive qu'ensuite (`ConcatenateSegments`).

Une exception, ajoutée au bloc 07 (T-80) : `original_path` peut être mis à `null` si l'histoire est `deleted`. L'immuabilité protège contre l'**écrasement**, pas contre l'**effacement** demandé par le narrateur — sans cette réserve, `PurgeDeletedStory` échouait en base et le droit à l'effacement restait une intention.

Nommage des objets (`App\Support\ObjectKeys`) : `projects/{uuid}/stories/{uuid}/recordings/{uuid}/segment-01.{ext}`, puis `original.{ext}` et `derived.{ext}`. Trois identifiants opaques et rien d'autre : un chemin circule dans les journaux et dans les URL présignées.

## client_events (bloc 04) — bigint
`id`, `story_id` FK nullable, `event` varchar (liste fermée `App\Enums\ClientEventName`), `payload` jsonb (≤ 2 Ko, aucune donnée personnelle en clair), `created_at`. Index `(event, created_at)`, `(story_id)`.

Ce que le navigateur du narrateur rapporte de sa séance : micro refusé, page cachée, interruption, brouillon repris, envoi échoué. Sans cette table, on ne sait pas *pourquoi* un narrateur n'a pas enregistré, et le taux d'échec de capture du doc 04 §11 n'est pas mesurable.

## transcripts (bloc 06)
`id` uuid, `story_id` uuid FK, `recording_id` uuid FK nullable (`nullOnDelete` : une réponse écrite n'a pas d'enregistrement), `kind` check (`verbatim`, `fluide`, `edited`), `source_transcript_id` FK nullable (auto-référence, posée par un `ALTER` séparé — Postgres refuse une clé auto-référente dans le `CREATE`), `version` int, `provider` varchar nullable (`gladia`, `deepgram`, `claude`, `human`), `provider_job_id` nullable, `language` défaut `fr`, `text` text, `words` jsonb nullable (mots horodatés), `metadata` jsonb (modèle, usage, durée de traitement, signalements sensibles), `edited_by_type`/`edited_by_id` nullable, `is_current` bool, `created_at` (pas d'`updated_at` : un rendu ne se modifie pas, on en crée un autre).

Index : `(story_id, kind)`, unique partiel `transcripts_one_current_per_kind (story_id, kind) where is_current` — un courant **par espèce**, pas un par histoire : la parole brute, la mise au propre et la correction humaine coexistent, et `Transcript::readableFor()` choisit dans cet ordre `edited` → `fluide` → `verbatim`.

Règle Postgres `transcripts_verbatim_no_delete` (fonction `forbid_verbatim_delete()` + déclencheur `BEFORE DELETE`) : un `DELETE` sur `kind = verbatim` échoue tant que l'histoire n'est pas `deleted`. Garde équivalente dans `Transcript::booted()`, pour que l'erreur soit lisible côté application avant d'atteindre la base.

## transcription_jobs (bloc 06) — bigint
`id`, `recording_id` uuid FK (`cascadeOnDelete`), `provider` varchar(32), `provider_job_id` varchar nullable (l'identifiant chez le fournisseur), `status` check (`queued`, `processing`, `done`, `failed`, défaut `queued`), `attempts` smallint défaut 0, `submitted_at` nullable, `completed_at` nullable, `error` text nullable, timestamps. Index `(status, submitted_at)` — celui que lit `PollTranscription` chaque minute — et `(provider_job_id)`, celui que lit le webhook.

Table **interne** : elle sert à savoir où en est une demande, pas à conserver un résultat. Le résultat vit dans `transcripts`. Une histoire dont le `transcription_job` est `failed` est un silence inexpliqué pour la famille : c'est ce qui déclenche la notification support.

## lexicon_entries (bloc 06) — bigint
`id`, `project_id` uuid FK (`cascadeOnDelete`), `term` varchar, `replacement` varchar nullable, `notes` varchar nullable, `created_by_type`/`created_by_id` nullable, timestamps. Unique `(project_id, term)`.

`replacement` nullable et ce n'est pas un oubli : un nom peut être au lexique **seulement** pour que l'ASR l'entende, sans correction à appliquer ensuite. `LexiconEntry::spelling()` rend alors le terme lui-même. Le lexique sert deux fois : en vocabulaire envoyé au fournisseur avant la transcription, et en correction appliquée au texte après.

## consent_texts (bloc 02) — bigint
`id`, `kind` check, `version`, `locale` défaut `fr`, `body` text, `effective_from`, `created_at` (pas d'`updated_at` : un texte publié ne se modifie pas, on en publie un autre). Unique `(kind, version, locale)`. `ConsentText::current($kind, $locale)` retourne la version en vigueur la plus récente.

## consents (bloc 02)
`id`, `project_id` FK, `subject_type`, `subject_id` varchar(64), `kind` check (voir glossaire §5), `status` check (`granted`, `revoked`), `channel` check (`web`, `phone`, `admin`), `text_version` varchar, `ip_hash` char(64) nullable, `user_agent` varchar nullable, `granted_at`, `revoked_at` nullable, `recorded_by_user_id` FK users nullable, `created_at` (pas d'`updated_at` : une ligne de consentement ne se modifie jamais). Index `(subject_type, subject_id, kind)`, `(project_id, kind)`.

`subject_id` est un `varchar` et non un `uuid` : le sujet peut être un narrateur ou un proche (uuid) comme un utilisateur (identifiant séquentiel). `subject_type` porte l'alias de la table de correspondance (`narrator`, `family_member`, `user`).

Contrainte `consents_phone_operator_check` : `channel <> 'phone' or recorded_by_user_id is not null`. Un accord oral recueilli par téléphone nomme toujours son opérateur (D-9) ; c'est aussi ce que vérifie la garde de `ValidateStory` avant d'accepter une validation `phone_operator`.

## invitations (bloc 10)
`id` uuid, `project_id`, `narrator_id`, `channel` check, `attempt` smallint check (`1`, `2`, `3`), `token_id` FK access_tokens nullable, `sent_at`, `opened_at`, `accepted_at`, `refused_at` nullable, timestamps. Unique `(narrator_id, attempt)`, index `(project_id, sent_at)`.

Deux contraintes plutôt qu'une règle applicative : le check sur `attempt` borne à trois envois — deux invitations et une relance, doc 04 §2 —, et l'unique empêche qu'un tick du moteur rejoué envoie deux fois la même relance. `opened_at` sépare « jamais reçu » de « reçu et pas répondu », et c'est toute la différence entre relancer et respecter un silence.

## access_tokens (bloc 03)
| Colonne | Type | Notes |
|---|---|---|
| id | uuid | |
| type | check | voir glossaire §4 |
| token_hash | char(64) unique | sha256 du jeton |
| subject_type, subject_id | morph | Story, Project, FamilyMember, Narrator, Invitation, Export, EngineEvent |
| scope | jsonb nullable | actions autorisées, ex. `["record","decide_share"]` |
| expires_at | nullable | |
| single_use | bool | |
| used_at | nullable | |
| revoked_at | nullable | |
| replaced_by_token_id | uuid nullable | rotation |
| issued_by_type, issued_by_id | morph nullable | |
| issued_reason | varchar | `initial`, `reissue_support`, `resend_other_channel`, `rotation` |
| last_used_at | nullable | |
| use_count | int | |
| created_at | | |
Index : `(subject_type, subject_id, type)`, `(expires_at)`.

### access_tokens — ajout du bloc 08

`issued_to_type` / `issued_to_id` varchar(64), nullables, index `(issued_to_type, issued_to_id)`.

`issued_by` disait qui a **émis** le lien ; il manquait qui le **détient**. Un lien d'histoire (`listen_story`) porte une histoire comme sujet : sans porteur, il devient anonyme — ce qui contredit la règle « un lien par personne, jamais un lien famille commun » et rend la visibilité restreinte inapplicable, puisqu'on ne sait plus qui écoute. Nullable, parce qu'un lien d'enregistrement porte déjà son narrateur par son sujet et n'a rien à répéter.

## otp_challenges (bloc 03)
`id`, `narrator_id` nullable, `family_member_id` nullable, `purpose` check (`narrator_space`, `sensitive_act`), `code_hash`, `channel`, `sent_to_masked`, `attempts` smallint, `expires_at`, `verified_at` nullable, `locked_until` nullable, `created_at`.

## outbound_messages (bloc 05)
`id`, `project_id` FK nullable, `channel` check (`sms`, `email`), `to_hash` char(64), `to_masked` varchar(64), `template` varchar, `payload` jsonb (sans données personnelles en clair), `provider` nullable, `provider_message_id` nullable, `status` check (`queued`, `sent`, `delivered`, `failed`, `bounced`, `undelivered`), `status_detail` nullable, `dedupe_key` unique, `sent_at`, `delivered_at`, `failed_at` nullable, `created_at` (pas d'`updated_at`). Index `(provider_message_id)`, `(status, created_at)`.

`sent` veut dire « accepté par l'opérateur », pas « reçu » : seul `delivered`, rapporté par un webhook signé, dit que le message est arrivé. C'est cette distinction qui empêche le moteur de complétion de relancer quelqu'un qui n'a jamais rien reçu. Un statut plus avancé ne redescend jamais : un rappel arrivé dans le désordre ne doit pas faire croire qu'un SMS n'est plus arrivé.

Le destinataire n'y figure jamais en clair : `to_hash` pour dédupliquer et regrouper, `to_masked` pour que le support puisse dire « envoyé au 06 •• •• •• 12 ».

## reactions (bloc 08)
`id` uuid, `story_id` uuid FK (`cascadeOnDelete`), `family_member_id` uuid FK (`cascadeOnDelete`), `type` check (`heart`, `thanks`), `comment` varchar(280) nullable, `created_at`, `updated_at`. Unique `(story_id, family_member_id, type)`, index `(story_id, created_at)`.

L'unicité est le fond de l'affaire : **un cœur donné deux fois reste un cœur**. Le narrateur n'a pas à distinguer un enthousiasme d'un double-clic, et une notification par tap serait du harcèlement. Le `comment`, lui, remplace le précédent — quelqu'un qui se relit et corrige son message ne doit pas en laisser deux.

Deux types seulement, et **aucun pouce baissé** : le produit ne propose aucune façon de désapprouver le souvenir de quelqu'un. `comment` est court par construction : on demande un mot, pas une lettre — et un mot arrive, là où une lettre reste en brouillon.

## listen_events (bloc 08) — bigint
`id`, `story_id` uuid FK (`cascadeOnDelete`), `family_member_id` uuid FK **nullable** (`cascadeOnDelete`), `token_type` check (types de `TokenType`), `seconds_listened` int défaut 0, `reached_30s` bool défaut faux, `started_at` nullable, timestamps. Index `(story_id, reached_30s)`, unique `(story_id, family_member_id)`.

Le maillon central de la chaîne H2 : sans lui, on ne distingue pas un proche qui a **ouvert la page** d'un proche qui a **écouté**, et le dossier refuse de présumer la causalité entre l'attention des proches et l'élan du narrateur.

Une ligne par proche et par histoire, cumulée. `reached_30s` est un booléen posé une fois, et non un calcul refait à la lecture : le franchissement du seuil est un fait daté, et l'événement analytics ne part qu'une fois — le recompter à chaque envoi gonflerait la mesure d'un facteur dix.

`family_member_id` reste nullable pour l'écoute par QR imprimé (bloc 13), où l'on ne sait pas qui écoute — et où l'on ne cherche pas à le savoir.

## engine_events (bloc 09) — bigint
`id`, `project_id` uuid FK (`cascadeOnDelete`), `story_id` uuid FK nullable (`nullOnDelete`), `rule_id` check (les onze de `EngineRuleId`), `occurrence_key` varchar (`projet:histoire|clé:tentative`), `dedupe_key` **unique** (`rule_id:occurrence_key`), `fired_at`, `action_taken` jsonb, `outcome` check nullable (`resumed`, `no_effect`), `outcome_at` nullable, `created_at`. Index `(project_id, rule_id)` et `(outcome, fired_at)` — celui que lit `MeasureResumptions` chaque heure.

`dedupe_key` est le cœur du mécanisme : la ligne est insérée **avant** l'envoi, dans la même transaction. Deux ticks simultanés — un `withoutOverlapping` qui a lâché, une reprise de file — ne peuvent donc pas envoyer deux fois le même message. C'est la contrainte de base qui fait le travail, pas une vérification en PHP.

`action_taken` distingue trois cas, et la distinction porte du sens :
- `told` = à qui le message est **parti** (`narrator`, `initiator`, `family`, `support`) ;
- `suppressed_by` + `would_have_told` = la règle voulait parler mais une règle plus prioritaire l'avait précédée le même jour. Un événement supprimé porte une `dedupe_key` **datée** (`…:suppressed:AAAA-MM-JJ`) pour ne pas consommer l'idempotence de l'occurrence : le rappel seulement différé doit pouvoir partir plus tard (T-99) ;
- l'absence des deux = rien n'est parti, et rien n'est compté.

Les compteurs de limite (« deux rappels », « une alerte par mois ») lisent les événements **partis** uniquement : un message qui n'est pas parti n'a relancé personne, et le compter priverait le narrateur d'un rappel qu'il n'a jamais reçu.

`outcome` est ce qui fait du moteur un actif défendable plutôt qu'une collection de messages : sans lui, on saurait combien on a relancé, pas si ça a servi. Un résultat négatif compte autant que l'autre — c'est lui qui dit qu'une règle ne sert à rien.

## support_tickets (bloc 09) — bigint
`id`, `project_id` uuid FK (`cascadeOnDelete`), `story_id` uuid FK nullable (`nullOnDelete`), `kind` check (`mic_denied_twice`, `phone_option_requested`, `transcription_failed`), `status` check (`open`, `closed`, défaut `open`), `payload` jsonb, `opened_at`, `closed_at` nullable, `closed_by_user_id` FK users nullable, timestamps. Index `(status, kind)`, `(project_id, kind)`.

Les tickets que le produit ouvre **de lui-même**. Une personne de 82 ans qui n'arrive pas à autoriser son micro n'écrit pas au support : elle abandonne, et personne ne sait pourquoi. C'est donc au produit de lever la main à sa place.

`OpenSupportTicket` est idempotent tant qu'un ticket du même genre est **ouvert** pour le même sujet : un support noyé sous les doublons ne traite plus rien. Un ticket fermé puis rouvert, en revanche, est une information nouvelle — le problème est revenu après qu'on l'a cru réglé.

`payload` ne porte que des identifiants et des compteurs : le support lit ces tickets, et ils ne doivent pas devenir une fiche de renseignement.

### access_tokens — ajout du bloc 09

Rien à la table : les jetons `action` utilisent le périmètre existant, sous la forme `["action", "<nom>"]`. La liste des noms est **fermée** (`OneTapRegistry`) et un périmètre inconnu rend 404 — du point de vue du visiteur, un lien bricolé est un lien qui n'existe pas.

## orders, order_items (bloc 10)
orders : `id` uuid, `user_id` FK users, `project_id` nullable (`nullOnDelete`), `stripe_checkout_session_id` **unique** — c'est la clé d'idempotence du webhook —, `stripe_payment_intent_id` nullable, `stripe_invoice_url` nullable, `status` check (`pending`, `paid`, `refunded`, `partially_refunded`, `cancelled`), `currency` `eur`, `subtotal_cents`, `total_cents`, `refunded_cents` défaut 0, `price_variant` smallint check nullable (`9900`, `12900`, **en centimes**), `paid_at`, `withdrawal_deadline_at`, `service_started_at` nullable, timestamps. Index `(user_id, status)` et `withdrawal_deadline_at`.

`withdrawal_deadline_at` est **stocké**, pas recalculé : le délai légal se compte à partir d'un fait daté, et une règle qui change ne doit pas rétroagir sur une commande déjà passée. `service_started_at` n'est posé que si l'acheteur a demandé le démarrage immédiat — c'est ce qui justifie de retenir une part en cas de rétractation.

order_items (bigint) : `order_id`, `sku` check (`pilot`, `core_prevente`, `extra_copy`, `phone_option`), `quantity` smallint, `unit_cents`, `stripe_price_id` nullable, `metadata` jsonb nullable, timestamps. Index `(order_id, sku)`. `unit_cents` est **copié** à la commande, jamais relu dans les réglages : le prix d'une commande passée ne change pas quand celui du produit change.

## checkout_drafts (bloc 10)
`id` uuid, `user_id` nullable (`nullOnDelete`) — le brouillon naît anonyme et se rattache au compte créé à la quatrième étape —, `step` smallint, `payload` jsonb, `price_variant` smallint nullable (en centimes, même unité que `orders`), `expires_at`, timestamps. Index `expires_at`.

Sept jours de vie. Le tunnel a six étapes et la quatrième crée un compte : quelqu'un qui abandonne à la cinquième ne doit pas tout ressaisir. Le brouillon est retrouvé par le compte s'il existe, sinon par un cookie `checkout_draft`.

## phone_options (bloc 10, opéré bloc 17)
`id` uuid, `project_id`, `order_item_id` bigint nullable (`nullOnDelete`), `entry` check (`checkout`, `rescue`), `status` check (`requested`, `active`, `cancelled`, `refunded`) défaut `requested`, `operator_user_id` nullable, `call_day` smallint nullable, `call_slot` check nullable (créneaux de prompt), `notes` text nullable, timestamps. Index `(status, entry)` et `project_id`.

Le plafond du pilote ne vit pas ici mais dans `PilotSettings::phone_option_cap`, et il est appliqué **côté serveur** au moment du paiement puis revérifié à l'ouverture de la session : entre l'étape 5 et le clic sur « payer », une autre famille a pu prendre le dernier créneau.

## books, book_chapters (bloc 13)
books : `id`, `project_id` unique, `template` check (`classic`), `format` check (`book`, `booklet`, `founding_chapter`), `status` check (`draft`, `proofing`, `approved`, `ordered`, `printed`, `delivered`, `reprint`), `page_count_estimate` int, `book_ready_at` nullable, `proof_pdf_path` nullable, `proof_approved_at` nullable, `proof_approved_by_user_id` nullable, `proof_acknowledged_final_print` bool, `print_order_ref` nullable, `ordered_at`, `delivered_at` nullable, timestamps.
book_chapters (bigint) : `book_id`, `story_id`, `position`, `qr_token_id` FK access_tokens, `included` bool. Unique `(book_id, story_id)`.

## exports (bloc 14)
`id`, `project_id`, `kind` check (`full`, `offline_pack`, `gdpr_access`), `status` check (`queued`, `building`, `ready`, `expired`, `failed`), `path` nullable, `bytes` bigint nullable, `manifest` jsonb nullable, `requested_by_type/id`, `token_id` nullable, `ready_at`, `expires_at` nullable, `created_at`.

## post_mortem_directives (bloc 10)
`id` uuid, `project_id`, `narrator_id` **unique**, `wishes` check (`transfer_to_family`, `freeze`, `delete`), `referent_name` nullable, `referent_contact_masked` nullable, `referent_contact_hash` char(64) nullable, `consent_id` FK consents (`restrictOnDelete`, **non nullable**), `recorded_at`, timestamps.

Une directive courante par narrateur, la dernière exprimée : on ne garde pas l'historique des volontés — savoir que quelqu'un a d'abord voulu tout supprimer puis changé d'avis n'aide personne, et pourrait servir contre lui. Le référent est stocké masqué **et** haché, jamais en clair : on doit pouvoir vérifier qu'une personne qui se présente est bien celle désignée, sans conserver le carnet d'adresses d'une famille en deuil. `consent_id` n'est pas nullable — une directive sans consentement journalisé n'a aucune valeur.

## cohorts (bloc 17)
`id`, `name`, `phase` check (`0A`, `0B`, `launch`), `started_at`, `notes`, timestamps.

## audit_logs (bloc 11) — bigint
`occurred_at`, `actor_user_id` nullable, `actor_role`, `actor_context` check (`web`, `filament`, `cli`, `phone_operator`, `system`), `action` varchar, `subject_type`, `subject_id`, `project_id` nullable, `ip_hash` nullable, `payload` jsonb (diff avant/après, données personnelles masquées), `previous_hash` char(64), `hash` char(64). Trigger `audit_logs_append_only` : `BEFORE UPDATE OR DELETE … RAISE EXCEPTION`. `hash = sha256(previous_hash || occurred_at || action || subject_type || subject_id || payload::text)`.

## Tables de packages
`settings` (spatie/laravel-settings, bloc 01), `permissions`/`roles`/`model_has_*` (spatie/laravel-permission, bloc 02), `features` (Pennant, bloc 02), `media` (spatie/laravel-medialibrary, bloc 12), `jobs`, `failed_jobs`, `job_batches`, `cache`, `sessions`, `telescope_*` (local).

**Cashier n'apporte aucune table** (décision T-104). Ses migrations d'abonnement — `subscriptions` et `subscription_items` — ont été retirées : le pilote ne vend qu'un paiement unique, et deux tables vides invitent à croire qu'un abonnement existe. Seules les colonnes client sont ajoutées à `users` par une migration maison : `stripe_id` (indexé), `pm_type`, `pm_last_four`, `trial_ends_at`.
