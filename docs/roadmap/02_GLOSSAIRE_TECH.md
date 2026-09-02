# Glossaire technique — métier FR ↔ code EN

Le dossier produit parle français. Le code parle anglais. Cette table est la seule correspondance autorisée. Si un terme manque, on l'ajoute ici avant de l'utiliser dans le code.

## 1. Acteurs et rôles (R-1)

| Terme du dossier | Identifiant de code | Stockage | Notes |
|---|---|---|---|
| Initiateur·rice | `Initiator` (rôle) ; modèle `User` avec `ProjectMember.role = initiator` | `users`, `project_members` | Achète, organise, prépare le BAT. A un compte (Fortify). |
| Narrateur·rice | `Narrator` | `narrators` | Pas de compte. Agit par jeton `record`. OTP pour les actes sensibles. |
| Proches | `FamilyMember` | `family_members` | Pas de compte. Agit par jeton `listen_project`. |
| Éditeur désigné | `ProjectMember.role = editor` | `project_members` | Délégué par l'Initiateur·rice. |
| Contributeur (photos) | `FamilyMember.can_contribute = true` | `family_members` | Bloc 12. |
| Support / admin | `User.role ∈ {admin, support, support_readonly}` | `users` | Accès Filament, MFA obligatoire. |
| Opérateur téléphone (D-9) | `User.role ∈ {admin, support}` agissant avec `actor_context = phone_operator` | `audit_logs` | Bloc 17. |

## 2. Objets métier

| Terme du dossier | Modèle | Table | Notes |
|---|---|---|---|
| Projet (un livre pour un narrateur principal) | `Project` | `projects` | `narrators` : plusieurs possibles en base, un seul `is_primary` (multi-narrateurs hors UI). |
| Question éditorialisée (corpus) | `Question` | `questions` | Corpus FR, `difficulty` 1-5, `theme`. |
| Histoire | `Story` | `stories` | Créée à la proposition d'une question. Porte la machine d'états R-4. |
| Enregistrement (audio) | `Recording` | `recordings` | Original conservé ; dérivé MP3 ; `is_current`. |
| Transcription verbatim | `Transcript` avec `kind = verbatim` | `transcripts` | Jamais supprimée. |
| Rendu Fluide | `Transcript` avec `kind = fluide` | `transcripts` | Généré par `StoryRenderer`, étiqueté IA. |
| Correction du narrateur ou de l'éditeur | `Transcript` avec `kind = edited`, `source_transcript_id` | `transcripts` | Historique complet par version. |
| Lexique des noms propres | `LexiconEntry` | `lexicon_entries` | Par projet. |
| Photo | média spatie sur `Story` (collection `photos`) | `media` | Bloc 12. |
| Réaction (❤️, merci, commentaire court) | `Reaction` | `reactions` | Bloc 08. |
| Écoute | `ListenEvent` | `listen_events` | Seuil 30 s. |
| Consentement | `Consent` | `consents` | Une ligne par consentement, révocation = nouvelle ligne `revoked`. |
| Invitation du narrateur | `Invitation` | `invitations` | H0, plafonds 2 + 1 relance. |
| Lien / jeton | `AccessToken` | `access_tokens` | Voir §4. |
| Code à usage unique | `OtpChallenge` | `otp_challenges` | 6 chiffres, 10 min, 5 essais. |
| Prompt envoyé (SMS/email) | `OutboundMessage` | `outbound_messages` | Statut de livraison par webhook. |
| Relance / règle du moteur | `App\Engine\Rules\*` ; occurrence `EngineEvent` | `engine_events` | Bloc 09, annexe C. |
| Pause demandée | `Project.paused_until` | `projects` | Bloc 09. |
| Commande, add-on | `Order`, `OrderItem` | `orders`, `order_items` | Bloc 10. |
| Option « Enregistrement par téléphone » (D-9) | `PhoneOption` | `phone_options` | `entry ∈ {checkout, rescue}` ; plafond dans `PilotSettings`. |
| Livre, BAT | `Book`, `BookChapter` | `books`, `book_chapters` | `proof_approved_at` = BAT validé. |
| Export complet, pack hors-ligne | `Export` | `exports` | Bloc 14. |
| Directives post-mortem | `PostMortemDirective` | `post_mortem_directives` | Bloc 10 (onboarding), lecture bloc 11. |
| Cohorte pilote | `Cohort` | `cohorts` | Bloc 17. |
| Journal d'audit | `AuditLog` | `audit_logs` | Append-only, chaîne de hachage. |

## 3. États d'une histoire (R-4)

Classe de base `App\States\Story\StoryState` (spatie/laravel-model-states), colonne `stories.state`.

