# Bloc 08 — Écoute famille et réactions

Statut : ◐ en cours · Dépend de : 07 · Tag de fin : `bloc-08-done`
Références dossier : PRD P0-10, P0-12, H2 (R-5), doc 04 §3 (visibilité fine), §12 (liens d'écoute distincts, lecture seule) ; flag `reaction-notification-timing`.

## 1. Objectif

Les proches reçoivent un lien d'écoute personnel, écoutent les histoires partagées, lisent le texte, réagissent en un tap ou laissent un mot court, et le narrateur en est informé. Chaque étape de la chaîne H2 est mesurée séparément : page ouverte, 30 secondes écoutées, réaction envoyée, notification reçue.

## 2. Pourquoi

« L'attention des proches est le carburant du narrateur. » Le dossier refuse de présumer la causalité : il faut instrumenter chaque maillon et tester le moment d'envoi de la notification.

## 3. Livrables

- Action `InviteFamilyMember` + notification d'invitation (email/SMS) avec jeton `listen_project`.
- Pages `family/Home`, `family/Story` sur `/l/{token}` ; jetons `listen_story` pour les liens directs.
- Lecteur audio accessible, URL temporaires R2.
- Tables `reactions`, `listen_events` ; actions `ReactToStory`, `RecordListenProgress`.
- Notification au narrateur `notifications.family.reaction_received`, immédiate ou en digest du matin selon le flag ; commande `reactions:send-digests`.
- Événements analytics de la chaîne H2 (émis dès maintenant via une façade `Analytics` en mode `log`, PostHog au bloc 15).

## 4. Packages

Aucun.

## 5. Tests à écrire d'abord

- `tests/Feature/Family/VisibilityGuardTest.php` — **le test le plus important du produit** : pour chaque état d'histoire (`proposed`, `recorded`, `transcribed`, `to_review`, `validated`, `hidden`, `archived`, `trashed`, `deleted`) la page `/l/{token}/stories/{story}` répond « Cette histoire n'est pas disponible » (404 amical) ; seuls `shared` et `in_book` avec `visibility ≠ book_only` répondent 200 ; `restricted` répond 200 pour un proche listé et 404 pour un autre.
- `tests/Feature/Family/TokenScopeTest.php` : un jeton `record` sur `/l/…` → `TokenTypeMismatch` ; un `listen_project` d'un autre projet → 404 ; un `listen_story` n'ouvre que son histoire.
- `tests/Feature/Family/HomePageTest.php` : liste les histoires visibles, plus récentes d'abord, badge « Nouvelle » si non écoutée par ce proche, aucune donnée d'une histoire non visible dans les props Inertia (assertion sur le JSON).
- `tests/Feature/Family/StoryPageTest.php` : props = titre, date, question, `audio_url` temporaire (expire ≤ 60 min), textes courants (`fluide` par défaut, `verbatim` disponible), mention IA, réactions existantes (prénoms seulement).
- `tests/Feature/Family/ListenProgressTest.php` : agrège les secondes, pose `reached_30s` une seule fois, émet `story_listened_30s` une seule fois par proche et par histoire.
- `tests/Feature/Family/ReactionTest.php` : ❤️ et merci idempotents, commentaire ≤ 280, refus sur histoire non visible, émet `reaction_sent`.
- `tests/Feature/Notifications/ReactionNotificationTest.php` : flag `immediate` → notification au narrateur dans la minute, au plus une par histoire et par jour (agrégation) ; flag `next-morning` → rien avant, digest à 09:00 le lendemain ; émet `narrator_notified`.
- `tests/Feature/Family/InviteFamilyMemberTest.php` : crée le proche, le jeton `listen_project` 12 mois, la notification sur le canal disponible, la ligne `outbound_messages`.
- `resources/js/components/AudioPlayer.test.tsx` : boutons ≥ 44 px, libellés texte, affiche le temps restant, émet `progress` toutes les 10 s, `prefers-reduced-motion` respecté.
- `tests/e2e/family-listen-react.spec.ts` : ouvrir `/l/{token}`, ouvrir une histoire, lire 35 s (audio de test court en boucle ou horloge simulée), réagir ❤️ + mot ; l'événement `reached_30s` et la réaction existent en base ; la notification narrateur est dans `outbound_messages`.
- `tests/e2e/family-a11y.spec.ts` : zéro violation axe.

## 6. Étapes

### 6.1 Tables et analytics
- [x] Migrations `create_reactions_table`, `create_listen_events_table` (annexe B).
- [x] `App\Support\Analytics` (façade) avec `capture(AnalyticsEvent $event, array $props, ?string $distinctId)` ; implémentation `LogAnalytics` maintenant, `PostHogAnalytics` au bloc 15. Enum `AnalyticsEvent` créé avec les premiers cas : `family_link_opened`, `story_page_opened`, `story_listened_30s`, `reaction_sent`, `narrator_notified`, `story_recorded_within_7d_of_notification` (calculé au bloc 09).

### 6.2 Invitation des proches
- [x] `InviteFamilyMember(Project, User $by, array $data): FamilyMember` : crée le proche, `TokenService::issue(listen_project, familyMember, ttl 12 mois)`, notification `FamilyInvitationNotification` (« {Initiateur} vous invite à écouter les histoires de {Narrateur} » + lien `/l/{token}` + rappel anti-phishing).
- [x] `ReissueFamilyLink(FamilyMember)` (rotation) pour le support et l'Initiateur·rice.
- [x] L'UI d'ajout arrive au bloc 10 ; ici, commande `family:invite {project} {name} {email|phone}` pour les tests manuels.

### 6.3 Pages famille
- [x] `routes/family.php` : groupe `resolve.token:listen_project|listen_story` (le middleware accepte une liste), `throttle:tokens`, `no-store`.
  - `GET /l/{token}` → `family/Home` (ou redirection vers l'histoire pour un `listen_story`)
  - `GET /l/{token}/stories/{story}` → `family/Story`
  - `POST /l/{token}/stories/{story}/listen` → `RecordListenProgress`
  - `POST /l/{token}/stories/{story}/reactions` → `ReactToStory`
- [x] Toute lecture d'histoire passe par `App\Queries\VisibleStoriesForFamilyMember` qui applique `isVisibleToFamily()` et la liste `restricted`. Aucune autre requête sur `stories` dans l'espace famille.
- [x] `family/Home.tsx` : en-tête « Les histoires de {Prénom} », liste de cartes (titre, date, durée, badge « Nouvelle », vos réactions), pied de page « Vous recevez ce lien parce que {Initiateur} vous a invité·e. Ne le transmettez qu'à des proches. »
- [x] `family/Story.tsx` : titre, question, `AudioPlayer`, onglets « Texte » / « Mot à mot », mention `family.story.ai_label`, barre de réactions (❤️ « J'ai aimé », « Merci »), champ « Laisser un mot » (280), liste « Ont réagi : … », navigation précédente/suivante.
- [x] `components/AudioPlayer.tsx` : `<audio>` natif piloté par des boutons larges (lecture/pause, −15 s, +15 s), barre de progression cliquable, vitesse ×1/×0,9 (les voix âgées), envoi de `progress` toutes les 10 s et à la pause.
- [x] URL audio : `MediaStorage::temporaryUrl(derived_mp3_path ?? original_path, 60)`, régénérée à chaque chargement.

### 6.4 Réactions et notifications
- [x] `ReactToStory(Story, FamilyMember, ReactionType, ?string $comment): Reaction` (`updateOrCreate` sur le couple, commentaire remplacé).
- [x] `RecordListenProgress(Story, FamilyMember|null, int $seconds)` : cumule, pose `reached_30s`, émet l'événement analytics une fois.
- [x] `NotifyNarratorOfReactions` : selon `reaction-notification-timing` : `immediate` → job différé de 60 s qui agrège les réactions de la dernière minute sur l'histoire et envoie `notifications.family.reaction_received` (« Marie a écouté « {titre} » et vous dit merci : « {mot} » ») ; `next-morning` → rien, et `reactions:send-digests` (`dailyAt('09:00')`) envoie un digest par narrateur ayant reçu des réactions la veille. Plafond : 1 notification par histoire et par jour ; jamais pendant `paused_until`.
- [x] Emit `narrator_notified` avec `timing` et `story_id` (pour la micro-expérience H2 du bloc 15).

### 6.5 Clôture
- [x] Annexe B, `01_CONVENTIONS.md` §15, `02_GLOSSAIRE_TECH.md` §7 si des événements ont été ajoutés.
- [x] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts.
- [ ] Commit `chore(bloc-08): terminé`, tag `bloc-08-done` — après le checkpoint §7 joué sur un vrai téléphone.

## 7. Checkpoint démontrable

1. `sail artisan family:invite {projet} "Marie" marie@example.test` → email dans Mailpit avec le lien `/l/…`.
2. Ouvrir le lien sur un téléphone : la liste ne montre que les histoires partagées ; masquer une histoire depuis l'espace narrateur (bloc 07) la fait disparaître immédiatement.
3. Écouter 35 s puis réagir « Merci » avec un mot : le narrateur reçoit (flag `immediate`) un SMS ou un email dans la minute avec le prénom et le mot.
4. Passer le flag à `next-morning`, réagir de nouveau : rien n'arrive ; `sail artisan reactions:send-digests` avec l'horloge forcée au lendemain 09:00 envoie le digest.
5. Tenter d'ouvrir `/l/{token}/stories/{uuid d'une histoire cachée}` → page « non disponible », aucune donnée dans la réponse.

## 8. Critères de sortie

- [x] `VisibleStoriesForFamilyMember` est la seule requête `stories` de `app/Http/Controllers/Family` (revue).
- [x] `VisibilityGuardTest` couvre les onze états.
- [x] Les URLs audio expirent et ne contiennent aucune donnée personnelle.

## 9. Règle de décision par défaut

Si un proche n'a ni email ni téléphone valide, l'Initiateur·rice peut copier le lien `/l/…` et le transmettre lui-même ; le lien reste personnel et révocable. Ne jamais créer de lien « famille » partagé entre plusieurs personnes.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_

**2026-09-02 — Claude (agent) — code livré, checkpoint §7 joué en simulé.**

### Ce qui est démontré

- **§7.5 et le critère de sortie — le test le plus important du produit.** `VisibilityGuardTest` éprouve **les onze états** de la machine, un par un, et un test annexe échoue si un état est ajouté sans être couvert. Seuls `shared` et `in_book` répondent 200 ; tout le reste rend un 404 amical **sans le titre, sans la question, sans le narrateur**. `book_only` est refusé, la liste `restricted` est respectée, et masquer une histoire la retire **immédiatement**.
- **La porte unique.** `OnlyOneDoorTest` vérifie qu'aucun fichier de `app/Http/Controllers/Family` — ni le présentateur — n'interroge `stories` autrement que par `VisibleStoriesForFamilyMember`. Le critère de sortie est éprouvé, pas relu.
- **§7.1 — L'invitation.** `family:invite` crée le proche, son lien de douze mois et son message. Un lien par personne, jamais un lien commun : deux proches, deux jetons. Le renouvellement révoque l'ancien.
- **§7.2 — La liste ne montre que le partagé**, avec « Nouvelle » entendu comme « pas encore écoutée par **vous** ». L'assertion porte sur le JSON des props, là où une donnée de trop passerait inaperçue.
- **§7.3 et §7.4 — Les deux moments de notification.** `ReactionNotificationTest` couvre l'immédiat (message nommant la personne et citant son mot, agrégation, plafond d'un par jour, silence pendant une pause, silence si l'histoire a été masquée entre-temps) et le lendemain matin (rien sur le coup, un digest par narrateur, aucun doublon avec l'immédiat).
- **L'écoute mesurée.** `ListenProgressTest` vérifie le cumul, le seuil posé **une seule fois**, le comptage séparé par proche, et le refus d'un incrément invraisemblable. `AudioPlayer.test.tsx` vérifie que tirer le curseur ne compte pas comme de l'écoute, et que les secondes en attente partent à la pause.
- **Bout en bout.** `family-listen-react.spec.ts` ouvre le lien, écoute, réagit avec un mot, et vérifie que le mot revient avec le prénom de qui l'écrit. `family-a11y.spec.ts` : zéro violation grave, texte à 18 px, commandes à 44 px.

### Ce qui attend un humain

- **§7.3 sur un vrai téléphone, avec du son.** La lecture réelle d'un MP3 dans un navigateur sans carte son est trop fragile pour un test : le bout en bout passe par la même route que le lecteur, ce qui éprouve la mesure mais pas l'audition. Reste à écouter une vraie histoire sur un vrai appareil, à vérifier que le ×0,9 rend une voix âgée plus claire, et que les boutons se touchent à bout de bras.
- **Le choix du moment de notification** est une micro-expérience, pas une décision technique. Le drapeau est par projet et mémorisé ; c'est le pilote qui tranchera.
- **L'écran d'ajout d'un proche** arrive au bloc 10, comme prévu. Ici, la commande `family:invite` suffit.

### Écarts consignés

- **T-86** — un lien porte désormais son détenteur (`issued_to`), sans quoi un lien d'histoire serait anonyme.
- **T-87** — le middleware de jeton accepte une liste de types déclarée sur la route.
- **T-88** — l'URL d'écoute est signée sur l'adresse vue par le navigateur (défaut réel, audio muet en local).
- **T-89** — après une navigation Inertia, `back()` ne revient pas où l'on croit.
- **T-90** — une histoire hors de portée rend un 404 amical, et ne dit pas pourquoi.
- **T-91** — tirer le curseur ne compte pas comme de l'écoute.
- **T-92** — le plafond de notification se lit dans les messages envoyés.
- **T-93** — la façade d'analytics existe avant PostHog, pour que les événements soient posés au bon endroit.

### Portail qualité

`sail composer check` vert (Pint, Larastan niveau 8, **697 tests**, 3 390 assertions), `sail npm run check` vert, `tsc --noEmit` vert, Vitest **90 tests** verts, Playwright **39 tests** verts.
