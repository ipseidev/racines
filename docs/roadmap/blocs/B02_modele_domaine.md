# Bloc 02 — Modèle de domaine et machine d'états

Statut : ☑ terminé (2026-09-02) · Dépend de : 01 · Tag de fin : `bloc-02-done`
Références dossier : R-1 (rôles), R-4 (états), PRD §2 (multi-narrateurs en base), doc 04 §2 (consentements distincts), doc 04 §3 (gouvernance), annexe B, glossaire §3.

## 1. Objectif

Toutes les tables du cœur du domaine existent (annexe B, blocs 02), avec factories complètes, la machine d'états des histoires est implémentée avec des transitions explicites et gardées, les rôles et permissions sont posés, et Pennant est installé. Aucune interface n'est construite dans ce bloc : tout se prouve par les tests.

## 2. Pourquoi

Le dossier est très précis sur les états, les rôles et les consentements. Les coder d'abord, sans UI, garantit que la souveraineté du narrateur est une propriété du modèle et non un effort de chaque écran.

## 3. Livrables

- Migrations : `users.role`, `cohorts`, `projects`, `project_members`, `narrators`, `family_members`, `questions`, `stories`, `consent_texts`, `consents`, tables Pennant et spatie/permission.
- Modèles Eloquent avec relations, casts, scopes ; enums PHP dans `app/Enums/`.
- `App\States\Story\*` et transitions dans `App\States\Story\Transitions\*`.
- Actions : `CreateProject`, `AddNarrator`, `AddFamilyMember`, `ProposeStory`, `RecordConsent`, `RevokeConsent`.
- Policies : `ProjectPolicy`, `StoryPolicy` (première version : Initiateur·rice et éditeur ; narrateur et proches arrivent avec les jetons au bloc 03).
- Factories avec états nommés pour chaque état d'histoire.
- Seeder `DemoProjectSeeder` (un projet, un narrateur, trois proches, cinq histoires dans cinq états différents) utilisé par les tests Playwright des blocs suivants.

## 4. Packages

```bash
sail composer require spatie/laravel-model-states spatie/laravel-permission laravel/pennant
sail artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
sail artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"
```

## 5. Tests à écrire d'abord

- `tests/Unit/States/StoryStateMachineTest.php` — une assertion par cellule de la matrice :
  - `it('starts in proposed')`
  - `it('moves proposed → recorded')`, `recorded → transcribed`, `transcribed → to_review`, `to_review → validated`, `validated → shared`, `shared → in_book`, `validated → in_book when visibility is book_only`
  - `it('moves transcribed → validated only when share_decision is share')` (variante A)
  - `it('refuses transcribed → validated without share decision')`
  - `it('refuses to_review → shared directly')`
  - `it('refuses proposed → validated')`
  - `it('moves recorded → validated only via phone_operator with an oral consent')`
  - `it('moves any state ≥ recorded → hidden and back to previous state')`
  - `it('moves any state ≥ recorded → archived / trashed')`
  - `it('moves trashed → deleted and refuses deleted → anything')`
  - `it('refuses hidden → shared without going back to validated')`
  - `it('records validated_at and validated_via on validation')`
  - `it('records previous_state when hiding')`
- `tests/Unit/Models/StoryTest.php` : `it('is never visible to family unless shared or in_book')` (méthode `isVisibleToFamily()`), ~~`it('exposes the current recording')`~~ → reporté au bloc 04, qui crée la table `recordings` (voir note de checkpoint).
- `tests/Unit/Actions/ProposeStoryTest.php` : incrémente `sequence`, état `proposed`, `proposed_at` posé, refuse si le projet est `paused` ou `frozen_bereavement`.
- `tests/Unit/Actions/RecordConsentTest.php` : crée une ligne `granted` avec `text_version` courante ; `RevokeConsent` crée une ligne `revoked` et ne modifie pas la ligne d'origine ; `Narrator::hasConsent(kind)` vrai puis faux.
- `tests/Unit/Models/ProjectTest.php` : `it('has exactly one primary narrator')` (contrainte partielle testée par exception), `it('computes collection and finalization windows for pilot and core offers')`.
- `tests/Feature/Policies/ProjectPolicyTest.php` : propriétaire et éditeur peuvent voir, un autre initiateur ne peut pas, un `support_readonly` peut voir sans modifier.
- `tests/Feature/Database/ConstraintsTest.php` : `check` sur `stories.visibility`, unicité `project_members`, `narrators` exige email ou téléphone.
- `tests/Feature/Seeders/DemoProjectSeederTest.php` : le seeder produit cinq histoires dans cinq états distincts.

