# Conventions du projet

Ces conventions s'appliquent à tout le code, tous les tests et tous les documents. Elles ne se discutent pas bloc par bloc : si une convention doit changer, on la change ici d'abord, puis on met le code en conformité.

## 1. Arborescence cible du dépôt (état après le bloc 00)

```
/                               ← racine = projet Laravel (déployé tel quel par Forge)
├── app/
│   ├── Actions/                ← une classe = une action métier (ex. ValidateStory, IssueRecordToken)
│   ├── Engine/                 ← moteur de complétion : Rules/, Detectors/, EngineTick
│   ├── Enums/                  ← enums PHP 8.1+ (StoryState est géré par model-states, pas ici)
│   ├── Exceptions/Domain/      ← exceptions métier (ForbiddenTransition, TokenExpired…)
│   ├── Filament/               ← admin interne uniquement (Resources, Pages, Widgets)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Public/         ← landing, essai 60 s, tunnel
│   │   │   ├── Initiator/      ← espace Initiateur·rice (authentifié)
│   │   │   ├── Narrator/       ← pages par jeton `record`
│   │   │   ├── Family/         ← pages par jeton `listen_*` et `qr`
│   │   │   └── Webhooks/       ← Stripe, Twilio, Resend, ASR
│   │   ├── Middleware/
│   │   └── Requests/           ← Form Requests, une par action HTTP
│   ├── Jobs/                   ← jobs de queue, un par étape de pipeline
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Services/
│   │   ├── Audio/              ← ffmpeg, durée, dérivés
│   │   ├── Llm/                ← StoryRenderer (interface) + ClaudeStoryRenderer + FakeStoryRenderer
│   │   ├── Sms/                ← SmsSender (interface) + TwilioSmsSender + FakeSmsSender
│   │   ├── Storage/            ← MediaStorage (interface S3/R2) + réplication
│   │   ├── Tokens/             ← TokenService, OtpService
│   │   └── Transcription/      ← TranscriptionProvider (interface) + Gladia + Deepgram + Fake
│   ├── Settings/               ← classes spatie/laravel-settings (BrandSettings, PilotSettings)
│   └── States/Story/           ← classes d'états spatie/laravel-model-states
├── config/
│   ├── brand.php               ← valeurs par défaut de marque (surchargées par BrandSettings en base)
│   └── product.php             ← paramètres produit chiffrés (durées, seuils, plafonds)
├── database/{migrations,factories,seeders}/
├── docs/
│   ├── dossier/                ← les 5 documents produit (déplacés au bloc 00)
│   ├── reference/remento-screenshot/   ← captures, ignoré par git
│   ├── roadmap/                ← ce dossier
│   ├── runbooks/               ← restauration, incident, déploiement
│   └── spikes/                 ← comptes rendus des spikes (navigateur, ASR)
├── lang/fr/                    ← toutes les chaînes UI et notifications
├── resources/
│   ├── js/
│   │   ├── brand/              ← BrandProvider, tokens CSS
│   │   ├── components/ui/      ← shadcn/ui (généré, ne pas modifier à la main sauf tokens)
│   │   ├── components/         ← composants métier partagés
│   │   ├── hooks/
│   │   ├── layouts/            ← PublicLayout, InitiatorLayout, NarratorLayout, FamilyLayout
│   │   ├── lib/                ← utilitaires purs (testés en Vitest)
│   │   ├── pages/{public,initiator,narrator,family}/
│   │   └── recorder/           ← machine à états d'enregistrement, IndexedDB, upload
│   ├── playbooks/*.md          ← playbooks support affichés dans Filament
│   └── views/                  ← app.blade.php (racine Inertia), emails, legal/*.md
├── routes/{web,narrator,family,webhooks,console}.php
└── tests/
    ├── Feature/                ← miroir de app/ (Http, Jobs, Engine…)
    ├── Unit/
    ├── e2e/                    ← Playwright (*.spec.ts)
    └── bench/asr/              ← banc d'essai WER (corpus privé, non commité)
```

## 2. Nommage

- **Interface utilisateur en français, code en anglais.** Le glossaire `02_GLOSSAIRE_TECH.md` fixe la correspondance. On n'invente pas de synonyme : un `Narrator` ne devient jamais `Storyteller` dans le code.
- **Aucune occurrence du nom de marque** dans `app/`, `resources/js/`, `lang/`, `tests/`, `database/`. Le nom vient de `BrandSettings::product_name`. Le test `tests/Unit/BrandAgnosticTest.php` (bloc 01) échoue si le nom configuré dans `config/brand.php` apparaît ailleurs.
- Modèles au singulier (`Story`), tables au pluriel (`stories`), clés primaires `uuid` ordonnées (`HasUuids`) pour tout modèle exposé à l'extérieur ; `bigint` autorisé pour les tables purement internes (`engine_events`, `audit_logs`).
- Actions : verbe à l'infinitif + objet, une méthode publique `handle()` : `IssueRecordToken`, `ValidateStory`, `HideStory`.
- Jobs : verbe + objet, suffixe `Job` interdit (le namespace suffit) : `App\Jobs\TranscodeRecording`.
- Règles du moteur : préfixe par état détecté : `App\Engine\Rules\InvitationNotAccepted`.
- Événements analytics : `snake_case` préfixé par domaine, listés dans l'enum `App\Enums\AnalyticsEvent` (bloc 15).
- Routes nommées par espace : `public.*`, `initiator.*`, `narrator.*`, `family.*`, `webhooks.*`, `admin.*`.
- Clés de traduction : `<espace>.<page>.<élément>` en `snake_case` : `narrator.record.start_button`.

