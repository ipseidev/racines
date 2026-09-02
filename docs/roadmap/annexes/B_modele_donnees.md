# Annexe B — Modèle de données

Postgres. Toutes les colonnes `*_at` sont `timestamptz`. Clés primaires `uuid` sauf mention `bigint`. Les enums sont stockés en `varchar` avec contrainte `check`, posée par `App\Support\Database\EnumCheck` et nommée `<table>_<colonne>_check`. Toute table nouvelle est ajoutée ici dans le même commit que sa migration. Le bloc qui crée la table est indiqué.

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

## narrators (bloc 02)
`id`, `project_id` FK, `first_name`, `last_name` nullable, `display_name`, `email` nullable, `phone_e164` nullable, `preferred_channel` check (`sms`, `email`), `is_primary` bool, `birth_year` smallint nullable, `opted_in_at`, `opted_out_at`, `contact_deletion_due_at` nullable, timestamps. Index unique partiel `narrators_one_primary (project_id) where is_primary`. Contrainte `narrators_reachable_check` : `email` ou `phone_e164` non nul. `preferred_channel` n'accepte que `sms` et `email` : le téléphone n'est jamais un canal d'envoi automatique (R-9).

## family_members (bloc 02)
`id`, `project_id` FK, `invited_by_user_id` FK users, `display_name`, `relationship` nullable, `email` nullable, `phone_e164` nullable, `can_contribute` bool défaut false, `invited_at` nullable, `first_seen_at` nullable, `removed_at` nullable, timestamps. Index `(project_id)`.

## questions (bloc 02, données bloc 05)
`id`, `slug` unique, `text`, `theme` check (`childhood`, `family_origins`, `youth`, `work`, `love`, `places`, `joys`, `hardships`, `beliefs_values`, `legacy`), `difficulty` smallint défaut 1, contrainte `questions_difficulty_check` 1-5, `order_hint` int défaut 0, `is_active` bool défaut true, `locale` défaut `fr`, timestamps. Index `(theme, order_hint)`.

## project_question_settings (bloc 05) — bigint
`project_id`, `question_id`, `excluded` bool, `custom_order` int nullable, timestamps. Unique `(project_id, question_id)`.

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
`story_id`, `family_member_id`. Unique sur le couple.

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
| device_info | jsonb | user-agent, plateforme, navigateur, version |
| created_at, updated_at | | |

## transcripts (bloc 06)
`id`, `story_id`, `kind` check (`verbatim`, `fluide`, `edited`), `source_transcript_id` FK nullable, `version` int, `provider` varchar nullable (`gladia`, `deepgram`, `claude`, `human`), `provider_job_id` nullable, `language` défaut `fr`, `text` text, `words` jsonb nullable (mots horodatés), `metadata` jsonb (modèle, usage, durée de traitement), `edited_by_type`/`edited_by_id` nullable, `is_current` bool, `created_at`. Règle : `DELETE` interdit sur `kind = verbatim` (règle Postgres `transcripts_verbatim_no_delete` + garde modèle). Unique partiel `(story_id, kind) where is_current`.

## lexicon_entries (bloc 06) — bigint
`project_id`, `term`, `replacement` nullable, `notes` nullable, `created_by_type/id`, timestamps. Unique `(project_id, term)`.

