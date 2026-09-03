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

**`Story::isVisibleToFamily()` est la seule source de vérité de la visibilité côté proches.** Elle retourne vrai si et seulement si l'état est `shared` ou `in_book` **et** que la visibilité n'est pas `book_only`. Aucun écran, aucune requête, aucun jeton ne recalcule cette règle pour son compte : tout passe par cette méthode. Une histoire « livre uniquement » est donc incluse au livre sans jamais être écoutable en ligne — le narrateur a choisi le papier, pas la diffusion.

Les transitions vivent dans `App\States\Story\Transitions` et nulle part ailleurs. `App\Exceptions\Domain\ForbiddenTransition` est la seule exception qu'un appelant ait à connaître : `StoryState::transitionTo()` traduit le refus du paquet en refus métier. Les gardes qui refusent, aujourd'hui : validation depuis `transcribed` sans `share_decision = share` ; partage d'une histoire `book_only` ; inclusion au livre depuis `validated` hors `book_only` ; validation depuis `recorded` sans `validated_via = phone_operator` **et** sans consentement `phone_call_recording` accordé ; sortie de `hidden` ou de `trashed` vers un autre état que `previous_state` ; restauration d'une corbeille de plus de trente jours ; toute transition depuis `deleted`.

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

**Où vivent les jetons.** Le jeton en clair n'existe qu'entre son émission et son envoi : la base ne garde que `sha256(jeton)`, et rien ne permet de relire un lien déjà envoyé — on en émet un nouveau. `App\Services\Tokens\TokenService` est le seul à lire `token_hash`, et `App\Support\Links` le seul à construire une URL. Deux tests inscrivent ces deux règles (`tests/Unit/Tokens/TokenServiceTest.php`).

Le `sensitive_grant` est le seul type qui ne voyage jamais dans un lien : il vit dans un cookie `sg`, `HttpOnly`, `SameSite=Strict`, quinze minutes. Une URL finirait dans l'historique du navigateur, dans les journaux et dans l'en-tête `Referer`.

**Frontière du consentement fort (bloc 03 §6.5).** Depuis un lien `record`, les actions sur **cette** histoire — enregistrer, reprendre, choisir `keep_private`, masquer juste après l'enregistrement — ne demandent **pas** d'OTP : le lien a été envoyé au narrateur sur un canal qu'il possède, et exiger un code à chaque geste ferait fuir la personne qu'on veut faire parler.

Exigent un `sensitive_grant` frais, obtenu par code à usage unique :

- toute action sur une **autre** histoire que celle du lien ;
- toute **suppression** (corbeille vers supprimée) ;
- tout **réglage durable** du projet (cadence, canal, pause longue, visibilité par défaut) ;
- toute **directive post-mortem** ;
- tout **retrait tardif** : masquer, archiver ou mettre à la corbeille une histoire déjà partagée depuis plus longtemps que la session d'enregistrement.

En cas de doute, l'action est sensible : c'est la règle de décision par défaut du bloc 03. On assouplit ensuite avec un test qui documente l'exception.

## 5. Consentements (`consents.kind`, doc 04 §2)

