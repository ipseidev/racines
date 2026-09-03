# Bloc 11 — Back-office support et journal d'audit

Statut : ◐ en cours · Dépend de : 10 · Tag de fin : `bloc-11-done`
**⛔ En attente de toi** — le point 5 du checkpoint §7 demande un compte Stripe en mode test ; les points 1 à 4 sont jouables en local dès maintenant. Détail dans [`05_A_FAIRE_HUMAIN.md`](../05_A_FAIRE_HUMAIN.md).

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
- [x] Migration `create_audit_logs_table` (annexe B) + fonction et trigger `audit_logs_append_only` + index `(project_id, occurred_at)`, `(actor_user_id, occurred_at)`.
- [x] `App\Audit\AuditLog::record(string $action, ?Model $subject, array $payload = [], ?Project $project = null): void` : détermine l'acteur (`auth()->user()`, contexte `filament|web|cli|system|phone_operator` via `App\Audit\ActorContext`), masque le payload (`App\Audit\Redactor` : emails, E.164, jetons, adresses), calcule `previous_hash` (dernière ligne, verrou `SELECT … FOR UPDATE` sur une ligne de séquence dédiée `audit_chain_head`) et `hash`.
- [x] Commande `audit:verify {--from=} {--to=}` : recalcule la chaîne, retourne 0 ou liste les ruptures ; planifiée `daily()`. L'alerte Flare attend le bloc 16 : en attendant, la rupture part en `Log::critical`, ce qui est ce que Flare lira de toute façon.
- [x] Brancher les appels différés des blocs précédents (`UpdateBrandSettings`, `PurgeDeletedStory`, retraits, mandats, remboursements).

### 6.2 MFA et accès
- [x] Activer la MFA obligatoire dans `AdminPanelProvider` (application TOTP + codes de récupération). Test `MfaTest`.
- [ ] `access:review` et son archivage trimestriel — **reporté au bloc 16**, avec le reste de l'exploitation. `canAccessPanel()` sur la permission est en place depuis le bloc 01, et `UserResource` montre déjà le rôle et la présence d'un second facteur, ce dont la première revue aura besoin.

