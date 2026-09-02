# Bloc 15 — Instrumentation, KPIs, tableaux de bord

Statut : ☐ non commencé · Dépend de : 14 · Tag de fin : `bloc-15-done`
Références dossier : PRD §7 (North Star, funnel, contre-métriques, charge de l'Initiateur·rice), R-5 (H0-H3, dénominateurs ITT), R-7, doc 04 §12 (URLs masquées dans les analytics) ; décision T-19.

## 1. Objectif

Chaque étape du funnel du PRD §7 émet un événement, la North Star « projets vivants » et les KPI du pilote se calculent chaque nuit, la chaîne H2 et la micro-expérience de notification sont analysables, la charge de l'Initiateur·rice est comptée, et rien de tout cela ne contient un jeton ni une donnée personnelle.

## 2. Pourquoi

« La donnée arbitre, pas la thèse. » Les gates 0A et Phase 1 se décident sur ces chiffres. Les données de complétion sont l'actif défendable du dossier.

## 3. Livrables

- `PostHogAnalytics` (cloud UE) derrière la façade `Analytics` du bloc 08 ; `posthog-js` sur les pages publiques et l'espace Initiateur·rice uniquement.
- Enum `AnalyticsEvent` complet et émission à chaque étape du funnel.
- Table `daily_metrics`, commande `metrics:compute` (nuit), widgets Filament « Pilote » : projets vivants, funnel H0/H1, chaîne H2, contre-métriques, charge Initiateur·rice, moteur.
- Enquête NPS narrateur à la semaine 8 (jeton `survey`, page `/s/{token}`).
- `docs/runbooks/analytics.md` : définitions exactes de chaque métrique et des insights PostHog.

## 4. Packages

```bash
sail composer require posthog/posthog-php
sail npm i posthog-js
```

## 5. Tests à écrire d'abord

- `tests/Unit/Analytics/PostHogAnalyticsTest.php` : hôte `https://eu.i.posthog.com`, `distinct_id` = `sha256(project_id + APP_KEY)` pour les événements projet, `sha256(user_id + APP_KEY)` pour l'Initiateur·rice ; refuse toute propriété contenant `@`, un E.164 ou un segment `/[rlqiaxns]/…` (exception `AnalyticsPiiLeak`) ; envoi en file (`notifications`) et jamais synchrone dans une requête.
- `tests/Feature/Analytics/FunnelEventsTest.php` : un test par événement du funnel qui exécute l'action métier et vérifie l'émission (`purchase_completed`, `invitation_delivered`, `invitation_accepted`, `invitation_refused`, `consent_recorded`, `first_link_clicked`, `mic_granted`, `mic_denied`, `first_story_recorded`, `story_recorded`, `first_story_validated`, `story_validated`, `third_story_validated`, `first_listen_30s`, `first_reaction`, `tenth_story_validated`, `book_ready`, `proof_approved`, `book_delivered`, `print_defect`, `refund_issued`, `story_hidden`, `story_deleted`, `initiator_action_requested`, `initiator_action_done`, `engine_rule_fired`, `engine_rule_resumed`, `narrator_notified`, `nps_answered`, `phone_option_selected`).
- `tests/Feature/Analytics/NoTrackingOnTokenPagesTest.php` : les pages `/r`, `/l`, `/q`, `/i`, `/n`, `/a`, `/x`, `/s` ne contiennent pas le script PostHog ; les pages `/`, `/acheter`, `/espace` le contiennent avec `persistence: 'memory'` et `autocapture: false`.
- `tests/Feature/Console/ComputeMetricsTest.php`
  - `it('computes living projects: at least one story validated and listened 30s by a distinct family member in the last 30 days')`
  - `it('computes H0 acceptance rate within 14 days by cohort with delivered invitations as denominator')`
  - `it('computes H1 ITT: accepted narrators with 8 validated stories at day 70, and the activated variant')`
  - `it('computes first unassisted recording success from client events')`
  - `it('computes H2 chain rates: opened, 30s, reacted, notified, recorded within 7 days')`
  - `it('computes counter metrics: refunds, refusals, mic failures, print defects, hidden and deleted stories')`
  - `it('computes initiator load per month: requests and estimated minutes')`
  - `it('is idempotent for a given date')`
- `tests/Feature/Surveys/NpsTest.php` : envoi à J+56 après acceptation, une seule fois, page à un écran (0-10 + mot facultatif), réponse stockée sans identifiant personnel, événement `nps_answered`.
- `resources/js/lib/analytics.test.ts` : `sanitizeUrl` retire query et segments de jeton ; `track` est un no-op sur les pages à jeton.

## 6. Étapes

- [ ] `PostHogAnalytics` (client `PostHog\PostHog::init`, `host` UE), job `SendAnalyticsEvent` (file `notifications`), garde anti-PII `App\Analytics\PiiGuard`. Liaison selon `POSTHOG_KEY` (vide → `LogAnalytics`).
- [ ] Compléter `AnalyticsEvent` et instrumenter chaque Action concernée (liste du test `FunnelEventsTest`). Propriétés autorisées : `project_hash`, `cohort`, `offer`, `validation_variant`, `rule_id`, `attempt`, `channel`, `story_sequence`, `delay_hours`, `timing`, `format`, `entry`, `reason_code`.
- [ ] Front : `resources/js/lib/analytics.ts` (`initAnalytics()` appelé par `PublicLayout` et `InitiatorLayout` uniquement, `persistence: 'memory'`, `autocapture: false`, `capture_pageview: true` avec `sanitizeUrl`) ; `[À VALIDER PAR CONSEIL]` : mesure d'audience sans cookie persistant, exemption CNIL revendiquée, à confirmer.
- [ ] Migration `create_daily_metrics_table` (bigint : `date`, `cohort_id` nullable, `metric` varchar, `value` numeric, `numerator`, `denominator`, unique `(date, cohort_id, metric)`) ; annexe B.
- [ ] `metrics:compute {--date=}` (`dailyAt('03:00')`) avec une classe par métrique dans `App\Metrics\` : `LivingProjects`, `H0Acceptance14d`, `H1Itt8StoriesJ70`, `H1Activated`, `FirstRecordingUnassisted`, `H2Chain`, `Refunds`, `Refusals`, `MicFailures`, `PrintDefects`, `HiddenStories`, `DeletedStories`, `InitiatorLoad`, `EngineFirings`, `EngineResumptions`, `PhoneOptionAttach` (par entrée `checkout|rescue`).
- [ ] Widgets Filament sur la page « Pilote » : cartes North Star et KPI, tableau funnel par cohorte, graphique H2, table du moteur, jauge charge Initiateur·rice (≤ 4 sollicitations et ≤ 15 min/mois), option téléphone vs seuils D-9.
- [ ] NPS : type de jeton `survey` (glossaire §4), table `survey_answers` (`project_id`, `kind nps`, `score`, `comment` nullable, `answered_at`), commande `surveys:send-nps` (`daily()`, J+56 après `accepted_at`), page `/s/{token}`.
- [ ] `docs/runbooks/analytics.md` : définition, requête SQL ou insight PostHog, dénominateur, fréquence, propriétaire de chaque métrique ; définition de la micro-expérience H2 (flag `reaction-notification-timing`, métrique `story_recorded_within_7d_of_notification` par bras).
- [ ] Annexe B, glossaire, `04_VERSIONS.md`, `.env.example` (`POSTHOG_*`).
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts ; commit `chore(bloc-15): terminé`, tag `bloc-15-done`.

## 7. Checkpoint démontrable

1. Avec une clé PostHog UE de test : parcourir achat → acceptation → premier enregistrement → écoute → réaction sur le projet de démonstration ; les événements apparaissent dans PostHog avec un `distinct_id` haché et sans URL de jeton.
2. `sail artisan metrics:compute` puis la page « Pilote » de Filament affiche les valeurs ; relancer la commande ne double rien.
3. Ouvrir une page `/r/…` avec l'inspecteur réseau : aucune requête vers PostHog.
4. Forcer `accepted_at` à J-56 et lancer `surveys:send-nps` : le SMS ou l'email NPS part ; répondre ; `nps_answered` émis.

## 8. Critères de sortie

- [ ] `PiiGuard` bloque toute propriété suspecte (test).
- [ ] Chaque métrique du PRD §7 a une définition écrite et une classe.
- [ ] Les dénominateurs ITT sont ceux de R-5 (accepteurs), pas les activés.

## 9. Règle de décision par défaut

En cas de doute sur une propriété, ne pas l'envoyer. Les analyses fines se font en SQL sur la base, jamais en enrichissant PostHog avec des données personnelles.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
