# RACINES — PRD du MVP
**v2.3 — Septembre 2026 — Build autorisé uniquement après la Gate Phase 1 (mars 2027). Valeurs canoniques : doc 05 (Référentiel).**

## 1. Objectif du MVP
Industrialiser ce que la Phase 0 aura prouvé : un narrateur senior clique, autorise le micro, s'enregistre, **valide explicitement** et recommence (H1) ; les proches écoutent et cette attention est associée à la production (H2) ; l'économie tient après acquisition (H3). Le MVP industrialise **le moteur de complétion** — pas un catalogue de fonctionnalités.
**Pré-requis d'entrée en build (Gate Phase 1)** : données J70 complètes des cohortes 0B ; prix retenu et contribution après CAC démontrée ; périmètre contractuel arrêté (offre, sortie honorable, QR) ; parcours de validation choisi entre les 2 variantes testées ; socle juridique du MVP validé.

## 2. Ce que le MVP n'est PAS
Téléphonie automatisée (build conditionné aux seuils D-9 mesurés en 0B ; l'option « Enregistrement par téléphone » du pilote est opérée humainement, hors code — §8.7) · app mobile native (PWA/web responsive) · WhatsApp Business natif (transfert manuel du lien par l'Initiateur·rice) · vidéo · restauration photo · rendu « littéraire » (le MVP livre un **double rendu Verbatim/Fluide**, pas un « curseur » multi-niveaux — Phase 2) · questions adaptatives IA · recherche sémantique · frise auto · traduction · capsules · coffre documentaire · funéraire (discovery séparée) · B2B2C · autres langues · app TV · studio de mise en page complet · multi-imprimeurs automatisé.
**Multi-narrateurs** : hors UI au MVP, prévu en modèle de données. Demande mesurée en Phase 0 ; **seuil de promotion pré-engagé : ≥ 35 % des intentions d'achat conditionnées à 2 narrateurs** → réponse commerciale avant build (option « 2e narrateur » opérée manuellement, ou 2e projet). Décision à la Gate Phase 1, pas par défaut.

## 3. Périmètre P0
| # | Fonctionnalité | Simplification assumée |
|---|---|---|
| P0-1 | Achat cadeau + onboarding Initiateur·rice | 1 SKU principal ; expérience cadeau selon le gagnant du test 0A (e-carte / carte imprimée / message audio) ; add-ons : exemplaires supplémentaires, et option « Enregistrement par téléphone » tant que D-9 est en test (plafond affiché, libellé honnête sur la livraison humaine) |
| P0-2 | Invitation & consentement narrateur | Invitation **SMS/email** avec message personnel ; aucun prompt avant opt-in ; consentements distincts (doc 04 §2) |
| P0-3 | Lien d'enregistrement navigateur | 1 lien/question ; validité 30 j ; réutilisable jusqu'à VALIDÉE ; révocable/ré-émissible ; page à un bouton ; pré-explication avant le popup micro ; pause/reprise ; upload résumable |
| P0-4 | Envoi des prompts | SMS + email ; cadence par défaut hebdo, créneau choisi ; WhatsApp = lien copiable en 1 tap pour transfert manuel |
| P0-5 | Réponse écrite simple | Zone de texte, pas d'éditeur avancé |
| P0-6 | Photos rattachées aux histoires | Upload simple ; contrôle antivirus/format (doc 04 §12) |
| P0-7 | Transcription FR | Audio smartphone ; audio source conservé, jamais remplacé |
| P0-8 | **Double rendu Verbatim/Fluide** | À la demande, côte à côte, réversible ; Verbatim jamais supprimé ; le texte conserve mots et tournures, **seul l'audio conserve accent, rythme, silences** |
| P0-9 | 60-100 questions éditorialisées FR | Corpus original, séquencé du facile vers l'intime |
| P0-10 | Écoute familiale privée + réaction simple | Page d'écoute par histoire, ❤️/merci, notification au narrateur |
| P0-11 | **Moteur de complétion v1** | Spécifié état par état en §5.3 — « adaptatif par règles » : les règles sont fixes et documentées, leurs **paramètres** (cadence, créneau, canal, type de question) s'adaptent au comportement observé |
| P0-12 | 1 narrateur principal | Contributeurs photos/réactions autorisés |
| P0-13 | Livre : 2-3 templates fixes | BAT interactif obligatoire ; critères book-ready **R-6** (mots/durée/pages/thèmes), pas un compte d'histoires |
| P0-14 | 1 imprimeur FR + secours manuel | SLA/réimpression contractualisés ; délais de livraison = objectif confirmé par devis 0A |
| P0-15 | QR audio + pack hors-ligne | QR → page d'écoute (durée d'engagement D-8, accès doc 04 §7) ; pack hors-ligne : modalité décidée après test 0B (téléchargement confirmé vs clé USB, +6-9 € COGS) |
| P0-16 | Export complet gratuit | PDF, MP3, ZIP + manifeste ; remise **proactive** à la finalisation du livre (R-10.2) |
| P0-17 | Back-office support | Ré-émission de liens, replanification, édition transcription, gestes commerciaux, journalisation des actions (doc 04 §12), playbooks |
| P0-18 | **Parcours de validation explicite à faible friction** | Fin d'enregistrement : « Partager avec mes proches / Garder privé / Décider plus tard » ; à la transcription : correction facultative ; **le texte n'est partagé qu'après accord explicite** ; relance dédiée aux histoires enregistrées non validées ; délégation à un proche uniquement par mandat explicite et révocable (test 0B, sinon Phase 2). **Jamais de validation tacite.** Deux variantes UX testées en 0A/0B (validation immédiate post-enregistrement vs différée après transcription) |

