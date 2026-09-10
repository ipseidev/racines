# Intégration Stripe Checkout — état et suites

Rédigé le 2026-09-10, après application des réglages du Checkout Studio.

Un seul appel crée une session de paiement dans ce dépôt :
[`app/Services/Payments/StripeCheckoutSessions.php`](app/Services/Payments/StripeCheckoutSessions.php).
C'est le seul fichier modifié. Le port
[`CheckoutSessions`](app/Services/Payments/CheckoutSessions.php) et son
double en mémoire n'ont bougé que pour la règle des codes promotionnels.

## Paramètres réglés dans le Studio, appliqués

| Paramètre                    | Valeur                                 |
| ---------------------------- | -------------------------------------- |
| `ui_mode`                    | `hosted_page`                          |
| `origin_context`             | `web`                                  |
| `billing_address_collection` | `auto`                                 |
| `submit_type`                | `auto`                                 |
| `phone_number_collection`    | `{ enabled: true }`                    |
| `automatic_tax`              | `{ enabled: true }`                    |
| `allow_promotion_codes`      | `true`, sous condition (voir plus bas) |

`ui_mode` vaut `hosted_page` et non `hosted` : `stripe/stripe-php` est en
v21.3.1, et l'API refuse désormais `hosted` — vérifié contre le compte de
test.

## Aucune valeur d'exemple à remplacer

`mode`, `success_url`, `cancel_url` et `line_items` portaient déjà de vraies
valeurs et n'ont pas été touchés : `mode` est `payment` (achat unique, jamais
d'abonnement), les deux adresses viennent des routes traduites du tunnel, et
les identifiants de prix viennent de `config/services.php`, alimenté par
`STRIPE_PRICE_*` dans `.env`. Rien à remplacer avant la mise en service.

## Ce qui n'a pas été appliqué, et pourquoi

**`payment_method_collection: always`** — l'API le refuse hors abonnement :
« You can only set `payment_method_collection` if there are recurring
prices ». Les consignes du Studio l'excluent d'ailleurs elles-mêmes en mode
`payment`.

**« Retirer les paramètres absents des Field Intents »** — non suivi. Cela
aurait supprimé `metadata` (le webhook `FulfillOrder` y lit le brouillon, le
code de réduction et le consentement Meta), `locale` (la page de Stripe parle
la langue du tunnel depuis T-238), `customer_email` et `discounts` (le coupon
de bienvenue, T-141). La commande ne se serait plus honorée.

## Codes promotionnels : une règle à connaître

Stripe refuse une session qui demanderait à la fois `discounts` et
`allow_promotion_codes` : « You may only specify one of these parameters ».

D'où la règle posée dans l'adaptateur : **un code de bienvenue posé au
récapitulatif gagne**, et la page de Stripe n'ouvre son champ « code promo »
que si aucun n'est posé. Un code comme `TEST20` fonctionne donc sur la page
de Stripe, à condition que le récapitulatif ne porte pas déjà un code de
bienvenue — sinon il faut le retirer d'abord.

Les deux branches ont été créées pour de vrai sur le compte de test : sans
coupon, `allow_promotion_codes` vaut `true` et le total reste 89,00 € ; avec
le coupon de bienvenue, le champ est fermé et le total tombe à 80,10 €.

## Ce qui reste à faire

**La TVA, avant d'encaisser en réel.** Stripe Tax est actif sur le compte,
siège en France, mais **aucune immatriculation n'est active** : rien n'est
calculé aujourd'hui, et les montants sont inchangés. Deux choses le jour où
la TVA doit être collectée :

1. Déclarer l'immatriculation française (Stripe Dashboard → Tax →
   Registrations). Le défaut du compte est `inferred_by_currency`, donc
   l'euro est traité toutes taxes comprises : les prix annoncés dans le
   dossier ne bougeront pas, la taxe s'extraira du montant.
2. Remplacer le code produit fiscal par défaut (`txcd_10000000`, biens
   matériels) par ceux qui conviennent : le livre imprimé et le service de
   collecte ne relèvent pas du même taux.

**Le téléphone.** La page de Stripe demande maintenant celui de l'acheteur.
Le tunnel ne le collectait pas — il ne demande que celui du narrateur, à qui
partent les SMS. Ce numéro-là n'est pour l'instant lu nulle part : à retirer
du Studio s'il ne sert pas.

## Essayer

`sail artisan migrate:fresh --seed`, puis le tunnel sur
`http://localhost:8001/acheter`. Carte de test : `4242 4242 4242 4242`, une
date future, n'importe quel CVC. Les autres numéros — refus, 3-D Secure — sont
sur <https://docs.stripe.com/testing>.

Le webhook local passe par `stripe listen --forward-to
localhost:8001/stripe/webhook` ; `STRIPE_WEBHOOK_SECRET` doit porter le secret
que cette commande affiche, sinon la signature est refusée et la commande
n'est jamais honorée.

Documentation : <https://docs.stripe.com>. Support : <https://support.stripe.com>.