### 6.3 Ressources Filament
- [x] `ProjectResource` : liste et fiche, avec les quatre gestes du rythme (`Pause`, `Reprendre`, `Replanifier`, `Geler (décès)`). Les **onglets** de la fiche — envois, moteur, commandes, journal — ne sont pas construits : chacune de ces vues existe déjà comme ressource filtrable, et un onglet qui recopie une liste est une seconde requête à maintenir. À reprendre si l'usage réel montre que le passage par la liste coûte du temps au support.
- [x] `StoryResource` : fiche (question, état, horodatages, enregistrement courant avec lecteur → `played Recording`, transcripts avec historique), actions : `Masquer` / `Archiver` / `Corbeille` (avec motif, uniquement à la demande du narrateur ou de l'Initiateur·rice, motif obligatoire), `Restaurer`, `Éditer le texte` (formulaire → `EditTranscript`, aperçu du diff), `Ré-émettre le lien d'enregistrement`. **Aucune action « Valider » ni « Partager ».**
- [x] `NarratorResource`, `FamilyMemberResource` (renvoyer/révoquer le lien d'écoute), `AccessTokenResource` (lecture, révocation), `OutboundMessageResource` (statuts, renvoi), `EngineEventResource` (lecture), `SupportTicketResource` (file de travail : ouverts d'abord, action `Clore` avec note), `OrderResource` (`Rembourser` total/partiel avec motif → Cashier `refund`), `UserResource` (rôles, réservé `admin`), `ConsentTextResource` (nouvelle version ; l'ancienne reste), `QuestionResource` (corpus), `CohortResource`, `PhoneOptionResource` (squelette ; opérations au bloc 17).
- [x] Trait `App\Filament\Concerns\LogsViews` sur toutes les pages `View*` et `Edit*` : `AuditLog::record('viewed …')` au `mount`. Un test parcourt `app/Filament` pour qu'aucune n'y échappe.
- [x] Toutes les actions Filament appellent une Action du domaine et journalisent via `AuditLog` ; aucune écriture Eloquent directe dans `app/Filament`.

### 6.4 Pages et tableau de bord
- [x] `ManagePilot` (SettingsPage sur `PilotSettings`) : mode, plafond téléphone, prix, `legal_validated_at`.
- [x] `Playbooks` : rend `resources/playbooks/*.md`.
- [x] `EngineReport` : le tableau de `engine:report` avec filtres période/cohorte.
- [x] Widget `PilotOverview`, cinq nombres côte à côte : projets actifs, histoires partagées (30 j), envois échoués (24 h), tickets ouverts, option téléphone. **Un widget et non cinq** : cinq classes pour cinq `count()` n'auraient apporté qu'une navigation de plus.

### 6.5 Playbooks v1 (`resources/playbooks/`)
Chaque playbook : « Quand », « Ce qu'on fait », « Ce qu'on ne fait jamais », « Modèle de message », « Qui décide ».
- [x] `deces.md` : gel immédiat de tous les envois (`Geler (décès)`), aucune bascule mémorielle sans demande écrite de la famille, lecture des directives post-mortem, référent désigné prioritaire, finalisation du livre seulement si autorisée, ton sobre, jamais de relance commerciale.
- [x] `conflit-familial.md` : neutralité, application mécanique des règles (le veto du narrateur prime), aucun arbitrage éditorial, escalade à l'admin, modèle de réponse identique à toutes les parties.
- [x] `regret-confidence.md` : masquer d'abord, expliquer corbeille et suppression, rappeler ce qui est imprimé, ne jamais minimiser.
- [x] `refus-cadeau.md` : message à l'Initiateur·rice avec tact, remboursement proposé, aucune tentative de convaincre le narrateur.
- [x] `micro-et-technique.md` : aide par OS, réponse écrite, ré-émission de lien, quand proposer l'option téléphone, quand créer un ticket.
- [x] `option-telephone.md` : renvoi vers le bloc 17.

### 6.6 Clôture
- [x] Annexe B (`audit_logs`), `02_GLOSSAIRE_TECH.md` (actions d'audit normalisées : `viewed`, `played`, `edited`, `reissued`, `refunded`, `paused`, `frozen`, `closed_ticket`…).
- [x] `sail composer check`, `sail npm run check`, Playwright depuis le Mac en série (T-110, T-111), CI verts.
- [ ] Commit `chore(bloc-11): terminé`, tag `bloc-11-done` — après le checkpoint §7, dont le point 5 demande un compte Stripe.

## 7. Checkpoint démontrable

1. Connexion au panneau : configuration TOTP forcée au premier accès ; connexion suivante avec code.
2. Ouvrir la fiche d'une histoire partagée, écouter 5 secondes, corriger un mot du texte : trois entrées d'audit (`viewed Story`, `played Recording`, `edited Transcript` avec diff).
3. `psql` : `UPDATE audit_logs SET action='x' WHERE id=1` → erreur du trigger. `sail artisan audit:verify` → « chaîne intacte ».
4. Avec un compte `support_readonly` : aucun bouton d'action, 403 sur une tentative directe.
5. Rembourser partiellement une commande de test Stripe : statut, montant, motif dans l'audit, événement Stripe reçu.

## 8. Critères de sortie

- [x] `grep -rn "->update(\|->save(\|::create(" app/Filament` ne retourne que des appels à des Actions du domaine (revue).
- [x] Toute page `View*`/`Edit*` de Filament utilise `LogsViews`.
- [x] Aucune action de validation ou de partage d'histoire n'existe pour le staff.

## 9. Règle de décision par défaut

Si une action support pourrait être interprétée comme une décision éditoriale (choisir une version du texte, retirer une histoire sans demande du narrateur), elle n'est pas implémentée : le support ré-émet un lien au narrateur pour qu'il décide lui-même.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_

**2026-09-03 — code livré, checkpoint non joué.** Le journal d'audit tient sur trois mécanismes dont aucun ne suffit seul : un trigger `BEFORE UPDATE OR DELETE`, une chaîne d'empreintes, et une racine littérale qui rend un journal tronqué distinguable d'un journal neuf. `audit:verify` distingue les trois ruptures possibles et tourne chaque nuit. La double authentification est obligatoire, avec une application TOTP et des codes de récupération — jamais de SMS, un second facteur qui passe par le réseau téléphonique n'en est pas un.

Quinze ressources, trois pages, un widget de cinq nombres, six playbooks. Porte qualité verte : **1 035 tests Pest / 5 314 assertions**, PHPStan niveau 8 sans erreur, Pint et `tsc` propres, **101 Vitest**, **63 Playwright**.

Les quatre premiers points du checkpoint sont jouables en local dès maintenant, et le bout en bout `admin-audit-trail` en couvre l'essentiel : la MFA se franchit, la fiche d'une histoire s'ouvre sans offrir de partage, les playbooks se lisent. Le point 5 — rembourser une commande de test — demande un compte Stripe.

**Deux reports assumés, nommés dans §6 :** `access:review` part au bloc 16 avec le reste de l'exploitation, et les onglets de la fiche projet ne sont pas construits — chaque vue existe déjà comme ressource filtrable, et un onglet qui recopie une liste est une seconde requête à maintenir.

**Écarts consignés : T-112 à T-117.** Le plus instructif est **T-113** : le contexte de l'acteur se déduisait de `runningInConsole()`, qui rend vrai sous PHPUnit même pendant qu'une requête de test est traitée. Toute lecture depuis le panneau s'inscrivait comme venant de la console — et c'est le test qui **ouvre réellement la page**, plutôt que d'appeler la fonction, qui l'a montré. La leçon vaut au-delà de ce bloc : un test qui rappelle la fonction ne prouve pas que la page l'appelle.
