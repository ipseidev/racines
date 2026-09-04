# Stripe — mise en place et exploitation

Ce document sert deux fois : une fois pour brancher le compte (une heure, à faire une seule fois), puis chaque fois qu'un paiement se comporte mal.

**Aucune clé ne figure ici.** Les clés vivent dans `.env`, qui n'est pas versionné. Elles ne passent ni par une conversation, ni par un message, ni par un commit.

## 1. Créer le compte et les produits

1. Créer le compte sur `dashboard.stripe.com`, activer le mode **test** (l'interrupteur en haut à droite).
2. Compléter le profil de l'entreprise plus tard : le mode test fonctionne sans.
3. Créer **quatre produits**, chacun avec un prix **unique** (`one-time`), en euros, TTC :

| Produit | Prix | Variable `.env` |
|---|---|---|
| Offre pilote | 49,00 € | `STRIPE_PRICE_PILOT` |
| Prévente — variante A | 99,00 € | `STRIPE_PRICE_PREVENTE_99` |
| Prévente — variante B | 129,00 € | `STRIPE_PRICE_PREVENTE_129` |
| Exemplaire supplémentaire | 45,00 € `[À CONFIRMER devis imprimeur]` | `STRIPE_PRICE_EXTRA_COPY` |
| Enregistrement par téléphone | 25,00 € | `STRIPE_PRICE_PHONE_OPTION` |

Copier l'identifiant de chaque prix — il commence par `price_`, **pas** `prod_` — dans `.env`.

4. Récupérer les deux clés d'API (Développeurs → Clés d'API) : la publiable dans `STRIPE_KEY`, la secrète dans `STRIPE_SECRET`.
5. Laisser `STRIPE_DRIVER=stripe`. La valeur `fake` existe pour les tests, et n'est jamais déduite de l'environnement (décision T-61).

Les deux prix de prévente sont deux produits distincts et non un produit à deux prix : le drapeau `prevente-price` affecte chaque visiteur à l'un des deux et l'y garde quatre-vingt-dix jours. Un prix qui change entre la découverte et le paiement fait fuir.

### Le coupon de bienvenue (T-141)

Un seul coupon, en pourcentage, 10 % de toute la commande, le même que le réglage « L'offre de bienvenue » du pilote dans l'administration. Pas de durée, pas de limite de rachats côté Stripe : ce sont nos codes (`leads.discount_code`, un par adresse, un an, à usage unique) qui portent ces règles, et le tunnel envoie le coupon par identifiant dans `discounts` seulement quand un code utilisable est posé sur le brouillon. Son identifiant va dans `STRIPE_COUPON_WELCOME`. Pour changer le pourcentage : créer un nouveau coupon, mettre à jour la variable **et** le réglage du pilote ; les codes déjà envoyés gardent le pourcentage copié au moment de la demande.

Ne pas activer « Autoriser les codes promotionnels » sur la session : la vérification du code se fait chez nous, là où l'on sait à qui il appartient et s'il a servi.

## 2. Brancher le webhook en local

```bash
# Une fois : installer la CLI
brew install stripe/stripe-cli/stripe
stripe login

# À chaque session de test
stripe listen --forward-to http://localhost:8001/stripe/webhook
```

La commande affiche un secret `whsec_…` : le mettre dans `STRIPE_WEBHOOK_SECRET`. Il change à chaque `stripe listen`, donc à chaque session.

