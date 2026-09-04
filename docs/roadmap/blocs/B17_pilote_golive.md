# Bloc 17 — Pilote : offre, option téléphone D-9, playbooks, go-live

Statut : ☐ non commencé · Dépend de : 16 · Tag de fin : `bloc-17-done`
Références dossier : R-2 (Pilote Fondateurs), R-5 (H0-H3), R-8 et R-8b (gates), R-12 D-9, doc 03 §8.7, doc 04 §2 (appels), §2bis, §9, §10 ; doc 01 §11 (décisions du comité).

## 1. Objectif

Tout ce qui est nécessaire pour accueillir 30 à 50 familles payantes pendant 12 semaines est en place : l'offre pilote contractualisée, l'option téléphone opérée humainement avec son script, sa journalisation et son plafond, la fin de pilote avec le choix de la famille, les rapports de gate, et une liste de mise en production entièrement cochée après une répétition avec trois familles internes.

## 2. Pourquoi

Le pilote est la première mesure réelle de H0, H1, H2 et de la demande téléphone. Il traite déjà des données sensibles : le socle juridique et opérationnel n'est pas négociable.

## 3. Livrables

- `PilotSettings` finalisés, cohorte 0B créée, `cohorts:report` (J70, ITT, H0, H2, contre-métriques, coût support).
- Option téléphone : `PhoneOptionResource` complet, action « Enregistrer un appel », script d'appel, comptage par entrée, `phone-option:report` avec les seuils D-9.
- Fin de pilote : notification à `collection_ends_at`, trois choix (continuer, exporter, supprimer), génération du « premier chapitre ».
- Colonne `support_tickets.minutes_spent` et coût support par projet.
- `docs/runbooks/go-live.md` (liste de contrôle), `docs/runbooks/repetition.md` (protocole des trois familles internes), `resources/playbooks/option-telephone.md` (script exact).

## 4. Packages

Aucun.

## 5. Tests à écrire d'abord

- `tests/Feature/Pilot/PhoneOptionCapTest.php` : le 11e achat de l'option est refusé côté serveur avec le message « Les 10 places sont prises » ; le flag `phone-option-offer` se désactive au 10e ; une annulation libère une place ; les entrées `checkout` et `rescue` partagent le même plafond.
- `tests/Feature/Pilot/PhoneOperatorRecordingTest.php`
  - `it('requires the consent phone_call_recording granted by phone with the operator id before accepting an upload')`
  - `it('creates a recording with source phone_operator on the story of the week and runs the normal pipeline')`
  - `it('validates via phone_operator only when the operator records the narrator oral decision as share, otherwise leaves the story recorded or hidden as stated')`
  - `it('writes an audit row with actor_context phone_operator for every step')`
  - `it('refuses an upload by support_readonly')`
- `tests/Feature/Pilot/PhoneOptionReportTest.php` : taux d'attache par cohorte et par entrée, comparaison aux seuils D-9 (≥ 20 %, 10-20 %, < 10 %), recommandation textuelle correspondante.
- `tests/Feature/Pilot/CohortReportTest.php` : H0 (acceptations ≤ 14 j / invitations délivrées), H1 ITT (accepteurs avec ≥ 8 histoires validées à J70), H1 activés, réussite du premier enregistrement non assisté, H2 par bras de notification, remboursements ≤ 8 %, support ≤ 10 €/projet (`minutes_spent × taux horaire de config`), demande 2e narrateur (`client_events` `two_narrators_interest`), critères R-8b.
- `tests/Feature/Pilot/PilotEndTest.php` : à `collection_ends_at`, notification aux deux parties avec trois choix ; `continue` → ticket `upgrade_request` (opéré manuellement au pilote) ; `export` → exports `full` + `offline_pack` ; `delete` → demande d'effacement ; sans réponse à J+30 → export proactif puis projet `dormant` ; génération d'un `Book(format founding_chapter)` si ≥ 3 histoires validées.
- `tests/Feature/Pilot/GoLiveChecklistTest.php` : la commande `golive:check` échoue tant qu'un prérequis machine manque (`legal_validated_at`, clés Stripe live présentes, domaine des liens en HTTPS, `restore:drill` daté < 90 j, `audit:verify` intact, Oh Dear configuré, plafond téléphone posé, cohorte courante, 3 projets de répétition marqués `rehearsal_completed_at`).
- `tests/e2e/phone-option-upload.spec.ts` : l'opérateur téléverse un audio pour un narrateur de démonstration, saisit la décision « partager », l'histoire devient partagée, l'audit contient trois lignes `phone_operator`.

