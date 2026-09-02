# Bloc 04 — Page d'enregistrement narrateur et spike navigateur

Statut : ◐ en cours — code livré et éprouvé, spike appareils réels à exécuter (§6.7) · Dépend de : 03 · Tag de fin : `bloc-04-done`

**⛔ En attente de toi** — [`05_A_FAIRE_HUMAIN.md`](../05_A_FAIRE_HUMAIN.md) §1.6 : deux téléphones réels (idéalement cinq, dont Samsung Internet) et un accès HTTPS. `getUserMedia` exige une connexion sécurisée : le spike ne peut pas se jouer sur `http://localhost` depuis un téléphone.

Références dossier : PRD P0-3, P0-5, P0-7 (audio source), US-01, US-06, doc 04 §11 (SLO capture), §12 (fichiers), spike §8.2 ; décisions T-09, T-10, T-26, T-27.

## 1. Objectif

Un narrateur de 80 ans qui reçoit un lien peut, sans aide, comprendre ce qu'on lui demande, autoriser son micro, parler, faire une pause, reprendre, envoyer, et voir « Votre histoire est enregistrée » uniquement quand le fichier est réellement sur le stockage. L'enregistrement survit à un appel entrant, à la mise en veille et à la purge d'onglet. Le fallback écrit existe. Le comportement sur appareils réels est documenté.

## 2. Pourquoi

C'est le risque technique n°1 du dossier et la première mesure de la Gate 0A (réussite du premier enregistrement non assisté ≥ 85 %). Le dossier interdit de promettre la « reprise automatique iOS » avant de l'avoir prouvée.

## 3. Livrables

- Disques `r2`, `r2_replica`, `r2_backups` ; `App\Services\Storage\MediaStorage` (S3 multipart, présignature, `head`, copie) + `FakeMediaStorage`.
- Table `recordings` (annexe B, avec colonne supplémentaire `segments jsonb`) et table `client_events` (bigint : `story_id`, `event`, `payload jsonb`, `created_at`).
- Routes narrateur : page, initiation, présignature de part, complétion, abandon, événements client, réponse écrite.
- Front `resources/js/recorder/` : machine à états, `MediaRecorder`, brouillon IndexedDB (Dexie), uploader multipart résumable, vu-mètre, verrou d'écran.
- Pages `narrator/Record` (explication → permission → enregistrement → envoi → confirmation), `narrator/MicHelp`, `narrator/WrittenAnswer`, `narrator/AlreadyRecorded`.
- Jobs `ConcatenateSegments` (si plusieurs segments), `ReplicateRecording`.
- `docs/spikes/navigateur.md` rempli.

## 4. Packages

```bash
sail composer require league/flysystem-aws-s3-v3 "^3.0"
sail npm i dexie
sail npm i -D fake-indexeddb
```

## 5. Tests à écrire d'abord

**Front (Vitest)**
- `resources/js/recorder/recorderMachine.test.ts` : transitions `idle → explaining → requesting_permission → ready → recording → paused → recording → stopping → reviewing → uploading → confirmed` ; `requesting_permission → permission_denied` ; `recording → interrupted` sur `visibilitychange` + recorder inactif ; `interrupted → recording` (nouveau segment) ; `uploading → upload_failed → uploading` (retry) ; `recording` refuse de dépasser `hard_stop_seconds` (→ `stopping` automatique) ; `soft_warning` émis à 600 s.
- `resources/js/recorder/mime.test.ts` : ordre de préférence `audio/mp4`, `audio/webm;codecs=opus`, `audio/webm`, `audio/ogg;codecs=opus` selon `isTypeSupported`.
- `resources/js/recorder/draftStore.test.ts` (fake-indexeddb) : `appendChunk`, `listChunks` dans l'ordre, `markPartUploaded`, `resumeInfo`, `clear` ; un brouillon est retrouvé par `story_ref` après « rechargement » (nouvelle instance).
- `resources/js/recorder/uploader.test.ts` (fetch mocké) : découpe en parts de 5 Mio, 2 parts en parallèle, reprise depuis les parts déjà marquées, backoff exponentiel 1 s → 2 s → 4 s → 8 s (max 5 essais), appelle `complete` avec les ETags dans l'ordre.
- `resources/js/pages/narrator/Record.test.tsx` : l'écran d'explication précède toute demande de micro ; le bouton principal fait ≥ 44 px ; le libellé change selon `address_form`.
- `resources/js/pages/narrator/MicHelp.test.tsx` : contenu par plateforme (`ios`, `android`, `samsung`, `other`) ; le bouton « Réessayer » disparaît après un essai.