## 4. Machine d'états (canonique R-4)
PROPOSÉE → ENREGISTRÉE → TRANSCRITE → À RELIRE → **VALIDÉE (explicite)** → PARTAGÉE → INCLUSE AU LIVRE. Retraits : MASQUÉE (conservée, non partagée) / ARCHIVÉE / CORBEILLE (30 j) / SUPPRIMÉE (irréversible, purge sauvegardes ≤ 90 j, politique publiée). Le narrateur accède à MASQUER et CORBEILLE à tout moment ; SUPPRIMÉE est définitive et le dit clairement.

## 5. Parcours

### 5.1 Happy path
Achat → invitation avec message personnel → opt-in (canal, cadence, consentements) → premier lien sous 72 h, question facile → **première histoire validée à J+3** → boucle hebdo (lien → enregistrement → choix de partage → transcription → correction facultative → écoute famille → réaction → notification) → book-ready (R-6) → BAT → impression → livraison → export proactif + pack hors-ligne.

### 5.2 Parcours d'échec (extraits — matrice complète en annexe de build)
Refus du cadeau (H0) → notification avec tact, remboursement ≤ 30 j · lien jamais cliqué / micro refusé / enregistrement abandonné / histoire non validée / famille silencieuse → **traités par le moteur §5.3** · pas de smartphone → détection honnête à l'opt-in, réponse écrite proposée, sinon remboursement · regret d'une confidence → MASQUÉE/CORBEILLE (imprimés : information explicite au BAT) · nom déformé → édition + lexique · corrections contradictoires → arbitrage narrateur/éditeur désigné, historique · décès en cours de projet → gel immédiat, directives post-mortem (doc 04 §6) · défaut print → réimpression gratuite · BAT jamais finalisé → relances M+12/13/14, dormant à M+15 (R-2).

### 5.3 Moteur de complétion — spécification v1 (le cœur du produit)
| État détecté | Déclencheur | Message → destinataire | Action proposée | Limite anti-culpabilisation | Métrique de reprise |
|---|---|---|---|---|---|
| Invitation non acceptée | J+7 puis J+14 | Rappel doux → narrateur ; à J+14 alerte → initiateur | Renvoyer via son propre canal ; message audio personnel | 2 relances max, puis H0 constaté | % acceptation post-relance |
| Lien non ouvert | J+3 | Renvoi sur l'autre canal (SMS↔email) → narrateur | — | 1 renvoi/question | % ouverture post-renvoi |
| Ouvert, micro refusé | Immédiat | Écran d'aide par OS → narrateur ; ticket proactif si 2 échecs | Fallback réponse écrite ; aide support | Pas de répétition du popup en boucle | % réussite après aide |
| Micro OK, enregistrement abandonné | J+2 | « Votre brouillon vous attend » → narrateur | Reprise du brouillon local | 1 rappel | % reprise brouillon |
| Enregistrée, non validée | J+4 | Relance dédiée validation → narrateur | 3 choix de P0-18 | 2 rappels, puis statut « en attente » silencieux | % validation post-relance |
| Validée, non écoutée | J+5 | Nudge → proches (« une nouvelle histoire de… ») | Lien d'écoute direct | 1 nudge/histoire | % écoute post-nudge |
| 3 histoires sans réaction famille | Au fil de l'eau | Suggestion → initiateur (« réagissez, ça le motive ») | Réaction en 1 tap | 1/mois max | Production post-réaction |
| Silence narrateur ≥ J+10 | J+10 | Question plus légère/ludique → narrateur | Changer de thème | Ton jamais culpabilisant (charte rédactionnelle) | % reprise |
| Silence ≥ J+21 | J+21 | **Alerte actionnable → initiateur** | 4 actions 1-tap : renvoyer via son WhatsApp / changer cadence / suggérer d'appeler soi-même son parent / **proposer l'option « Enregistrement par téléphone » (entrée « sauvetage », comptée à part — D-9)** | 1 alerte/mois | % reprise à J+30 |
| Pause demandée | Sur action | Confirmation + date de reprise → narrateur | Reprise programmée | Aucune relance pendant la pause | % retour de pause |
| Baisse tendancielle (cadence ÷2 sur 4 sem.) | Hebdo | Proposition de rythme réduit → narrateur | Passage quinzomadaire | Réduire vaut mieux qu'arrêter | Rétention à 8 sem. |
Chaque ligne est instrumentée (déclenchements, reprises) : **ces données de complétion sont l'actif défendable en construction.**