## 6. Étapes

### 6.1 Enums
- [x] `App\Enums\UserRole`, `ProjectStatus`, `Offer`, `AddressForm`, `Cadence`, `PromptSlot`, `Channel`, `ShareDecision`, `ValidatedVia`, `StoryVisibility`, `AnswerType`, `ConsentKind`, `ConsentStatus`, `ConsentChannel`, `QuestionTheme`, `ProjectMemberRole`. Tous `string` backed, avec méthode `label()` qui retourne une clé de traduction.

### 6.2 Migrations (dans cet ordre)
- [x] `add_role_to_users_table` (si non fait au bloc 01).
- [x] `create_cohorts_table` (créée ici pour la clé étrangère de `projects`, remplie au bloc 17).
- [x] `create_projects_table`, `create_project_members_table`, `create_narrators_table` (index unique partiel : `DB::statement('create unique index narrators_one_primary on narrators (project_id) where is_primary')` ; contrainte `check (email is not null or phone_e164 is not null)`), `create_family_members_table`, `create_questions_table`, `create_stories_table` (contraintes `check` sur `visibility`, `share_decision`, `validated_via`, `answer_type`), `create_consent_texts_table`, `create_consents_table`.
- [x] Migrations spatie/permission et Pennant publiées.
- [x] `sail artisan migrate:fresh` sans erreur ; `ConstraintsTest` vert.

### 6.3 Modèles
- [x] `Project`, `ProjectMember`, `Narrator`, `FamilyMember`, `Question`, `Story`, `ConsentText`, `Consent`, `Cohort`. `HasUuids` sur tous sauf `ProjectMember`. Casts vers les enums. Relations : `Project hasMany narrators/familyMembers/stories/members/consents`, `Project primaryNarrator()`, `Story belongsTo project/narrator/question`, `Narrator hasMany stories/consents`.
- [x] `Project::collectionWindow()` : pilote = 12 semaines ; cœur = 12 mois + 3 mois de finalisation (`config('product.offer')`).
- [x] `Story::isVisibleToFamily()` : vrai uniquement si `state ∈ {shared, in_book}` et `visibility ≠ book_only` pour l'écoute en ligne.
- [x] `Narrator::hasConsent(ConsentKind $kind): bool` : dernière ligne pour ce `kind` est `granted`.

### 6.4 Machine d'états
- [x] `App\States\Story\StoryState extends State` avec `config()` déclarant les transitions :
  - `Proposed → Recorded` : `RecordStory`
  - `Recorded → Transcribed` : `MarkTranscribed`
  - `Transcribed → ToReview` : `RequestReview`
  - `ToReview → Validated` : `ValidateStory` (variante B)
  - `Transcribed → Validated` : `ValidateStory` avec garde `share_decision === share` (variante A)
  - `Recorded → Validated` : `ValidateStory` avec garde `validated_via === phone_operator` et existence d'un consentement `phone_call_recording` + accord oral journalisé dans `action payload`
  - `Validated → Shared` : `ShareStory` (garde `visibility ≠ book_only`)
  - `Shared → InBook`, `Validated → InBook` : `IncludeInBook`
  - `* ≥ Recorded → Hidden` : `HideStory` (stocke `previous_state`) ; `Hidden → previous_state` : `UnhideStory`
  - `* ≥ Recorded → Archived` : `ArchiveStory`
  - `* ≥ Recorded → Trashed` : `TrashStory` (pose `trashed_at`) ; `Trashed → previous_state` : `RestoreStory` (garde ≤ 30 jours)
  - `Trashed → Deleted` : `DeleteStory` (pose `deleted_at`, `deletion_requested_by`)
  - Aucune transition depuis `Deleted`.
- [x] Chaque classe de transition pose les horodatages correspondants et lève `App\Exceptions\Domain\ForbiddenTransition` si la garde échoue.
- [x] `StoryStateMachineTest` entièrement vert.

### 6.5 Rôles et permissions
- [x] Seeder `RolesAndPermissionsSeeder` : rôles `admin`, `support`, `support_readonly` ; permissions `admin.access`, `support.write`, `support.read`, `brand.manage`, `refunds.issue`, `tokens.reissue`, `transcripts.edit`, `audit.read`. `admin` a tout ; `support` a tout sauf `brand.manage` et `refunds.issue` ; `support_readonly` a `admin.access`, `support.read`, `audit.read`.
- [x] `User::isStaff()`. `canAccessPanel()` du bloc 01 remplacé par `hasPermissionTo('admin.access')`.
- [x] Policies `ProjectPolicy` (`view`, `update`, `manageMembers`) et `StoryPolicy` (`view`, `editText`, `manageVisibility`) pour les utilisateurs authentifiés ; tests verts.

