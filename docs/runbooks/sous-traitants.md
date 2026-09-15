# Runbook — sous-traitants

Bloc 16, doc 04 §8. Document interne : il liste ce que nous savons, ne vaut pas
avis juridique, et le registre des traitements au sens du RGPD reste à établir
avec un conseil.

**Les mentions légales nomment désormais deux de ces fournisseurs** —
l'hébergeur du site et celui des médias (T-242), la LCEN l'exige. Changer de
fournisseur, c'est donc aussi changer `legal_host` ou `legal_host_media` dans
l'administration, sans quoi la page nomme le mauvais hébergeur.

Règle de décision (§9 du bloc) : **un fournisseur sans région ou juridiction
UE n'est pas utilisé pour des données personnelles.** On cherche
l'équivalent UE, et l'on note la décision.

| Fournisseur | Rôle | Données confiées | Région | Sortie |
|---|---|---|---|---|
| **DigitalOcean** (via Forge) | Serveur, base | Tout | **Francfort-sur-le-Main, Allemagne (UE)** — vérifié le 15 septembre 2026, et non déclaré : `whois` sur l'adresse IP de `narrae.fr` donne DigitalOcean, LLC, et le fichier de géolocalisation publié par DigitalOcean place cette plage (`209.38.112.0/22`) à Francfort. C'est la réponse à la question ouverte par T-202, où l'Irlande avait été indiquée de mémoire | Forge redéploie ailleurs ; la base se restaure par dump |
| **Cloudflare R2** | Médias, sauvegardes | Audio, photos, archives chiffrées | Juridiction UE **obligatoire**, pas optionnelle | Port `MediaStorage` ; alternative Scaleway documentée, sans changement de code |
| **Twilio** | SMS | Numéro, texte du message | Hors UE (États-Unis) | Port `SmsSender`. **Le texte d'un SMS ne contient jamais de récit** — un lien et un prénom |
| **Resend** | Courriels | Adresse, contenu du message | Hors UE | Port de notification Laravel |
| **Anthropic** | Rendu Fluide | **Le texte du récit** | Hors UE | Port `StoryRenderer`. Pas d'entraînement sur les données client (engagement contractuel, doc 04 §1) |
| **Gladia** | Transcription | **L'audio du récit** | UE (France) | Port `Transcriber` ; Deepgram en second adaptateur |
| **Deepgram** | Transcription, secours | L'audio | Hors UE | Second adaptateur, non activé au pilote |
| **Stripe** | Paiement | Nom, adresse de facturation, moyen de paiement | UE (Irlande) pour les clients européens | Port `CheckoutSessions` ; Cashier |
| **PostHog EU** | Analytique | Événements sans donnée personnelle, **jamais d'URL à jeton** | UE (Allemagne) | Port `Analytics` |
| **Flare** | Erreurs applicatives | Traces, journaux masqués | UE (Belgique) | `LOG_CHANNEL` |

## Ce qui sort le plus, et ce que cela oblige

**Anthropic reçoit le texte des récits, Gladia reçoit la voix.** Ce sont les
deux transferts qui comptent, et le dossier les encadre : IA toujours
divulguée, aucun entraînement sur les contenus des familles, aucun clonage
vocal (doc 04 §1). Anthropic étant hors UE, le transfert repose sur les
clauses contractuelles types — **c'est le point à faire valider en premier**.

Gladia est en France, ce qui est la raison de l'avoir choisi avant Deepgram.

## Ce qui ne sort jamais

- Les **jetons porteurs**. Masqués dans les journaux (`RedactTokens`), absents
  de l'analytique, absents des traces d'erreur.
- Les **récits** vers Twilio, Resend, PostHog, Flare ou Oh Dear.
- Les **coordonnées** vers Anthropic, Gladia ou Deepgram.

## À faire avant le go-live (bloc 17)

- [ ] Récupérer et archiver le DPA de chaque fournisseur, avec sa date.
- [x] Confirmer la région exacte de l'hébergeur et l'écrire ici. ☑ 15 septembre 2026 : DigitalOcean, Francfort (T-242). **Reste à vérifier** que le Postgres managé est bien dans la même région, et que le compartiment R2 porte la juridiction UE et non la seule localisation.
- [ ] Faire valider ce tableau et le registre des traitements par un conseil.
- [ ] Publier la liste dans la politique de confidentialité — le dossier
      s'engage à la nommer, pas seulement à la tenir.