## 6. Critères d'acceptation (extraits)
**US-01 Enregistrement.** Page < 2 s en 4G ; explication AVANT le popup micro ; pause/reprise ; brouillon local ; upload résumable ; audio au projet < 5 min ; **compatibilité : Safari iOS N-2, Chrome Android N-2, Samsung Internet** ; survit à un appel entrant, à la mise en veille et à la purge d'onglet Safari (spike d'ingénierie dédié — la « reprise automatique iOS » est à **prouver**, pas à promettre) ; réussite 1er enregistrement non assisté ≥ 85 % (65+).
**US-02 Lien.** Validité 30 j ; réutilisable jusqu'à VALIDÉE ; révocation immédiate ; ré-émission ≤ 1 min ; jeton conforme doc 04 §12 (entropie, périmètre strict, aucune donnée personnelle dans l'URL).
**US-03 Rendus.** Verbatim/Fluide côte à côte < 10 min post-transcription ; réversible ; Verbatim jamais supprimé.
**US-04 Validation (P0-18).** Aucune visibilité famille avant VALIDÉE ; 3 choix en fin d'enregistrement ; texte partagé seulement après accord explicite ; masquage ≤ 2 taps.
**US-05 Export.** ZIP < 30 min pour 5 Go ; lien 7 j ; gratuit ; manifeste ; **génération proactive à la finalisation du livre**.
**US-06 Accessibilité.** WCAG 2.2 AA ; zones tactiles ≥ 44 px ; respect de l'agrandissement système ; lecteurs d'écran sur les parcours narrateur ; contrastes ; erreurs récupérables ; langage simple ; **aucun compte à rebours anxiogène**.

