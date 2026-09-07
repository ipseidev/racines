# Ce qui dépend de toi

**Créé le 3 septembre 2026.** Ce fichier liste tout ce que le code ne peut pas fabriquer : des comptes, des clés, des appareils, des voix, et des décisions. Il est la contrepartie de `00_INDEX.md` : celui-ci dit ce qu'il reste à coder, celui-là ce qu'il reste à **réunir**.

Chaque bloc arrêté par une de ces lignes le dit en tête de son fichier. Quand une ligne est cochée ici, le bloc correspondant peut aller jusqu'à son tag.

## Comment s'en servir

1. Les sections sont dans l'ordre où elles débloquent du travail. La §1 débloque quatre blocs déjà écrits ; la §5 n'est utile que dans plusieurs semaines.
2. Chaque entrée dit **où l'obtenir**, **où le mettre**, et **comment vérifier** que ça marche — la commande de vérification est là pour que tu n'aies pas à me demander.
3. Coche dans le tableau de la §6 au fur et à mesure. C'est le seul endroit à tenir à jour.

> **Les secrets ne passent jamais par une conversation.** Ni ici, ni dans un message, ni dans un commit. Ils vont dans `.env` sur ta machine, et plus tard dans les variables d'environnement de Forge. `.env` est ignoré par git, `.env.example` ne contient que des valeurs vides — c'est volontaire, et ça doit rester vrai.
>
> Pour éditer : `! nano .env` depuis la conversation, ou ton éditeur habituel.

## La feuille des vérifications

Deux checkpoints attendent encore quelqu'un devant un navigateur — les blocs 11 et 12 ; les autres sont joués. Aucun ne se joue sans un
lien à jeton de quarante-trois caractères, un téléphone connu ou un code à six
chiffres — et rien de tout cela ne se retient.

```
sail artisan demo:liens            # la feuille entière
sail artisan demo:liens --bloc=07  # un seul bloc
```

La commande imprime les URL réelles du décor local, les comptes, les codes, et
marque en rouge les étapes qui attendent encore quelque chose de ce document.
Elle lit les valeurs dans `E2ELinksSeeder` — elle ne peut pas mentir sans qu'un
test échoue. Si un lien répond 404, le décor n'est pas semé : `sail artisan
migrate:fresh --seed` (qui **efface** la base locale).

---

## §0. Ce qui dépend du nom et du domaine — et ce qui n'en dépend pas

Le nom de marque et le domaine sont arrêtés depuis le 4 septembre 2026 : **Narrae**, sur **narrae.fr** (T-143). Ils ne bloquaient que deux lignes de ce document, qui peuvent démarrer :

| Ligne | Ce que le nom et le domaine permettent maintenant |
|---|---|
| **Resend** | Vérifier **narrae.fr** comme domaine d'envoi : SPF, DKIM, DMARC posés chez le registrar. Cela se fait en heures. |
| **Twilio** | ~~Enregistrer l'expéditeur alphanumérique **NARRAE** en France~~ — **rien à faire, réglé le 2026-09-07** : « Narrae » ne figure pas sur la liste des marques protégées de l'AF2M, et Twilio n'exige l'enregistrement que pour celles qui y figurent. Le nom part tel quel (T-211). |

Tout le reste est indépendant du nom, et une bonne partie est déjà branchée :

- **Le nom lui-même n'est nulle part dans le code** (bloc 01, `BrandSettings`) : la migration de réglages du 4 septembre l'a posé en base, et l'administration permet de le retoucher. Un test échoue si quelqu'un l'écrit en dur.
- **En local, tu n'as besoin ni de Resend ni de Twilio.** Les courriels partent dans Mailpit (`http://localhost:8027`) et les SMS dans le journal (`SMS_PROVIDER=log`). **Toute la boucle hebdomadaire est jouable comme ça** — c'est ainsi que les checkpoints des blocs 05, 07, 08 et 09 sont prévus.
- **Anthropic ne demande rien d'autre que sa clé.** C'est un appel synchrone : pas de rappel, pas de tunnel, pas de domaine. Le rendu « Fluide » est donc éprouvable **tout de suite** — et c'est le plus gros risque produit après l'enregistreur, parce que c'est là que se joue « l'IA range, elle n'invente pas ».
- **Gladia demande une URL joignable** pour son rappel, mais pas un domaine : `cloudflared tunnel --url http://localhost:8001` suffit (§1.6).
- **Stripe en mode test ne demande pas de domaine** : il te manque seulement les cinq prix à créer (§1.8).

---