**Back (Pest)**
- `tests/Feature/Narrator/RecordPageTest.php` : GET `/r/{token}` rend `narrator/Record` avec la question, l'état et les limites ; une histoire déjà `recorded` rend `narrator/AlreadyRecorded` avec la possibilité de recommencer ; une histoire `validated` rend `LinkUnavailable` (jeton révoqué).
- `tests/Feature/Narrator/RecordingUploadTest.php` : `initiate` crée un `Recording(initiated)` et un `upload_id` ; refuse un mime non accepté (422) ; `sign-part` retourne une URL et refuse un `partNumber` > 2000 ; `complete` appelle `completeMultipart` puis `head`, pose `confirmed_at`, `original_bytes`, transitionne l'histoire en `recorded`, dispatch `ReplicateRecording` ; `complete` sans `head` réussi laisse `upload_status = failed` et ne transitionne pas ; `abort` marque `aborted` ; un second `initiate` sur une histoire déjà `recorded` crée un nouveau `Recording` courant et conserve l'ancien (`is_current = false`).
- `tests/Feature/Narrator/ClientEventsTest.php` : `mic_denied`, `recording_started`, `page_hidden`, `resumed_from_draft`, `soft_warning_reached` sont stockés ; payload limité à 2 Ko ; 120 événements/min/jeton max.
- `tests/Feature/Narrator/WrittenAnswerTest.php` : POST crée `answer_type = text`, `written_answer`, transition `recorded` ; refuse un texte vide ou > 20 000 caractères.
- `tests/Unit/Jobs/ConcatenateSegmentsTest.php` : concatène par `ffmpeg -f concat -c copy` (binaire mocké via `Process::fake()`), écrit `original_path`, conserve `segments`.
- `tests/Unit/Jobs/ReplicateRecordingTest.php` : copie vers `r2_replica`, pose `replicated_at`, idempotent.
- `tests/Unit/Storage/MediaStorageTest.php` : clés d'objet `projects/{project}/stories/{story}/recordings/{recording}/segment-01.{ext}` sans donnée personnelle.

**Bout en bout (Playwright, flags `--use-fake-ui-for-media-stream --use-fake-device-for-media-stream`)**
- `tests/e2e/record-happy-path.spec.ts` : lien valide → explication → permission → 3 s d'enregistrement → pause → reprise → terminer → envoyer → « Votre histoire est enregistrée » ; l'objet existe dans MinIO ; l'histoire est `recorded`.
- `tests/e2e/record-resume-after-reload.spec.ts` : enregistrer 3 s, recharger la page, le bouton « Reprendre mon enregistrement » apparaît, terminer et envoyer.
- `tests/e2e/record-mic-denied.spec.ts` (contexte avec permission refusée) : écran d'aide, lien « Répondre par écrit », soumission écrite.
- `tests/e2e/record-a11y.spec.ts` : zéro violation axe `serious`/`critical` sur chaque écran ; taille de police ≥ 18 px ; zones tactiles ≥ 44 px (mesurées).
- `tests/e2e/record-budget.spec.ts` : JavaScript transféré sur `/r/*` ≤ 150 Ko gzip.

## 6. Étapes