## 3. Style PHP

- `declare(strict_types=1);` en tête de tout fichier PHP.
- Classes `final` par défaut, `readonly` pour les DTO, promotion de constructeur, types de retour partout, jamais `mixed` sans commentaire justificatif.
- Enums PHP natifs (backed, `string`) pour toute valeur fermée.
- Contrôleurs sans logique métier : valider (Form Request), autoriser (Policy), appeler une Action, retourner une réponse Inertia ou JSON. Un contrôleur de plus de 40 lignes est un signal de refactor.
- Aucune requête Eloquent dans les composants React ni dans les vues Blade : les props Inertia sont construites par des classes `App\Http\Resources\*` ou par l'Action.
- Larastan niveau 8 sur `app/`, `config/`, `database/`, `routes/`. Les tests ne sont pas analysés par PHPStan.
- Pint avec le preset `laravel`, configuration dans `pint.json` : `declare_strict_types: true`, `final_class: true` (exclure `app/Models`, `app/Filament`, `app/Providers` de `final_class`).

## 4. Style TypeScript et React

- `strict: true`, `noUncheckedIndexedAccess: true`, `any` interdit (ESLint `@typescript-eslint/no-explicit-any: error`).
- Composants fonction, hooks, pas de classes. Un composant par fichier, nom de fichier en `PascalCase.tsx`, tests à côté en `PascalCase.test.tsx`.
- Formulaires avec `useForm` d'Inertia, validation côté serveur par Form Request ; pas de bibliothèque de formulaire supplémentaire.
- shadcn/ui pour les primitives (Button, Dialog, Input…). Les tokens de couleur passent par les variables CSS de marque (bloc 01). Aucune couleur en dur dans les composants métier.
- Toute logique non triviale (machine à états du recorder, calculs d'affichage, formatage) vit dans `resources/js/lib/` ou `resources/js/recorder/` en fonctions pures testées par Vitest.
- Les pages narrateur et famille n'importent aucune dépendance lourde : budget 150 Ko gzip de JavaScript par page, vérifié par `npm run build -- --report` et un test Playwright qui mesure la taille des ressources.
- **Une seule couleur d'action par page.** La terracotta est réservée au bouton qui fait l'action de l'écran ; tout le reste — commandes, secondaires, panneaux — reste dans la couleur de marque, le lin ou le sable. Un bouton se voit par son isolement, pas par sa teinte (analyse colorimétrique du 3 septembre 2026, `docs/design/README.md`). Le thème est clair, seul ; Fraunces est la police variable auto-hébergée des titres, Inter celle du texte, et il n'y en a pas d'autre.

## 5. Protocole TDD

**Cycle obligatoire pour chaque étape d'un bloc :**

1. Écrire le test décrit en §5 du bloc (ou plus fin). Le nommer d'après le comportement : `it('refuses to share a story that is not validated')`.
2. Lancer le test, constater l'échec pour la bonne raison (pas une erreur de syntaxe).
3. Écrire le minimum de code qui le fait passer.
4. Refactorer sans changer le comportement, tests verts.
5. Commit.

**Ce qu'on teste à chaque couche :**

| Couche | Outil | Obligatoire pour |
|---|---|---|
| Unitaire PHP | Pest, dossier `tests/Unit` | Toute Action, Service, Rule du moteur, transition d'état, Value Object |
| Feature PHP | Pest, `tests/Feature` | Toute route (succès, refus d'autorisation, validation), tout Job (avec fakes), tout webhook (signature valide et invalide) |
| Unitaire front | Vitest (via `vp test`) + Testing Library, fichiers `*.test.tsx` / `*.test.ts` | Tout composant qui a un état ou une condition, toute fonction de `lib/` et `recorder/` |
| Bout en bout | Playwright, `tests/e2e/*.spec.ts` | Le scénario nominal de chaque bloc et son scénario d'échec principal |
| Accessibilité | `@axe-core/playwright` dans les specs des pages narrateur et famille | Zéro violation `serious` ou `critical` |

**Règles :**

- Les fournisseurs externes (Twilio, Resend, Stripe, Gladia, Deepgram, Anthropic, R2) ont chacun une implémentation `Fake*` de leur interface, utilisée dans les tests. Aucun test n'appelle le réseau. `Http::preventStrayRequests()` est activé dans `tests/Pest.php`.
- Le temps est contrôlé : `$this->travelTo(...)` ou `Carbon::setTestNow()` ; aucune assertion ne dépend de l'heure réelle.
- Les factories couvrent tous les états : `Story::factory()->recorded()`, `->validated()`, etc.
- Une régression corrigée s'accompagne toujours d'un test qui la reproduisait.
- Les tests Playwright tournent contre `sail` avec la base `testing` réinitialisée (`sail artisan migrate:fresh --seed --env=testing`) et les fournisseurs en mode `fake` (`ASR_PROVIDER=fake`, `SMS_PROVIDER=fake`, `LLM_PROVIDER=fake`).

## 6. Commandes canoniques

Définies dans `composer.json` (`scripts`) et `package.json` (`scripts`) au bloc 00. **On n'en invente pas d'autres pour le portail qualité** : une seconde façon de lancer les tests finit toujours par en dire autre chose. L'outillage des vérifications humaines est une famille distincte, listée plus bas.

| Commande | Fait |
|---|---|
| `sail up -d` | Démarre l'environnement local : app, pgsql, redis, mailpit, minio. L'antivirus est sous profil Compose, donc éteint : `sail up -d clamav` le lève, comme `laradev --clamav` |
| `sail composer check` | Alias de `ci:check` : contrôle front puis PHP, la porte complète |
| `sail composer test` | Enchaîne `lint:check`, `types:check` et les tests PHP |
| `sail composer lint` | Pint, corrige |
| `sail composer lint:check` | Pint, vérifie sans corriger |
| `sail composer types:check` | PHPStan niveau 8 |
| `sail npm run check` | Vite+ : format (oxfmt) et lint (oxlint) |
| `sail npm run check:fix` | Vite+ : corrige format et lint |
| `sail npm run types:check` | `tsc --noEmit` |
| `sail npm run test` | Vitest, une passe |
| `sail npm run test:watch` | Vitest en continu |
| `E2E_BASE_URL=http://localhost:8001 npx playwright test --workers=1` | Tests bout en bout, **depuis le Mac et en série** (voir la note ci-dessous) |
| `sail npm run build` | Compile les assets |
| `sail artisan migrate:fresh --seed` | Base locale propre avec le corpus et un projet de démonstration |

L'application locale répond sur `http://localhost:8001`, Mailpit sur `http://localhost:8027`, la console MinIO sur `http://localhost:8901` (ports décalés, décision T-34).

### Outillage des vérifications humaines

Six checkpoints demandent quelqu'un devant un navigateur, et aucun ne se joue
sans un lien à jeton de quarante-trois caractères, un identifiant de projet, un
code à six chiffres ou un état de drapeau. Ces commandes existent pour cela.
Toutes **refusent de tourner en production**, et toutes lisent leurs valeurs
dans le décor plutôt que de les recopier — une feuille de test fausse coûte
plus cher que pas de feuille du tout.

| Commande | Fait |
|---|---|
| `laradev` | Démarre tout : Sail, journaux, Vite. `--tunnel` ouvre deux tunnels Cloudflare et construit les assets (un téléphone ne joint pas Vite sur `localhost`) ; `--clamav` démarre l'antivirus ; `--fresh` resème |
| `larakill` | Arrête tout et **restaure `.env`** depuis `.laradev.state` : un `LINKS_DOMAIN` mort ferait répondre 404 à chaque lien à jeton |
| `sail artisan demo:liens` | La feuille des vérifications : liens réels, comptes, codes, et en rouge ce qui attend encore une clé ou un appareil. `--bloc=07` n'en imprime qu'un |
| `sail artisan demo:moteur` | Arme les trois signaux du checkpoint du moteur et efface la trace du tour précédent, pour qu'il se rejoue (bloc 09) |
| `sail artisan demo:invitation` | Fabrique un projet neuf et imprime son lien d'opt-in : l'opt-in est définitif, donc le lien du décor ne sert qu'une fois (bloc 10) |
| `sail artisan demo:cadeau` | Le parcours du cadeau bout à bout : l'annonce d'abord, puis `--question` pour la première question sans attendre la nuit que l'acceptation pose — jusqu'à la photo (blocs 10 → 04 → 12) |
| `sail artisan demo:reaction-timing` | Affiche ou bascule le drapeau des réactions du projet d'essai ; `--veille` antidate une réaction pour que le résumé la voie (bloc 08) |
| `sail artisan fluide:try --file=…` | Soumet un mot à mot au vrai modèle et imprime le rendu à côté, sans rien écrire en base (bloc 06, corpus dans `docs/corpus/`) |

**Ces commandes ne remplacent jamais la vraie.** `demo:reaction-timing --veille`
prépare l'état, mais c'est `reactions:send-digests` — celle qui tournera à 9 h
en production — que la vérification doit exercer. Envelopper la commande réelle
reviendrait à tester l'enveloppe.

### Outillage de production

Symétrique de la famille précédente, et de sens inverse : ces commandes sont
faites pour tourner **sur le serveur**, là où les doubles n'existent pas. Elles
appellent les vrais prestataires — c'est le point, une clé présente ne prouve
pas qu'elle est valide (T-208).

| Commande | Fait |
|---|---|
| `php artisan prod:check` | « Si quelqu'un achète maintenant, est-ce que ça marche ? » Chaque ligne dit ce que le client perd, pas ce qui manque techniquement. `--rapide` n'appelle pas les prestataires. Vérifie aussi ce qu'aucun test ne peut voir : les textes de consentement en vigueur, les contraintes `check` restées en arrière de leur énumération (§13), la règle CORS du stockage — origine, `PUT` et **exposition de l'`ETag`**, sans laquelle un envoi réussit et ne se conclut pas — un **envoi présigné réel** par l'adresse que verra le navigateur — seul contrôle qui refasse ce que fait le magnétophone —, les trois compartiments sondés en écriture, et la présence de `ffmpeg`/`ffprobe`, sans lesquels une voix conservée n'atteint jamais le texte |
| `php artisan prod:messages` | « Je n'ai jamais reçu le SMS. » Les derniers messages sortants, et **ce que Twilio en dit vraiment** : l'état réel de l'identifiant et son code d'erreur, traduit en sortie. Signale les messages restés à `sent`, c'est-à-dire le rappel de statut qui n'arrive pas. Lecture seule ; `--gabarit=`, `--nombre=`, `--local` |
| `php artisan prod:sms +33…` | Envoie **un vrai SMS** à un numéro nommé et le suit jusqu'à `delivered`. Annonce avant d'envoyer l'expéditeur que verra le téléphone, la longueur et le nombre de segments ; refuse le numéro d'un narrateur ou d'un proche ; `--corps=` pour un autre texte, `--attendre=0` pour ne pas attendre le rappel, `--force` sans confirmation |
| `php artisan prod:demo` | Fabrique un décor complet **en production** — compte, commande, narrateur sur un vrai téléphone, proches invités — par le chemin du webhook Stripe, sans qu'aucun argent ne bouge. `--question` pose la première question sans attendre la nuit que l'acceptation pose ; `--purge` efface par le chemin RGPD ; `--telephone=`, `--email=`, `--canal=`, `--proches=`, `--motdepasse`, `--force` |

`prod:sms` est la seule commande du dépôt qui **écrit à une personne** de sa
propre initiative. Son texte par défaut ne ressemble donc pas à un message du
produit et ne porte aucun lien : la faute de frappe la plus probable est un
chiffre pour un autre, et un faux prompt chez un inconnu serait exactement le
smishing que le doc 04 §9 combat.

`prod:messages` est son inverse : elle n'écrit rien, elle demande. La raison
d'être des deux est la même — `outbound_messages` sait ce que **nous** avons
fait, jamais ce que l'opérateur en a fait. Cette moitié arrive par le rappel de
statut, et quand ce rappel n'aboutit pas, la ligne ne quitte jamais `sent` :
« accepté » se lit alors comme « reçu ». Or en France un expéditeur
alphanumérique **non déposé auprès des opérateurs** est jeté *après*
l'acceptation de l'API — Twilio rend un identifiant, le téléphone ne sonne
jamais, et aucune ligne de journal ne le montre. `prod:messages` va donc
chercher l'état à la source. Le rappel manquant reste un défaut à lui seul :
sans lui, le moteur de complétion ne distingue pas « lien non ouvert » de
« SMS jamais arrivé », et se tait quand il faudrait relancer.

`prod:demo` écrit aussi, mais **des messages du produit**, et c'est le point :
entre « les clés répondent » et « un iPhone reçoit un SMS, ouvre un lien,
enregistre une voix et l'envoie sur R2 », il reste tout ce qui casse
vraiment — et rien de cela ne casse en local. Trois garde-fous en découlent :

 - **aucun argent ne bouge** : la session porte le préfixe `demo_` au lieu du
   `cs_` de Stripe, et `stripe_payment_intent_id` reste nul ; c'est ce préfixe
   qui retrouve le décor, et il n'y a donc rien à rembourser ;
 - **le projet n'appartient à aucune cohorte** : `FulfillOrder` rangerait un
   achat dans la cohorte en cours, et le décor décalerait son H0 et son H1.
   Sans cohorte, il sort de toutes les lectures par cohorte. Il reste compté
   dans la **lecture globale**, et l'entonnoir PostHog reçoit un
   `purchase_completed` de plus — `--purge` puis `metrics:compute --date=…`
   remet les jours touchés ;
 - **le compte acheteur est un alias** (`toi+demo@…`) et la commande refuse de
   s'installer sur un compte du personnel : un décor ne change pas le mot de
   passe de celui qui répond au support.

Aucun lien à jeton n'est imprimé, contrairement à `demo:invitation` : le canal
est justement ce qu'on vient éprouver, et un lien recopié dans le terminal
prouverait que la base sait fabriquer une URL, pas qu'un téléphone reçoit un
SMS.

### Ce qu'on relance, et quand

La porte complète coûte cher : la suite bout en bout demande deux à trois
minutes et un semis propre, et la rejouer après avoir changé un mot ou une
marge est du temps perdu — le nôtre et celui du modèle. **On la fait donc
proportionnelle au changement** (T-165). L'intégration continue joue la suite
entière sur `main` : elle est le filet, pas la première ligne.

| Ce qu'on vient de changer | Ce qu'on relance |
|---|---|
| Un texte dans `lang/` | `sail npm run check` et le test qui cite la clé |
| Un style, une marge, une couleur, **sans toucher au balisage** | `sail npm run check` et `sail npm run build` |
| Le balisage d'une page : un élément ajouté ou déplacé, un rôle, un composant | En plus, la ou les spécifications **de cette page**, avec `--no-deps` |
| Un composant partagé, une mise en page, un intergiciel, une route | La suite entière, `--workers=1` |
| Avant un tag de bloc, ou avant de pousser | La suite entière, `--workers=1` |

**Les pages de vente sont hors de la porte automatique (T-218).** L'accueil,
« Comment ça marche » et les variantes de `/lp/*` n'ont plus de test Feature,
de test de composant, d'audit d'accessibilité ni de scénario bout en bout qui
lise leur balisage ou leur rédaction. La raison est un calcul, pas un
relâchement : ces pages sont réécrites plusieurs fois par jour, chaque
déplacement de section faisait tomber une dizaine d'assertions de texte, et
aucun de ces échecs n'a jamais désigné un défaut — ils désignaient la
modification qu'on venait de demander. Ce qui reste couvert :

- que la page **réponde** (`tests/Feature/SmokeTest.php`, `tests/e2e/smoke.spec.ts` : un 200 et un titre, rien sur le contenu) ;
- que le **vocabulaire interdit** R-11 n'entre pas dans `lang/fr/public.php` (`tests/Unit/ForbiddenVocabularyTest.php`, qui lit les valeurs traduites de tous les fichiers du produit) ;
- que ce qui est **derrière** un bouton marche : `WelcomeOfferTest` pour la réduction contre une adresse, `DiscountCodeTest` pour son code, `checkout-pilot.spec.ts` pour le tunnel, `public-a11y.spec.ts` pour le tunnel et les pages légales.

Conséquence assumée : **la relecture d'une page de vente est humaine.** On la
regarde à 360, 390, 768 et 1440 px, on suit chaque bouton jusqu'à sa
destination, on écoute l'extrait, on ouvre les questions au clavier. Ce que le
filet attrapait — un contraste perdu, une cible sous 44 px, une ancre cachée
sous la barre — se voit à l'œil sur une page qu'on a sous les yeux, et ne se
voyait de toute façon pas sur celles qu'on ne regardait plus.

Cette dispense ne s'étend à **rien d'autre**. Les pages narrateur, famille,
d'export et le tunnel gardent leur protocole entier : elles s'ouvrent par un
lien porteur, sur un vieux téléphone, chez quelqu'un qui n'a pas de compte, et
personne ne les relit chaque jour.

**Le balisage est la ligne de partage, et elle n'est pas cosmétique.** Trois
défauts de cette journée sont nés d'un changement qui « n'était que du
design » : un évitement clavier ajouté en tête de page a fait de
`getByRole('link').first()` autre chose qu'une histoire (T-162), un sélecteur
d'onglets a introduit des cibles de 40 px là où le dossier en exige 44 (T-164),
et une entrée en fondu a rendu trois audits d'accessibilité intermittents
(T-161). Aucun n'aurait été vu par `npm run check`.

**Et jamais deux fois en même temps.** Deux sessions qui lancent la suite
partagent la base `testing` **et** les compartiments MinIO : le `migrate:fresh`
de l'une efface le schéma sous l'autre. Les échecs qui en sortent ressemblent à
des régressions et n'en sont pas — « relation "users" does not exist », des
interblocages sur un `drop table … cascade`, un fichier média illisible juste
après avoir été écrit. Trois passes de porte y sont passées le 2026-09-08 avant
qu'on comprenne, et la même série est repassée verte en isolation. Avant de
conclure à une régression sur une erreur de ce genre, relancer **le sous-dossier
seul** : s'il est vert, c'était la collision.

**Et on ne la joue pas pendant une vérification humaine.** La suite écrit sur la même base que la personne qui déroule un checkpoint : une notification différée d'une minute tombée au milieu d'une exécution a coûté une heure de recherche pour un défaut qui n'existait pas (T-166).

**Et on resème avant de conclure.** Trois échecs consécutifs ont été imputés à
tort au code alors qu'ils venaient du décor vieilli entre deux exécutions —
cent cinq secondes écoutées au lieu de trente-cinq, un jeton à usage unique
déjà consommé. Un décor qui traîne fabrique des échecs qui ressemblent à des
régressions, et on perd plus de temps à les disculper qu'à ressemer.

**`--workers=1` n'est pas facultatif.** L'intégration continue joue la suite avec un seul ouvrier ; en parallèle, deux tests qui se disputent le même décor passent par chance et échouent en série (écart T-111). Une suite verte en parallèle et rouge en série ne prouve rien : c'est la version en série qui compte.

**Playwright tourne depuis le Mac, pas depuis le conteneur** (`E2E_BASE_URL=http://localhost:8001 npx playwright test`). La raison est dans `R2_PUBLIC_ENDPOINT` : les URLs présignées d'envoi sont signées pour l'adresse **vue par le navigateur**, soit `http://localhost:9001` — le port que Docker publie sur l'hôte. Un navigateur lancé dans le conteneur y trouve une connexion refusée, et les trois tests qui enregistrent pour de vrai échouent sur un délai dépassé sans dire pourquoi (écart T-110). La CI, elle, sert l'application avec `php artisan serve` sur le runner : même situation qu'un Mac. **Et quand `laradev --tunnel` tourne, `E2E_BASE_URL` est l'URL du tunnel**, pas `localhost` : `APP_URL` force les scripts en `https://…trycloudflare.com`, et la page ouverte sur `localhost:8001` reste inerte — aucun bouton ne monte, les quatre scénarios de l'opt-in tombent sur un délai dépassé sans rien dire de plus (vu le 2026-09-08).

**Aucun test n'éprouve le serveur de développement de Vite.** L'intégration
continue exécute `npm run build` puis Playwright : elle teste les assets
**construits**. Le mode que l'on utilise toute la journée — `laradev`, Vite en
écoute sur 5176 — n'est jamais couvert. Ce n'est pas un oubli qu'on peut
combler à peu de frais : lancer un serveur de développement en intégration
continue doublerait la durée des tests bout en bout pour éprouver un mode qui
ne part jamais en production. Mais la conséquence doit être dite, parce
qu'elle a déjà coûté : un double montage de React propre au mode
développement a cassé **toute** la navigation côté client sans qu'aucun test
échoue (écart T-129), et c'est un humain devant un navigateur qui l'a trouvé.
Concrètement : quand un checkpoint humain se comporte étrangement en local,
vérifier le comportement avec les assets construits (`npm run build` puis
retirer `public/hot`) fait partie du diagnostic, et sépare en une minute un
défaut du produit d'un défaut de l'outillage.

## 6bis. Versions des dépendances

- **On installe toujours la dernière version stable.** `composer require <paquet>` et `npm i <paquet>` sans contrainte de version ; on laisse le gestionnaire résoudre. Les numéros écrits dans les blocs de la roadmap sont indicatifs et datent de sa rédaction : quand ils divergent de ce qui est publié, la dernière version gagne et l'écart est noté dans `03_DECISIONS.md`.
- **On vérifie avant de clore un bloc** : `sail composer outdated --direct` et `sail npm outdated` ne doivent lister aucune montée possible, ou chaque exception doit être justifiée par écrit.
- **Une montée majeure passe par la porte qualité complète** avant d'être commitée, et seule, pour que l'échec soit attribuable.
- Dependabot ouvre des demandes hebdomadaires sur Composer, npm et les actions GitHub ; la CI les valide.

## 7. Git et commits

- Dépôt GitHub privé, branche `main` protégée par la CI (les checks doivent passer). Travail en solo : commits directs sur `main` autorisés ; une branche par bloc si le bloc dure plus d'une journée.
- Conventional Commits avec le bloc en scope : `feat(bloc-04): resumable multipart upload to R2`, `test(bloc-04): recorder survives page reload`, `chore(bloc-04): terminé`.
- Un commit = un cycle TDD ou une étape cochée. Pas de commit « WIP » sur `main`.
- Tag annoté `bloc-XX-done` à la fin de chaque bloc.
- Jamais commités : `.env*` sauf `.env.example`, `docs/reference/remento-screenshot/`, `tests/bench/asr/corpus/`, `storage/`, `node_modules/`, `vendor/`.

## 8. Variables d'environnement

Toutes dans `.env.example` avec une valeur d'exemple ou vide et un commentaire d'une ligne. Toute variable nouvelle est ajoutée ici dans le même commit.

| Variable | Rôle | Exemple local |
|---|---|---|
| `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Standard Laravel | `local`, généré, `true`, `http://localhost` |
| `APP_NAME` | Repli uniquement si `BrandSettings` indisponible (migrations) | `Product` |
| `APP_TIMEZONE` | Toujours `Europe/Paris` | `Europe/Paris` |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Français partout | `fr`, `fr`, `fr_FR` |
| `LINKS_DOMAIN` | Domaine court des liens `/r`, `/l`, `/q`, `/i` ; identique à l'hôte de `APP_URL` en local | `localhost` |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE` | Postgres | `pgsql`, `pgsql`, `5432`, `app`, `sail`, `password`, `prefer` |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` | Redis via extension phpredis | `phpredis`, `redis`, vide, `6379` |
| `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER` | Tous `redis` depuis le bloc 05 | `redis` |
| `PENNANT_STORE` | Magasin des drapeaux de fonctionnalité (`database` ou `array`) | `database` |
| `FILESYSTEM_DISK` | Disque par défaut | `r2` (local : `r2` pointant sur MinIO du Sail, voir bloc 04) |
| `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY` | Identifiants R2 (jeton limité aux buckets du projet) | — |
| `R2_ENDPOINT` | `https://<account>.eu.r2.cloudflarestorage.com` pour la juridiction UE | `http://minio:9000` |
| `R2_PUBLIC_ENDPOINT` | Adresse **vue par le navigateur** pour les URLs présignées ; identique à `R2_ENDPOINT` en production | `http://localhost:9001` |
| `R2_REGION` | Région S3 ; R2 n'en a pas, `auto` convient | `auto` |
| `R2_BUCKET_MEDIA`, `R2_BUCKET_MEDIA_REPLICA`, `R2_BUCKET_BACKUPS` | Trois buckets distincts | `media`, `media-replica`, `backups` |
| `MAIL_MAILER`, `RESEND_API_KEY`, `RESEND_WEBHOOK_SECRET`, `MAIL_FROM_ADDRESS` | Email via Resend ; local : `smtp` vers Mailpit | `resend` / `smtp` |
| `SMS_PROVIDER` | `twilio`, `log` ou `fake` ; un fournisseur inconnu lève | `log` |
| `MEDIA_DRIVER` | `s3` ou `fake` ; jamais déduit de l'environnement (T-61) | `s3` |
| `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM` | SMS ; `TWILIO_FROM` est le numéro de repli si l'expéditeur alphanumérique est refusé par l'opérateur | — |
| `ASR_PROVIDER` | `gladia`, `deepgram` ou `fake` | `fake` |
| `GLADIA_API_KEY`, `DEEPGRAM_API_KEY` | Clés ASR | — |
| `ASR_CALLBACK_SECRET` | Secret HMAC ajouté aux URLs de callback ASR | généré |
| `LLM_PROVIDER` | `claude` ou `fake` | `fake` |
| `ANTHROPIC_API_KEY`, `LLM_MODEL`, `LLM_EFFORT`, `LLM_MAX_TOKENS` | Rendu Fluide | —, `claude-opus-5`, `medium`, `8000` |
| `STRIPE_DRIVER` | `stripe` ou `fake` ; jamais déduit de l'environnement (T-61). Le SDK de Stripe a son propre transport, que `Http::preventStrayRequests()` n'atteint pas (T-105) | `stripe` (tests : `fake`) |
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Cashier | clés de test |
| `STRIPE_PRICE_PILOT`, `STRIPE_PRICE_PREVENTE_99`, `STRIPE_PRICE_PREVENTE_129`, `STRIPE_PRICE_EXTRA_COPY`, `STRIPE_PRICE_PHONE_OPTION` | Identifiants `price_…` créés dans Stripe | — |
| `POSTHOG_KEY`, `POSTHOG_HOST` | Analytics ; hôte UE obligatoire | —, `https://eu.i.posthog.com` |
| `FLARE_KEY` | Suivi d'erreurs en production | — |
| `OH_DEAR_HEALTH_CHECK_SECRET` | Endpoint de santé | — |
| `TELESCOPE_ENABLED` | `true` en local seulement | `true` |
| `ANTIVIRUS_SCANNER` | `clamav`, `fake` ou `off` ; **jamais déduit de l'environnement** (T-61). `off` débranche le contrôle en journalisant chaque fichier admis — décision **D-12**, et c'est la valeur de la production depuis le 2026-09-07 ; `fake` simule un scanner et n'a sa place que dans les tests, où `phpunit.xml` le force | `clamav` (prod : `off`) |
| `CLAMAV_HOST`, `CLAMAV_PORT` | Antivirus (bloc 12). En production, l'adresse du démon **local** (`127.0.0.1`) : `clamav` est le nom du conteneur Sail et ne résout nulle part ailleurs — c'est ce qui a fait échouer tous les dépôts de photos en ligne | `clamav`, `3310` |
| `FFMPEG_BINARIES`, `FFPROBE_BINARIES` | Chemins ffmpeg | `/usr/bin/ffmpeg`, `/usr/bin/ffprobe` |
| `THROTTLE_TOKENS_PER_TOKEN` | Requêtes par minute et par jeton ; protège du balayage, identique partout | `20` |
| `THROTTLE_TOKENS_PER_IP` | Requêtes par minute et par IP ; desserrée d'office hors production (T-79) | `60` |
| `INERTIA_SSR_ENABLED` | Rendu serveur des pages publiques. **Éteint par défaut** : c'est un service séparé, et l'allumer ferait tenter une connexion à 127.0.0.1:13714 depuis chaque test (T-107) | `false` |
| `INERTIA_SSR_URL` | Adresse du service de rendu serveur | `http://127.0.0.1:13714` |
| `BROWSERSHOT_NODE_BINARY`, `BROWSERSHOT_CHROME_PATH` | Génération PDF (bloc 13) | — |

## 8bis. Routes qui ne rendent pas une page

`StartSession` note l'adresse de **toute** requête `GET` non-ajax comme « page
précédente », sans regarder ce qu'elle a rendu. Une route qui rend un
manifeste, un fichier, une archive — que le navigateur demande de lui-même, au
moment qu'il choisit — devient donc la cible du prochain `back()`, et Inertia
qui suit la redirection reçoit du JSON ou du binaire. Un narrateur a lu « All
Inertia requests must receive a valid Inertia response » après avoir cliqué
« Partager avec mes proches » (T-231).

**Toute route qui ne rend pas une page porte donc `not-a-page`.** Aujourd'hui
`/site.webmanifest` et `/vcard` ; le téléchargement d'export est le prochain
candidat. La correction vit dans `terminate()` et non au retour de `handle()` :
`storeCurrentUrl()` s'exécute après toute la pile.

## 9. Sécurité, règles permanentes

- Les jetons ne sont jamais stockés en clair : on stocke `sha256(token)`. La comparaison se fait sur le hash.
- Aucune donnée personnelle dans une URL, ni en chemin ni en query : ni nom, ni email, ni téléphone, ni identifiant séquentiel.
- Toute route par jeton passe par le middleware de masquage des journaux (bloc 03) et par le limiteur `tokens` (60 requêtes/minute/IP).
- Tout webhook vérifie sa signature avant de lire le corps ; un test couvre la signature invalide.
- Les secrets vivent dans l'environnement Forge, jamais dans le code ni dans les tests.
- En-têtes HTTP : `Content-Security-Policy` stricte (nonce pour Inertia), `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`, `Permissions-Policy: microphone=(self)` (le micro doit rester autorisé sur les pages narrateur **et sur l'essai public `/essai`**, et nulle part ailleurs). Cette politique vaut pour le **document**, pas pour l'URL : sur le site public, qui est une seule application Inertia, la page chargée en premier décide pour toute la visite. Une page qui a besoin du micro s'ouvre donc par un lien ordinaire, jamais par un `<Link>` Inertia.
- Tout accès en lecture à une donnée sensible depuis le back-office est journalisé (bloc 11).

## 10. Internationalisation

- Toutes les chaînes visibles vivent dans `lang/fr/*.php`, un fichier par espace : `public.php`, `initiator.php`, `narrator.php`, `family.php`, `notifications.php`, `admin.php`, `legal.php`.
- Le front reçoit l'objet de traduction de la page courante via la prop Inertia partagée `i18n` et l'utilise avec le hook `useT()` (`resources/js/hooks/useT.ts`, bloc 01). Pas de bibliothèque tierce.
- Test `tests/Unit/I18nKeysTest.php` : toute clé appelée dans `resources/js/**/*.tsx` existe dans `lang/fr`. Test `tests/Unit/ForbiddenVocabularyTest.php` : aucune expression de R-11 dans `lang/fr`, `resources/views`, `resources/playbooks`.
- Pluriels et genres : utiliser la syntaxe `trans_choice` et la forme inclusive « Initiateur·rice », « Narrateur·rice » dans l'interface Initiateur ; l'interface narrateur tutoie-vouvoie selon le réglage du projet (`Project::address_form` : `vous` par défaut).

## 11. Accessibilité

- WCAG 2.2 AA. Zones tactiles ≥ 44 × 44 px sur toutes les pages narrateur et famille. Taille de police minimale 18 px sur les pages narrateur, respect de l'agrandissement système (unités `rem`, jamais de `maximum-scale`).
- Aucun compte à rebours visible. Aucune animation qui ne puisse être coupée (`prefers-reduced-motion`).
- Chaque bouton a un libellé texte ; les icônes seules sont interdites sur les pages narrateur.
- Erreurs récupérables : un message en langage simple et une action de reprise, jamais un code d'erreur seul.
- Contraste ≥ 4,5:1 vérifié par un test Vitest sur les tokens de marque (bloc 01) : l'admin refuse une combinaison de couleurs qui casse le contraste.

## 12. Journalisation et observabilité

- Canal `stack` en local, `flare` en production. Niveau `info` en production.
- Contexte structuré : `project_id`, `story_id`, `actor_type`, `actor_id`, `rule_id`. Jamais `token`, `phone`, `email` en clair.
- Le processeur Monolog `RedactTokens` (bloc 03) remplace tout segment `/r/…`, `/l/…`, `/q/…`, `/i/…` par `/[type]/[redacted]` dans tous les journaux.
- Telescope : activé en local seulement, `hideRequestParameters(['token', 'code'])`, `hideRequestHeaders(['authorization', 'cookie'])`.

## 13. Données et migrations

- Postgres uniquement. Aucune fonctionnalité qui ne marche pas sur Postgres n'est acceptée.
- `timestampsTz()` partout. Fuseau applicatif `Europe/Paris`, stockage en UTC.
- Migrations toujours réversibles jusqu'au bloc 16 ; après le premier déploiement en production, plus jamais de modification d'une migration existante.
- Soft delete uniquement là où le dossier le prévoit (état `trashed` des histoires = colonne `trashed_at`, pas `SoftDeletes` global).
- Contraintes en base et pas seulement en code : clés étrangères, `check` sur les enums stockés en texte, index uniques sur les hash de jetons.
- **Ajouter un cas à une énumération oblige à réémettre la contrainte de _chaque_ table qui la stocke.** `EnumCheck::of($enum)` est évalué au moment où la migration tourne, contre le code du jour : une base créée par `migrate:fresh` obtient l'énumération complète, une base migrée pas à pas garde la liste d'alors. Les deux divergent **en silence**, et la suite de tests tourne toujours sur la première — aucun test ne peut voir l'écart. `ConsentKind` vit dans `consents` et `consent_texts` ; deux migrations l'ont élargi et n'ont réémis que la première. La conséquence était le tunnel d'achat : `RecordConsent` levait sur un texte absent, la commande était annulée dans sa transaction, le webhook répondait 500 et Stripe désactivait l'endpoint — la punition de T-169, pour une case cochée (T-222). C'est `prod:demo` qui l'a trouvé, en production, et `prod:check` qui le dit maintenant : il compare les `casts()` des modèles aux contraintes vivantes, et nomme les trois colonnes volontairement plus étroites que leur énumération.
- Le modèle complet est dans `annexes/B_modele_donnees.md`. Toute nouvelle table y est ajoutée dans le même commit.

## 14. Jobs et queues

- Horizon dès le bloc 05. Files : `default`, `media` (ffmpeg, réplication), `transcription`, `llm`, `notifications`, `engine`, `exports`.
- Tout job est idempotent : clé d'idempotence en base (`engine_events.dedupe_key`, `outbound_messages.dedupe_key`) ou vérification d'état avant action.
- `tries` explicite, `backoff` exponentiel, `failed()` qui journalise et, pour les jobs de pipeline média, remet l'histoire dans un état cohérent.
- Aucun job ne supprime un objet R2 sauf `PurgeDeletedStory` (bloc 07) et `EraseProject` (bloc 14).

## 15. Feature flags (Laravel Pennant)

Tous déclarés dans `app/Features/`, portée par projet sauf indication.

| Flag | Portée | Valeurs | Bloc |
|---|---|---|---|
| `validation-variant` | projet | `immediate` (A) / `deferred` (B) | 07 |
| `mandate-delegation` | projet | bool | 07 |
| `reaction-notification-timing` | projet | `immediate` / `next-morning` | 08 |
| `prevente-price` | visiteur anonyme (cookie `pv`, 90 jours) | `9900` / `12900` centimes | 10 |
| `gift-experience` | projet | `ecard` / `printed-card` / `audio-message` | 10 |
| `phone-option-offer` | global | bool, **ouvert par défaut** depuis T-137, fermé quand le plafond est atteint | 10 (posé) / 17 (fermeture manuelle si besoin) |

`gift-experience` rend `ecard` pour tout le monde au bloc 10, et les deux autres variantes ne sont pas livrées : le PDF A6 attend la chaîne d'impression du bloc 13, et le message vocal de l'acheteur attend un enregistreur hors page narrateur. Les valeurs restent dans le drapeau et dans la validation parce que le modèle de données doit être prêt à les mesurer — pas parce que le parcours existe (décision T-108).

## 16. Erreurs

- Exceptions métier dans `App\Exceptions\Domain\`, une par cas : `TokenExpired`, `TokenRevoked`, `ForbiddenTransition`, `StoryNotVisible`, `PhoneOptionCapReached`…
- Elles sont rendues par `bootstrap/app.php` : pages Inertia dédiées pour les espaces narrateur et famille (langage simple, action de reprise), JSON pour les webhooks et l'API interne.
- Jamais de `500` non maîtrisé sur une page narrateur : un test Playwright ouvre un lien révoqué et un lien expiré et vérifie la page amicale.

## 17. Documents à maintenir à chaque bloc

- `docs/roadmap/blocs/BXX_*.md` : cases cochées, note de checkpoint en bas.
- `docs/roadmap/04_VERSIONS.md` : versions figées.
- `docs/roadmap/03_DECISIONS.md` : toute décision prise faute d'information.
- `docs/roadmap/annexes/B_modele_donnees.md` : toute table ou colonne nouvelle.
- `.env.example` et la table §8 ci-dessus.
- `CLAUDE.md` à la racine : chemins et commandes si ils changent.