| Dossier | Classe | Valeur stockée | Entrée autorisée depuis |
|---|---|---|---|
| PROPOSÉE | `Proposed` | `proposed` | création |
| ENREGISTRÉE | `Recorded` | `recorded` | `proposed` |
| TRANSCRITE | `Transcribed` | `transcribed` | `recorded` |
| À RELIRE | `ToReview` | `to_review` | `transcribed` |
| VALIDÉE | `Validated` | `validated` | `to_review` (variante B), `transcribed` (variante A, si `share_decision = share` posée en fin d'enregistrement), `recorded` (opérateur téléphone avec accord oral journalisé) |
| PARTAGÉE | `Shared` | `shared` | `validated` |
| INCLUSE AU LIVRE | `InBook` | `in_book` | `shared`, `validated` (visibilité « livre uniquement ») |
| MASQUÉE | `Hidden` | `hidden` | tout état ≥ `recorded` ; retour possible vers l'état précédent (`previous_state`) |
| ARCHIVÉE | `Archived` | `archived` | tout état ≥ `recorded` |
| CORBEILLE | `Trashed` | `trashed` | tout état ≥ `recorded` ; `trashed_at` ; restauration ≤ 30 j |
| SUPPRIMÉE | `Deleted` | `deleted` | `trashed` après 30 j, ou acte explicite sous OTP |

`share_decision` (colonne de `stories`) : `null`, `share`, `keep_private`, `decide_later` ; `share_decided_at`, `validated_at`, `validated_via ∈ {recording_end, post_transcription, mandate, phone_operator}`.

Visibilité à la validation (`stories.visibility`) : `all_family`, `restricted` (liste `story_visibility_family_members`), `book_only`.

## 4. Types de jetons (`access_tokens.type`)

| Type | Périmètre | Durée | Invalidé par |
|---|---|---|---|
| `record` | 1 histoire, actions d'enregistrement et choix de partage | 30 jours | passage à `validated`, révocation, ré-émission |
| `listen_project` | 1 proche, lecture des histoires visibles du projet | 12 mois renouvelables | révocation par l'Initiateur·rice |
| `listen_story` | 1 histoire, lecture seule (lien direct dans une notification) | 90 jours | révocation |
| `qr` | 1 histoire, lecture seule non authentifiée, code famille optionnel | selon D-8, sans expiration technique | révocation par la famille |
| `invitation` | 1 invitation narrateur, page d'opt-in | 30 jours | acceptation, refus, expiration |
| `action` | 1 action 1-tap de l'Initiateur·rice (alerte du moteur) | 14 jours | usage |
| `export` | 1 export, téléchargement | 7 jours | expiration |
| `narrator_space` | espace du narrateur (liste de ses histoires) après OTP | 30 jours glissants | révocation |
| `sensitive_grant` | autorisation d'un acte sensible après OTP (masquer une histoire ancienne, supprimer, régler) | 15 minutes | usage ou expiration |

## 5. Consentements (`consents.kind`, doc 04 §2)

`voice_recording` (a), `transcription` (b), `ai_rendering` (c), `family_sharing` (d), `sensitive_categories`, `phone_call_recording` (D-9), `photo_rights` (déposant), `post_mortem_directives`.

Colonnes : `subject_type/subject_id` (narrateur, proche ou utilisateur), `project_id`, `kind`, `status ∈ {granted, revoked}`, `channel ∈ {web, phone, admin}`, `text_version` (référence `consent_texts.version`), `ip_hash`, `user_agent`, `granted_at`, `revoked_at`, `recorded_by` (utilisateur admin si `channel = phone`).

## 6. Canaux et fournisseurs

| Terme | Code |
|---|---|
| SMS | `Channel::Sms`, `SmsSender` → `TwilioSmsSender` / `FakeSmsSender` |
| Email | `Channel::Email`, mailer `resend` |
| WhatsApp (transfert manuel) | pas un canal : bouton « copier le lien » côté Initiateur·rice |
| Téléphone (D-9) | `Channel::PhoneOperator`, aucun envoi automatique |
| ASR | `TranscriptionProvider` → `GladiaProvider`, `DeepgramProvider`, `FakeTranscriptionProvider` |
| Rendu Fluide | `StoryRenderer` → `ClaudeStoryRenderer`, `FakeStoryRenderer` |
| Stockage | disque `r2` (Flysystem S3), `r2_replica`, `r2_backups` |

## 7. Identifiants des règles du moteur (annexe C)

`invitation_not_accepted`, `link_not_opened`, `mic_denied`, `recording_abandoned`, `recorded_not_validated`, `validated_not_listened`, `three_stories_no_reaction`, `narrator_silence_10d`, `narrator_silence_21d`, `pause_requested`, `declining_cadence`.

## 8. Espaces d'URL (hôte `LINKS_DOMAIN`)

| Préfixe | Espace | Jeton |
|---|---|---|
| `/r/{token}` | Narrateur, enregistrement | `record` |
| `/n/{token}` | Narrateur, espace personnel après OTP | `narrator_space` |
| `/l/{token}` | Famille, projet ou histoire | `listen_project`, `listen_story` |
| `/q/{token}` | QR imprimé | `qr` |
| `/i/{token}` | Opt-in narrateur | `invitation` |
| `/a/{token}` | Action 1-tap Initiateur·rice | `action` |
| `/x/{token}` | Téléchargement d'export | `export` |

Tout le reste (`/`, `/essai`, `/acheter`, `/espace`, `/admin`, `/webhooks/*`) vit sur l'hôte de `APP_URL`.