### 6.1 Stockage
- [x] `config/filesystems.php` : disques `r2`, `r2_replica`, `r2_backups` (driver `s3`, `endpoint` = `R2_ENDPOINT`, `use_path_style_endpoint` = `AWS_USE_PATH_STYLE_ENDPOINT`, `region` `auto`, `bucket` respectif, `visibility` `private`, `throw` `true`).
- [x] `App\Services\Storage\MediaStorage` (interface) : `createMultipartUpload(string $key, string $mime): string $uploadId`, `presignPart(string $key, string $uploadId, int $partNumber, int $ttlMinutes = 15): string`, `completeMultipart(string $key, string $uploadId, array $parts): void`, `abortMultipart(...)`, `head(string $key): ObjectInfo` (taille, ETag, mime), `copy(string $key, string $toDisk): void`, `temporaryUrl(string $key, int $minutes): string`, `delete(string $key): void`. Implémentation `S3MediaStorage` (client `Aws\S3\S3Client` construit depuis la config du disque) et `FakeMediaStorage` (mémoire).
- [x] Nommage des clés : `App\Support\ObjectKeys::recordingSegment(Recording $r, int $n, string $ext)`.
- [x] Test `MediaStorageTest` vert. Vérifier manuellement contre MinIO que la présignature et le CORS fonctionnent (`docker/minio/cors.json` autorisant `http://localhost` en `PUT` avec exposition de l'en-tête `ETag`).

### 6.2 Table et modèle `Recording`, `client_events`
- [x] Migrations `create_recordings_table` (annexe B + `segments jsonb`) avec trigger `recordings_original_immutable` (`BEFORE UPDATE` : si `OLD.confirmed_at IS NOT NULL AND NEW.original_path <> OLD.original_path` → `RAISE EXCEPTION`), `create_client_events_table`.
- [x] Modèle `Recording` (`HasUuids`, casts, `story()`, scope `current()`), `ClientEvent`.
- [x] Factory `RecordingFactory` avec états `initiated()`, `confirmed()`.

### 6.3 Routes et contrôleurs
- [x] `routes/narrator.php`, groupe `resolve.token:record`, `throttle:tokens`, `no-store` :
  - `GET /r/{token}` → `RecordPageController::show`
  - `POST /r/{token}/recordings` → `RecordingUploadController::initiate` (Form Request : `mime` in accepted, `expected_bytes` ≤ max)
  - `POST /r/{token}/recordings/{recording}/parts/{part}/sign` → `sign`
  - `POST /r/{token}/recordings/{recording}/complete` → `complete` (Form Request : `parts[]` `{number, etag}`, `client_duration_seconds`, `segments_count`)
  - `POST /r/{token}/recordings/{recording}/abort` → `abort`
  - `POST /r/{token}/events` → `ClientEventController::store` (`throttle:client-events` 120/min)
  - `POST /r/{token}/written-answer` → `WrittenAnswerController::store`
- [x] `{recording}` doit appartenir à l'histoire du jeton (binding scopé) ; sinon 404.
- [x] Actions : `InitiateRecording`, `CompleteRecording` (complète, `head`, vérifie `original_bytes ≤ max`, pose `confirmed_at`, transition `RecordStory`, dispatch `ConcatenateSegments` si `segments_count > 1` puis `ReplicateRecording`), `AbortRecording`, `SubmitWrittenAnswer`.
- [x] Tests Pest verts.

### 6.4 Jobs média
- [x] File `media`. `ConcatenateSegments` : télécharge les segments dans un répertoire temporaire, `ffmpeg -f concat -safe 0 -i list.txt -c copy original.{ext}`, téléverse en `original_path`, conserve la liste dans `segments`. `Process::fake()` dans les tests.
- [x] `ReplicateRecording` : `copy(original_path, 'r2_replica')`, pose `replicated_at` ; `tries 5`, `backoff [30, 120, 600, 1800, 3600]` ; `failed()` journalise en `error` avec `recording_id` (c'est un incident P1 potentiel, doc 04 §11).
- [x] Le dérivé MP3 et la durée `ffprobe` arrivent au bloc 06 (`TranscodeRecording`).

### 6.5 Front : moteur d'enregistrement
- [x] `resources/js/recorder/recorderMachine.ts` : reducer pur `(state, event) → state` avec les états de §5 et un contexte `{ elapsedSeconds, segments, warningShown, permissionRetries }`.
- [x] `resources/js/recorder/mime.ts` : `pickMimeType()`.
- [x] `resources/js/recorder/draftStore.ts` : base Dexie `recorder`, tables `drafts` (`storyRef`, `mime`, `segments`, `createdAt`, `uploadedParts`), `chunks` (`storyRef`, `segment`, `index`, `blob`). Quota : si `estimate()` indique < 50 Mo libres, avertir sans bloquer.
- [x] `resources/js/recorder/useMediaRecorder.ts` : `getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } })`, `MediaRecorder` avec `timeslice = 5000`, chaque `dataavailable` → `draftStore.appendChunk` ; gère `pause()/resume()` ; sur `visibilitychange` → si le recorder est `inactive` alors qu'on était en `recording`, émettre `INTERRUPTED` (les tranches sont déjà persistées) ; `RESUME_AFTER_INTERRUPTION` crée un nouveau segment.
- [x] `resources/js/recorder/uploader.ts` : construit un `Blob` par segment depuis les tranches, découpe en parts de 5 Mio (dernière part libre), `initiate` → `sign` → `PUT` présigné (ETag lu depuis l'en-tête) → `complete` ; parallélisme 2 ; reprise depuis `uploadedParts` ; backoff ; en cas d'échec définitif, état `upload_failed` avec bouton « Réessayer » et message « Votre enregistrement est conservé sur votre téléphone ».
- [x] `resources/js/recorder/levelMeter.ts` (AudioContext + AnalyserNode, 12 barres), `wakeLock.ts` (`navigator.wakeLock?.request('screen')`, silencieux si absent).
- [x] `client-events.ts` : envoi `fetch` best-effort (`keepalive: true`) de chaque transition notable.
- [x] Tests Vitest verts.

### 6.6 Front : pages
- [x] `narrator/Record.tsx` orchestre les écrans :
  1. **Explication** : « {Prénom}, voici votre question de la semaine » ; carte question en 24 px ; encart « Quand vous appuierez sur le bouton, votre téléphone demandera l'autorisation d'utiliser le micro. Choisissez “Autoriser”. » ; bouton unique « Je suis prêt·e ».
  2. **Permission** : spinner et rappel ; en cas de refus → `MicHelp`.
  3. **Enregistrement** : bouton rond ≥ 88 px « Commencer » puis « Pause » / « Reprendre », minuteur, vu-mètre, bouton « Terminer » ; bandeau discret à 10 min ; arrêt à 20 min avec explication.
  4. **Vérification** : « Réécouter » (lecteur natif sur le blob), « Envoyer » (primaire), « Recommencer » (confirmation).
  5. **Envoi** : barre de progression, « Ne fermez pas cette page, mais si cela arrive votre enregistrement est conservé ».
  6. **Confirmation** : « Votre histoire est enregistrée. Merci {Prénom}. » (l'écran des trois choix de partage est ajouté au bloc 07).
  Au chargement : si un brouillon existe pour `story_ref`, écran « Reprendre mon enregistrement » / « Recommencer ».
- [x] `narrator/MicHelp.tsx` : détection `ios` / `android` / `samsung` / `other` via `navigator.userAgent` ; captures schématiques en SVG inline ; « Réessayer » une seule fois ; « Répondre par écrit ».
- [x] `narrator/WrittenAnswer.tsx` : textarea 20 px, compteur, « Envoyer ».
- [x] `narrator/AlreadyRecorded.tsx` : « Vous avez déjà répondu à cette question le {date} » ; « Recommencer » (nouvel enregistrement) ; « Fermer ».
- [x] Toutes les chaînes dans `lang/fr/narrator.php`. `address_form` piloté par le projet.
- [x] Tests Vitest et Playwright verts.

### 6.7 Spike navigateur (appareils réels)
- [ ] Déployer une version de test accessible en HTTPS (tunnel `sail share` ou staging du bloc 16 s'il existe déjà ; le micro exige HTTPS hors `localhost`).
- [ ] Remplir `docs/spikes/navigateur.md` (protocole écrit, matrice et scénarios prêts) : matrice iPhone Safari (iOS N et N-1), Android Chrome (N et N-1), Samsung Internet ; scénarios : appel entrant pendant l'enregistrement ; verrouillage 2 min ; changement d'application 5 min ; purge d'onglet (ouvrir 10 onglets lourds puis revenir) ; 4G bridée à 1 Mb/s pendant l'envoi ; refus puis réautorisation du micro. Colonnes : résultat, segments produits, perte de données (oui/non), remarques.
- [ ] Si un scénario perd des données confirmées à l'écran : bloquant, corriger avant de clore le bloc. Si un scénario perd des données **avant** confirmation : documenter le taux, l'objectif est < 2 % (doc 04 §11).

### 6.8 Clôture
- [x] Annexe B mise à jour (`recordings.segments`, `client_events`).
- [x] `04_VERSIONS.md` : flysystem S3, dexie.
- [x] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts.
- [ ] Commit `chore(bloc-04): terminé`, tag `bloc-04-done` — **après** le spike.

## 7. Checkpoint démontrable

1. Sur un iPhone réel et un Android réel, via HTTPS : ouvrir un lien `record` du seeder, suivre les six écrans, parler 2 minutes, verrouiller l'écran 1 minute au milieu, reprendre, envoyer.
2. Vérifier dans MinIO ou R2 : l'objet existe, sa taille correspond ; dans Filament (bloc 11) ou tinker : `Recording.confirmed_at` posé, `Story.state = recorded`.
3. Recharger la page pendant un enregistrement : le brouillon est proposé et l'envoi aboutit.
4. Refuser le micro : l'écran d'aide propose la réponse écrite ; une réponse écrite crée l'histoire en `recorded`.
5. `docs/spikes/navigateur.md` rempli avec au moins 4 appareils.

## 8. Critères de sortie

- [x] Aucune confirmation à l'écran sans `HeadObject` réussi (revue du code de `CompleteRecording`).
- [x] Aucun texte visible hors `t()`.
- [x] Budget JavaScript respecté.
- [ ] Le spike ne montre aucune perte après confirmation.

## 9. Règle de décision par défaut

Si `MediaRecorder` est indisponible sur un navigateur de la matrice, afficher l'écran d'aide avec la réponse écrite et journaliser `recorder_unsupported`. Ne pas ajouter de polyfill lourd ni de WebAssembly dans ce bloc ; noter le besoin dans `03_DECISIONS.md`.

## 10. Note de checkpoint

**2026-09-02 — code livré et éprouvé — bloc non clos : le spike §6.7 exige des
appareils réels.**

### Ce qui est démontré, automatiquement

- `sail artisan storage:prepare-local` crée les trois seaux ; l'envoi présigné
  fonctionne contre MinIO, vérifié dans un vrai navigateur.
- **Parcours nominal bout en bout** : lien → explication → permission →
  enregistrement → pause → reprise → vérification → envoi → « Votre histoire
  est enregistrée ». L'objet est ensuite relu au stockage : 641 octets
  annoncés, 641 octets détenus, même ETag. L'histoire est en `recorded`, et
  `ReplicateRecording` a posé `replicated_at` et `replica_path`.
- **Rechargement en pleine phrase** : le brouillon est proposé, l'envoi
  aboutit.
- **Micro refusé** : aide propre à la plateforme, un seul nouvel essai,
  réponse écrite acceptée, histoire en `recorded` avec `answer_type = text`.
- **Accessibilité** : zéro violation axe `serious`/`critical` sur les trois
  écrans, police de la zone principale ≥ 18 px, tous les boutons ≥ 44 px de
  haut, aucun compte à rebours.
- **Budget** : sous 150 Ko de JavaScript gzip sur `/r/*`, mesuré en
  compressant les réponses dans le test — le serveur local ne compresse pas.
- Porte verte : Pint, PHPStan niveau 8, **333 tests Pest**, 76 tests Vitest,
  17 tests Playwright.

### Ce qui reste, et qui ne peut pas être fait sans toi

§6.7 et les points 1 et 5 du checkpoint demandent un iPhone et un Android
réels, en HTTPS, pour éprouver l'appel entrant, la mise en veille, la purge
d'onglet et la 4G bridée. `docs/spikes/navigateur.md` est écrit : matrice de
cinq appareils, dix scénarios, tableau de relevé, et la règle de clôture —
**une seule perte après confirmation est bloquante**. Il reste à le jouer.

Tant qu'il n'est pas joué, ce bloc reste `◐ en cours` et n'est pas taggé. Le
code des blocs suivants ne dépend pas du spike ; la promesse commerciale de la
reprise iOS, elle, en dépend, et le dossier interdit de la faire avant.

**Écarts par rapport au plan :**

- **Un envoi multipart par segment**, et non un par enregistrement (T-54). Le
  modèle du bloc ne tenait pas : deux continuités de flux déposées comme des
  parts consécutives d'un même envoi produisent un conteneur illisible.
  `POST /recordings` n'accepte donc plus `segments_count` — on ne sait pas
  d'avance combien d'appels une personne va recevoir — et un segment s'ouvre à
  la demande.
- Le déclencheur d'immuabilité laisse **renseigner** `original_path` une
  première fois après confirmation : un enregistrement interrompu est confirmé
  sur ses segments, et son fichier recollé n'arrive qu'ensuite.
- `R2_PUBLIC_ENDPOINT` ajouté (T-56) : en local, MinIO n'a pas la même adresse
  depuis le conteneur et depuis le navigateur.
- MinIO n'implémente pas `PutBucketCors` (T-58) : la règle de
  `docker/minio/cors.json` est à reporter dans la console Cloudflare au
  bloc 16, avec l'exposition de l'`ETag`.
- La durée `ffprobe` et le dérivé MP3 restent au bloc 06, comme prévu.
- `client_duration_seconds` est rangé dans `device_info` plutôt que dans une
  colonne : c'est une valeur annoncée par le client, indicative, à comparer
  plus tard à la vraie durée.

**Défauts trouvés et corrigés en chemin :**

- **Le brouillon local ne survivait pas à un rechargement.** Les `Blob`
  stockés dans IndexedDB ressortaient inutilisables. Corrigé en stockant des
  octets bruts (T-55) — et c'est justement Safari iOS qui invalide ces
  références quand il purge un onglet, donc le scénario cible.
- **La politique de contenu bloquait l'envoi lui-même.** `connect-src` listait
  l'adresse vue par le serveur, pas celle contactée par le navigateur. Trouvé
  en lisant la console du navigateur pendant un test bout en bout, pas par un
  test unitaire.
- **Le budget JavaScript était dépassé de 51 Ko** et la politique de contenu
  refusait le style injecté par les notifications de l'espace authentifié.
  Les deux avaient la même cause : l'enveloppe globale chargeait la barre
  latérale, les info-bulles et les notifications sur une page narrateur (T-57).
- **Les tests bout en bout se marchaient sur les pieds** : ils partageaient un
  seul lien, donc une seule histoire (T-59).
- **Le limiteur des pages étouffait la mesure** : 20 requêtes par minute et par
  jeton rendaient inatteignables les 120 événements du bloc (T-60).
- **`ffmpeg` peut rendre 0 sans produire de fichier** : le job levait alors un
  chemin pointant le vide. Il vérifie désormais la présence du fichier.
- **En intégration continue, l'application servie utilisait le faux
  stockage.** La liaison interrogeait `runningUnitTests()`, et le bout en bout
  tourne avec `APP_ENV=testing` : les envois partaient vers un hôte qui
  n'existe pas. Trouvé en faisant parler les tests — les erreurs du navigateur
  sont désormais rapportées dans leur sortie. Le pilote est explicite (T-61),
  et le même diagnostic a corrigé le cookie d'autorisation, forcé en `secure`
  et donc jeté sur une connexion en clair.
- **`color-scheme` était posé en style en ligne** et refusé par la politique
  de contenu : les formulaires natifs se lisaient au mauvais schéma. Il
  s'exprime désormais en CSS (T-62).

**Ce que le bloc laisse ouvert :**

- Le spike appareils réels (§6.7).
- L'écran des trois choix de partage après la confirmation arrive au bloc 07,
  comme prévu.
- Le vu-mètre et le verrou d'écran sont écrits mais non branchés dans la page :
  ils demandent un `AudioContext` et une API absents de jsdom, donc du spike
  pour être jugés utiles. À trancher avec les résultats.
- La durée réelle, le dérivé MP3 et la transcription : bloc 06.
- Une violation de style en ligne subsiste dans la console des pages
  narrateur, sans effet fonctionnel constaté : même empreinte sur toutes les
  pages, source non identifiée après recherche. La politique fait son travail
  en la refusant ; c'est le premier suspect si un défaut visuel apparaît
  (T-62).
