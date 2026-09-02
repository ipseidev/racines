# Bloc 06 — Transcription, rendu Fluide et banc d'essai ASR

Statut : ◐ en cours · Dépend de : 05 · Tag de fin : `bloc-06-done`

**⛔ En attente de toi** — [`05_A_FAIRE_HUMAIN.md`](../05_A_FAIRE_HUMAIN.md) §1.1 à §1.5 : clé Anthropic, clé Gladia, secret de rappel ASR, **le corpus de dix voix de personnes de 65 ans et plus avec références relues** (le plus long à réunir), et une lecture humaine du Fluide sur cinq histoires.

Références dossier : PRD P0-7, P0-8, US-03, spike §8.3, doc 04 §5 (transparence IA), §1 (« L'IA range, elle n'invente pas », pas de clonage vocal) ; décisions T-07, T-08, T-10.

## 1. Objectif

Dix minutes après la confirmation d'un enregistrement, l'histoire a un verbatim et un rendu Fluide côte à côte, le verbatim n'est jamais supprimé, l'audio source n'est jamais modifié, le lexique du projet corrige les noms propres, et un banc d'essai mesure le WER des fournisseurs sur des voix âgées.

## 2. Pourquoi

Le texte est ce qui va dans le livre ; la voix est ce qui va dans le QR. Le dossier exige que le texte conserve les mots et tournures du narrateur et que le rendu IA soit étiqueté et réversible. La qualité ASR sur voix âgées est une hypothèse à mesurer, pas à supposer.

## 3. Livrables

- Job `TranscodeRecording` (durée `ffprobe`, dérivé MP3 128 kb/s).
- Table `transcription_jobs`, table `transcripts`, table `lexicon_entries`.
- `TranscriptionProvider` + `GladiaProvider`, `DeepgramProvider`, `FakeTranscriptionProvider` ; jobs `SubmitTranscription`, `PollTranscription` ; webhook `/webhooks/asr/{provider}`.
- `StoryRenderer` + `ClaudeStoryRenderer`, `FakeStoryRenderer` ; job `RenderFluide`.
- Actions `StoreVerbatimTranscript`, `EditTranscript`, `AddLexiconEntry`.
- Événement `TranscriptionReady(Story)` (consommé au bloc 07).
- Commande `asr:bench`, classe `App\Support\Wer`, compte rendu `docs/spikes/asr.md`.

## 4. Packages

```bash
sail composer require anthropic-ai/sdk pbmedia/laravel-ffmpeg
sail artisan vendor:publish --provider="ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider"
```

Vérifier que `ffmpeg` et `ffprobe` répondent dans le conteneur Sail (`sail exec laravel.test ffmpeg -version`). Sur Forge : `sudo apt-get install -y ffmpeg` (bloc 16).

## 5. Tests à écrire d'abord

- `tests/Unit/Jobs/TranscodeRecordingTest.php` : lit la durée via `ffprobe` (`Process::fake`), écrit `derived_mp3_path`, ne modifie jamais `original_path`, idempotent, dispatch `SubmitTranscription`.
- `tests/Unit/Transcription/GladiaProviderTest.php` (`Http::fake` sur fixtures `tests/Fixtures/asr/gladia-*.json`) : `submit` envoie `audio_url` signée 1 h, `language fr`, `custom_vocabulary` depuis le lexique, `callback_url` avec HMAC ; `fetch` mappe `text`, `words[] {word,start,end,confidence}` ; `parseWebhook` refuse une signature HMAC invalide.
- `tests/Unit/Transcription/DeepgramProviderTest.php` : idem avec `nova-3`, `language=fr`, `smart_format=true`, `keyterm` depuis le lexique, réponse synchrone.
- `tests/Feature/Jobs/TranscriptionPipelineTest.php` (`FakeTranscriptionProvider`)
  - `it('submits, stores a verbatim transcript, marks the story transcribed and dispatches RenderFluide')`
  - `it('polls processing jobs older than 30 seconds without webhook')`
  - `it('retries submission 3 times then marks the job failed and alerts support')`
  - `it('never creates two current verbatim transcripts for one recording')`
