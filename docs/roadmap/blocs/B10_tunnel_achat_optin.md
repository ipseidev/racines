# Bloc 10 — Tunnel d'achat, Stripe, cadeau, onboarding Initiateur·rice, opt-in narrateur

Statut : ◐ en cours · Dépend de : 09 · Tag de fin : `bloc-10-done`
**⛔ En attente de toi** — le checkpoint §7 demande un compte Stripe en mode test. Le code est livré, testé et poussé. Détail dans [`05_A_FAIRE_HUMAIN.md`](../05_A_FAIRE_HUMAIN.md).

Références dossier : PRD P0-1, P0-2, §5.1 (happy path), H0 (R-5), R-2 (offre pilote), R-3 (prix), doc 04 §2 (invitation : 2 + 1, suppression des coordonnées, consentements distincts), §2bis (rétractation, option téléphone), §6 (directives post-mortem), §9 (anti-phishing) ; décisions T-16, T-17, T-26, T-28 ; flags `prevente-price`, `gift-experience`, `phone-option-offer`.

## 1. Objectif

Un acheteur découvre le produit, l'essaie en 60 secondes, achète l'offre pilote ou une prévente, programme le cadeau, et le narrateur reçoit une invitation qu'il accepte ou refuse en toute liberté. L'Initiateur·rice dispose d'un espace pour suivre et organiser sans devenir chef de projet.

## 2. Pourquoi

H0 (acceptation du cadeau) est la première hypothèse ; elle se joue dans l'invitation. H3 (économie) se mesure dans le tunnel et les préventes. Tout doit être conforme avant la première famille payante.

## 3. Livrables

- Landing `/` en SSR, pages légales, démo `/essai`.
- Tunnel `/acheter` en 6 étapes, Stripe Checkout, webhook, `FulfillOrder`.
- Tables `orders`, `order_items`, `phone_options`, `invitations`, `post_mortem_directives` ; `PilotSettings` (mode `pilot|prevente|core`, plafond option téléphone, dates).
- Invitation programmée, page d'opt-in `/i/{token}`, refus avec tact, suppression des coordonnées.
- Espace Initiateur·rice `/espace` (Fortify) : suivi, réglages, questions, proches, alertes, liens, lexique, commandes, rétractation.
- Textes de consentement v1.0 et pages légales `[À VALIDER PAR CONSEIL]`.

## 4. Packages

```bash
sail composer require laravel/cashier
sail artisan vendor:publish --tag="cashier-migrations"
sail artisan vendor:publish --tag="cashier-config"
```

Inertia SSR : `sail npm run build` doit produire `bootstrap/ssr/ssr.js` (`vite.config.ts` avec `ssr: 'resources/js/ssr.tsx'`) ; service `ssr` dans Sail (`php artisan inertia:start-ssr`).

## 5. Tests à écrire d'abord

- `tests/Feature/Public/LandingTest.php` : SSR rend le titre, la promesse (`public.landing.promise`), les quatre étapes, le prix selon `PilotSettings::mode`, les engagements R-10 avec leur formulation canonique, aucun mot interdit.
- `tests/Feature/Public/DemoTest.php` : `/essai` rend le recorder en `demo`, aucune route d'upload n'est appelée (test Playwright : zéro requête vers `/recordings`).
- `tests/Feature/Checkout/PriceVariantTest.php` : le flag `prevente-price` assigne `99` ou `129` par cookie anonyme, stable sur 10 visites, réparti ~50/50 sur 1 000 identifiants.
- `tests/Feature/Checkout/CheckoutFlowTest.php` : chaque étape valide ses champs ; l'étape « Pour qui » avec « vous-même » enregistre l'intérêt (`self_narration_interest`) et propose de continuer pour un proche ; l'étape options refuse `phone_option` au-delà du plafond et sans flag ; la case marketing est décochée par défaut et distincte de l'acceptation des CGV ; la session Stripe contient les bons `price_id` et `quantity`.
- `tests/Feature/Webhooks/StripeWebhookTest.php` : `checkout.session.completed` signé → `orders(paid)`, `order_items`, `projects(draft)`, `narrators`, `phone_options` si commandé, `withdrawal_deadline_at = paid + 14 j`, email de confirmation ; événement rejoué → idempotent ; signature invalide → 403 ; `charge.refunded` → `orders(refunded)`.
- `tests/Feature/Gift/ScheduleGiftTest.php` : invitation envoyée à `gift_send_at` (09:00 Europe/Paris) sur le canal du narrateur ; variante `audio-message` joint le lecteur ; `printed-card` génère un PDF A6 imprimable téléchargeable par l'acheteur.
- `tests/Feature/Invitation/OptInTest.php`
  - `it('shows the personal message and never a recording prompt before opt in')`
  - `it('requires four separate consents and the sensitive categories acknowledgment')`
  - `it('activates the project, schedules the first prompt within 72 hours and offers the vcard')`
  - `it('records acceptance for H0 with attempt number')`
  - `it('handles refusal with an optional reason, notifies the initiator tactfully and schedules contact deletion in 30 days')`
  - `it('caps invitations at 2 plus 1 reminder')`
  - `it('lets the narrator skip post mortem directives and record them later')`