## §1. Ce qui débloque du travail déjà écrit

Trois blocs sont codés, testés et poussés, mais ne peuvent pas être tagués sans ça : **04** (téléphones réels), **05** (Twilio, Resend : le nom est arrêté, restent le domaine d'envoi à vérifier et l'expéditeur à enregistrer) et **06** (clés, corpus de voix). Le **10** est fermé depuis le 2026-09-05 : le compte Stripe de test est configuré, restent les prix en mode live et une clé restreinte avant le go-live. Le **11** et le **12** n'attendent que trente-cinq minutes de ton temps.

### 1.1 Clé Anthropic — débloque le bloc 06

Le rendu « Fluide » : la transcription brute mise au propre en un texte lisible dans un livre.

- **Où** : [console.anthropic.com](https://console.anthropic.com) → API Keys. Prévois un plafond de dépense sur le compte, par prudence.
- **Où le mettre** : `ANTHROPIC_API_KEY=` dans `.env`, puis `LLM_PROVIDER=claude`.
- **Le modèle est déjà choisi** : `claude-opus-5`, effort `medium`, 8 000 jetons de sortie. Rien à régler.
- **Vérifier** :
  ```bash
  sail artisan tinker --execute="dd(config('services.anthropic.key') !== '');"
  ```
- **Coût attendu** : quelques centimes par histoire. Le prompt système est mis en cache, donc il ne se paie qu'une fois par fenêtre.

### 1.2 Clé Gladia — débloque le bloc 06

La transcription. **Gladia est le choix par défaut pour une seule raison : l'hébergement UE** (décision T-07). C'est la voix de personnes âgées qui part chez ce prestataire, et la juridiction pèse plus qu'une décimale de précision.

- **Où** : [gladia.io](https://www.gladia.io) → un compte, puis la clé API. Un plan d'essai suffit pour le banc d'essai.
- **Où le mettre** : `GLADIA_API_KEY=` dans `.env`, puis `ASR_PROVIDER=gladia`.
- **Deepgram est optionnel** : il ne sert qu'à **comparer** sur le banc d'essai (`DEEPGRAM_API_KEY=`). Sans lui, le banc tourne quand même, il n'a juste rien à opposer à Gladia. Utile, pas bloquant.
- **Vérifier** : voir §1.3, la clé seule ne prouve rien.

### 1.3 Un secret de rappel ASR — débloque le bloc 06

Les fournisseurs de transcription rappellent le serveur quand ils ont fini. On signe nous-mêmes l'URL qu'on leur donne, sinon n'importe qui pourrait injecter une fausse transcription dans l'histoire de quelqu'un (T-71).

- **Le générer** :
  ```bash
  echo "ASR_CALLBACK_SECRET=base64:$(openssl rand -base64 32)"
  ```
  Colle la ligne dans `.env`.
- **Attention** : s'il est vide, la construction d'une URL de rappel **lève une erreur** au lieu de signer avec une chaîne vide. C'est voulu — un secret manquant doit casser bruyamment.

### 1.4 Le corpus de voix — débloque le bloc 06 (le plus long)

**C'est la pièce à commencer aujourd'hui**, parce qu'elle demande des gens et du temps, pas une carte bancaire.

Le banc d'essai ASR mesure le taux d'erreur de mots (WER) sur des voix réelles. Sans corpus, on ne sait pas si Gladia comprend une dame de 82 ans qui parle vite dans une cuisine — et c'est exactement la question.

**Ce qu'il faut réunir dans `tests/bench/asr/corpus/`** (le dossier est ignoré par git, et ce n'est pas un détail : ce sont des voix de personnes identifiables) :

| Exigence | Détail |
|---|---|
| **Au moins 10 enregistrements** | En dessous, la médiane ne veut rien dire. |
| **Des personnes de 65 ans et plus** | C'est le public du produit, pas un échantillon générique. |
| **Sur leur propre téléphone** | Pas en studio, pas au micro-casque. Le bruit de fond fait partie de la mesure. |
| **Au moins deux avec un fond sonore réaliste** | Télévision, cuisine, rue. |
| **Au moins un accent régional marqué** | |
| **1 à 3 minutes chacun** | La longueur d'une vraie réponse. |
| **+ 3 enregistrements téléphoniques** | Seulement si l'option téléphone D-9 démarre : la bande passante du téléphone dégrade le WER, et un autre fournisseur peut gagner sur ce sous-corpus. |

**Le nommage compte** — une paire par enregistrement :

```
tests/bench/asr/corpus/
├── 01-marie-crepes.mp3     ← l'audio
├── 01-marie-crepes.txt     ← la transcription de référence
├── 02-…
```

**La référence est relue à l'oreille, pas générée.** Une référence produite par un ASR mesurerait la ressemblance entre deux ASR, pas la justesse. Écris ce que tu entends, mot pour mot, hésitations comprises.

**Le consentement** : ces personnes doivent savoir que leur voix part chez un prestataire de transcription pour une mesure technique, et que l'enregistrement n'ira pas dans un livre. Un accord oral enregistré au début du fichier suffit pour la Phase 0A, mais il doit exister.

- **Vérifier, dès 3 fichiers** :
  ```bash
  sail artisan asr:bench tests/bench/asr/corpus --providers=gladia
  ```
  Ça écrit `docs/spikes/asr-AAAA-MM-JJ.md` avec un tableau par fichier. **Tu peux commencer par 3 fichiers de ta propre voix** juste pour prouver que la chaîne technique répond — c'est un jalon utile, à ne pas confondre avec la mesure.

### 1.5 Une lecture humaine du rendu Fluide — débloque le bloc 06

Aucun test ne peut dire si le texte mis au propre reste **la parole de la personne**. C'est une lecture, pas une assertion.

**Fait à moitié, le 3 septembre 2026.** Cinq mots à mot **écrits** ont été lus ensemble
(`docs/corpus/`, commande `sail artisan fluide:try`), chacun visant une trahison
différente. Rien d'inventé dans aucun des cinq, mais deux défauts trouvés — une négation
orale rétablie une fois sur trois, et un « conflit familial » signalé dans un souvenir
qui n'en contenait aucun. `fluide-v2` corrige les deux (T-126), et la relecture des cinq
textes le confirme sans régression.

**Ce qui reste dû.** Un mot à mot écrit est propre ; un mot à mot réel sort d'une
transcription automatique, avec ses mots mal entendus, ses phrases coupées et ses noms
propres massacrés. C'est là que le rendu peut encore trahir, et aucun texte écrit à la
main ne le simule.

- **Quoi** : lire le Fluide de 5 histoires réelles à côté de leur mot à mot. Le corpus de
  voix du §1.4 fournit la matière : les deux tâches se font dans la même séance.
- **Ce qu'on cherche** : des mots ajoutés qui n'ont pas été dits, un niveau de langue
  rehaussé, un souvenir « corrigé », un ton qui n'est pas le sien — et, propre au mot à
  mot réel, un mot mal entendu que le rendu « répare » en inventant du sens.
- **Si ça ne va pas** : on ne retouche pas le prompt en place. On écrit
  `resources/prompts/fluide-v3.txt` et on met le snapshot à jour — c'est ce qui rend un
  rendu réexplicable des mois plus tard.

### 1.6 Deux téléphones réels et un accès HTTPS — débloque le bloc 04

`getUserMedia` — l'accès au micro — **exige une connexion sécurisée**. Le spike navigateur ne peut pas se jouer sur `http://localhost` depuis un téléphone.

**Les appareils.** `docs/spikes/navigateur.md` attend cinq créneaux :

| # | Appareil | Pourquoi celui-là |
|---|---|---|
| A | iPhone, iOS courant | Safari est le plus contraignant sur `MediaRecorder` |
| B | iPhone, iOS N-1 | Les seniors ne mettent pas à jour |
| C | Android, version courante | Chrome |
| D | Android, version N-1 | |
| E | **Samsung Internet** | **C'est celui qui casse le plus souvent**, et il est par défaut sur des millions de téléphones vendus en France |

Deux appareils (A et C) suffisent pour taguer le bloc ; les cinq sont ce qu'il faut pour avoir confiance.

**L'accès HTTPS.** Le plus simple, sans rien déployer :

```bash
# Cloudflare Tunnel, gratuit, pas de compte requis pour un essai
cloudflared tunnel --url http://localhost:8001
```

Il te rend une URL `https://…trycloudflare.com`. Deux réglages à faire ensuite dans `.env` :

```
APP_URL=https://ton-tunnel.trycloudflare.com
LINKS_DOMAIN=ton-tunnel.trycloudflare.com
```

puis `sail artisan config:clear`. **Dis-le moi quand tu en es là** : le tunnel change l'origine, donc la politique de contenu et les règles CORS de MinIO doivent la connaître, et c'est deux lignes que je préfère poser moi-même.

### 1.7 Twilio et Resend — débloque le bloc 05, **le nom est arrêté**

Les questions hebdomadaires partent par SMS et par courriel. Ce sont les **deux seules lignes** de ce document qui dépendaient du nom de marque et du domaine, arrêtés le 4 septembre 2026 (Narrae, narrae.fr, T-143) : Resend veut le domaine d'envoi narrae.fr vérifié ; côté Twilio, l'expéditeur alphanumérique « NARRAE » **ne demande aucune démarche** — réponse du 7 septembre 2026, « Narrae » n'est pas sur la liste protégée de l'AF2M (T-211). Il ne reste donc que Resend. En attendant, la boucle complète se joue en local avec Mailpit et le journal SMS, et c'est ainsi que les checkpoints sont prévus.

**Twilio** ([console.twilio.com](https://console.twilio.com)) :

- `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN` : sur la page d'accueil de la console.
- `TWILIO_FROM` : un numéro. Sur un compte d'essai, il faut **vérifier le numéro destinataire** avant de pouvoir lui écrire.
- ✅ **L'expéditeur alphanumérique** — que le SMS arrive au nom de la marque plutôt que d'un numéro — **ne demande aucun enregistrement** pour « Narrae » : la liste protégée de l'AF2M ne le porte pas, et Twilio n'exige la démarche que pour les marques qui y figurent (ticket 29391714, réponse du 2026-09-07, T-211). Le nom suffit dans `from`, ce que `TwilioSmsSender` fait déjà pour la France, la Belgique, la Suisse et le Luxembourg ; `TWILIO_FROM` reste le repli **hors** de ces quatre pays et doit donc porter un numéro. Deux réserves : sur un compte d'essai l'alphanumérique n'est en général pas ouvert, et **le nom n'est réservé à personne** — quelqu'un d'autre peut signer un SMS « Narrae » (ligne 9bis).

**Resend** ([resend.com](https://resend.com)) :

- `RESEND_API_KEY` : dans le tableau de bord.
- **Un domaine d'envoi vérifié** : SPF, DKIM et DMARC à poser chez ton registrar. Sans ça les courriels partent en indésirables, et un courriel de relance en indésirable est une relance qui n'existe pas.
- `RESEND_WEBHOOK_SECRET` : en créant le webhook, avec l'URL `https://narrae.fr/webhooks/resend` (ou l'URL du tunnel en local). C'est lui qui fait passer un message en `delivered` — donc qui permet au moteur de complétion (bloc 09) de distinguer « lien non ouvert » de « courriel jamais reçu ». La différence entre relancer un narrateur et lui adresser un reproche injuste.
- **Vérifier** : `MAIL_MAILER=resend`, puis déclencher un envoi et regarder le tableau de bord Resend.

### 1.8 Stripe — débloque le bloc 10

Le tunnel d'achat est écrit, testé et poussé. Ce qui manque, c'est un compte : sans identifiants de prix, le clic sur « Payer » lève une erreur — et c'est voulu, plutôt que d'inventer un montant.

**Compte de test** ([dashboard.stripe.com](https://dashboard.stripe.com), interrupteur « mode test » en haut à droite). Pas besoin de compléter le profil de l'entreprise : le mode test fonctionne sans.

**Cinq prix à créer**, chacun un produit avec un prix **unique** (`one-time`), en euros :

| Produit | Prix | Variable `.env` |
|---|---|---|
| Le livre relié et l'année de questions | 89,00 € | `STRIPE_PRICE_PILOT` (le nom de la variable reste, T-136) |
| Prévente — variante A | 99,00 € | `STRIPE_PRICE_PREVENTE_99` |
| Prévente — variante B | 129,00 € | `STRIPE_PRICE_PREVENTE_129` |
| Exemplaire supplémentaire | 45,00 € `[à confirmer avec le devis d'imprimeur]` | `STRIPE_PRICE_EXTRA_COPY` |
| Livre numérique | 25,00 € (affiché à côté d'un prix barré de 45,00 €) | `STRIPE_PRICE_EBOOK` |
| Enregistrement par téléphone | 25,00 € | `STRIPE_PRICE_PHONE_OPTION` |

⚠️ Copier l'identifiant du **prix** (`price_…`), pas celui du produit (`prod_…`). C'est l'erreur la plus fréquente, et elle se voit seulement au moment du paiement.

**Un coupon**, pour la réduction de bienvenue de la page d'accueil (T-141) : Produits → Coupons → **pourcentage, 10 %** (le même que le réglage « Réduction, en pour cent de la commande » du pilote dans l'administration), sans durée côté Stripe, ce sont nos codes qui portent la fin de validité (un an), sans limite de rachats. Copier l'identifiant du coupon dans `STRIPE_COUPON_WELCOME`. Ne pas créer de « code promotionnel » Stripe : les codes sont les nôtres, un par adresse, et c'est nous qui les vérifions avant d'envoyer le coupon. Sans lui, un acheteur qui a posé un code voit son paiement refusé avec une erreur claire, plutôt qu'encaissé plein tarif.

**Deux clés** (Développeurs → Clés d'API) : la publiable dans `STRIPE_KEY`, la secrète dans `STRIPE_SECRET`.

**La CLI, pour le webhook en local** :

```bash
brew install stripe/stripe-cli/stripe
stripe login
stripe listen --forward-to http://localhost:8001/stripe/webhook
```

La commande affiche un secret `whsec_…` → `STRIPE_WEBHOOK_SECRET`. Il **change à chaque `stripe listen`**, donc à chaque session de test. Sans lui, la vérification de signature n'est pas installée du tout.

**Pourquoi deux prix de prévente et non un produit à deux prix** : le drapeau `prevente-price` affecte chaque visiteur à l'un des deux, par un cookie anonyme, et l'y garde quatre-vingt-dix jours. C'est le test de demande de R-3. Un prix qui change entre la découverte et le paiement fait fuir.

Tout le mode opératoire — jouer un achat, rejouer un événement, diagnostiquer un paiement qui se comporte mal, rembourser, passer en live — est dans [`docs/runbooks/stripe.md`](../runbooks/stripe.md).

**Ce qui n'est pas bloqué par Stripe** : l'opt-in du narrateur, l'espace Initiateur·rice, les pages publiques et légales. Ils sont jouables en local dès maintenant, et ils sont dans le §2.

---

## §2. Ce qui ne demande que ton temps

**Rien à acheter, rien à créer.** Il reste **deux** checkpoints à dérouler en local — les blocs 11 et 12. Les blocs 07, 08, 09 et 10 sont joués et tagués.

- **Bloc 07** — validation, visibilité, retraits. ~30 minutes.
- ~~**Bloc 08** — écoute famille et réactions.~~ **Fait le 2026-09-05**, sur un vrai iPhone.
- ~~**Bloc 09** — moteur de complétion.~~ **Fait le 2026-09-05.** Le préalable ne se force plus à la main : `sail artisan demo:moteur` arme les trois signaux, dit ce qu'il a armé, et se rejoue (T-153).
- **Bloc 12** — les photos. ~15 minutes, et deux préalables gratuits : `sail up -d clamav` une première fois (deux à trois minutes, un demi-gigaoctet de signatures, gardé ensuite), puis une **photo prise avec ton iPhone** — c'est le seul moyen d'éprouver la conversion HEIC, qu'aucun outil de cette image ne sait fabriquer. Vérifie avec `exiftool` sur l'original stocké qu'il ne reste aucune coordonnée GPS : c'est le point le plus important du bloc.
- ~~**Bloc 13, le livre**~~ — **en pause depuis le 2026-09-06**, sur décision : livrable M+12. Le PDF et le QR ont été vérifiés sur un rendu réel et fonctionnent. Quatre points de vérification restent, listés en §10 de la feuille du bloc.

- ~~**Bloc 11** — le back-office.~~ **Fait le 2026-09-06**, les cinq points, remboursement partiel compris. Cinq défauts trouvés : T-182, T-186, T-188, T-189, T-195. Le second facteur du panneau est désormais éteint hors production (`ADMIN_2FA`, ignoré en production) ; pour rejouer le point 1, remettre la clé à `true`.
- ~~**Bloc 10**~~ — **fait le 2026-09-05**, avec un vrai paiement de test. Trois défauts de paiement trouvés et corrigés (T-167 à T-169).

Un piège vérifié : `RedactTokens` masque aussi **les codes à six chiffres** dans les journaux, donc `SMS_PROVIDER=log` ne te donnera pas le code OTP. Le chemin local passe par un narrateur dont le canal préféré est le courriel — le code arrive alors en clair dans Mailpit (`http://localhost:8027`).

**Dis-moi quand tu veux les jouer** : j'écris les deux procédures en liste à cocher, commande par commande, avec le décor semé d'avance. Tu déroules, je note le résultat dans les notes de checkpoint et je pose les tags.

---

## §3. Cloudflare R2 — à prévoir avant le bloc 16, utile plus tôt

Le stockage des enregistrements. En local, MinIO le remplace et **tu n'as rien à faire pour l'instant** ; mais le compte est à ouvrir avant le déploiement, et l'ouvrir tôt permet de tester sur du vrai.

- **Trois compartiments**, et pas un : `media`, `media-replica`, `backups`. La réplication est ce qui protège de la perte d'un audio confirmé, et un compartiment unique ne protège de rien.
- **Juridiction UE à la création** : c'est un choix irréversible sur R2, et c'est une exigence non négociable du dossier.
- **Un jeton limité à ces trois compartiments**, jamais un jeton de compte.
- **La règle CORS à reporter dans la console** (MinIO ne sait pas la poser par API, T-58). Elle est dans `docker/minio/cors.json` ; l'exposition de l'`ETag` en est la partie qu'on oublie et sans laquelle les envois échouent en silence.
- **Variables** : `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_ENDPOINT` (`https://<compte>.eu.r2.cloudflarestorage.com` — noter le `.eu.`), `R2_PUBLIC_ENDPOINT` (identique en production).

---

## §4. Ce qui arrive avec les blocs suivants

Rien à faire maintenant. C'est ici pour que tu puisses grouper les démarches longues.

| Quand | Quoi | Pourquoi maintenant le savoir |
|---|---|---|
| ~~Bloc 13~~ | ~~Fichiers de polices OFL~~ | **Fait** : Fraunces et Inter sont déjà servis localement depuis `public/fonts/`, sous licence SIL OFL qui autorise l'intégration au document imprimé. Le gabarit pointera dessus, et un test échouera si le HTML du livre référence un domaine extérieur |
| Bloc 13 | **Un devis d'imprimeur** | Le format est arrêté (16 × 24 cm, T-179) et la commande se passera **à la main** au pilote (`ManualPrintProvider`) : le devis n'est donc plus bloquant pour construire. Il reste dû pour le passage en CMJN, le prix de l'exemplaire supplémentaire, et toute promesse de délai — aucune n'apparaît dans l'interface avant lui |
| Bloc 15 | **PostHog, instance UE** (`POSTHOG_KEY`, `POSTHOG_HOST=https://eu.i.posthog.com`) | L'hôte UE est obligatoire |
| Bloc 16 | **DigitalOcean** (région européenne : AMS ou FRA), **Forge**, **Flare**, **Oh Dear** | Le Postgres managé doit être créé en région européenne dès le départ |
| Bloc 17 | **Le socle juridique validé par un conseil** : consentements, LIA, AIPD proportionnée, CGV, politique de confidentialité, contrat pilote, information sur l'enregistrement des appels | `golive:check` **refuse de passer** sans `legal_validated_at`. C'est la démarche la plus longue de toutes : à lancer bien avant le bloc 17 |
| Bloc 17 | **Les DPA signés** avec chaque sous-traitant : Cloudflare, DigitalOcean, Twilio, Resend, Anthropic, Gladia, Deepgram, Stripe, PostHog, Flare, Oh Dear | Idem : la liste est longue, chaque signature prend des jours |

**À ajouter à la revue juridique (T-175).** Mesurer le WER sur les premières familles du pilote suppose d'écrire à la main la référence d'une histoire réelle : écouter et transcrire le souvenir de quelqu'un **pour une mesure technique** n'est pas couvert par le consentement `transcription`, qui autorise la transcription du service et non sa relecture par nous. Deux exigences à faire trancher : un consentement distinct, et le passage par le chemin audité du back-office plutôt que par un dossier local.

Les textes de consentement semés aujourd'hui portent tous la mention `[À VALIDER PAR CONSEIL]`. Elle ne disparaît pas d'elle-même. Depuis le bloc 10, les trois pages légales — `/cgv`, `/confidentialite`, `/mentions-legales` — portent aussi leur bandeau, sur **toutes** les pages publiques : tant que `PilotSettings::legal_validated_at` est nul, une page de vente qui le tairait mentirait par omission. Les textes sont rédigés et lisibles ; ils attendent une relecture, pas une rédaction.

---

## §5. Les décisions qui t'appartiennent

Ni le code ni moi ne pouvons les prendre.

0. **Le nom de marque et le domaine.** ☑ Tranché le 4 septembre 2026 : Narrae, narrae.fr (T-143). Restent à lancer les deux démarches qu'ils débloquent (§1.7) : la vérification du domaine d'envoi se fait en heures, l'enregistrement de l'expéditeur SMS en jours ouvrés en France.
1. **La variante de validation** (Phase 0A, bloc 07). Deux façons de demander au narrateur ce qu'il veut faire de son histoire : les trois choix en fin d'enregistrement (A), ou la relecture du texte d'abord (B). Le drapeau est par projet et **mémorisé** — une famille ne change pas de variante en cours de route, sinon la comparaison ne veut rien dire. C'est le test le plus important de la Phase 0A, et il se tranche en regardant de vraies familles, pas en discutant.
2. **Le moment de la notification de réaction** (bloc 08). Tout de suite, ou en résumé le lendemain matin. Un SMS à 23 h chez une personne de 85 ans n'est pas une bonne nouvelle ; reste à savoir si l'élan survit à la nuit.
3. **Le fournisseur de transcription**, si l'écart de WER dépasse 2 points. En dessous, la règle est déjà écrite et Gladia gagne (hébergement UE). Au-dessus, c'est un arbitrage qualité contre juridiction, et il te revient.
4. **Le prix de l'exemplaire supplémentaire** (bloc 10). Il vaut 45 € dans les réglages, marqué `[À CONFIRMER]` depuis l'origine, et il attend le devis de l'imprimeur — c'est le seul prix du produit dont la marge dépend d'un tiers. Il se change dans l'administration, sans déploiement, mais il faut aussi recréer le prix correspondant dans Stripe : un prix Stripe ne se modifie pas, il se remplace.
5. **La variante du cadeau** (bloc 10). `ecard` est livrée et sert tout le monde. La carte A6 imprimable et le message vocal de l'acheteur sont décrits, mais non construits (décision T-108) : le premier attend la chaîne d'impression du bloc 13, le second ouvrirait un point d'envoi accessible sans compte. La question est de savoir si l'une des deux mérite d'être mesurée au pilote — et si oui, laquelle en premier.

---

## §6. Tableau de suivi

Le seul endroit à tenir à jour.

| | À réunir | Débloque | État |
|---|---|---|---|
| 1 | Clé Anthropic | bloc 06 | ☐ |
| 2 | Clé Gladia | bloc 06 | ☐ |
| 3 | `ASR_CALLBACK_SECRET` généré | bloc 06 | ☐ |
| 4 | Clé Deepgram *(optionnelle)* | bloc 06 | ☐ |
| 5 | ~~Corpus : 10 voix de 65 ans et plus~~ | bloc 06 | **reporté le 2026-09-06** (T-175) — la mesure se fera sur les premières familles du pilote. Ne bloque ni le code ni le go-live ; le coût est de découvrir la qualité ASR en même temps qu'elles |
| 6 | Lecture humaine du Fluide sur 5 histoires **réelles** | bloc 06 | **à moitié** — 5 mots à mot **écrits** lus le 2026-09-03, deux défauts corrigés (`fluide-v2`, T-126) ; reste 5 histoires **réelles**, dans la même séance que le corpus de voix |
| 7 | iPhone réel + Android réel *(5 idéalement, dont Samsung Internet)* | bloc 04 | ☐ |
| 8 | Accès HTTPS (tunnel ou préproduction) | bloc 04 | ☐ |
| 9 | Twilio : SID, token, numéro vérifié, expéditeur « NARRAE » | bloc 05 | **en cours** — `SMS_ALLOWLIST` renseignée ; l'**enregistrement AF2M n'est pas nécessaire** (réponse du 2026-09-07, T-211). **Le jeton d'authentification ne marche pas** : essai d'envoi réel le 2026-09-07, `[HTTP 401] 20003 — auth token is not valid for account AC78371…`. Le SID est bien celui qui a déposé le ticket, il n'y a pas de configuration en cache et la valeur est propre : c'est le jeton lui-même qu'il faut recopier depuis la console (ou qui a été remplacé par un secret de clé d'API). **Dès qu'il est bon** : `artisan prod:sms +33…` rejoue l'essai de bout en bout, rappel de livraison compris. **Autre correction** : `TWILIO_FROM` porte encore « Narrae », or c'est un **numéro** qu'il doit porter — c'est le repli hors FR/BE/CH/LU *et* le `TEL` de la fiche contact VCF du doc 04 §9, qu'un nom rend inimportable (T-174) |
| 9bis | Déposer la marque **Narrae**, puis demander son inscription sur la liste protégée de l'**AF2M** | bloc 05 | ☐ **pas bloquant, à mener avant que le pilote ne s'élargisse** — hors liste, l'expéditeur donne la reconnaissance mais pas l'exclusivité : n'importe qui peut signer un SMS « Narrae », et le doc 04 §9 s'appuie sur l'expéditeur constant comme pilier anti-hameçonnage (T-211) |
| 10 | Resend : clé, domaine narrae.fr vérifié, secret de webhook | bloc 05 | **en cours** — clé dans `.env` le 2026-09-05. Restent le domaine vérifié (SPF, DKIM, DMARC) et `RESEND_WEBHOOK_SECRET`, sans lequel aucun message ne passe en `delivered` — et c'est ce statut qui distingue « lien non ouvert » de « courriel jamais reçu » |
| 11 | 30 min pour le checkpoint du bloc 07 | bloc 07 | ☑ **fait le 2026-09-03** — cinq points sur cinq, quatre écarts trouvés et corrigés (T-127 à T-129), bloc tagué |
| 12 | 20 min pour le checkpoint du bloc 08 | bloc 08 | ☑ **fait le 2026-09-05** — sept points sur sept, joué sur un vrai iPhone ; quatre écarts trouvés et corrigés (T-156 à T-158, T-160, T-164, T-166), bloc tagué |
| 12bis | 20 min pour le checkpoint du bloc 09 | bloc 09 | ☑ **fait le 2026-09-05** — cinq points sur cinq, trois écarts trouvés et corrigés (T-153 à T-155), bloc tagué |
| 12ter | Relire le ton des onze messages du moteur | bloc 09 | **en cours** — les onze textes lus le 2026-09-05, rien de culpabilisant ; deux formulations pointent une absence (`react_suggestion`, `initiator_alert`), toutes deux adressées à l'Initiateur·rice. Verdict du fondateur en attente |
| 12quater | 20 min pour le checkpoint du bloc 10 | bloc 10 | ☑ **fait le 2026-09-05** — cinq points sur cinq avec un vrai paiement ; trois défauts de paiement trouvés et corrigés (T-167 à T-169), bloc tagué |
| 12quinquies | Relire les trois textes légaux avant de les envoyer au conseil, et leur soumettre la garantie « satisfait ou remboursé pendant trente jours, sans justification » affichée sur la page d'accueil (T-134) | bloc 10 | ☐ |
| 12sexies | 20 min pour le checkpoint du bloc 11, points 1 à 4 | bloc 11 | ☑ 2026-09-06 |
| 12octies | 15 min pour le checkpoint du bloc 12 (+ `sail up -d clamav`, une photo iPhone) | bloc 12 | ☑ 2026-09-06 |
| 12nonies | 15 min pour **finir** le checkpoint du bloc 13 : code famille, bascule de forme, accord à l'impression, révocation d'un QR | bloc 13 | ⏸ reporté le 2026-09-06, livrable M+12 |
| 12septies | Relire les six playbooks du support (`resources/playbooks/`) | bloc 11 | ☐ |
| 13 | Cloudflare R2 : 3 compartiments UE + CORS | bloc 16 | ☐ |
| 14 | Stripe : compte, clés de test, 6 prix (`price_…`), coupon, CLI pour le webhook | bloc 10 | ☑ **fait le 2026-09-05** — compte `acct_1UBU3n…`, produits et prix créés par API, paiement réel joué de bout en bout, bloc tagué. **Restent les prix en mode live et une clé restreinte avant le go-live** |
| 15 | Polices OFL déposées | bloc 13 | ☐ |
| 16 | Devis imprimeur | bloc 13 | ☐ |
| 17 | PostHog UE | bloc 15 | ☐ |
| 18 | DigitalOcean UE, Forge, Flare, Oh Dear | bloc 16 | ☐ |
| 19 | **Socle juridique validé par conseil** | bloc 17 | ☐ |
| 20 | DPA signés (11 sous-traitants) | bloc 17 | ☐ |

**Le chemin le plus court vers les tags restants** : les blocs 11 et 12 sont clos depuis le 2026-09-06. Reste le bloc 06, dont le corpus de voix est la pièce la plus longue à réunir, puis le bloc 04 — son spike navigateur attend un second appareil, un Android. Ensuite 1 + 2 + 3 + 5 pour le bloc 06, dont le corpus de voix est la pièce la plus longue à réunir.

**Ce qui ne dépend ni de Resend ni de Twilio, par ordre d'utilité** : la ligne 6 (une heure de lecture du Fluide, la clé Anthropic est déjà dans ton `.env`), la ligne 14 (Stripe, vingt minutes), puis les lignes 11 à 12octies (les checkpoints, deux heures de ton temps).

**Le moins cher en argent, le plus utile en information** : la ligne 14. Un compte Stripe en mode test est gratuit et prend vingt minutes ; il débloque le bloc 10 en entier, donc le premier parcours d'achat complet — et c'est ce parcours qui dira si la promesse tient devant quelqu'un qui n'est pas nous.