- `tests/Unit/Llm/ClaudeStoryRendererTest.php` (client Anthropic mocké via l'interface `AnthropicClientFactory`)
  - `it('sends the cached system prompt, the question, the verbatim and the lexicon')`
  - `it('requests a json schema output and maps it to FluideResult')`
  - `it('keeps the verbatim only and flags the rendering as refused when stop reason is refusal')`
  - `it('records model, usage and duration in metadata')`
  - `tests/Unit/Llm/__snapshots__/system-prompt.txt` : snapshot du prompt système.
- `tests/Feature/Jobs/RenderFluideTest.php` : crée `Transcript(fluide, is_current)`, pose `stories.title` si vide, émet `TranscriptionReady`, ne s'exécute pas si le narrateur n'a pas le consentement `ai_rendering` (verbatim seul, `TranscriptionReady` émis quand même).
- `tests/Unit/Actions/EditTranscriptTest.php` : crée `kind = edited`, `version = n+1`, `source_transcript_id`, bascule `is_current` ; le verbatim reste `is_current` parmi les `verbatim` ; l'historique est complet.
- `tests/Feature/Database/VerbatimNoDeleteTest.php` : `DELETE` sur un verbatim d'une histoire non `deleted` lève une exception Postgres ; passe si l'histoire est `deleted`.
- `tests/Unit/Support/WerTest.php` : normalisation (casse, ponctuation, apostrophes typographiques, nombres en chiffres inchangés), WER de cas connus (`0.0`, `0.25`, insertion, suppression, substitution).
- `tests/Feature/Console/AsrBenchTest.php` : avec `FakeTranscriptionProvider`, produit un tableau markdown par fournisseur avec WER médian et p90.

## 6. Étapes

### 6.1 Transcodage
- [x] ~~`config/laravel-ffmpeg.php`~~ : le paquet n'est pas installé (T-69). Les binaires sont lus dans `config('product.media.ffmpeg'|'ffprobe')`, alimentés par `FFMPEG_BINARIES` / `FFPROBE_BINARIES`.
- [x] `TranscodeRecording` (file `media`) : télécharge l'original en tmp, `ffprobe` → `duration_seconds`, `ffmpeg -i in -codec:a libmp3lame -b:a 128k -ac 1 out.mp3` → `derived_mp3_path` (`ObjectKeys::recordingDerived`), puis `SubmitTranscription`. Dispatché par `CompleteRecording` (bloc 04) après `ReplicateRecording` (chaîne `Bus::chain`).

### 6.2 Tables
- [x] Migrations `create_transcription_jobs_table` (bigint : `recording_id`, `provider`, `provider_job_id` nullable, `status` check `queued|processing|done|failed`, `attempts`, `submitted_at`, `completed_at`, `error` nullable, timestamps ; index `(status, submitted_at)`), `create_transcripts_table`, `create_lexicon_entries_table` (annexe B).
- [x] Règle Postgres `transcripts_verbatim_no_delete` : `CREATE OR REPLACE FUNCTION forbid_verbatim_delete() … IF OLD.kind = 'verbatim' AND (SELECT state FROM stories WHERE id = OLD.story_id) <> 'deleted' THEN RAISE EXCEPTION …` + trigger `BEFORE DELETE`. Garde modèle équivalente dans `Transcript::booted()`.
- [x] Annexe B : ajouter `transcription_jobs`.

### 6.3 Fournisseurs ASR
- [x] Interface `App\Services\Transcription\TranscriptionProvider` :
  ```php
  public function name(): string;                                          // 'gladia'
  public function submit(Recording $recording, TranscriptionRequest $request): SubmittedJob; // {providerJobId, mode: webhook|poll|sync, result?: TranscriptionResult}
  public function fetch(string $providerJobId): ?TranscriptionResult;       // null si en cours
  public function parseWebhook(Request $request): ?TranscriptionResult;     // vérifie le HMAC de l'URL
  ```
  `TranscriptionRequest` : `language`, `vocabulary: string[]`, `callbackUrl`. `TranscriptionResult` : `text`, `words[]`, `language`, `providerMetadata`.
- [x] `GladiaProvider` : API v2 (`POST /v2/pre-recorded` avec `audio_url`, `language_config`, `custom_vocabulary_config`, `callback_url` ; `GET /v2/pre-recorded/{id}`). **Vérifier les noms exacts des champs sur docs.gladia.io au moment d'écrire l'adaptateur ; la documentation officielle prime sur cette ligne.**
- [x] `DeepgramProvider` : `POST https://api.deepgram.com/v1/listen?model=nova-3&language=fr&smart_format=true&punctuate=true&paragraphs=true&keyterm=…` avec `{"url": …}` ; réponse synchrone. Même règle de vérification documentaire.
- [x] `FakeTranscriptionProvider` : retourne un texte déterministe dérivé du nom de fichier, ou un scénario (`processing` n fois puis `done`, ou `failed`).
- [x] Liaison dans `AppServiceProvider` selon `ASR_PROVIDER`. Route `POST /webhooks/asr/{provider}` avec paramètre `sig` HMAC (`hash_hmac('sha256', recording_id, ASR_CALLBACK_SECRET)`).
- [x] `SubmitTranscription` (file `transcription`, `tries 3`, `backoff [60, 300, 900]`) : crée `transcription_jobs`, appelle `submit`, si `sync` → `StoreVerbatimTranscript` immédiat ; `failed()` → statut `failed`, notification `notifications.support.transcription_failed`.
- [x] `PollTranscription` planifié `everyMinute()` : jobs `processing` avec `submitted_at < now - 30s`, `fetch`, stocke si prêt ; abandonne après 60 minutes (`failed`).
- [x] `StoreVerbatimTranscript(Recording, TranscriptionResult)` : applique le lexique (remplacement insensible à la casse des `term → replacement` dans `text`, les `words` conservent l'original), crée `Transcript(verbatim, version 1, is_current)`, transition `MarkTranscribed`, dispatch `RenderFluide`.

### 6.4 Rendu Fluide avec Claude
- [x] Interface `App\Services\Llm\StoryRenderer { public function render(Transcript $verbatim, RenderingContext $context): FluideResult; }` ; `RenderingContext` : question, prénom, `address_form`, lexique, thèmes possibles ; `FluideResult` : `title`, `text`, `themes[]`, `properNouns[]`, `sensitiveFlags[]`, `refused: bool`, `metadata`.
- [x] ~~`AnthropicClientFactory`~~ → interface `App\Services\Llm\AnthropicMessages` + `SdkAnthropicMessages` (T-70) : `MessagesService` est `final`, donc indoublable ; le port l'est.
- [x] `ClaudeStoryRenderer::render()` :
  - `model` = `config('services.anthropic.model')` (`LLM_MODEL`, défaut `claude-opus-5`), `maxTokens` = `LLM_MAX_TOKENS` (8000).
  - `system` : un bloc texte avec `'cacheControl' => ['type' => 'ephemeral']`, contenu fixe (ci-dessous).
  - `messages` : un message `user` contenant, dans cet ordre : la question, le prénom et la forme d'adresse, le lexique (`terme → graphie`), puis le verbatim entre balises `<verbatim>`.
  - `outputConfig` : `['effort' => config('services.anthropic.effort'), 'format' => ['type' => 'json_schema', 'schema' => …]]` avec le schéma : `title` (string, ≤ 60 caractères), `text` (string), `themes` (array d'enum = valeurs de `QuestionTheme`), `proper_nouns` (array de string), `sensitive_flags` (array d'enum `health|religion|conflict|intimacy|money|other`), `additionalProperties: false`, tous requis.
  - Repli en cas de refus : **vérifier dans `vendor/anthropic-ai/sdk` si `$client->beta->messages->create()` accepte un paramètre `fallbacks`** ; si oui, appeler la version bêta avec `betas: ['server-side-fallback-2026-07-01']` et `fallbacks: 'default'` ; sinon appel standard. Dans tous les cas, si `$message->stopReason === 'refusal'`, retourner `FluideResult(refused: true)` avec `stopDetails->category` en métadonnée.
  - Lire le premier bloc `text`, `json_decode`, valider contre le schéma (`opis/json-schema` inutile : validation manuelle des clés et types), mapper.
  - Exceptions `Anthropic\Core\Exceptions\APIStatusException` : `rate_limit_error`/`overloaded_error` → relance du job (backoff) ; autres → échec journalisé.
- [x] **Prompt système (texte exact, versionné dans `resources/prompts/fluide-v1.txt`, snapshot testé) :**
  > Tu es l'assistant de mise au propre d'un service de livres de souvenirs familiaux. On te donne la transcription brute d'une personne âgée qui raconte un souvenir à l'oral, en français. Ta tâche : produire une version « fluide » lisible dans un livre, qui reste la parole de cette personne.
  > Règles absolues : tu conserves ses mots, ses tournures, son niveau de langue et l'ordre de son récit. Tu écris à la première personne, comme elle. Tu retires uniquement les hésitations, les répétitions involontaires, les faux départs et les tics de langage ; tu ajoutes la ponctuation et les paragraphes. Tu n'ajoutes aucun fait, aucun détail, aucune interprétation, aucun sentiment qui ne soit pas dit. Tu ne résumes pas : la longueur reste proche du verbatim, au plus 20 % plus courte. Tu ne changes pas les noms propres ; s'ils figurent dans le lexique fourni, tu utilises la graphie du lexique. Tu ne corriges pas les souvenirs, même s'ils te semblent inexacts. Tu ne t'adresses jamais au lecteur et tu n'écris aucun commentaire.
  > Tu proposes un titre court (au plus 60 caractères, sans guillemets, sans point final) tiré des mots de la personne. Tu identifies les thèmes parmi la liste fournie, les noms propres présents, et les sujets sensibles éventuels (santé, religion, conflit familial, intimité, argent, autre) pour que la famille soit prévenue avant impression.
  > Tu réponds uniquement au format JSON demandé.
- [x] `FakeStoryRenderer` : texte = verbatim avec majuscules en début de phrase, titre = 6 premiers mots.
- [x] `RenderFluide` (file `llm`, `tries 4`, `backoff [30, 120, 600, 1800]`) : vérifie le consentement `ai_rendering` (sinon émet `TranscriptionReady` et s'arrête), appelle le renderer, crée `Transcript(fluide, version 1, is_current, provider claude, metadata {model, usage.inputTokens, usage.outputTokens, cacheReadInputTokens, duration_ms, refused, sensitive_flags})`, pose `stories.title` si vide, `LexiconEntry` suggérés stockés dans `metadata.proper_nouns` (pas créés automatiquement), émet `TranscriptionReady`.
- [x] `config/services.php` : `anthropic => ['key', 'model', 'effort', 'max_tokens']`.
- [x] Tests verts.

### 6.5 Édition et lexique
- [x] `EditTranscript(Transcript $base, string $text, Model $editor): Transcript`.
- [x] `AddLexiconEntry(Project, string $term, ?string $replacement, Model $by)` ; `RemoveLexiconEntry`.
- [x] Les pages qui exposent ces actions arrivent aux blocs 07 (narrateur), 10 (Initiateur·rice) et 11 (support).

### 6.6 Banc d'essai ASR
- [x] `App\Support\Wer::compute(string $reference, string $hypothesis): float` (Levenshtein sur mots normalisés).
- [x] Commande `asr:bench {dir} {--providers=gladia,deepgram}` : pour chaque paire `x.(wav|mp3|m4a)` + `x.txt`, téléverse en tmp sur `r2` (préfixe `bench/`, supprimé à la fin), soumet à chaque fournisseur, calcule le WER, écrit `docs/spikes/asr-YYYY-MM-DD.md` (tableau par fichier et fournisseur, médiane, p90, durée moyenne de traitement, coût estimé).
- [ ] **Reste à faire (Phase 0A, hors code).** Le corpus (`tests/bench/asr/corpus/`, ignoré par git) est constitué en Phase 0A : au moins 10 enregistrements de personnes de 65 ans et plus, sur smartphone, avec transcription de référence relue. Ajouter 3 enregistrements téléphoniques si l'option D-9 démarre.
- [x] **Règle de choix** (à écrire dans `docs/spikes/asr.md`) : fournisseur par défaut = WER médian le plus bas ; si l'écart est ≤ 2 points, Gladia (hébergement UE).

### 6.7 Clôture
- [x] Annexe B, `04_VERSIONS.md` (anthropic-ai/sdk, laravel-ffmpeg), `.env.example` (ASR_*, ANTHROPIC_*, LLM_*).
- [x] `sail composer check`, `sail npm run check`, CI verts.
- [x] Commit `chore(bloc-06): terminé`, tag `bloc-06-done`.

## 7. Checkpoint démontrable

1. Enregistrer une histoire de 2 minutes via la page du bloc 04 avec `ASR_PROVIDER=gladia` (clé réelle) et `LLM_PROVIDER=claude` (clé réelle).
2. Suivre dans Horizon : `TranscodeRecording` → `SubmitTranscription` → webhook ou `PollTranscription` → `RenderFluide`. Durée totale < 10 minutes.
3. `sail artisan tinker` : la story a deux `transcripts` courants (`verbatim`, `fluide`), un `title`, des `themes` ; `recordings.original_path` inchangé ; `derived_mp3_path` lisible via `temporaryUrl`.
4. Tenter `DELETE FROM transcripts WHERE kind='verbatim'` → exception.
5. `sail artisan asr:bench tests/bench/asr/corpus` sur au moins 3 fichiers réels → tableau généré.

## 8. Critères de sortie

- [x] Le prompt système est versionné et snapshoté ; toute modification passe par un nouveau fichier `fluide-vN.txt` et une mise à jour du snapshot.
- [x] Aucun appel réseau dans les tests (`preventStrayRequests`).
- [x] Le rendu Fluide est marqué `provider = claude` et exposé plus tard avec la mention « Texte mis au propre par une IA, à partir de la voix de {Prénom} » (clé `family.story.ai_label`, utilisée au bloc 08).

## 9. Règle de décision par défaut

Si le SDK PHP ne propose pas `fallbacks`, ne pas l'émuler côté client : gérer `refusal` en conservant le verbatim, alerter le support, et noter dans `03_DECISIONS.md`. Si Gladia change son API, adapter l'adaptateur à la documentation officielle sans toucher à l'interface.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_

**2026-09-02 — Claude (agent) — code livré, checkpoint §7 partiellement exécutable.**

### Ce qui est démontré

- **§7.4 — Le verbatim ne se supprime pas.** `DELETE FROM transcripts WHERE kind='verbatim'` sur une histoire vivante lève `a verbatim transcript is never deleted while its story lives (story=…)`. Vérifié en base par `tests/Feature/Database/VerbatimNoDeleteTest.php`, qui éprouve aussi le cas autorisé : l'histoire passée à `deleted` laisse la ligne partir. La garde modèle rend la même erreur en français avant d'atteindre Postgres.
- **§7.2 — La chaîne complète, avec le fournisseur simulé.** `TranscodeRecording` → `SubmitTranscription` → webhook **ou** `PollTranscription` → `RenderFluide`, éprouvée de bout en bout par `tests/Feature/Jobs/TranscriptionPipelineTest.php` : soumission, stockage du verbatim, transition `transcribed`, mise au propre, relance après 30 secondes, trois tentatives puis échec notifié au support, et l'impossibilité de créer deux verbatims courants pour un même enregistrement.
- **§7.3 — Deux transcriptions courantes, un titre, des thèmes.** `RenderFluideTest` vérifie le rendu fluide courant, le titre posé s'il est vide et jamais remplacé s'il existe, les thèmes et signalements en métadonnées ; `TranscodeRecordingTest` vérifie que `original_path` ne bouge pas et que `derived_mp3_path` est écrit sur le stockage.
- **§7.5 — Le banc d'essai produit son tableau.** `AsrBenchTest` le fait tourner sur un corpus simulé de deux paires, vérifie le tableau par fichier et fournisseur, le WER médian, le p90, et **la suppression des objets téléversés** — un corpus de voix identifiables ne reste pas sur le stockage après la mesure.

### Ce qui attend un humain

- **§7.1 et §7.2 avec des clés réelles.** `ASR_PROVIDER=gladia` et `LLM_PROVIDER=claude` demandent une clé Gladia et une clé Anthropic, et un vrai enregistrement de deux minutes. Les adaptateurs `GladiaProvider` et `DeepgramProvider` sont éprouvés sur des réponses figées (`tests/Fixtures/asr/`), jamais sur le réseau (critère §8). Ce qui reste à voir en vrai : les noms exacts des champs de l'API Gladia au jour de l'appel, la latence réelle sous les 10 minutes, et le comportement du webhook signé derrière un tunnel.
- **§7.5 sur le corpus réel.** Le corpus de Phase 0A — dix enregistrements de personnes de 65 ans et plus, sur leur téléphone, avec transcriptions de référence relues — n'existe pas encore. La **règle de choix** est écrite avant les chiffres, dans `docs/spikes/asr.md` : WER médian le plus bas, et Gladia gagne tout écart inférieur ou égal à 2 points, pour l'hébergement UE. Écrite après, elle aurait été un habillage du résultat.
- **La qualité du rendu Fluide sur de vraies voix.** Le prompt est figé et snapshoté (`resources/prompts/fluide-v1.txt`, `PROMPT_VERSION = 'fluide-v1'`), mais aucun test ne peut dire s'il conserve bien la parole d'une personne de 82 ans. C'est une lecture humaine, sur au moins cinq histoires réelles, avant le pilote. Toute retouche passe par un `fluide-v2.txt` et un nouveau snapshot, jamais par une modification en place.

### Écarts consignés

- **T-68** — le SDK n'expose `fallbacks` que sur l'API bêta : la règle §9 s'applique, aucun repli côté client.
- **T-69** — `pbmedia/laravel-ffmpeg` n'est pas installé ; deux appels `Process`, éprouvables par `Process::fake()`.
- **T-70** — `AnthropicClientFactory` remplacé par le port `AnthropicMessages` : `MessagesService` est `final`.
- **T-71** — rappels ASR signés en HMAC, et un secret vide lève au lieu de signer.
- **T-72** — un transcript courant *par espèce*, pas un par histoire.
- **T-73** — le transcodage entre dans la chaîne de `CompleteRecording` ; les tests du bloc 04 passent à `assertPushedWithChain`.
- **T-74** — les noms propres suggérés ne créent pas d'entrées de lexique.
- **T-75** — le nonce est passé à Inertia : clôt la violation CSP restée ouverte en T-62. Les pages à jeton ne rapportent plus aucune violation.
- **T-76** — `E2ELinksSeeder` vide le cache, sans quoi la limite d'une demande de lien par heure rendait la suite non rejouable.

### Portail qualité

`sail composer check` vert (Pint, Larastan niveau 8, **522 tests**, 2 557 assertions), `sail npm run check` vert, `tsc --noEmit` vert, Vitest **76 tests** verts, Playwright **17 tests** verts.