- `tests/Feature/Console/DeleteUnacceptedContactsTest.php` : à `contact_deletion_due_at`, email/téléphone mis à null, jetons révoqués, journal.
- `tests/Feature/Initiator/DashboardTest.php` : l'Initiateur·rice voit l'état de chaque histoire mais ni texte ni audio d'une histoire non partagée ; peut réordonner/exclure/ajouter une question ; inviter un proche ; copier le lien courant ; changer cadence/jour/créneau ; ajouter un terme au lexique ; demander une rétractation avant l'échéance ; voir les alertes du moteur ; activer un mandat si le flag est actif.
- `tests/Feature/Initiator/WithdrawalTest.php` : demande dans les 14 jours → ticket `withdrawal_requested` + email ; après l'échéance → message expliquant la garantie 30 jours et le contact support.
- `tests/e2e/checkout-pilot.spec.ts` (Stripe en mode test, carte `4242…`), `optin-accept.spec.ts`, `optin-refuse.spec.ts`, `initiator-dashboard.spec.ts`, `landing-a11y.spec.ts`.

## 6. Étapes

### 6.1 Réglages du pilote et prix
- [x] `App\Settings\PilotSettings` : `mode` (`pilot|prevente|core`), `phone_option_cap` (10), `pilot_price_cents` (4900), `prevente_prices_cents` (`[9900, 12900]`), `extra_copy_price_cents` (4500, `[À CONFIRMER devis 0A]`), `phone_option_price_cents` (2500), `gift_send_hour` (9), `cohort_id` courant. Page Filament « Pilote » (bloc 11 la complète).
- [ ] Créer dans Stripe (mode test puis live) les produits et prix, renseigner `STRIPE_PRICE_*`. **Attend un compte Stripe** → [`05_A_FAIRE_HUMAIN.md`](../05_A_FAIRE_HUMAIN.md). Le runbook `docs/runbooks/stripe.md` est écrit et attend les identifiants.
- [x] `App\Features\PreventePrice` (portée : cookie anonyme `pv` posé 90 jours ; hachage stable → `99|129`), `App\Features\GiftExperience` (défaut `ecard`), `App\Features\PhoneOptionOffer` (global ; désactivé automatiquement quand le plafond est atteint, via un écouteur sur `PhoneOption`).

### 6.2 Landing et pages publiques (SSR)
- [x] `public/Landing.tsx` : sections dans cet ordre : promesse (doc 01 §4) ; « Comment ça marche » en 4 étapes (lien reçu, on parle, texte relu et validé par le narrateur, la famille écoute) ; « Essayez en 60 secondes » ; le livre (QR, formats adaptables : livre, livret, chapitre fondateur) ; nos engagements (R-10 en formulation canonique, « validation explicite, jamais tacite », « pas de clonage vocal », « l'IA range, elle n'invente pas ») ; prix selon `mode` ; questions fréquentes (contenu dans `lang/fr/public.php`, dont « Que se passe-t-il si vous cessez votre activité ? » avec la réponse doc 04 §7) ; pied de page légal.
- [x] Pages `/cgv`, `/confidentialite`, `/mentions-legales`, `/consentements` (texte courant de chaque `ConsentText`) rendues depuis `resources/views/legal/*.md` (markdown → HTML côté serveur, `league/commonmark` déjà dépendance de Laravel) avec le bandeau `[À VALIDER PAR CONSEIL]` tant que `PilotSettings::legal_validated_at` est nul.
- [x] `public/Demo.tsx` (`/essai`) : recorder du bloc 04 en mode `demo` (aucun appel réseau, lecture locale, effacement à la fermeture), puis CTA « Offrir ».
- [x] Inertia SSR configuré ; `HandleInertiaRequests` partage `brand`, `i18n`, `pilot` (mode, prix).