## 7. Instrumentation & KPIs (canonique R-5/R-7)
North Star : **projets vivants** (30 j). KPI business : % projets → book-ready (R-6) sous 15 mois — **ambition calibrée post-0B, pas un engagement v1** (le raccord 8 histoires → book-ready n'est pas encore observé). Funnel : achat → invitation délivrée → **acceptée (H0)** → consentement → clic 1er lien → micro OK → 1re histoire → 1re validation → 3e histoire → 1re écoute (≥ 30 s) → réaction → 10e histoire → book-ready → BAT → livré sans défaut → suite. Mesures H1 en **ITT** (dénominateur : accepteurs) + secondaire (activés). H2 : chaîne ouverte/écoute 30 s/réaction/notification/production ≤ 7 j + micro-expérience. Contre-métriques : remboursements, refus H0, échec micro, défauts print, coût support/projet, masquages/suppressions, NPS narrateur, **charge de l'Initiateur·rice (≤ 4 actions et ≤ 15 min/mois)**.

## 8. Spikes & travaux Phase 0 (préalables au build)
1. **Ergonomie lien+micro+validation** (0A) : 15-20 seniors, appareils réels, 2 variantes de validation.
2. **Spike d'ingénierie navigateur** (0A) : reprise après interruption iOS/Android, stockage local limité, enregistrements longs, 4G instable, Samsung Internet.
3. **ASR voix âgées** (0A/0B) : WER sur corpus réel + lexique noms propres.
4. **Print** (0A) : 3 devis réels → COGS contractuels ; délais confirmés.
5. **Audit concurrentiel FR** (0A) : doc 02 §6.
6. **WhatsApp Business** (0B, documentaire) : décision Phase 2.
7. **Test de demande « Enregistrement par téléphone » (0B) [v2.3, D-9]** : option à 25 € dans le tunnel pilote et préventes, plafonnée à 10 familles, remboursable ; livraison **humaine** : un membre de l'équipe appelle le narrateur au créneau choisi, une fois par semaine, ~15 min ; il annonce l'enregistrement en début d'appel, pose la question de la semaine, et demande en fin d'appel le choix de partage (P0-18, réponse journalisée) ; audio téléphonique versé au corpus ASR (spike 3) ; deux points d'entrée comptés séparément (achat, sauvetage J+21). Coût assumé déficitaire (§9). Décision à la Gate Phase 1 selon les seuils D-9 (doc 05 R-12).
Architecture : ADR post-Gate Phase 1. Contraintes non négociables : hébergement UE, abstraction ASR/LLM/SMS, upload résumable, journalisation des consentements, exigences sécurité doc 04 §12, SLO doc 04 §11.

## 9. Pricing & unit economics [HYPOTHÈSES — prix testés R-3]
Scénarios à 99 € TTC (82,5 € HT, TVA conservatrice 20 % — structuration fiscale du bundle à valider) :
| Scénario | Print+port | IA+SMS | Paiement | Support var. | Provisions réimpr./rembours. | **Coûts de durée de vie*** | COGS | Marge brute |
|---|---|---|---|---|---|---|---|---|
| Optimiste | 18 € | 4 € | 2,5 € | 4 € | 2,5 € | 3 € | 34 € | 48,5 € (**59 %**) |
| Base | 22 € | 6 € | 2,5 € | 7 € | 4 € | 5 € | 46,5 € | 36 € (**44 %**) |
| Pessimiste | 25 € | 8 € | 2,5 € | 10 € | 5 € | 8 € | 58,5 € | 24 € (**29 %**) |
| Base à 129 € (107,5 € HT) | 22 € | 6 € | 3 € | 7 € | 4 € | 5 € | 47 € | 60,5 € (**56 %**) |
\* Sauvegardes/restauration, maintien des pages QR sur la durée D-8, production des exports, hébergement longue durée, provision de fin de service, chargebacks, pertes transport, support émotionnel complexe, pages excédentaires.
**La marge brute n'est pas le critère : H3 = contribution après CAC > 20 €/projet**, mesurée au test d'acquisition 0B (CAC max par canal, conversion, panier moyen réel avec add-ons — modélisé à 120 €).
**Offre 149 € (D-7, ouverte)** : une session humaine 45-60 min coûte ~35-50 € en coût complet → à 149 € la contribution devient marginale. Options : 179-199 €, session 20-25 min, ou test d'appétence assumé non rentable en 0B. Décision à la Gate Phase 1.
Fair use publié (ex. 60 min d'audio/mois) ; garantie de complétion plafonnée et provisionnée.
**Option « Enregistrement par téléphone » 25 € (D-9)** : au pilote, 12 appels × ~15 min ≈ 3 h humaines, soit ~60-90 € en coût complet au ratio de D-7 — **déficitaire, assumé comme coût d'apprentissage plafonné** (10 familles). 25 € n'est pas un prix de référence pour une version automatisée ; il mesure la disposition à payer au même endroit du tunnel que Remento.

## 10. Sortie honorable des projets incomplets (contractualisée dès la vente)
Même en réussite, une part des projets n'atteindra pas book-ready. La promesse porte sur **un résultat adaptable à la matière recueillie** :
- Matière riche (R-6 atteint) → livre standard.
- Matière intermédiaire → **livret 24-60 pages**, même qualité de fabrication.
- Matière faible → **« Chapitre fondateur »** relié court + export complet + pack audio.
- À M+12 sans BAT : proposition automatique du format adapté + **1 prolongation de 3 mois incluse** (puis payante) ; à défaut, **crédit d'impression valable 24 mois**.
- Export seul si volume vraiment insuffisant, avec geste commercial encadré.

## 11. Roadmap (calendrier canonique R-8)
**0A (oct. 2026)** : travaux §8 + H0 + contrat pilote → **Gate 0A**. **0B (nov. 2026-fév. 2027)** : Pilote Fondateurs 30-50 familles (12 sem., 49 €, livrable réduit) + test acquisition/prix séparé → **Gate Phase 1 (mars 2027)**. **Build T2-T3 2027** → soft launch automne → **Noël 2027**. Phase 2 (2028) : WhatsApp natif, multi-narrateurs UI (si seuil atteint), questions adaptatives, commentaires vocaux, 2e imprimeur, archive continue, EN/ES, téléphonie automatisée ou option « appels humains » premium selon les seuils D-9. Phase 3 : capsules, recherche sémantique, B2B2C, coffre, funéraire après discovery.
**Règle : toute promotion de périmètre exige une preuve chiffrée du pilote. La donnée arbitre, pas la thèse.**
