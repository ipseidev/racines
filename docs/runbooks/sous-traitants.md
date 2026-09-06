# Runbook — sous-traitants

Bloc 16, doc 04 §8. **`[À VALIDER PAR CONSEIL]`** — ce document liste ce que
nous savons ; il ne vaut pas avis juridique, et le registre des traitements
au sens du RGPD reste à établir avec un conseil.

Règle de décision (§9 du bloc) : **un fournisseur sans région ou juridiction
UE n'est pas utilisé pour des données personnelles.** On cherche
l'équivalent UE, et l'on note la décision.

| Fournisseur | Rôle | Données confiées | Région | Sortie |
|---|---|---|---|---|
| **Hébergeur applicatif** (via Forge) | Serveur, base | Tout | **Irlande (UE)** — à confirmer, la feuille de route disait « DigitalOcean », qui n'a pas de région irlandaise (T-202) | Forge redéploie ailleurs ; la base se restaure par dump |
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
- [ ] Confirmer la région exacte de l'hébergeur et l'écrire ici.
- [ ] Faire valider ce tableau et le registre des traitements par un conseil.
- [ ] Publier la liste dans la politique de confidentialité — le dossier
      s'engage à la nommer, pas seulement à la tenir.