### 6.3 Tunnel `/acheter`
- [x] Étape 1 « Pour qui ? » : « Un proche » / « Vous-même » (ce dernier enregistre `self_narration_interest` dans `client_events` et affiche « Au pilote, nous accompagnons un proche. Voulez-vous continuer pour un proche ? »).
- [x] Étape 2 « Le narrateur » : prénom, nom (facultatif), lien de parenté, email et/ou téléphone (au moins un ; validation E.164 avec `+33` par défaut), canal préféré, forme d'adresse `vous|tu`.
- [x] Étape 3 « Le cadeau » : date d'envoi (défaut J+1, max J+90), message personnel (défaut prérempli, modifiable), variante selon `gift-experience`. **`ecard` seule est livrée** : le PDF A6 attend la chaîne d'impression du bloc 13, et le message vocal de l'acheteur attend un enregistreur hors page narrateur — les deux valeurs restent dans le drapeau et la validation (décision T-108).
- [x] Étape 4 « Vos coordonnées » : création de compte Fortify inline (email, mot de passe, prénom, nom) ou connexion ; vérification d'email envoyée mais non bloquante pour payer. **Depuis T-135 (4 septembre) :** l'étape s'appelle « Votre compte », les deux formulaires (créer un compte, se connecter) sont dans le tunnel, sans « Continuer » tant qu'on n'a pas de compte ; Fortify revient à l'étape 5 par `url.intended`, posée par le contrôleur.
- [x] Étape 5 « Options et accords » : exemplaires supplémentaires (0-5) ; option « Enregistrement par téléphone » si flag actif et plafond non atteint, libellé : « Un membre de notre équipe appelle {Prénom} chaque semaine au créneau choisi et enregistre l'histoire. Offre limitée aux 10 premières familles du pilote. 25 € » ; cases séparées : acceptation des CGV et de la politique de confidentialité (obligatoire), demande de démarrage immédiat du service numérique avec information sur la rétractation (facultative, consentement `early_service_start`), « Je souhaite recevoir des nouvelles » (facultative, décochée, consentement `marketing_email`).
- [x] Étape 6 « Récapitulatif » puis `Stripe Checkout` (`mode payment`, `customer_email`, `line_items`, `metadata.draft_id`, `success_url /acheter/merci?session_id={CHECKOUT_SESSION_ID}`, `cancel_url /acheter?step=6`). Brouillon de commande en base (`checkout_drafts` jsonb, 7 jours) pour reprendre.
- [x] Ajouter `ConsentKind` : `early_service_start`, `marketing_email` ; glossaire §5.

### 6.4 Webhook et exécution
- [x] Route Cashier `/stripe/webhook` conservée ; écouteur `WebhookReceived` → `FulfillOrder(session)` : idempotent par `stripe_checkout_session_id` ; crée `Order`, `OrderItem[]`, `Project(status draft, offer selon mode, cohort courante)`, `Narrator(is_primary)`, `ProjectMember(initiator)`, `FamilyMember` pour l'Initiateur·rice (il écoute comme un proche), `PhoneOption(entry=checkout, requested)` le cas échéant, consentements de l'acheteur ; planifie `SendGiftInvitation` à `gift_send_at` ; email `notifications.checkout.confirmation`.
- [x] `charge.refunded` → `orders.status`, `refunded_cents` ; projet passé `cancelled` si remboursement total avant acceptation.