### 6.6 Actions et consentements
- [x] `CreateProject(User $owner, Offer $offer, array $attributes): Project` — crée le projet en `draft` et la ligne `project_members(initiator)`.
- [x] `AddNarrator(Project, array): Narrator` — premier narrateur = `is_primary`.
- [x] `AddFamilyMember(Project, User $invitedBy, array): FamilyMember`.
- [x] `ProposeStory(Project, ?Question, ?string $customText): Story`.
- [x] `RecordConsent(Model $subject, Project, ConsentKind, ConsentChannel, ?User $recordedBy, array $context): Consent` — lit `ConsentText::current($kind)` ; `RevokeConsent(...)`.
- [x] Seeder `ConsentTextSeeder` : version `1.0` de chaque `ConsentKind` avec un texte provisoire marqué `[À VALIDER PAR CONSEIL]` (le texte réel arrive au bloc 10).
- [x] Tests d'actions verts.

### 6.7 Factories et seeder de démonstration
- [x] Factories pour tous les modèles ; `StoryFactory` avec états `proposed()`, `recorded()`, `transcribed()`, `toReview()`, `validated()`, `shared()`, `inBook()`, `hidden()`, `trashed()`, `deleted()` qui posent les horodatages cohérents.
- [x] `DemoProjectSeeder` (`local`, `testing`) : un utilisateur initiateur `demo@example.test`, un projet `active`, un narrateur principal avec téléphone factice `+33600000000`, trois proches, cinq histoires (`proposed`, `recorded`, `to_review`, `shared`, `hidden`).
- [x] `DatabaseSeeder` appelle `RolesAndPermissionsSeeder`, `ConsentTextSeeder`, `AdminUserSeeder`, `DemoProjectSeeder` (les deux derniers hors production).

### 6.8 Clôture
- [x] Annexe B relue et alignée sur les migrations réelles (colonnes, index).
- [x] `sail composer check`, `sail npm run check`, CI verts.
- [x] `04_VERSIONS.md` mis à jour.
- [x] Commit `chore(bloc-02): terminé`, tag `bloc-02-done`.

## 7. Checkpoint démontrable

1. `sail artisan migrate:fresh --seed` sans erreur.
2. `sail artisan tinker` :
   ```php
   $s = App\Models\Story::factory()->transcribed()->create();
   $s->state->transitionTo(App\States\Story\Validated::class); // lève ForbiddenTransition (pas de share_decision)
   $s->update(['share_decision' => 'share', 'share_decided_at' => now()]);
   $s->state->transitionTo(App\States\Story\Validated::class); // passe
   $s->refresh()->validated_via; // 'recording_end'
   ```
3. `sail composer test --filter=StoryStateMachine` : toutes les cellules de la matrice passent.

## 8. Critères de sortie

- [x] Aucune transition n'existe en dehors de `StoryState::config()` (revue de code : aucun `update(['state' => …])` direct ; un test `grep` dans `tests/Unit/States/NoDirectStateWriteTest.php` cherche `'state' =>` hors des transitions et échoue s'il en trouve).
- [x] `isVisibleToFamily()` est la seule source de vérité de visibilité ; documenté dans le glossaire.

## 9. Règle de décision par défaut

Si spatie/laravel-model-states ne permet pas une garde conditionnelle sur la même paire d'états (`Transcribed → Validated` avec deux gardes), implémenter une seule classe `ValidateStory` qui inspecte le contexte (`validated_via`) et lève `ForbiddenTransition` selon le cas. Ne pas contourner par une écriture directe de `state`.

## 10. Note de checkpoint

**2026-09-02 — exécuté — résultat : conforme.** Le checkpoint §7 a été joué tel qu'il est écrit, dans `sail artisan tinker` :

```
état de départ : transcribed
refusé comme prévu : Transition [transcribed -> validated] is refused: the narrator has not decided to share yet.
état après validation : validated
validated_via : recording_end
visible des proches : false
```

`migrate:fresh --seed` passe, le retour en arrière des neuf migrations et leur rejeu passent aussi. Porte verte : Pint sans correction en attente, PHPStan niveau 8 sans erreur, **204 tests Pest** (890 assertions), 16 tests Vitest, 3 tests Playwright. `composer outdated --direct` est vide.

