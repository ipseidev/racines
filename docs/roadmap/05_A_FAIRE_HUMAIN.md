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

---

## §1. Ce qui débloque du travail déjà écrit

Quatre blocs sont codés, testés et poussés, mais ne peuvent pas être tagués sans ça : **04** (téléphones réels), **05** (Twilio, Resend), **06** (clés, corpus de voix) et **10** (Stripe). Le **11** n'attend que vingt minutes de ton temps pour ses quatre premiers points.

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

- **Quoi** : lire le Fluide de 5 histoires réelles à côté de leur mot à mot.
- **Ce qu'on cherche** : des mots ajoutés qui n'ont pas été dits, un niveau de langue rehaussé, un souvenir « corrigé », un ton qui n'est pas le sien.
- **Si ça ne va pas** : on ne retouche pas le prompt en place. On écrit `resources/prompts/fluide-v2.txt` et on met le snapshot à jour — c'est ce qui rend un rendu réexplicable des mois plus tard.

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

### 1.7 Twilio et Resend — débloque le bloc 05

Les questions hebdomadaires partent par SMS et par courriel.

**Twilio** ([console.twilio.com](https://console.twilio.com)) :

- `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN` : sur la page d'accueil de la console.
- `TWILIO_FROM` : un numéro. Sur un compte d'essai, il faut **vérifier le numéro destinataire** avant de pouvoir lui écrire.
- ⚠️ **L'expéditeur alphanumérique** — que le SMS arrive au nom de la marque plutôt que d'un numéro — **demande un enregistrement préalable en France** et n'est en général pas disponible sur un compte d'essai. `TWILIO_FROM` existe précisément comme repli. Si ça ne passe pas, ce n'est pas un échec du checkpoint : c'est une information à consigner, et une démarche à lancer avant le pilote.

**Resend** ([resend.com](https://resend.com)) :

- `RESEND_API_KEY` : dans le tableau de bord.
- **Un domaine d'envoi vérifié** : SPF, DKIM et DMARC à poser chez ton registrar. Sans ça les courriels partent en indésirables, et un courriel de relance en indésirable est une relance qui n'existe pas.
- `RESEND_WEBHOOK_SECRET` : en créant le webhook, avec l'URL `https://ton-domaine/webhooks/resend`. C'est lui qui fait passer un message en `delivered` — donc qui permet au moteur de complétion (bloc 09) de distinguer « lien non ouvert » de « courriel jamais reçu ». La différence entre relancer un narrateur et lui adresser un reproche injuste.
- **Vérifier** : `MAIL_MAILER=resend`, puis déclencher un envoi et regarder le tableau de bord Resend.

### 1.8 Stripe — débloque le bloc 10

Le tunnel d'achat est écrit, testé et poussé. Ce qui manque, c'est un compte : sans identifiants de prix, le clic sur « Payer » lève une erreur — et c'est voulu, plutôt que d'inventer un montant.

**Compte de test** ([dashboard.stripe.com](https://dashboard.stripe.com), interrupteur « mode test » en haut à droite). Pas besoin de compléter le profil de l'entreprise : le mode test fonctionne sans.

**Cinq prix à créer**, chacun un produit avec un prix **unique** (`one-time`), en euros :

| Produit | Prix | Variable `.env` |
|---|---|---|
| Offre pilote | 49,00 € | `STRIPE_PRICE_PILOT` |
| Prévente — variante A | 99,00 € | `STRIPE_PRICE_PREVENTE_99` |
| Prévente — variante B | 129,00 € | `STRIPE_PRICE_PREVENTE_129` |
| Exemplaire supplémentaire | 45,00 € `[à confirmer avec le devis d'imprimeur]` | `STRIPE_PRICE_EXTRA_COPY` |
| Enregistrement par téléphone | 25,00 € | `STRIPE_PRICE_PHONE_OPTION` |

⚠️ Copier l'identifiant du **prix** (`price_…`), pas celui du produit (`prod_…`). C'est l'erreur la plus fréquente, et elle se voit seulement au moment du paiement.

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

**Rien à acheter, rien à créer.** Six checkpoints sont codés et n'attendent qu'un humain pour être déroulés, en local, avec les fournisseurs simulés.

- **Bloc 07** — validation, visibilité, retraits. ~30 minutes.
- **Bloc 08** — écoute famille et réactions. ~20 minutes.
- **Bloc 09** — moteur de complétion. ~20 minutes : forcer trois horodatages sur le projet semé, lancer `engine:tick`, lire les envois, relancer pour vérifier qu'aucun ne se répète, cliquer un lien d'action, puis `engine:report`.
- **Bloc 12** — les photos. ~15 minutes, et deux préalables gratuits : `sail up -d clamav` une première fois (deux à trois minutes, un demi-gigaoctet de signatures, gardé ensuite), puis une **photo prise avec ton iPhone** — c'est le seul moyen d'éprouver la conversion HEIC, qu'aucun outil de cette image ne sait fabriquer. Vérifie avec `exiftool` sur l'original stocké qu'il ne reste aucune coordonnée GPS : c'est le point le plus important du bloc.
- **Bloc 11, points 1 à 4** — le back-office. ~20 minutes : configurer ta double authentification à la première connexion sur `/admin` (l'application d'authentification de ton téléphone suffit), ouvrir la fiche d'une histoire, corriger un mot, puis `sail artisan audit:verify` — et pour voir la garde fonctionner, tenter un `update audit_logs set action='x'` en base, qui doit échouer. Le point 5 demande Stripe.
- **Bloc 10, points 3 à 5** — le cadeau, l'opt-in et l'espace Initiateur·rice. ~20 minutes, sans Stripe : `sail artisan migrate:fresh --seed` puis ouvrir `/i/demo-optin-accept-linkxxxxxxxxxxxxxxxxxxxxx` (accepter) et `/i/demo-optin-refuse-linkxxxxxxxxxxxxxxxxxxxxx` (refuser), et se connecter en `espace@example.test` pour l'espace. Les points 1 et 2 du checkpoint, eux, demandent Stripe.

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
| Bloc 13 | **Fichiers de polices OFL** des polices de marque, à déposer dans `resources/fonts/` | Le PDF ne doit charger aucune police depuis Internet au moment du rendu |
| Bloc 13 | **Un devis d'imprimeur** | Tant qu'il est inconnu, le PDF reste en RGB et le format 200 × 250 mm est un placeholder. Aucune promesse de délai n'apparaît dans l'interface avant ce devis |
| Bloc 15 | **PostHog, instance UE** (`POSTHOG_KEY`, `POSTHOG_HOST=https://eu.i.posthog.com`) | L'hôte UE est obligatoire |
| Bloc 16 | **DigitalOcean** (région européenne : AMS ou FRA), **Forge**, **Flare**, **Oh Dear** | Le Postgres managé doit être créé en région européenne dès le départ |
| Bloc 17 | **Le socle juridique validé par un conseil** : consentements, LIA, AIPD proportionnée, CGV, politique de confidentialité, contrat pilote, information sur l'enregistrement des appels | `golive:check` **refuse de passer** sans `legal_validated_at`. C'est la démarche la plus longue de toutes : à lancer bien avant le bloc 17 |
| Bloc 17 | **Les DPA signés** avec chaque sous-traitant : Cloudflare, DigitalOcean, Twilio, Resend, Anthropic, Gladia, Deepgram, Stripe, PostHog, Flare, Oh Dear | Idem : la liste est longue, chaque signature prend des jours |

Les textes de consentement semés aujourd'hui portent tous la mention `[À VALIDER PAR CONSEIL]`. Elle ne disparaît pas d'elle-même. Depuis le bloc 10, les trois pages légales — `/cgv`, `/confidentialite`, `/mentions-legales` — portent aussi leur bandeau, sur **toutes** les pages publiques : tant que `PilotSettings::legal_validated_at` est nul, une page de vente qui le tairait mentirait par omission. Les textes sont rédigés et lisibles ; ils attendent une relecture, pas une rédaction.

---

## §5. Les décisions qui t'appartiennent

Ni le code ni moi ne pouvons les prendre.

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
| 5 | **Corpus : 10 voix de 65 ans et plus, avec références relues** | bloc 06 | ☐ |
| 6 | Lecture humaine du Fluide sur 5 histoires | bloc 06 | ☐ |
| 7 | iPhone réel + Android réel *(5 idéalement, dont Samsung Internet)* | bloc 04 | ☐ |
| 8 | Accès HTTPS (tunnel ou préproduction) | bloc 04 | ☐ |
| 9 | Twilio : SID, token, numéro vérifié | bloc 05 | ☐ |
| 10 | Resend : clé, domaine vérifié, secret de webhook | bloc 05 | ☐ |
| 11 | 30 min pour le checkpoint du bloc 07 | bloc 07 | ☐ |
| 12 | 20 min pour le checkpoint du bloc 08 | bloc 08 | ☐ |
| 12bis | 20 min pour le checkpoint du bloc 09 | bloc 09 | ☐ |
| 12ter | Relire le ton des onze messages du moteur | bloc 09 | ☐ |
| 12quater | 20 min pour le checkpoint du bloc 10, points 3 à 5 (sans Stripe) | bloc 10 | ☐ |
| 12quinquies | Relire les trois textes légaux avant de les envoyer au conseil | bloc 10 | ☐ |
| 12sexies | 20 min pour le checkpoint du bloc 11, points 1 à 4 | bloc 11 | ☐ |
| 12octies | 15 min pour le checkpoint du bloc 12 (+ `sail up -d clamav`, une photo iPhone) | bloc 12 | ☐ |
| 12septies | Relire les six playbooks du support (`resources/playbooks/`) | bloc 11 | ☐ |
| 13 | Cloudflare R2 : 3 compartiments UE + CORS | bloc 16 | ☐ |
| 14 | Stripe : compte, clés de test, 5 prix (`price_…`), CLI pour le webhook | **bloc 10, livré et bloqué** | ☐ |
| 15 | Polices OFL déposées | bloc 13 | ☐ |
| 16 | Devis imprimeur | bloc 13 | ☐ |
| 17 | PostHog UE | bloc 15 | ☐ |
| 18 | DigitalOcean UE, Forge, Flare, Oh Dear | bloc 16 | ☐ |
| 19 | **Socle juridique validé par conseil** | bloc 17 | ☐ |
| 20 | DPA signés (11 sous-traitants) | bloc 17 | ☐ |

**Le chemin le plus court vers trois tags** : lignes 11, 12 et 12bis (une heure et demie de ton temps, rien à acheter), puis 1 + 2 + 3 + 5 pour le bloc 06.

**Le moins cher en argent, le plus utile en information** : la ligne 14. Un compte Stripe en mode test est gratuit et prend vingt minutes ; il débloque le bloc 10 en entier, donc le premier parcours d'achat complet — et c'est ce parcours qui dira si la promesse tient devant quelqu'un qui n'est pas nous.
