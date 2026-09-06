# Runbook — supervision et alertes

Bloc 16. **Oh Dear est écarté sur son coût** (T-201). Ce qu'il faisait se
sépare en deux moitiés, et elles ne se remplacent pas de la même façon.

## 1. « Un contrôle est au rouge » — depuis le serveur

C'est fait, et cela ne coûte rien.

Le planificateur passe `health:check` toutes les minutes. Dix contrôles
tournent, déclarés dans `App\Providers\HealthServiceProvider` — **jamais dans
`config/health.php`**, que le paquet ne lit pas pour cela et que `config:cache`
refuse de sérialiser (T-207). Quatre sont écrits pour ce produit :

| Contrôle | Ce qu'il protège |
|---|---|
| `R2ReachableCheck` | Sans le stockage, un narrateur parle et **rien n'est conservé**. La page paraît normale jusqu'à une confirmation qui n'arrive jamais. |
| `AuditChainCheck` | Une rupture n'est jamais technique : c'est une altération ou une restauration ratée. |
| `ClamavCheck` | Sans le démon, **aucune photo ne passe plus**, avec un message qui parle de sécurité et fait croire à la famille que ses images sont en cause. |
| `ReplicationLagCheck` | La promesse de non-perte cesse d'être tenue **avant** qu'on ait rien perdu. |

Un échec ou un avertissement part par courriel à `HEALTH_TO_ADDRESS` (à
défaut, l'adresse de support de la marque), **une fois par heure au plus**.
Sans limite, un contrôle au rouge enverrait soixante messages par heure — et
le soixante-et-unième, celui qui compte, serait lu comme les précédents :
pas du tout.

L'endpoint `/health` reste disponible pour une lecture détaillée. Il n'existe
que si `OH_DEAR_HEALTH_CHECK_SECRET` est posé, et le secret voyage dans
l'en-tête `oh-dear-health-check-secret` — en paramètre d'URL il finirait dans
les journaux du serveur.

```bash
curl -H 'oh-dear-health-check-secret: <secret>' https://<domaine>/health | jq
```

Le premier réflexe quand on doute de la supervision : compter les résultats.
Une liste vide répond `200` comme une liste saine.

```bash
php artisan health:check   # doit afficher dix contrôles, pas « All done! » seul
```

## 2. « Le serveur ne répond plus » — depuis l'extérieur

**Cette moitié-là ne peut pas venir du serveur** : s'il est tombé, il ne
prévient personne. Il faut un appel de l'extérieur, et c'est la seule pièce
qui reste à brancher.

À surveiller : `https://<domaine>/up`. C'est la route de Laravel, publique et
muette — elle répond 200 si le cadre démarre, et n'expose ni la liste des
services ni l'état de la file. Rien à protéger, rien à cacher.

Options gratuites, par ordre de simplicité :

1. **UptimeRobot** — offre gratuite, contrôle toutes les cinq minutes,
   alerte par courriel. Suffisant : une panne de cinq minutes sur un service
   qui vise 99,5 % de disponibilité (doc 04 §11) reste dans le budget.
2. **Better Stack** — offre gratuite plus généreuse en fréquence, page de
   statut publique incluse.
3. **Un cron sur une autre machine** — `curl -fsS https://<domaine>/up ||
   mail -s "Narrae ne répond plus" …`. Gratuit, mais c'est une machine de
   plus à surveiller, et personne ne surveille le surveillant.

**Ce qu'aucune de ces options ne remplace** : la page de statut publique que
le dossier mentionne. Elle attend le bloc 17, et l'offre gratuite de Better
Stack la fournit si on la veut.

## 3. Les erreurs applicatives

`spatie/laravel-ignition` est déjà installé. En production, poser `FLARE_KEY`
et `LOG_CHANNEL=stack` avec `flare` dedans. Les alertes qui doivent y
remonter, en plus des exceptions :

- `audit.chain_broken` — `Log::critical`, émis par `audit:verify` ;
- `antivirus.unavailable` — `Log::critical`, émis quand le démon ne répond
  pas et qu'on refuse donc tous les fichiers ;
- l'échec d'une sauvegarde, par la notification de `laravel-backup`.

## 4. `prod:check` — « si quelqu'un achète maintenant, est-ce que ça marche ? »

Les contrôles du §1 tournent en continu et surveillent des **pannes**.
`prod:check` répond à une autre question, une seule fois, quand on la pose :
la chaîne entre un paiement et une famille qui écoute une voix est-elle
entière ?

```bash
php artisan prod:check            # avec les appels réels aux prestataires
php artisan prod:check --rapide   # sans, quand on veut juste l'état interne
```

Elle contacte vraiment Twilio, Gladia et Anthropic. C'est le point : une clé
présente dans l'environnement ne prouve pas qu'elle est valide, et on
l'apprend sinon avec le premier client.

Chaque ligne dit **ce que le client perd**, pas ce qui manque techniquement.
« TWILIO_FROM est vide » ne se lit pas à trois heures du matin ; « aucun SMS
ne part, les parents ne reçoivent pas leur invitation » se lit.

| Verdict | Ce que ça veut dire |
|---|---|
| rouge | La chaîne est coupée. Quelqu'un peut payer et ne rien recevoir. Code de sortie 1. |
| orange | Une fonction est dégradée, la chaîne tient. Code de sortie 0. |
| vert | Rien à faire. |

Hors production, ce qui serait rouge devient orange et le dit — un décor
local est *censé* avoir une clé Stripe de test et un faux transcripteur, et
les peindre en rouge apprendrait à ignorer le rouge. C'est aussi ce qui rend
la répétition locale utile : on voit la ligne avant le jour où elle casse.

**À lancer après chaque déploiement qui touche l'environnement**, et en
premier réflexe quand quelque chose cloche sans qu'on sache quoi.

Ce qu'elle ne regarde pas, volontairement : la mesure d'audience, le RGPD, le
rendu du livre. Ce sont des sujets importants qui ne coupent pas la chaîne.

## 5. Ce qu'on ne surveille pas, et pourquoi

**Le temps de réponse des pages.** Un budget de performance existe
(`lighthouserc.json`), mesuré au déploiement. Le surveiller en continu
demanderait un service payant pour une information qu'on ne saurait pas
exploiter à dix familles.

**Les métriques métier** (histoires enregistrées, taux de réponse). Elles ne
sont pas de la supervision : elles décident du pilote, et elles vivent dans
le bloc 15.
