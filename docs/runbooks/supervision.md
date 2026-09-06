# Runbook — supervision et alertes

Bloc 16. **Oh Dear est écarté sur son coût** (T-201). Ce qu'il faisait se
sépare en deux moitiés, et elles ne se remplacent pas de la même façon.

## 1. « Un contrôle est au rouge » — depuis le serveur

C'est fait, et cela ne coûte rien.

Le planificateur passe `health:check` toutes les minutes. Dix contrôles
tournent, dont quatre écrits pour ce produit :

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

## 4. Ce qu'on ne surveille pas, et pourquoi

**Le temps de réponse des pages.** Un budget de performance existe
(`lighthouserc.json`), mesuré au déploiement. Le surveiller en continu
demanderait un service payant pour une information qu'on ne saurait pas
exploiter à dix familles.

**Les métriques métier** (histoires enregistrées, taux de réponse). Elles ne
sont pas de la supervision : elles décident du pilote, et elles vivent dans
le bloc 15.
