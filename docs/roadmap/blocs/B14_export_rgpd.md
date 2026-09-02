# Bloc 14 — Export complet et droits RGPD

Statut : ☐ non commencé · Dépend de : 13 · Tag de fin : `bloc-14-done`
Références dossier : PRD P0-16, US-05, R-10.2 (export gratuit pendant l'hébergement, remise proactive), doc 04 §4 (droits RGPD outillés, effacement ≤ 30 j), §7 (fin d'hébergement, fenêtre d'export), §12.

## 1. Objectif

À tout moment, la famille obtient gratuitement un export complet (PDF, MP3, textes, photos, manifeste), généré en moins de 30 minutes pour 5 Go, livré par lien 7 jours. Il est remis sans demande à la livraison du livre et avant toute fin d'hébergement. Les droits d'accès, de rectification, de portabilité et d'effacement sont exerçables depuis l'interface.

## 2. Pourquoi

La non-captivité est devenue un standard de catégorie (v2.3 du dossier) ; c'est un prérequis de confiance et un actif juridique. L'effacement irréversible est la contrepartie de la souveraineté du narrateur.

## 3. Livrables

- Table `exports`, job `BuildExport`, notification `notifications.export.ready`, jeton `export` 7 jours.
- Formats : `full` (tout), `offline_pack` (audios + lecteur HTML statique + QR), `gdpr_access`.
- Déclencheurs proactifs : livraison du livre, `hosting_ends_at − 60 jours`, fin de pilote.
- Colonne `projects.hosting_ends_at` et réglage `PilotSettings::hosting_years`.
- Page `/espace/donnees` (Initiateur·rice) et section « Mes données » de l'espace narrateur : export, rectification, effacement.
- Jobs `EraseProject`, `EraseNarratorData` ; tickets `erasure_requested` avec délai 30 jours.
- `docs/runbooks/export-et-effacement.md`.

## 4. Packages

```bash
sail composer require maennchen/zipstream-php
```

## 5. Tests à écrire d'abord

- `tests/Feature/Exports/BuildExportTest.php` (`FakeMediaStorage`)
  - `it('builds a zip with README, manifest, book pdf, one folder per story with original audio, mp3, verbatim, current text, story json and photos')`
  - `it('includes only validated stories when requested by the initiator and all narrator stories except trashed when requested by the narrator')`
  - `it('writes sha256 checksums in the manifest and they match the files')`
  - `it('issues a 7 day export token and notifies the requester')`
  - `it('streams to a temp file and uploads by multipart without loading the zip in memory')` (assertion sur l'usage mémoire < 256 Mo avec 200 fichiers factices de 10 Mo)
  - `it('marks the export expired after 7 days and deletes the object')`
- `tests/Feature/Exports/ProactiveExportTest.php` : `books.status → delivered` → export `full` + `offline_pack` en file ; `hosting_ends_at − 60 j` → export + notification `notifications.export.hosting_ending` ; pilote terminé (`collection_ends_at` passé sur un projet `pilot`) → export.
- `tests/Feature/Exports/OfflinePackTest.php` : `index.html` autonome (aucune ressource distante), liste des histoires, lecteur audio local, image QR de chaque chapitre.
- `tests/Feature/Rgpd/AccessRequestTest.php` : `gdpr_access` = export `full` + `consentements.json` + `journal.json` (entrées d'audit concernant la personne, masquées).
- `tests/Feature/Rgpd/ErasureTest.php`
  - `it('lets the narrator request erasure of their data with a sensitive grant and executes within 30 days after admin confirmation')`
  - `it('lets the initiator request project erasure; narrator erasure requests take precedence')`
  - `it('erases R2 objects, transcripts, media, personal fields, tokens; keeps orders for accounting with anonymized references; keeps audit rows masked')`
  - `it('blocks erasure while a print order is in progress and explains why')`
- `tests/Feature/Rgpd/RectificationTest.php` : l'Initiateur·rice modifie ses coordonnées ; le narrateur modifie prénom et canal depuis son espace ; chaque modification est journalisée.
- `tests/e2e/export-download.spec.ts` : demander un export, recevoir le lien (Mailpit), télécharger, vérifier `manifest.json`.

## 6. Étapes

### 6.1 Données
- [ ] Migration `create_exports_table` ; colonne `projects.hosting_ends_at` (= `collection_started_at + hosting_years`, défaut 5 ans dans `PilotSettings`, `[À CONFIRMER : durée d'hébergement incluse, doc 04 §7]`) ; annexe B.
- [ ] `PilotSettings::hosting_years`, `qr_commitment_years` (10, D-8).

### 6.2 Construction de l'export
- [ ] `App\Exports\ExportBuilder` : itère les histoires selon la portée du demandeur (`ExportScope::forInitiator` = `validated|shared|in_book` ; `ExportScope::forNarrator` = tout sauf `trashed|deleted`), écrit avec `ZipStream` vers un fichier temporaire sur disque local (vérifier l'espace libre ≥ 2 × taille estimée), puis `MediaStorage::putStream` multipart vers `exports/{export}/export.zip`.
- [ ] Arborescence : `LISEZ-MOI.txt` (français, explique le contenu, la durée du lien, le fait que la famille conserve l'intégralité de ses droits sur ces fichiers (formulation R-10.1) et peut les lire sans le service) ; `manifest.json` ; `livre/bat-vN.pdf` si présent ; `histoires/NN-{slug}/audio-original.{ext}`, `audio.mp3`, `mot-a-mot.txt`, `texte.txt`, `histoire.json`, `photos/*.jpg` ; `lexique.json` ; `consentements.json` (portée narrateur) ; `hors-ligne/index.html` + `hors-ligne/qr/*.svg` pour `offline_pack`.
- [ ] `manifest.json` (schéma versionné `1.0`) : `{ version, generated_at, product_name, project: { id, narrator_display_name, collection_started_at }, requested_by: { type }, stories: [ { id, sequence, title, question, state, visibility, recorded_at, validated_at, duration_seconds, files: { audio_original, audio_mp3, verbatim, text, story_json, photos: [] }, checksums: { path: sha256 } } ], checksum_algorithm: "sha256" }`.
- [ ] `BuildExport(Export)` (file `exports`, `timeout 3600`) : statut `building` → `ready`, `bytes`, `manifest`, jeton `export` 7 jours, notification. `exports:expire` (`daily()`) supprime l'objet et passe `expired`.
- [ ] Route `GET /x/{token}` → redirection 302 vers une URL temporaire R2 (15 min) ; audit `downloaded Export`.

### 6.3 Déclencheurs proactifs
- [ ] Écouteur `BookDelivered` → `RequestExport(full)` + `RequestExport(offline_pack)` pour l'Initiateur·rice, notification « Votre livre est livré ; voici votre export complet et votre pack hors-ligne, à conserver. »
- [ ] `exports:proactive` (`daily()`) : projets avec `hosting_ends_at − 60 j` atteint et sans export des 90 derniers jours ; projets `pilot` dont `collection_ends_at` est passé sans export.

### 6.4 Droits
- [ ] `/espace/donnees` : « Télécharger mes données » (export `gdpr_access`), « Corriger mes coordonnées », « Demander l'effacement du projet » (explication : irréversible, 30 jours, priorité au narrateur, blocage si impression en cours) ; espace narrateur : « Mes données » avec les mêmes actions sous `sensitive_grant`.
- [ ] `RequestErasure(subject, scope)` → ticket `erasure_requested` + email de confirmation ; `ConfirmErasure` (admin, Filament) → `EraseProject` ou `EraseNarratorData` planifié (`delay` jusqu'à 30 jours max, exécuté dès confirmation par défaut) ; `EraseProject` : `PurgeDeletedStory` sur chaque histoire, suppression des médias, `transcripts`, `lexicon_entries`, `consents` conservés (obligation de preuve, sujets anonymisés par hash), `narrators`/`family_members` champs personnels à `null`, jetons révoqués, `orders` conservés avec `project_id` gardé mais `user_id` remplacé par un utilisateur `anonymized` si l'acheteur demande aussi l'effacement de son compte ; audit `erased Project`.
- [ ] Politique publiée : `resources/views/legal/confidentialite.md` section « Conservation et effacement » : effacement des systèmes actifs immédiat à l'exécution, purge des sauvegardes ≤ 90 jours (rétention des sauvegardes réglée au bloc 16).

### 6.5 Clôture
- [ ] `docs/runbooks/export-et-effacement.md` : test de performance manuel (projet synthétique 5 Go → < 30 min), procédure de confirmation d'effacement, vérification post-effacement.
- [ ] Annexe B, `04_VERSIONS.md`, `.env.example`.
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts ; commit `chore(bloc-14): terminé`, tag `bloc-14-done`.

## 7. Checkpoint démontrable

1. Depuis `/espace/donnees`, demander un export : notification avec lien `/x/…`, ZIP téléchargé, `manifest.json` valide, `sha256sum -c` sur les fichiers listés passe.
2. Ouvrir `hors-ligne/index.html` depuis le ZIP, hors connexion : les audios se lisent.
3. Marquer un livre « livré » dans Filament : deux exports partent sans action de la famille.
4. Demander l'effacement du narrateur (OTP), confirmer dans Filament : objets R2 supprimés, champs à `null`, le journal d'audit conserve les lignes masquées, `audit:verify` intact.
5. Test de performance sur un projet synthétique de 5 Go : durée notée dans le runbook.

## 8. Critères de sortie

- [ ] Un export ne contient jamais une histoire non validée quand il est demandé par l'Initiateur·rice.
- [ ] Le ZIP est lisible sans le service (aucune URL du produit nécessaire pour lire les fichiers).
- [ ] L'effacement est journalisé et vérifiable.

## 9. Règle de décision par défaut

En cas de conflit entre une demande d'effacement du narrateur et une demande de conservation de l'Initiateur·rice, le narrateur gagne, sans arbitrage éditorial (doc 04 §3). Les commandes restent conservées le temps légal comptable.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
