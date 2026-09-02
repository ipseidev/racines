# Registre des décisions techniques

Format : décision, pourquoi, alternatives écartées, réversibilité. Numérotation `T-nn`. Les décisions produit (D-7, D-8, D-9) restent dans le dossier produit, doc 05 R-12.

## Décisions fondatrices (2 septembre 2026)

**T-01 — Stack : Laravel + Inertia (React, TypeScript) + Postgres.** Pourquoi : maîtrise de l'équipe, écosystème Spatie/Filament mature, Inertia évite une API séparée tout en gardant React pour les interfaces riches (recorder, lecteur). Écartées : Next.js (deuxième langage, hébergement UE moins simple), Livewire pour le front utilisateur (moins adapté au recorder). Réversibilité : faible, c'est le socle.

**T-02 — Hébergement : serveur DigitalOcean existant piloté par Forge, région européenne obligatoire.** Pourquoi : déjà en place, Forge gère PHP, Redis, Horizon, scheduler, SSL. Condition : le droplet doit être en `ams3` ou `fra1` ; si ce n'est pas le cas, créer un droplet UE et y rattacher le site avant le bloc 16. Écartées : Laravel Cloud (AWS, moins de contrôle sur la région et le coût), Scaleway (pas d'outillage existant). Réversibilité : moyenne, Forge est portable vers un autre VPS.

**T-03 — Base de données : Postgres managé DigitalOcean dans la même région, sauvegardes quotidiennes et PITR activés.** Pourquoi : le dossier exige une restauration testée et un RPO borné ; le managé donne le PITR sans opération. Écartée : Postgres installé par Forge sur le droplet (moins cher, mais restauration artisanale). Réversibilité : élevée.

**T-04 — Stockage objet : Cloudflare R2, buckets créés avec juridiction UE, endpoint `*.eu.r2.cloudflarestorage.com`.** Pourquoi : choix de l'équipe, S3-compatible, pas de frais de sortie. Point de vigilance : Cloudflare est une société américaine ; la restriction de juridiction, le DPA et une mention dans l'AIPD sont obligatoires (bloc 16). Alternative sans changement de code : Scaleway Object Storage (même driver S3). Trois buckets : `media`, `media-replica`, `backups`, chacun avec un jeton d'accès distinct. Réversibilité : élevée.

**T-05 — Admin interne : Filament, réservé au back-office. Interfaces utilisateur : React custom.** Pourquoi : Filament accélère le support ; jugé trop brut pour des seniors et des acheteurs. Réversibilité : élevée.

**T-06 — Email : Resend (transport Laravel officiel `resend/resend-laravel`). SMS : Twilio via le SDK officiel `twilio/sdk` derrière l'interface `SmsSender` (pas de package de canal tiers, pour garder un seul point d'abstraction).** Pourquoi : choix de l'équipe. Twilio supporte l'expéditeur alphanumérique en France pour les messages sortants sans réponse ; un numéro de repli reste configuré. Réversibilité : élevée, interfaces `SmsSender` et mailer Laravel.

**T-07 — Rendu Fluide : Claude via le SDK PHP officiel `anthropic-ai/sdk`, modèle `claude-opus-5`, `outputConfig.effort = medium`, sortie structurée par schéma JSON, prompt système mis en cache.** Pourquoi : qualité de réécriture en français, SDK officiel, sortie structurée qui évite tout parsing fragile. Le modèle et l'effort sont des variables d'environnement. Le repli serveur en cas de refus (`fallbacks`) est activé par défaut sur l'appel bêta ; si le SDK installé ne l'expose pas, on gère `stopReason = refusal` en conservant le verbatim seul. Écartée : Mistral (souveraineté, mais qualité non évaluée par l'équipe). Réversibilité : élevée, interface `StoryRenderer`.

**T-08 — Transcription : Gladia comme premier adaptateur, Deepgram comme second, décision par banc d'essai WER sur corpus de voix âgées au bloc 06.** Pourquoi : Gladia est française, hébergée en UE, bonne en français, avec vocabulaire personnalisé et horodatage des mots ; Deepgram sert de référence de qualité. Voxtral (Mistral) non retenu faute de connaissance de l'équipe ; peut devenir un troisième adaptateur. Réversibilité : élevée, interface `TranscriptionProvider`.

**T-09 — Capture audio : `MediaRecorder` natif, tranches de 5 s stockées dans IndexedDB, upload multipart résumable vers R2 par URLs présignées (parts de 5 Mio).** Pourquoi : aucune dépendance lourde, reprise après rechargement, l'objet n'est confirmé qu'après `CompleteMultipartUpload` et `HeadObject`. Écartées : tus (serveur supplémentaire), upload via le serveur PHP (double transfert). Réversibilité : moyenne.

**T-10 — Traitement audio : ffmpeg via `pbmedia/laravel-ffmpeg` ; l'original n'est jamais réencodé, un MP3 128 kb/s est dérivé pour la lecture et l'export.** Réversibilité : élevée.

**T-11 — PDF du livre : `spatie/browsershot` (Chromium) avec Paged.js pour la mise en page, une passe Ghostscript vers PDF/X-1a quand l'imprimeur sera connu.** Pourquoi : HTML/CSS testable, un seul moteur de rendu, pas de service supplémentaire. Écartées : Prince (payant), LaTeX/Typst (courbe d'apprentissage), Gotenberg (Docker absent sur Forge). Réversibilité : moyenne.