### 6.5 Invitation et opt-in
- [x] Migrations `create_invitations_table`, `create_post_mortem_directives_table`, `create_orders_table`, `create_order_items_table`, `create_phone_options_table`, `create_checkout_drafts_table`.
- [x] `SendGiftInvitation(Project, int $attempt)` : jeton `invitation` 30 jours, message sur le canal préféré : « {Initiateur} vous offre {Marque} : {message personnel}. Pour découvrir de quoi il s'agit : {lien}. Ce lien ne demandera jamais de mot de passe ni de paiement. » ; `projects.status = awaiting_acceptance`, `gift_sent_at`.
- [x] Page `/i/{token}` (`narrator/OptIn.tsx`) : le message personnel (texte ou audio), « Ce que cela veut dire pour vous » (3 phrases), les quatre consentements en cases séparées avec un lien « lire » vers le texte courant, l'accord « je comprends que je peux parler de sujets personnels » (catégories sensibles), canal, cadence (hebdo par défaut, « toutes les deux semaines » possible), jour et créneau, confirmation du numéro, forme d'adresse, puis deux boutons de même taille : « J'accepte » / « Non merci ».
- [x] Acceptation → `AcceptInvitation` : `RecordConsent` × 5, `projects.status = active`, `accepted_at`, `collection_started_at`, fenêtres calculées, `ScheduleNextPrompt` (lendemain au créneau), écran de bienvenue avec « Ajouter le contact {Marque} » (`/vcard`) et « Vos souhaits pour plus tard » (directives post-mortem, facultatif, « Plus tard » toujours proposé).
- [x] Refus → `RefuseInvitation` : `refused_at`, motif facultatif (« Ce n'est pas le bon moment », « Je préfère ne pas », « Autre »), `contact_deletion_due_at = now + 30 j`, notification à l'Initiateur·rice `notifications.initiator.invitation_refused` (ton : « {Prénom} a préféré ne pas participer pour le moment. C'est son choix et nous le respectons. Vous êtes remboursé·e intégralement sous 30 jours si vous le souhaitez : {lien}. »), ticket `refund_offer`.
- [x] Commande `narrators:delete-unaccepted-contacts` (`daily()`).
- [x] Renvois : la règle `invitation_not_accepted` du bloc 09 appelle `SendGiftInvitation(attempt 2)` puis `(attempt 3)` ; au-delà, rien.

### 6.6 Espace Initiateur·rice `/espace`
- [x] `initiator/Dashboard.tsx` : carte projet (statut en langage simple, prochain envoi, narrateur, cadence), frise des histoires (état, titre si partagée), « Copier le lien de cette semaine » (WhatsApp), alertes du moteur (jetons `action`), « Demander une pause ».
- [x] `initiator/Questions.tsx` : ordre, exclusions, ajout d'une question personnalisée (`ProjectQuestionSetting`, `stories.custom_question_text`).
- [x] `initiator/Family.tsx` : proches, invitation, renvoi de lien, retrait ; `can_contribute`.
- [x] `initiator/Settings.tsx` : cadence, jour, créneau, forme d'adresse, canal ; lexique ; mandat (si flag, avec explication et consentement du narrateur requis).
- [x] `initiator/Orders.tsx` : commande, facture Stripe, « Exercer mon droit de rétractation » (jusqu'à `withdrawal_deadline_at`), option téléphone (statut).
- [x] L'Initiateur·rice est un `FamilyMember` : il écoute via `/l/{son jeton}` ; le tableau de bord lie vers cette page.
- [x] Toutes les pages sous `auth` + `verified` (sauf `Orders` accessible non vérifié pour la rétractation).

### 6.7 Textes légaux et consentements
- [x] `ConsentTextSeeder` v1.0 : un paragraphe par `ConsentKind` en langage clair, marqué `[À VALIDER PAR CONSEIL]`.
- [x] `resources/views/legal/cgv.md` : bundle (service numérique + bien personnalisé), rétractation 14 jours, exclusion du livre imprimé après BAT, offre pilote (12 semaines, livrable réduit, statut expérimental, remboursable), engagements R-10 en formulation canonique, fair use, option téléphone (plafond, livraison humaine). `confidentialite.md` : traitements, sous-traitants (doc 04 §8), durées, droits, DPO. `mentions-legales.md` : entité de `BrandSettings`.

### 6.8 Clôture
- [x] Annexe B (`orders`, `order_items`, `phone_options`, `invitations`, `post_mortem_directives`, `checkout_drafts`), glossaire §5, `01_CONVENTIONS.md` §15.
- [x] `04_VERSIONS.md` : cashier.
- [x] `sail composer check`, `sail npm run check`, Playwright depuis le Mac (écart T-110), CI verts.
- [ ] Commit `chore(bloc-10): terminé`, tag `bloc-10-done` — après le checkpoint §7, qui demande un compte Stripe.

## 7. Checkpoint démontrable

1. Parcourir `/` → `/essai` (enregistrer 20 s, réécouter, rien n'est envoyé) → `/acheter` en mode `pilot` : commande 89 € + option téléphone 25 € avec la carte de test Stripe.
2. Webhook reçu (Stripe CLI `stripe listen --forward-to localhost/stripe/webhook`) : commande, projet, narrateur, option téléphone créés ; email de confirmation dans Mailpit.
3. Forcer `gift_send_at` à maintenant, lancer le scheduler : l'invitation arrive (log SMS ou Mailpit). Ouvrir `/i/…` : accepter avec les quatre cases → projet actif, premier prompt planifié le lendemain 09:00, fiche contact proposée.
4. Refaire avec « Non merci » : l'Initiateur·rice reçoit le message avec tact, `contact_deletion_due_at` posé.
5. `/espace` : réordonner deux questions, inviter un proche, copier le lien WhatsApp, demander la rétractation.

## 8. Critères de sortie

- [x] Aucun prompt d'enregistrement n'est envoyé avant `accepted_at` — `OptInTest::it('n’envoie aucune question avant l’acceptation')` lance `prompts:dispatch-due` sur un projet en attente et vérifie qu'aucune histoire n'est proposée.
- [x] Le consentement marketing est séparé, décoché, et n'est jamais requis pour payer — `CheckoutFlowTest`, deux tests : l'un refuse l'étape sans les CGV puis l'accepte sans le marketing, l'autre relit le brouillon.
- [x] Le plafond de l'option téléphone est appliqué côté serveur — `CheckoutFlowTest::it('applique le plafond … côté serveur')` rejoue le formulaire avec `phone_option = true` alors que le plafond est atteint, et le brouillon garde `false`.
- [x] La landing ne contient aucun mot de R-11 et emploie la formulation canonique R-10 — `LandingTest`, deux tests dédiés, plus `ForbiddenVocabularyTest` qui couvre désormais aussi `resources/views/legal/*.md`.

## 9. Règle de décision par défaut

Le mode par défaut de `PilotSettings` est `pilot`. Les préventes ne s'activent qu'en passant `mode = prevente` dans l'admin, et ne créent jamais de projet actif : elles créent une commande `core_prevente` et un projet `draft` gelé jusqu'à la Gate Phase 1.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_

**2026-09-03 — code livré, checkpoint non joué.** Le bloc est complet côté code : réglages du pilote, trois drapeaux, six tables, le port de paiement et son doublon, le tunnel en six étapes, l'exécution de commande idempotente, le cadeau, l'opt-in, l'espace Initiateur·rice, les pages publiques et légales, le rendu serveur. Porte qualité verte : **974 tests Pest / 4 960 assertions**, PHPStan niveau 8 sans erreur, Pint et `tsc` propres, **101 Vitest**, **62 Playwright**.

Le checkpoint §7 ne peut pas être joué : ses points 1 et 2 demandent un compte Stripe en mode test (produits, prix, `stripe listen`). Les points 3, 4 et 5 sont jouables en local dès maintenant et couverts par les tests bout en bout `optin-accept`, `optin-refuse` et `initiator-dashboard`, mais un checkpoint est un acte humain : il reste à exécuter.

**Écarts consignés : T-104 à T-110.** Deux méritent d'être lus avant de reprendre ce bloc — **T-108**, qui borne le cadeau à la variante `ecard`, et **T-109**, une contrainte de base qui rendait l'effacement obligatoire des coordonnées impossible depuis le bloc 02 et que le test de la commande a découvert.

**Deux gardes ajoutées en cours de route**, parce qu'un défaut silencieux a été trouvé à la main : `I18nKeysTest` détecte désormais une clé de langue définie deux fois (PHP garde la dernière et jette la première, sans un mot) et vérifie que toute clé appelée par `__()` existe. La seconde a immédiatement trouvé un courriel du bloc 06 qui n'avait jamais eu de texte.
