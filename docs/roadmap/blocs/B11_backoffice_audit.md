# Bloc 11 — Back-office support et journal d'audit

Statut : ☐ non commencé · Dépend de : 10 · Tag de fin : `bloc-11-done`
Références dossier : PRD P0-17, doc 04 §12 (MFA, rôles minimaux, journalisation inviolable lecture comprise, revue d'accès), §3 (conflits familiaux : neutralité), §10 (playbooks émotionnels) ; décision T-05, T-18.

## 1. Objectif

Le support peut ré-émettre un lien, replanifier, corriger une transcription, rembourser, gérer l'option téléphone, lire les playbooks, sans jamais outrepasser le narrateur. Chaque action et chaque lecture de donnée sensible sont inscrites dans un journal infalsifiable. MFA obligatoire.

## 2. Pourquoi

Le pilote sera opéré à la main pour 30 à 50 familles ; le back-office est l'outil de travail quotidien. Le dossier exige la journalisation des lectures : un support qui écoute une histoire privée doit laisser une trace.

## 3. Livrables

- Table `audit_logs` avec trigger append-only et chaîne de hachage ; `AuditLog::record()` ; commande `audit:verify`.
- Filament : MFA obligatoire ; ressources `Project`, `Story`, `Narrator`, `FamilyMember`, `Order`, `AccessToken`, `OutboundMessage`, `EngineEvent`, `SupportTicket`, `PhoneOption`, `User`, `ConsentText`, `Question`, `Cohort`, `Transcript` (édition) ; pages `ManagePilot`, `Playbooks`, `EngineReport` ; tableau de bord.
- Journalisation automatique des vues (`viewed`) et de toutes les actions Filament.
- Playbooks v1 dans `resources/playbooks/`.

## 4. Packages

Aucun nouveau (Filament installé au bloc 01). Vérifier la disponibilité native de la MFA dans la version de Filament installée ; sinon utiliser le plugin officiel recommandé par la documentation Filament et le noter dans `03_DECISIONS.md`.

## 5. Tests à écrire d'abord

- `tests/Feature/Audit/AuditLogTest.php`
  - `it('appends a row with hash chained to the previous row')`
  - `it('refuses update and delete at database level')`
  - `it('masks emails, phones and tokens in payload')`
  - `it('records actor role and context')`
- `tests/Feature/Console/AuditVerifyTest.php` : chaîne intacte → 0 ; ligne altérée (via `DB::statement` avec le trigger désactivé dans le test) → détectée avec son id.
- `tests/Feature/Admin/MfaTest.php` : un utilisateur staff sans MFA configurée est redirigé vers la configuration avant toute page ; un `initiator` ne peut pas accéder au panneau.
- `tests/Feature/Admin/PermissionsTest.php` : matrice rôle × action (`support_readonly` ne voit aucun bouton d'action et reçoit 403 sur les POST ; `support` ne peut pas rembourser ni éditer la marque ; `admin` peut tout).
- `tests/Feature/Admin/ViewLoggingTest.php` : ouvrir la fiche d'une histoire écrit `viewed Story` ; lire un transcript écrit `viewed Transcript` ; écouter un audio (URL temporaire générée) écrit `played Recording`.
- `tests/Feature/Admin/SupportActionsTest.php` : `reissue record token` (ancien révoqué, nouveau envoyé sur le canal choisi, audit) ; `resend on other channel` ; `reschedule next prompt` ; `edit transcript` (via `EditTranscript`, audit avec diff) ; `refund` (Cashier, motif obligatoire, audit) ; `pause project` ; `freeze bereavement` (gel immédiat de tous les envois, doc 04 §6) ; `close ticket`.
- `tests/Feature/Admin/StoryStateActionsTest.php` : les actions d'état disponibles dans Filament sont exactement celles autorisées par la machine d'états ; aucune action « valider » n'existe pour le support (la validation reste au narrateur ou au mandataire ; l'opérateur téléphone passe par la ressource `PhoneOption`, bloc 17).
- `tests/Feature/Admin/PlaybooksTest.php` : les cinq playbooks se rendent ; aucun mot interdit.
- `tests/e2e/admin-reissue-link.spec.ts` : connexion + MFA (TOTP calculé dans le test), ré-émission d'un lien, vérification de l'entrée d'audit affichée.

## 6. Étapes

### 6.1 Journal d'audit
- [ ] Migration `create_audit_logs_table` (annexe B) + fonction et trigger `audit_logs_append_only` + index `(project_id, occurred_at)`, `(actor_user_id, occurred_at)`.
- [ ] `App\Audit\AuditLog::record(string $action, ?Model $subject, array $payload = [], ?Project $project = null): void` : détermine l'acteur (`auth()->user()`, contexte `filament|web|cli|system|phone_operator` via `App\Audit\ActorContext`), masque le payload (`App\Audit\Redactor` : emails, E.164, jetons, adresses), calcule `previous_hash` (dernière ligne, verrou `SELECT … FOR UPDATE` sur une ligne de séquence dédiée `audit_chain_head`) et `hash`.
- [ ] Commande `audit:verify {--from=} {--to=}` : recalcule la chaîne, retourne 0 ou liste les ruptures ; planifiée `daily()` avec alerte Flare en cas de rupture (bloc 16).
- [ ] Brancher les appels différés des blocs précédents (`UpdateBrandSettings`, `PurgeDeletedStory`, retraits, mandats, remboursements).

### 6.2 MFA et accès
- [ ] Activer la MFA obligatoire dans `AdminPanelProvider` (application TOTP + codes de récupération). Test `MfaTest`.
- [ ] `canAccessPanel()` = `hasPermissionTo('admin.access')`. Revue d'accès trimestrielle : commande `access:review` qui liste les utilisateurs staff, leur dernière connexion et leurs permissions, à exécuter et archiver dans `docs/runbooks/acces-YYYY-QN.md`.

### 6.3 Ressources Filament
- [ ] `ProjectResource` : liste (statut, narrateur, Initiateur·rice, cohorte, prochaine relance, histoires par état), fiche avec onglets : Frise (histoires et états), Narrateur (canaux, consentements, jetons), Proches, Envois (`outbound_messages`), Moteur (`engine_events`), Commandes, Option téléphone, Journal. Actions : `Pause`, `Reprendre`, `Geler (décès)`, `Replanifier le prochain envoi`, `Changer cadence/jour/créneau`, `Renvoyer le lien courant sur l'autre canal`.
- [ ] `StoryResource` : fiche (question, état, horodatages, enregistrement courant avec lecteur → `played Recording`, transcripts avec historique), actions : `Masquer` / `Archiver` / `Corbeille` (avec motif, uniquement à la demande du narrateur ou de l'Initiateur·rice, motif obligatoire), `Restaurer`, `Éditer le texte` (formulaire → `EditTranscript`, aperçu du diff), `Ré-émettre le lien d'enregistrement`. **Aucune action « Valider » ni « Partager ».**
- [ ] `NarratorResource`, `FamilyMemberResource` (renvoyer/révoquer le lien d'écoute), `AccessTokenResource` (lecture, révocation), `OutboundMessageResource` (statuts, renvoi), `EngineEventResource` (lecture), `SupportTicketResource` (file de travail : ouverts d'abord, action `Clore` avec note), `OrderResource` (`Rembourser` total/partiel avec motif → Cashier `refund`), `UserResource` (rôles, réservé `admin`), `ConsentTextResource` (nouvelle version ; l'ancienne reste), `QuestionResource` (corpus), `CohortResource`, `PhoneOptionResource` (squelette ; opérations au bloc 17).
- [ ] Trait `App\Filament\Concerns\LogsViews` sur toutes les pages `View*` et `Edit*` : `AuditLog::record('viewed …')` au `mount`.
- [ ] Toutes les actions Filament appellent une Action du domaine et journalisent via `AuditLog` ; aucune écriture Eloquent directe dans `app/Filament`.

### 6.4 Pages et tableau de bord
- [ ] `ManagePilot` (SettingsPage sur `PilotSettings`) : mode, plafond téléphone, prix, `legal_validated_at`.
- [ ] `Playbooks` : rend `resources/playbooks/*.md`.
- [ ] `EngineReport` : le tableau de `engine:report` avec filtres période/cohorte.
- [ ] Widgets : projets par statut ; histoires par état (30 jours) ; envois échoués (24 h) ; tickets ouverts ; option téléphone (utilisé/plafond).

### 6.5 Playbooks v1 (`resources/playbooks/`)
Chaque playbook : « Quand », « Ce qu'on fait », « Ce qu'on ne fait jamais », « Modèle de message », « Qui décide ».
- [ ] `deces.md` : gel immédiat de tous les envois (`Geler (décès)`), aucune bascule mémorielle sans demande écrite de la famille, lecture des directives post-mortem, référent désigné prioritaire, finalisation du livre seulement si autorisée, ton sobre, jamais de relance commerciale.
- [ ] `conflit-familial.md` : neutralité, application mécanique des règles (le veto du narrateur prime), aucun arbitrage éditorial, escalade à l'admin, modèle de réponse identique à toutes les parties.
- [ ] `regret-confidence.md` : masquer d'abord, expliquer corbeille et suppression, rappeler ce qui est imprimé, ne jamais minimiser.
- [ ] `refus-cadeau.md` : message à l'Initiateur·rice avec tact, remboursement proposé, aucune tentative de convaincre le narrateur.
- [ ] `micro-et-technique.md` : aide par OS, réponse écrite, ré-émission de lien, quand proposer l'option téléphone, quand créer un ticket.
- [ ] `option-telephone.md` : renvoi vers le bloc 17.

### 6.6 Clôture
- [ ] Annexe B (`audit_logs`), `02_GLOSSAIRE_TECH.md` (actions d'audit normalisées : `viewed`, `played`, `edited`, `reissued`, `refunded`, `paused`, `frozen`, `closed_ticket`…).
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts.
- [ ] Commit `chore(bloc-11): terminé`, tag `bloc-11-done`.

## 7. Checkpoint démontrable

1. Connexion au panneau : configuration TOTP forcée au premier accès ; connexion suivante avec code.
2. Ouvrir la fiche d'une histoire partagée, écouter 5 secondes, corriger un mot du texte : trois entrées d'audit (`viewed Story`, `played Recording`, `edited Transcript` avec diff).
3. `psql` : `UPDATE audit_logs SET action='x' WHERE id=1` → erreur du trigger. `sail artisan audit:verify` → « chaîne intacte ».
4. Avec un compte `support_readonly` : aucun bouton d'action, 403 sur une tentative directe.
5. Rembourser partiellement une commande de test Stripe : statut, montant, motif dans l'audit, événement Stripe reçu.

## 8. Critères de sortie

- [ ] `grep -rn "->update(\|->save(\|::create(" app/Filament` ne retourne que des appels à des Actions du domaine (revue).
- [ ] Toute page `View*`/`Edit*` de Filament utilise `LogsViews`.
- [ ] Aucune action de validation ou de partage d'histoire n'existe pour le staff.

## 9. Règle de décision par défaut

Si une action support pourrait être interprétée comme une décision éditoriale (choisir une version du texte, retirer une histoire sans demande du narrateur), elle n'est pas implémentée : le support ré-émet un lien au narrateur pour qu'il décide lui-même.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