`voice_recording` (a), `transcription` (b), `ai_rendering` (c), `family_sharing` (d), `sensitive_categories`, `phone_call_recording` (D-9), `photo_rights` (déposant), `post_mortem_directives`, `mandate_delegation` (bloc 07), `early_service_start` et `marketing_email` (bloc 10, l'acheteur).

Les cinq premiers sont ceux de l'opt-in : cinq cases, cinq lignes datées avec la version du texte lu. Pas un « j'accepte tout » — le dossier les veut distincts et révocables, et une case unique rendrait la révocation d'un seul impossible.

Les deux du bloc 10 appartiennent à l'**acheteur**, pas au narrateur, et sont séparés de l'acceptation des CGV : `early_service_start` fait perdre une partie du droit de rétractation, et une case à cocher qui mêlerait les deux ne vaudrait pas consentement. `marketing_email` est décoché par défaut et n'est jamais requis pour payer (critère de sortie du bloc 10).

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

## 7bis. Événements de mesure (`App\Enums\AnalyticsEvent`, bloc 08)

`family_link_opened`, `story_page_opened`, `story_listened_30s`, `reaction_sent`, `narrator_notified`, `story_recorded_within_7d_of_notification` (calculé au bloc 09).

Les cinq premiers sont les maillons de la chaîne H2, mesurés **séparément** : un taux global ne dirait pas *où* la chaîne casse — page jamais ouverte, écoute abandonnée à dix secondes, réaction jamais envoyée, notification jamais reçue. Aucun de ces événements ne porte de donnée personnelle : des identifiants opaques et des durées, jamais un prénom, une coordonnée, un jeton ni le contenu d'un message.

## 7ter. Actions du journal d'audit (`audit_logs.action`, bloc 11)

Forme : **verbe au passé, puis nom de classe du sujet** — `viewed Story`, `played Recording`, `edited Transcript`, `refunded Order`. Le verbe au passé parce qu'une ligne d'audit décrit un fait accompli ; le nom de classe parce qu'il est stable et qu'il se retrouve par `subject_type`.

| Action | Quand |
|---|---|
| `viewed <Classe>` | Une page de consultation du panneau s'ouvre. Une ligne par visite, pas par battement de l'interface. |
| `played Recording` | Une URL d'écoute a été demandée. Distinct de `viewed Recording` : lire une fiche technique n'est pas écouter la voix de quelqu'un. |
| `edited Transcript` | Une correction crée une nouvelle version. Le journal garde la **taille** du changement, jamais les deux textes. |
| `hided Story`, `trashed Story`, `restored Story` | Retrait ou remise, avec le motif — la trace de la demande du narrateur. |
| `paused Project`, `resumed Project`, `rescheduled Project`, `froze Project` | Les quatre gestes du support sur le rythme. Le gel porte le motif. |
| `reissued AccessToken`, `reissued ListenLink`, `revoked AccessToken` | Émission ou fermeture d'un lien. Jamais le lien lui-même. |
| `removed FamilyMember` | Accès retiré, ligne conservée. |
| `refunded Order` | Remboursement demandé, avec le motif et le montant. |
| `changed UserRole` | Rôle modifié, avec l'ancien et le nouveau. |
| `edited PilotSettings` | Prix, mode ou validation juridique changés. |
| `attached Photo`, `removed Photo`, `edited PhotoCaption` | Dépôt, retrait et légende d'une photo (bloc 12). Le journal garde la **taille** d'une légende, jamais son texte : une légende peut nommer des personnes. |
| `purged Recording` | Purge programmée. Acteur `system` : une purge n'a pas d'auteur humain. |

Ce que le contenu ne porte **jamais** : un lien en clair, un courriel, un numéro, un mot de passe, un code. `App\Audit\Redactor` les remplace avant l'insertion — et une ligne d'audit ne peut pas être modifiée après coup, donc ce qui y passe y reste.

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

Tout le reste vit sur l'hôte de `APP_URL` : `/` (accueil), `/essai` (démonstration), `/acheter` (tunnel, six étapes), `/acheter/merci`, `/cgv`, `/confidentialite`, `/mentions-legales`, `/consentements`, `/espace` (Initiateur·rice : `/questions`, `/proches`, `/reglages`, `/commandes`), `/admin`, `/stripe/webhook` et `/webhooks/*`.

Deux exceptions au préfixe `/i/`, ajoutées au bloc 10 : `/i/{token}/bienvenue` porte le jeton d'invitation comme la page d'opt-in, mais `/i/farewell` **n'en porte aucun** — il s'affiche après un refus, quand le jeton vient d'être consommé, et exiger un jeton valide y renverrait une erreur à quelqu'un qui vient de dire non.

Un jeton fait exactement 43 caractères de base64url (32 octets aléatoires) : `Route::pattern('token', '[A-Za-z0-9_-]{43}')` refuse tout le reste par un 404, **avant** la moindre requête. Chaque route de `narrator.php` et de `family.php` porte `resolve.token:<type>`, `throttle:tokens` et `no-store` ; un test parcourt la table de routage et échoue si l'une y échappe. Seule exception documentée : `POST /r/{token}/request-new-link`, qui agit justement parce que le lien est mort.

Les espaces `/a/` (action en un tap) et `/x/` (export) servent l'Initiateur·rice, mais leur page d'erreur est celle des proches : elle ne propose pas de renvoi automatique, l'Initiateur·rice ayant un compte pour se reconnecter.