Sans ce secret, Cashier **n'installe pas** la vérification de signature et accepte n'importe quel appel. Acceptable en local, jamais ailleurs : en production le secret vient du tableau de bord (Développeurs → Webhooks → l'endpoint), et il est fixe.

Deux événements nous intéressent, et deux seulement :

- `checkout.session.completed` → exécute la commande (`FulfillOrder`) ;
- `charge.refunded` → enregistre le remboursement, et annule le projet si le remboursement est total et que le narrateur n'a pas encore accepté.

Tout le reste est ignoré **sans broncher**. Stripe envoie des dizaines de types d'événements, et une erreur sur un type inconnu ferait retenter le webhook indéfiniment.

## 3. Jouer un achat de bout en bout

```bash
sail artisan migrate:fresh --seed
# dans un second terminal
stripe listen --forward-to http://localhost:8001/stripe/webhook
```

Puis dans le navigateur : `/` → `/essai` → `/acheter`. Carte de test `4242 4242 4242 4242`, n'importe quelle date future, n'importe quel CVC.

Ce qu'il faut voir ensuite :

```bash
sail artisan tinker --execute="
  \$o = App\Models\Order::latest()->first();
  echo \$o->status->value, ' ', \$o->total_cents, ' ', \$o->withdrawal_deadline_at, PHP_EOL;
  echo \$o->project->status->value, ' narrateur: ', \$o->project->primaryNarrator->first_name, PHP_EOL;
"
```

- la commande est `paid`, avec `withdrawal_deadline_at` à J+14 ;
- le projet est **`draft`**, pas `active` : rien ne part avant que le narrateur ait accepté ;
- le courriel de confirmation est dans Mailpit (`http://localhost:8027`) ;
- si l'option téléphone était cochée, une ligne `phone_options` existe en `requested`.

Pour forcer l'envoi du cadeau tout de suite :

```bash
sail artisan tinker --execute="
  App\Models\Project::latest()->first()->forceFill(['gift_send_at' => now()])->save();
"
sail artisan queue:work --once --queue=notifications
```

## 4. Rejouer un événement

```bash
stripe events resend evt_…            # depuis le tableau de bord ou `stripe events list`
```

L'exécution est **idempotente par `stripe_checkout_session_id`** : rejouer trois fois le même événement crée une seule commande et un seul projet. C'est vérifié par `StripeWebhookTest`, et ce n'est pas une précaution théorique — Stripe rejoue ses webhooks, parfois plusieurs fois.

## 5. Quand un paiement se comporte mal

| Symptôme | Cause probable | Quoi faire |
|---|---|---|
| `RuntimeException: Aucun article vendable` au clic sur « Payer » | un `STRIPE_PRICE_*` est vide | remplir la variable, `sail artisan config:clear` |
| Le paiement passe, aucune commande n'apparaît | le webhook n'arrive pas | vérifier que `stripe listen` tourne ; chercher `checkout.fulfilment_orphan` dans les journaux |
| `checkout.fulfilment_orphan` dans les journaux | le brouillon a expiré (sept jours) ou l'utilisateur a été supprimé | rattacher la commande à la main ; **ne rien créer en devinant** |
| Le webhook répond 403 | `STRIPE_WEBHOOK_SECRET` ne correspond pas à la session `stripe listen` en cours | recopier le `whsec_…` affiché par la commande |
| Le webhook répond 500 sur un type inconnu | un handler de Cashier attend une charge utile complète | vérifier que l'événement est bien signé et complet ; nos deux types sont couverts par les tests |
| Deux projets pour un achat | l'idempotence est cassée | incident : `orders.stripe_checkout_session_id` doit être unique, vérifier l'index |

## 6. Remboursement

Depuis le tableau de bord Stripe (Paiements → le paiement → Rembourser), total ou partiel. Le webhook `charge.refunded` met à jour `orders.status` et `refunded_cents`.

Le cas fréquent est le **partiel** : on rembourse l'option téléphone qu'on n'a pas pu assurer, pas la commande entière. Un remboursement **total avant acceptation** passe le projet en `cancelled` — inutile de laisser un cadeau en attente que plus personne ne paie.

Rappel de la garantie annoncée aux acheteurs : quatorze jours de rétractation légale, puis trente jours de remboursement intégral si la personne invitée préfère ne pas participer. Le second n'est pas une obligation légale ; c'est une promesse écrite dans les CGV, et elle se tient sans discussion.

## 7. Passer en production

1. Recréer les cinq prix en mode **live** — les identifiants de test ne fonctionnent pas en live.
2. Créer l'endpoint webhook sur le domaine de production, avec les deux types d'événements.
3. Mettre les clés live et le secret d'endpoint dans les variables d'environnement du serveur, jamais dans le dépôt.
4. Passer une commande réelle de 1 € avec une vraie carte, puis se la rembourser. C'est la seule façon de savoir que la chaîne complète fonctionne.
