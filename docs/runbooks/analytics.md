# Runbook — les chiffres du pilote

Bloc 15. Ce document est la **source de vérité des définitions**. Une métrique
citée dans une présentation au comité se vérifie ici, pas dans une requête.

## 1. Où vivent les chiffres

| | |
|---|---|
| **Les gates** (H0–H3, North Star, contre-métriques) | Table `daily_metrics`, calculée par `metrics:compute` chaque nuit à 03:00 |
| **L'audience** (visiteurs, sources, pages) | PostHog EU, pages publiques et espace uniquement |

**Les deux ne se mélangent pas.** Un outil tiers échantillonne, applique sa
propre rétention et ne se rejoue pas — or une définition de métrique se
corrige toujours en cours de pilote, et il faut alors recalculer le passé.
Aucun chiffre de gate ne vient de PostHog.

```bash
sail artisan metrics:compute                 # hier
sail artisan metrics:compute --date=2026-08-01   # recalcule un jour passé
```

La commande est **idempotente** : l'unicité `(date, cohorte, métrique)` est
dans la base. La relancer écrase au lieu d'empiler.

## 2. Les définitions

Chaque classe de `app/Metrics/` porte sa définition en commentaire et la rend
par `definition()`. Ce qui suit est le résumé, avec **le dénominateur**, qui
est toujours le point délicat.

### North Star — `living_projects`

Projets ayant, dans les 30 derniers jours, **au moins une histoire validée
écoutée ≥ 30 s par un proche**. Dénominateur : les projets acceptés et non
effacés.

Les trois conditions comptent. *Validée* et non enregistrée : un récit non
approuvé n'existe pour personne d'autre que son auteur. *Écoutée* et non
partagée : un lien que personne n'ouvre ne fait pas une famille vivante.
*Par un proche* : une réécoute du narrateur ne ferme pas la boucle.

### H0 — `h0_acceptance_14d` — seuil ≥ 60 %

Part des invitations **délivrées** acceptées en ≤ 14 jours.

Le dénominateur est la délivrance confirmée par le prestataire
(`outbound_messages.delivered_at`), **pas** l'envoi. Une invitation qui
n'arrive jamais n'a pas été refusée : elle n'a pas été posée. Les confondre
ferait retravailler le texte du message quand c'est le canal qui échoue
(T-205).

### H1 — `h1_itt_8_stories_j70` — seuil ≥ 50 %

Part des **accepteurs**, y compris ceux qui n'ont jamais rien enregistré,
ayant 8 histoires validées à J70 après acceptation.

C'est **le** chiffre du dossier. Compter sur les activés retirerait du
dénominateur exactement les familles où le produit a échoué le plus tôt.

### H1 secondaire — `h1_activated_8_stories_j70` — seuil ≥ 65 %

Même numérateur, dénominateur restreint aux projets ayant au moins un
enregistrement. **À lire à côté de l'ITT, jamais à sa place.** L'écart entre
les deux dit où le produit casse : ITT bas et activés hauts, c'est l'entrée ;
les deux bas, c'est la répétition.

### `first_recording_unassisted` — seuil ≥ 85 %

Part des histoires ayant atteint la demande de micro qui aboutissent à un
enregistrement confirmé.

Calculée sur les **événements du navigateur**, pas sur la base : un narrateur
dont le micro est refusé ne laisse aucune histoire, donc aucune ligne.
Compter depuis les enregistrements mesurerait la réussite de ceux qui ont
réussi — un chiffre toujours à 100 %.

### `initiator_requests_per_month` — plafond ≤ 4

Sollicitations envoyées à l'Initiateur·rice par projet actif sur 30 jours. On
compte les demandes **envoyées**, pas les actions faites : une demande ignorée
pèse quand même sur la personne à qui elle arrive.

### Les contre-métriques

`stories_hidden_30d`, `stories_deleted_30d`, `refunds_30d`,
`invitations_refused_30d`, `print_defects_30d`, `erasures_requested_30d`.

Elles existent pour **empêcher un chiffre flatteur**. Un taux de validation
excellent accompagné de retraits massifs dirait qu'on a poussé des gens à
partager ce qu'ils ne voulaient pas. Elles se lisent **contre** ce qui
précède, jamais isolément.

## 3. Le troisième état

Le tableau de bord affiche **atteint**, **manqué**, et **échantillon trop
petit**. Le dernier sera longtemps le seul vrai.

H0 ≥ 60 % sur cinq invitations, c'est trois acceptations : un résultat que le
hasard produit une fois sur trois. Le seuil d'échantillon est de dix par
défaut. **Ne jamais annoncer une hypothèse validée sur un dénominateur
inférieur** — c'est la seule façon de rendre ces chiffres indéfendables.

## 4. Ce qui ne part jamais vers PostHog

`PiiGuard` **lève** sur toute propriété contenant un courriel, un numéro
E.164 ou un jeton de 43 caractères. Il inspecte les clés autant que les
valeurs et descend dans les tableaux.

Les identifiants internes — `project_id`, `story_id` — sont **hachés par
l'adaptateur** avant l'envoi. Sans cela, le hachage du `distinct_id` ne
servirait à rien : on rejoindrait les deux jeux de données.

Aucune page à jeton (`/r`, `/l`, `/q`, `/n`, `/i`, `/a`, `/x`, `/s`) ne
reçoit de clé, et le paquet `posthog-js` y est absent — chargement dynamique,
donc fragment jamais demandé. Deux tests le vérifient.

## 5. Ce qui reste à faire

- [ ] `metrics:describe` : imprimer le catalogue et ses définitions.
- [ ] Le NPS à J+56 et sa page `/s/{token}`.
- [ ] La micro-expérience H2 : métrique par bras du drapeau
      `reaction-notification-timing`.
- [ ] `[À VALIDER PAR CONSEIL]` : la mesure sans cookie persistant et
      l'exemption revendiquée.