La matrice R-4 est couverte cellule par cellule : 29 tests dans `tests/Unit/States/StoryStateMachineTest.php`.

**Écarts par rapport au plan :**

- `users.id` reste un identifiant séquentiel, contre l'annexe B qui prévoyait un uuid (décision T-41). Tous les modèles créés ici et exposés à l'extérieur sont bien en uuid ordonné. Conséquence : `consents.subject_id` est un `varchar(64)`, puisque le sujet peut être un narrateur (uuid) ou un utilisateur (entier).
- `consent_texts` garde une clé séquentielle, comme l'annonce l'annexe B, là où §6.3 demandait `HasUuids` sur tous les modèles sauf `ProjectMember`. La table est purement interne, jamais référencée par une clé étrangère — `consents.text_version` pointe la version, pas l'identifiant.
- Trois énumérations non listées en §6.1 ont été ajoutées, parce que l'annexe B pose une contrainte `check` sur les colonnes correspondantes : `ValidationVariant`, `DeletionRequestedBy`, `CohortPhase` (T-46).
- `it('exposes the current recording')` est reporté au bloc 04 : la table `recordings` y est créée, et le test n'a pas d'objet avant. Tous les autres tests de §5 existent, plus une trentaine d'autres.
- Aucune transition ne sort de `Archived` : le bloc 02 §6.4 n'en prévoit pas et l'interface des retraits est le sujet du bloc 07. `ArchiveStory` mémorise déjà `previous_state`, donc l'ouvrir ne demandera aucune migration (T-45).
- La règle de décision par défaut §9 a été appliquée : une seule classe `ValidateStory` qui inspecte le contexte. Corollaire non prévu : `UnhideStory` et `RestoreStory` héritent de `DefaultTransition` pour connaître l'état visé et le comparer à `previous_state` (T-43).
- Le point resté ouvert au bloc 01 est fermé : l'accès au panneau et à la page Marque se décide sur les permissions `admin.access` et `brand.manage`, plus sur un nom de rôle.

**Défauts trouvés et corrigés en chemin :**

- **Décalage de deux heures sur toutes les dates enregistrées.** Eloquent sérialise les dates sans décalage et Postgres les interprétait en UTC, alors que l'application vit à Paris. Tout `now()` était stocké deux heures trop tôt — de quoi fausser chaque fenêtre du moteur de complétion avant même de l'écrire. Le fuseau de la session Postgres est désormais aligné sur `APP_TIMEZONE` (T-44), avec un test de régression.
- **Le compte d'administration semé restait à la porte du panneau.** `DatabaseSeeder` coupait les événements de modèle, comme le fait le squelette Laravel ; or c'est un événement `saved` qui traduit `users.role` en rôle de back-office. Le compte avait le bon rôle en colonne, le bon mot de passe, et aucune permission : il se connectait puis recevait un 403. Deux tests couvrent la régression.
- **Le cache de spatie/laravel-permission survivait à `migrate:fresh`** et faisait échouer l'association des permissions tout juste créées. Le seeder le vide avant et après.
- **`is_primary` était assignable en masse** sur `Narrator` : la règle « le premier narrateur est le principal » aurait pu être contournée par un tableau d'attributs. La colonne est sortie de `$fillable` ; la règle vit dans `AddNarrator`.
- **Les instances fraîchement créées mentaient sur leurs valeurs par défaut.** Un proche sortant de `AddFamilyMember` avait `can_contribute` à `null` en mémoire et `false` en base. Les défauts des migrations sont désormais repris dans `$attributes` côté modèle.

**Ce que le bloc laisse ouvert :**

- Le narrateur et les proches n'ont encore aucun chemin d'accès : tout passe par les jetons du bloc 03. Les policies de ce bloc ne concernent que les utilisateurs authentifiés.
- `StoryPolicy::manageVisibility()` refuse la visibilité à l'Initiateur·rice et ne l'accorde qu'au personnel muni de `support.write` (T-42). À revalider quand le mandat du bloc 07 sera spécifié.
- L'accord oral de l'opérateur téléphone est prouvé par un consentement `phone_call_recording` recueilli par téléphone et attribué à un opérateur nommé — en base comme dans la garde de `ValidateStory`. Sa journalisation dans un `action payload` attend le journal d'audit du bloc 11.
- Les drapeaux Pennant eux-mêmes arrivent avec les blocs qui les utilisent (convention §15) : seul le magasin est installé et éprouvé.
- La double authentification obligatoire du back-office reste planifiée au bloc 11.
