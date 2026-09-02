# Bloc 07 — Validation explicite, visibilité, retraits

Statut : ☐ non commencé · Dépend de : 06 · Tag de fin : `bloc-07-done`
Références dossier : PRD P0-18, US-04, R-4 (états de retrait), doc 04 §1 et §3 (souveraineté, « l'absence de réaction ne vaut jamais accord »), §12 (actes sensibles sous OTP) ; décision T-15.

## 1. Objectif

Le narrateur choisit explicitement, en un geste, le sort de chaque histoire : partager, garder pour lui, décider plus tard. Les deux variantes du dossier existent derrière un flag. Il peut relire et corriger son texte, restreindre qui écoute, masquer, mettre à la corbeille, supprimer. Rien n'est jamais visible des proches sans son accord. L'espace narrateur après OTP liste ses histoires.

## 2. Pourquoi

C'est le différenciateur structurel face au partage instantané de Remento, et le point de tension identifié : la validation doit ressembler à une récompense d'un tap, pas à une relecture. Les deux variantes sont le test le plus important de la Phase 0A.

## 3. Livrables

- Flags Pennant `validation-variant`, `mandate-delegation`.
- Écran des trois choix en fin d'enregistrement (variante A) et page de relecture après transcription (variante B), tous deux sur le jeton `record`.
- Actions `RecordShareDecision`, `ApplyShareDecision`, `ValidateStoryAction`, `SetStoryVisibility`, `HideStoryAction`, `UnhideStoryAction`, `TrashStoryAction`, `RestoreStoryAction`, `DeleteStoryAction`.
- Écouteur de `TranscriptionReady` qui applique la décision (A) ou notifie la relecture (B).
- Espace narrateur `/n/{token}` (après OTP) : liste, relecture, retraits.
- Commande `stories:purge-trashed`, job `PurgeDeletedStory`.
- Table `mandates` et action `GrantMandate` / `RevokeMandate` (UI au bloc 10).
- Table `story_visibility_family_members`.

## 4. Packages

Aucun nouveau (Pennant installé au bloc 02).

## 5. Tests à écrire d'abord

- `tests/Feature/Narrator/ShareDecisionTest.php`
  - `it('records share, keep_private or decide_later from the record token')`
  - `it('refuses a decision from a listen token')`
  - `it('does not validate immediately: state stays recorded until transcription')`
- `tests/Feature/Listeners/ApplyShareDecisionOnTranscriptionReadyTest.php`
  - variante A : `share` → `validated (recording_end)` puis `shared` ; `keep_private` → reste `transcribed` ; `decide_later` → `to_review` + notification `notifications.review.decide_later`
  - variante B : toujours `to_review` + notification `notifications.review.ready` avec le lien de relecture
  - `it('never shares when no explicit decision exists')`
- `tests/Feature/Narrator/ReviewPageTest.php` : rend verbatim et fluide, le lecteur audio de sa propre histoire, la correction facultative (`EditTranscript`), les trois choix ; `share` → `validated (post_transcription)` + `shared` ; `keep_private` → `validated` non partagé ? **Non** : `keep_private` laisse l'histoire `to_review → transcribed` ? Décision : `keep_private` transitionne vers `validated` avec `visibility = book_only` **uniquement si** le narrateur coche « garder pour le livre » ; sinon l'histoire reste `transcribed`, privée, réversible depuis l'espace narrateur. Le test couvre les deux cas.
- `tests/Feature/Narrator/VisibilityTest.php` : `restricted` avec liste de proches ; `book_only` ; changement de visibilité après partage révoque la visibilité en ligne immédiatement (`isVisibleToFamily()` faux pour les proches exclus).
- `tests/Feature/Narrator/WithdrawalsTest.php`
  - `it('hides the current story from the record token without otp')`
  - `it('requires a sensitive grant to hide another story')`
  - `it('hides in at most two requests')` (POST unique après un écran de confirmation)
  - `it('trashes then restores within 30 days')`, `it('refuses restore after 30 days')`
  - `it('deletes only with a sensitive grant and the typed word SUPPRIMER')`
  - `it('shows the printed copies warning when the story is in_book')`
- `tests/Feature/Console/PurgeTrashedStoriesTest.php` : après 30 jours → `deleted`, `PurgeDeletedStory` supprime original, dérivé, réplique, photos, transcripts ; la ligne `stories` reste avec `deleted_at` et sans contenu (colonnes `title`, `written_answer` vidées).
- `tests/Feature/Narrator/NarratorSpaceTest.php` : `/n/{token}` exige un `narrator_space` issu d'un OTP ; liste les histoires par état avec libellés en langage simple ; les actions sensibles exigent un `sensitive_grant`.
- `tests/Feature/Mandates/MandateTest.php` (flag actif) : un mandataire peut valider une histoire `to_review` (`validated_via = mandate`) ; révocation immédiate ; sans flag, 404.
- `tests/Unit/Features/ValidationVariantTest.php` : le flag est résolu par projet et copié dans `projects.validation_variant`.
- `resources/js/pages/narrator/ShareDecision.test.tsx` : trois boutons ≥ 44 px, ordre « Partager avec mes proches », « Garder pour moi », « Décider plus tard », aucun compte à rebours, aucun choix pré-sélectionné.
- `tests/e2e/validation-variant-a.spec.ts`, `validation-variant-b.spec.ts`, `withdrawals.spec.ts`, `narrator-space-otp.spec.ts` (avec `SMS_PROVIDER=fake` exposant le code via une route de test locale).

## 6. Étapes

### 6.1 Flags
- [ ] `app/Features/ValidationVariant.php` (Pennant, résolution : valeur de `projects.validation_variant` si posée, sinon défaut `immediate`), `app/Features/MandateDelegation.php` (défaut `false`).
- [ ] Commande `features:set-variant {project} {immediate|deferred}` pour le pilote.

### 6.2 Décision de partage (variante A)
- [ ] Route `POST /r/{token}/share-decision` (`decision` in `share|keep_private|decide_later`) → `RecordShareDecision` : pose `share_decision`, `share_decided_at` ; pas de transition.
- [ ] Écran `narrator/ShareDecision.tsx` affiché après la confirmation du bloc 04 : « Que souhaitez-vous faire de cette histoire ? » puis trois boutons ; sous « Partager » : « Vos proches pourront l'écouter et lire le texte » ; sous « Garder pour moi » : « Personne d'autre que vous ne l'entendra » ; sous « Décider plus tard » : « Nous vous le redemanderons, sans insister ».
- [ ] Après le choix : écran de remerciement adapté.

### 6.3 Écouteur `TranscriptionReady`
- [ ] `ApplyShareDecision(Story)` : variante A + `share` → `ValidateStoryAction(via recording_end)` puis `ShareStory` ; A + `keep_private` → rien ; A + `decide_later` → `RequestReview` + notification `notifications.review.decide_later` ; variante B → `RequestReview` + `notifications.review.ready` (lien `/r/{token}/review`, même jeton `record`).
- [ ] `ValidateStoryAction(Story, ValidatedVia, ?Model $actor)` centralise : transition, `validated_at`, `validated_via`, révocation des jetons `record` (bloc 03), émission de `StoryValidated`.

### 6.4 Page de relecture (variante B et « décider plus tard »)
- [ ] Route `GET /r/{token}/review` → `narrator/Review.tsx` : question, lecteur audio (URL temporaire du MP3), onglets « Texte mis au propre » / « Mot à mot » (mobile) ou côte à côte (≥ 768 px), mention IA (`narrator.review.ai_label`), bouton « Corriger » qui ouvre un textarea prérempli avec le texte courant → `POST /r/{token}/review/edit` (`EditTranscript`), puis les trois choix → `POST /r/{token}/review/decision`.
- [ ] `POST /r/{token}/review/decision` : `share` → validation `post_transcription` + partage ; `keep_private` avec option `keep_for_book` → `validated` + `visibility book_only`, sans option → reste `transcribed` ; `decide_later` → reste `to_review` (la règle `recorded_not_validated` du bloc 09 relance deux fois maximum).
- [ ] Choix de visibilité au moment de partager : par défaut « Tous mes proches » ; lien discret « Choisir qui peut écouter » → liste à cocher des proches ; « Pour le livre seulement ».

### 6.5 Retraits
- [ ] `App\Support\SensitiveActs::requiresGrant(Story $target, AccessToken $current): bool` : faux si le jeton est `record` **et** `target === token.subject` ; vrai sinon.
- [ ] Routes sur `record` : `POST /r/{token}/hide` (histoire du jeton, sans OTP, après écran de confirmation « Masquer cette histoire ? Vous pourrez la remettre plus tard. »).
- [ ] Routes sur `narrator_space` (`/n/{token}/stories/{story}/…`) : `hide`, `unhide`, `trash`, `restore`, `delete` (avec `RequireSensitiveGrant` et champ `confirmation === 'SUPPRIMER'`), `visibility`.
- [ ] Avertissement `narrator.withdrawals.printed_copies_warning` si `printed_in_book`.
- [ ] `stories:purge-trashed` (`daily()`), `PurgeDeletedStory` (file `media`) : supprime les objets R2 (original, dérivé, réplique, médias), les `transcripts`, vide `title`/`written_answer`, journalise `story_purged` (audit bloc 11 si présent, sinon log).

### 6.6 Espace narrateur
- [ ] `GET /n/request` (domaine des liens, formulaire : numéro ou email déjà connu) → `OtpService::challenge(narrator_space)` ; `POST /n/verify` → jeton `narrator_space` posé en cookie `HttpOnly` et redirection `/n/{token}`.
- [ ] `narrator/Space.tsx` : « Vos histoires », cartes par histoire (titre, date, état en langage simple : « Partagée avec vos proches », « Gardée pour vous », « En attente de votre choix », « Masquée », « Dans la corbeille jusqu'au {date} »), actions par carte, lien « Demander une pause » (`projects.paused_until`, bloc 09 gère la reprise).
- [ ] Lien vers l'espace dans chaque email de relecture et dans la fiche contact.

### 6.7 Mandat (flag)
- [ ] Migration `create_mandates_table` (`project_id`, `narrator_id`, `holder_type/holder_id` (User ou FamilyMember), `scope` jsonb `["validate"]`, `consent_id`, `granted_at`, `revoked_at`) ; annexe B.
- [ ] `GrantMandate` exige un consentement `channel = phone|web` journalisé du narrateur (OTP côté narrateur) ; `RevokeMandate` immédiat.
- [ ] Policy : un mandataire peut appeler `ValidateStoryAction(via mandate)` sur `to_review` uniquement. L'UI arrive au bloc 10.

### 6.8 Clôture
- [ ] Annexe B (`mandates`, `story_visibility_family_members`), `01_CONVENTIONS.md` §15 vérifié.
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts.
- [ ] Commit `chore(bloc-07): terminé`, tag `bloc-07-done`.

## 7. Checkpoint démontrable

1. Projet en variante A : enregistrer, choisir « Partager » ; attendre la transcription ; l'histoire passe `shared` sans autre action.
2. Même projet : enregistrer, choisir « Décider plus tard » ; la notification arrive après transcription ; la page de relecture permet de corriger un mot et de partager.
3. Projet en variante B : enregistrer ; aucune décision demandée ; notification de relecture ; les trois choix.
4. Depuis l'espace narrateur (OTP par SMS de test) : masquer une histoire partagée → elle disparaît côté famille (vérifiable au bloc 08) ; corbeille → restaurer ; supprimer avec le mot SUPPRIMER.
5. `sail artisan stories:purge-trashed` avec `trashed_at` forcé à J-31 → objets R2 supprimés.

## 8. Critères de sortie

- [ ] Il n'existe aucun chemin de code qui passe une histoire en `shared` sans `validated_at` et `validated_via` posés par `ValidateStoryAction` (revue + test `NoDirectStateWrite`).
- [ ] Les trois choix sont présentés sans présélection, sans minuteur, dans le même ordre partout.

## 9. Règle de décision par défaut

En cas d'ambiguïté sur « garder pour moi », l'histoire reste privée et hors livre. Le livre n'inclut jamais une histoire qui n'a pas été explicitement validée.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