## 6. Étapes

### 6.1 Offre pilote
- [ ] `PilotSettings` : `mode = pilot`, `pilot_weeks 12`, `pilot_target_stories_min 10`, `pilot_target_stories_max 15`, `support_hourly_cost_cents` (pour le coût support), `legal_validated_at`, `rehearsal_required_projects 3`.
- [ ] Récapitulatif contractuel affiché à l'étape 6 du tunnel et repris dans l'email de confirmation : 12 semaines, 89 €, 10 à 15 histoires visées, export complet, mini-livre « premier chapitre » imprimé, statut expérimental, remboursable, sort des données au choix en fin de pilote. Texte dans `lang/fr/public.php` `pilot.contract.*`, `[À VALIDER PAR CONSEIL]` tant que `legal_validated_at` est nul.
- [ ] Cohorte `0B-2026-11` créée (`CohortResource`), `PilotSettings::cohort_id` posé.

### 6.2 Option téléphone (D-9)
- [ ] `PhoneOptionResource` : liste (statut, entrée, narrateur, créneau, opérateur), fiche : planification du créneau hebdomadaire (jour, heure), historique des appels, action **« Enregistrer un appel »** : formulaire en trois temps : (1) case « J'ai lu le script d'ouverture et le narrateur a donné son accord oral à l'enregistrement » → `RecordConsent(phone_call_recording, channel phone, recorded_by opérateur)` si absent ; (2) fichier audio (MP3/M4A/WAV ≤ 200 Mo) → `Recording(source phone_operator)` sur l'histoire `proposed` de la semaine (ou création via `ProposeStory` si aucune) → pipeline normal du bloc 06 ; (3) « Décision du narrateur en fin d'appel » : `share` → `ValidateStoryAction(phone_operator)` puis `ShareStory` ; `keep_private` → reste `recorded` ; `decide_later` → `to_review`, relance normale ; `hide` → `HideStory`. Chaque temps écrit une ligne d'audit `actor_context = phone_operator`.
- [ ] `resources/playbooks/option-telephone.md` — script exact :
  > **Ouverture** : « Bonjour {Prénom}, c'est {Opérateur} de {Marque}, comme convenu {jour} à {heure}. Cet appel est enregistré pour garder votre histoire dans votre voix ; l'enregistrement servira au livre de {Initiateur} et vous pourrez le retirer à tout moment. Êtes-vous d'accord pour que j'enregistre ? » Attendre un oui explicite. Sinon : « Très bien, je n'enregistre pas ; voulez-vous qu'on se rappelle une autre fois ? » et fin.
  > **Question** : lire la question de la semaine, laisser parler, ne pas relancer sur le fond, ne poser que des questions de clarification neutres (« Vous pouvez m'en dire un peu plus ? »).
  > **Clôture** : « Merci. Souhaitez-vous que vos proches puissent écouter cette histoire, la garder pour vous, ou décider plus tard ? » Noter la réponse mot pour mot. Rappeler le prochain créneau. Ne jamais demander de code, de paiement ni de coordonnées.
- [ ] Comptage : `phone_options.entry` renseigné à la création (`checkout` par `FulfillOrder`, `rescue` par `OfferPhoneOption`) ; `phone-option:report` : attache par entrée et par cohorte, recommandation D-9.
- [ ] Coût : `support_tickets.minutes_spent` et `phone_options.minutes_spent` saisis par l'opérateur ; `CohortReport` calcule le coût support par projet.

### 6.3 Fin de pilote
- [ ] `pilot:end-of-collection` (`daily()`) : projets `pilot` dont `collection_ends_at` est atteint → notification `notifications.pilot.end` (Initiateur·rice et narrateur, ce dernier via son espace) avec trois choix (jetons `action`) ; `PilotEnd` action ; J+30 sans réponse → export proactif, `dormant`.
- [ ] Génération du « premier chapitre » : `Book(format founding_chapter)` avec toutes les histoires validées, BAT allégé (mêmes cases d'accord), impression manuelle.

### 6.4 Rapports de gate
- [ ] `cohorts:report {cohort}` : produit `docs/reports/cohorte-{nom}-{date}.md` avec les indicateurs de R-5 et R-8b, les dénominateurs explicites, la micro-expérience H2 par bras, l'option téléphone, le coût support, la demande 2e narrateur (seuil 35 %), et la liste des projets exclus du calcul et pourquoi.
- [ ] Widget Filament « Gate » reprenant les seuils kill/go avec un statut vert/orange/rouge par hypothèse.

### 6.5 Répétition et go-live
- [ ] `docs/runbooks/repetition.md` : trois familles internes (membres de l'équipe et leurs parents volontaires), une semaine, tous les parcours : achat test, invitation, acceptation, deux enregistrements dont un par téléphone, une relecture, une écoute, une réaction, une relance du moteur forcée, un masquage, une demande d'export, un remboursement partiel ; grille d'observation (blocages, temps, erreurs) ; correction avant go-live ; `projects.rehearsal_completed_at` posé.
- [ ] `docs/runbooks/go-live.md` (chaque ligne cochée par une personne nommée et datée) :
  - [ ] Socle juridique validé par le conseil : consentements, LIA, AIPD proportionnée, CGV, politique de confidentialité, contrat pilote, information sur l'enregistrement des appels → `PilotSettings::legal_validated_at`.
  - [ ] DPA signés ou acceptés avec chaque sous-traitant de `sous-traitants.md`.
  - [ ] Registre des traitements et DPO désignés.
  - [ ] Stripe en mode live, webhook live testé avec un paiement réel remboursé.
  - [ ] Twilio : expéditeur validé en France ; Resend : domaine vérifié ; test réel des deux canaux.
  - [ ] Domaine des liens en production, HTTPS, HSTS, annoncé dans les textes d'invitation.
  - [ ] `restore:drill` daté < 90 jours, `audit:verify` intact, sauvegardes vertes 7 jours d'affilée.
  - [ ] Oh Dear et Flare alertent le téléphone du fondateur ; page de statut publique.
  - [ ] Boîte support, engagement de réponse < 24 h ouvrées, playbooks relus.
  - [ ] Plafond téléphone = 10, script relu, opérateur désigné.
  - [ ] Cohorte 0B créée et sélectionnée.
  - [ ] Répétition terminée, écarts corrigés.
  - [ ] Tableaux de bord du pilote vérifiés sur les données de répétition.
  - [ ] `sail artisan golive:check` vert.
- [ ] Commande `golive:check` (vérifie les prérequis machine de la liste).

### 6.6 Clôture
- [ ] Annexe B (`phone_options.minutes_spent`, `support_tickets.minutes_spent`, `projects.rehearsal_completed_at`, `survey_answers` si absent).
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts ; commit `chore(bloc-17): terminé`, tag `bloc-17-done`.
- [ ] Mettre à jour `docs/dossier/` si une donnée du pilote contredit le dossier (nouvelle version v2.4 avec changelog).

## 7. Checkpoint démontrable

1. Achat test de l'offre pilote avec option téléphone ; un opérateur enregistre un appel avec le script, téléverse l'audio, saisit « partager » ; la famille écoute l'histoire ; l'audit montre chaque étape en `phone_operator`.
2. Onzième option téléphone : refusée au checkout ; le flag est désactivé ; la landing n'affiche plus l'option.
3. `sail artisan cohorts:report 0B-2026-11` sur les données de répétition : rapport généré avec les dénominateurs.
4. Forcer `collection_ends_at` sur un projet : notification de fin avec trois choix ; choisir « exporter » → exports ; « premier chapitre » généré.
5. `sail artisan golive:check` : rouge tant qu'un prérequis manque, vert quand tout est en place.

## 8. Critères de sortie

- [ ] Aucune famille payante avant que `go-live.md` soit entièrement coché.
- [ ] Le plafond téléphone est appliqué côté serveur et le comptage distingue `checkout` et `rescue`.
- [ ] Les rapports de gate utilisent les dénominateurs de R-5.

## 9. Règle de décision par défaut

Si le conseil juridique n'a pas validé un texte au moment prévu, le pilote attend. On ne lance pas avec un bandeau `[À VALIDER PAR CONSEIL]`.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