**T-12 — QR : `endroid/qr-code`, URL `https://{LINKS_DOMAIN}/q/{token}`.** Réversibilité : élevée.

**T-13 — Machine d'états : `spatie/laravel-model-states` avec classes de transition explicites et gardes.** Pourquoi : transitions déclaratives, testables une à une, impossibilité structurelle d'une validation implicite. Réversibilité : moyenne.

**T-14 — Réglages : `spatie/laravel-settings` (`BrandSettings`, `PilotSettings`) édités dans Filament ; `config/brand.php` porte les défauts.** Pourquoi : le nom, le domaine et les couleurs doivent changer sans déploiement. Réversibilité : élevée.

**T-15 — Feature flags : Laravel Pennant, portée projet.** Pourquoi : les deux variantes de validation et les tests de prix du dossier sont des flags par cohorte. Réversibilité : élevée.

**T-16 — Paiement : Stripe Checkout (page hébergée) + Laravel Cashier pour clients, webhooks et remboursements.** Pourquoi : conformité PCI minimale, prise en charge SCA, remboursements outillés. Réversibilité : moyenne.

**T-17 — Authentification : Fortify pour les Initiateur·rice·s (email + mot de passe, vérification d'email, 2FA optionnelle) ; Filament avec MFA obligatoire pour le back-office ; jetons porteurs et OTP pour narrateurs et proches.** Réversibilité : moyenne.

**T-18 — Journal d'audit : table `audit_logs` maison, append-only par trigger Postgres, chaîne de hachage `previous_hash → hash`.** Pourquoi : « journalisation inviolable de toutes les actions support (lecture comprise) » du doc 04 §12 ; un package générique ne bloque pas les `UPDATE`. Réversibilité : faible par nature.

**T-19 — Analytics : PostHog cloud UE, capture serveur par défaut, front uniquement hors pages à jeton, URL nettoyées.** Écartées : Plausible (pas de funnels par utilisateur), Matomo auto-hébergé (opération). Réversibilité : élevée.

**T-20 — Erreurs : Flare. Disponibilité et page de statut : Oh Dear.** Pourquoi : hébergés en UE, intégration Laravel native. Réversibilité : élevée.

**T-21 — i18n : fichiers `lang/fr/*.php` partagés au front par prop Inertia, hook `useT()` maison, test d'existence des clés.** Pourquoi : zéro dépendance, français seul au MVP, mais aucune chaîne en dur pour permettre EN/ES en Phase 2. Réversibilité : élevée.

**T-22 — Tests : Pest (parallèle), Larastan niveau 8, Vitest + React Testing Library, Playwright (Chromium en CI, matrice d'appareils réels manuelle), `@axe-core/playwright`.** Réversibilité : faible, c'est le contrat de qualité.

**T-23 — Identifiants : UUID ordonnés (`HasUuids`) pour tout modèle exposé ; jetons de 32 octets aléatoires encodés base64url, stockés hachés SHA-256.** Réversibilité : faible.

**T-24 — Environnement local : Laravel Sail avec `pgsql`, `redis`, `mailpit`, `minio` (émule R2 en local), `clamav` à partir du bloc 12 ; image Sail publiée et étendue avec ffmpeg et Chromium.** Réversibilité : élevée.

**T-25 — Organisation du dépôt : la racine est le projet Laravel ; les documents produit vont dans `docs/dossier/`, les captures dans `docs/reference/` (ignorées par git).** Pourquoi : Forge déploie la racine ; les documents restent versionnés avec le code. Réversibilité : élevée.

**T-26 — Démo « essayez en 60 secondes » : enregistrement local seulement, lecture immédiate, aucun envoi au serveur.** Pourquoi : zéro donnée personnelle collectée sans achat, zéro coût de stockage, et le test d'ergonomie porte sur le micro et le bouton, pas sur l'upload. Réversibilité : élevée.

**T-27 — Limites d'enregistrement : alerte douce à 10 min, arrêt à 20 min, taille maximale 200 Mo, formats acceptés `audio/webm`, `audio/mp4`, `audio/ogg`, `audio/mpeg`, `audio/wav`.** Pourquoi : le dossier ne fixe pas de durée par histoire ; le fair use mensuel (60 min) borne le total. Paramètres dans `config/product.php`. Réversibilité : élevée.

**T-28 — Heure d'envoi par défaut : cadeaux à 09:00 Europe/Paris ; prompts au créneau choisi par le narrateur (matin 09:00, après-midi 14:00, soir 18:00) le jour choisi ; tick du moteur toutes les heures à la minute 07 ; dispatch des prompts toutes les 5 minutes.** Réversibilité : élevée.

## Décisions prises en cours de route

Ajouter ici, par ordre chronologique, toute décision prise faute d'information pendant l'exécution d'un bloc. Format : `T-nn — date — bloc — décision — pourquoi — à revalider par l'humain : oui/non`.

_(vide)_