## consent_texts (bloc 02) — bigint
`id`, `kind` check, `version`, `locale` défaut `fr`, `body` text, `effective_from`, `created_at` (pas d'`updated_at` : un texte publié ne se modifie pas, on en publie un autre). Unique `(kind, version, locale)`. `ConsentText::current($kind, $locale)` retourne la version en vigueur la plus récente.

## consents (bloc 02)
`id`, `project_id` FK, `subject_type`, `subject_id` varchar(64), `kind` check (voir glossaire §5), `status` check (`granted`, `revoked`), `channel` check (`web`, `phone`, `admin`), `text_version` varchar, `ip_hash` char(64) nullable, `user_agent` varchar nullable, `granted_at`, `revoked_at` nullable, `recorded_by_user_id` FK users nullable, `created_at` (pas d'`updated_at` : une ligne de consentement ne se modifie jamais). Index `(subject_type, subject_id, kind)`, `(project_id, kind)`.

`subject_id` est un `varchar` et non un `uuid` : le sujet peut être un narrateur ou un proche (uuid) comme un utilisateur (identifiant séquentiel). `subject_type` porte l'alias de la table de correspondance (`narrator`, `family_member`, `user`).

Contrainte `consents_phone_operator_check` : `channel <> 'phone' or recorded_by_user_id is not null`. Un accord oral recueilli par téléphone nomme toujours son opérateur (D-9) ; c'est aussi ce que vérifie la garde de `ValidateStory` avant d'accepter une validation `phone_operator`.

## invitations (bloc 10)
`id`, `project_id`, `narrator_id`, `channel`, `attempt` smallint (1, 2, 3), `token_id` FK access_tokens, `sent_at`, `opened_at`, `accepted_at`, `refused_at` nullable, timestamps. Contrainte : au plus 3 lignes par narrateur.

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

## otp_challenges (bloc 03)
`id`, `narrator_id` nullable, `family_member_id` nullable, `purpose` check (`narrator_space`, `sensitive_act`), `code_hash`, `channel`, `sent_to_masked`, `attempts` smallint, `expires_at`, `verified_at` nullable, `locked_until` nullable, `created_at`.

## outbound_messages (bloc 05)
`id`, `project_id` nullable, `channel` check (`sms`, `email`), `to_hash`, `to_masked`, `template` varchar, `payload` jsonb (sans données personnelles en clair), `provider`, `provider_message_id` nullable, `status` check (`queued`, `sent`, `delivered`, `failed`, `bounced`, `undelivered`), `status_detail` nullable, `dedupe_key` unique, `sent_at`, `delivered_at`, `failed_at` nullable, `created_at`.

## reactions (bloc 08)
`id`, `story_id`, `family_member_id`, `type` check (`heart`, `thanks`), `comment` varchar(280) nullable, `created_at`. Unique `(story_id, family_member_id, type)`.

## listen_events (bloc 08) — bigint
`story_id`, `family_member_id` nullable, `token_type`, `seconds_listened` int, `reached_30s` bool, `started_at`, `created_at`. Index `(story_id, reached_30s)`.

## engine_events (bloc 09) — bigint
`project_id`, `story_id` nullable, `rule_id` varchar, `occurrence_key` varchar, `dedupe_key` unique (`rule_id:occurrence_key`), `fired_at`, `action_taken` jsonb, `outcome` check nullable (`resumed`, `no_effect`), `outcome_at` nullable, `created_at`. Index `(project_id, rule_id)`.

## orders, order_items (bloc 10)
orders : `id`, `user_id`, `project_id` nullable, `stripe_checkout_session_id` unique, `stripe_payment_intent_id` nullable, `status` check (`pending`, `paid`, `refunded`, `partially_refunded`, `cancelled`), `currency` `eur`, `subtotal_cents`, `total_cents`, `refunded_cents`, `price_variant` check nullable (`99`, `129`), `paid_at`, `withdrawal_deadline_at`, `service_started_at` nullable, timestamps.
order_items (bigint) : `order_id`, `sku` check (`pilot`, `core_prevente`, `extra_copy`, `phone_option`), `quantity`, `unit_cents`, `stripe_price_id`, `metadata` jsonb.

## phone_options (bloc 10, opéré bloc 17)
`id`, `project_id`, `order_item_id` nullable, `entry` check (`checkout`, `rescue`), `status` check (`requested`, `active`, `cancelled`, `refunded`), `operator_user_id` nullable, `call_day` smallint nullable, `call_slot` check nullable, `notes` text nullable, timestamps.

## books, book_chapters (bloc 13)
books : `id`, `project_id` unique, `template` check (`classic`), `format` check (`book`, `booklet`, `founding_chapter`), `status` check (`draft`, `proofing`, `approved`, `ordered`, `printed`, `delivered`, `reprint`), `page_count_estimate` int, `book_ready_at` nullable, `proof_pdf_path` nullable, `proof_approved_at` nullable, `proof_approved_by_user_id` nullable, `proof_acknowledged_final_print` bool, `print_order_ref` nullable, `ordered_at`, `delivered_at` nullable, timestamps.
book_chapters (bigint) : `book_id`, `story_id`, `position`, `qr_token_id` FK access_tokens, `included` bool. Unique `(book_id, story_id)`.

## exports (bloc 14)
`id`, `project_id`, `kind` check (`full`, `offline_pack`, `gdpr_access`), `status` check (`queued`, `building`, `ready`, `expired`, `failed`), `path` nullable, `bytes` bigint nullable, `manifest` jsonb nullable, `requested_by_type/id`, `token_id` nullable, `ready_at`, `expires_at` nullable, `created_at`.

## post_mortem_directives (bloc 10)
`id`, `project_id`, `narrator_id`, `wishes` check (`transfer_to_family`, `freeze`, `delete`), `referent_name` nullable, `referent_contact_masked` nullable, `referent_contact_hash` nullable, `consent_id` FK, `recorded_at`.

## cohorts (bloc 17)
`id`, `name`, `phase` check (`0A`, `0B`, `launch`), `started_at`, `notes`, timestamps.

## audit_logs (bloc 11) — bigint
`occurred_at`, `actor_user_id` nullable, `actor_role`, `actor_context` check (`web`, `filament`, `cli`, `phone_operator`, `system`), `action` varchar, `subject_type`, `subject_id`, `project_id` nullable, `ip_hash` nullable, `payload` jsonb (diff avant/après, données personnelles masquées), `previous_hash` char(64), `hash` char(64). Trigger `audit_logs_append_only` : `BEFORE UPDATE OR DELETE … RAISE EXCEPTION`. `hash = sha256(previous_hash || occurred_at || action || subject_type || subject_id || payload::text)`.

## Tables de packages
`settings` (spatie/laravel-settings, bloc 01), `permissions`/`roles`/`model_has_*` (spatie/laravel-permission, bloc 02), `features` (Pennant, bloc 02), `media` (spatie/laravel-medialibrary, bloc 12), `customers`/`subscriptions` (Cashier, bloc 10, tables de souscription inutilisées mais créées), `jobs`, `failed_jobs`, `job_batches`, `cache`, `sessions`, `telescope_*` (local).
