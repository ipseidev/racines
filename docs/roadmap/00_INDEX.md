# Roadmap technique — index maître

**Version 1.0 — 2 septembre 2026.** Ce dossier est la source de vérité de l'exécution technique. Le dossier produit (`01_EXECUTIVE_MEMO.md` … `05_REFERENTIEL_GLOSSAIRE_SOURCES.md`, v2.3) est la source de vérité du *quoi* et du *pourquoi* ; en cas de conflit, le dossier produit prime et la roadmap doit être corrigée.

## Comment utiliser ce dossier (humain ou IA)

1. Lire dans cet ordre, une seule fois : `00_INDEX.md` (ce fichier), `01_CONVENTIONS.md`, `02_GLOSSAIRE_TECH.md`, `03_DECISIONS.md`.
2. Ouvrir le premier bloc dont le statut n'est pas « terminé » dans le tableau ci-dessous. Ne jamais commencer un bloc dont les dépendances ne sont pas terminées.
3. Dans le bloc : installer les packages listés (commandes exactes), **écrire d'abord les tests listés en §5**, les voir échouer, implémenter les étapes de §6 dans l'ordre, cocher chaque case au fur et à mesure, exécuter le checkpoint §7, vérifier les critères de sortie §8.
4. Un bloc est terminé quand : toutes les cases sont cochées, `sail composer check` est vert (voir conventions), le checkpoint a été démontré, un commit `chore(bloc-XX): terminé` a été créé et le tag `bloc-XX-done` posé.
5. Reporter le statut dans le tableau ci-dessous et dans `04_VERSIONS.md` si une version a été figée.
6. Si une information manque pour exécuter une étape, **ne pas inventer** : appliquer la règle de décision par défaut du bloc (chaque bloc en a une en §9) et noter la décision prise dans `03_DECISIONS.md` sous « Décisions prises en cours de route ».

## Principes non négociables

- **TDD partout.** Aucune ligne de code de production sans test rouge préalable. Back : Pest. Front : Vitest + React Testing Library. Bout en bout : Playwright. Détail dans `01_CONVENTIONS.md` §5.
- **Le narrateur est souverain.** Rien n'est visible des proches avant l'état `validated`. La validation est un acte explicite, jamais un délai écoulé. Toute violation est un bug bloquant.
- **Le lien est un jeton porteur.** Entropie ≥ 128 bits, jamais de donnée personnelle dans l'URL, périmètre strict, journaux masqués. Détail dans le bloc 03.
- **L'audio source est sacré.** Jamais remplacé, jamais supprimé hors état `deleted`. Le rendu verbatim n'est jamais supprimé.
- **Hébergement UE.** Serveur DigitalOcean en région européenne, Postgres managé en région européenne, R2 avec restriction de juridiction UE.
- **Marque variable.** Aucune occurrence du nom de marque dans le code ni les tests. Nom, domaine, couleurs, polices et expéditeur SMS viennent de `BrandSettings` (bloc 01).
- **La donnée arbitre.** Chaque règle du moteur de complétion émet un événement instrumenté (bloc 09, bloc 15).
- **Vocabulaire interdit** (R-11) : « pour toujours », « illimité », « QR autonomes », « les contenus appartiennent à la famille », « validation tacite/automatique », « garanti à vie ». Un test Pest vérifie les fichiers de langue (bloc 01).

## Ordre des blocs et avancement

Chaque bloc a un fichier dans `blocs/`. Les dépendances sont strictes.

| # | Bloc | Dépend de | Statut | Tag |
|---|---|---|---|---|
| 00 | [Fondations du dépôt et outillage](blocs/B00_fondations.md) | — | ☑ terminé (2026-09-02) | `bloc-00-done` |
| 01 | [Marque, réglages, admin Filament, i18n](blocs/B01_marque_reglages_i18n.md) | 00 | ☑ terminé (2026-09-02) | `bloc-01-done` |
| 02 | [Modèle de domaine et machine d'états](blocs/B02_modele_domaine.md) | 01 | ☑ terminé (2026-09-02) | `bloc-02-done` |
| 03 | [Jetons, OTP et sécurité des liens](blocs/B03_jetons_securite.md) | 02 | ☐ non commencé | `bloc-03-done` |
| 04 | [Page d'enregistrement narrateur et spike navigateur](blocs/B04_enregistrement.md) | 03 | ☐ non commencé | `bloc-04-done` |
| 05 | [Corpus de questions et envoi des prompts SMS/email](blocs/B05_prompts_envoi.md) | 04 | ☐ non commencé | `bloc-05-done` |
| 06 | [Transcription, rendu Fluide et banc d'essai ASR](blocs/B06_transcription_rendu.md) | 05 | ☐ non commencé | `bloc-06-done` |
| 07 | [Validation explicite, visibilité, retraits](blocs/B07_validation_retraits.md) | 06 | ☐ non commencé | `bloc-07-done` |
| 08 | [Écoute famille et réactions](blocs/B08_ecoute_famille.md) | 07 | ☐ non commencé | `bloc-08-done` |
| 09 | [Moteur de complétion v1](blocs/B09_moteur_completion.md) | 08 | ☐ non commencé | `bloc-09-done` |
| 10 | [Tunnel d'achat, Stripe, cadeau, opt-in narrateur](blocs/B10_tunnel_achat_optin.md) | 09 | ☐ non commencé | `bloc-10-done` |
| 11 | [Back-office support et journal d'audit](blocs/B11_backoffice_audit.md) | 10 | ☐ non commencé | `bloc-11-done` |
| 12 | [Photos, réponse écrite, contributeurs](blocs/B12_photos_contributeurs.md) | 11 | ☐ non commencé | `bloc-12-done` |
| 13 | [Livre : book-ready, BAT, PDF, QR, impression](blocs/B13_livre.md) | 12 | ☐ non commencé | `bloc-13-done` |
| 14 | [Export complet et droits RGPD](blocs/B14_export_rgpd.md) | 13 | ☐ non commencé | `bloc-14-done` |
| 15 | [Instrumentation, KPIs, tableaux de bord](blocs/B15_instrumentation.md) | 14 | ☐ non commencé | `bloc-15-done` |
| 16 | [Sécurité, SLO, sauvegardes, déploiement Forge](blocs/B16_securite_slo_deploiement.md) | 15 | ☐ non commencé | `bloc-16-done` |
| 17 | [Pilote : offre, option téléphone D-9, playbooks, go-live](blocs/B17_pilote_golive.md) | 16 | ☐ non commencé | `bloc-17-done` |

Statuts possibles : `☐ non commencé`, `◐ en cours`, `☑ terminé`.

## Pourquoi cet ordre

- Les blocs 00 à 03 posent le socle sans lequel rien n'est testable : outillage, marque variable, modèle de données, jetons.
- Le bloc 04 arrive tôt parce que la page d'enregistrement est le risque technique n°1 (reprise iOS, Samsung Internet) et que le dossier exige un spike avant toute promesse.
- Les blocs 05 à 09 construisent la boucle hebdomadaire complète : question envoyée, histoire enregistrée, transcrite, validée, écoutée, relancée. À la fin du bloc 09, le produit fonctionne de bout en bout pour une famille créée à la main dans l'admin.
- Le bloc 10 ajoute l'achat et l'onboarding. Il vient après la boucle parce qu'il ne sert à rien de vendre ce qui ne tourne pas.
- Les blocs 11 à 14 complètent le support, les photos, le livre et l'export.
- Les blocs 15 à 17 rendent le pilote observable, sûr et opérable.

## Définition de « terminé » pour un bloc

- [ ] Toutes les cases de §6 sont cochées.
- [ ] Tous les tests listés en §5 existent et passent.
- [ ] `sail composer check` passe : Pint, Larastan niveau 8, Pest.
- [ ] `sail npm run check` passe : TypeScript, ESLint, Prettier, Vitest.
- [ ] Les tests Playwright du bloc passent en local (`sail npm run e2e`).
- [ ] La CI GitHub Actions est verte sur `main`.
- [ ] Le checkpoint §7 a été exécuté par un humain et le résultat noté en bas du fichier du bloc (date, résultat, écarts).
- [ ] Les nouvelles variables d'environnement sont dans `.env.example` et dans `01_CONVENTIONS.md` §8.
- [ ] Les versions figées sont dans `04_VERSIONS.md`.
- [ ] Commit `chore(bloc-XX): terminé` et tag `bloc-XX-done`.

## Registre des risques techniques suivis

| Risque | Bloc qui le traite | Mitigation |
|---|---|---|
| Reprise d'enregistrement après appel entrant ou purge d'onglet iOS | 04 | Brouillon local IndexedDB par tranches, upload résumable, protocole de test sur appareils réels |
| Popup micro refusé par des seniors | 04 | Pré-explication avant la demande, écrans d'aide par OS, fallback réponse écrite |
| Qualité ASR sur voix âgées et audio téléphone | 06 | Banc d'essai WER sur corpus réel, lexique par projet, second adaptateur |
| Fuite d'un jeton porteur dans un journal ou un outil d'analytics | 03, 15 | Processeur Monolog de masquage, Telescope filtré, PostHog sans URL de jeton |
| Perte d'un audio confirmé | 04, 16 | Confirmation à l'écran seulement après `HeadObject` réussi, réplication vers un second bucket, restauration trimestrielle |
| Marque changée tardivement | 01 | Zéro occurrence du nom dans le code, test automatique |
| Vendeur R2 hors UE | 16 | Bucket créé avec juridiction UE, DPA, alternative Scaleway documentée sans changement de code |
| Coût humain de l'option téléphone D-9 | 17 | Plafond à 10 familles dans `PilotSettings`, compteur bloquant au checkout |

## Fichiers du dossier

```
docs/roadmap/
├── 00_INDEX.md                  ← ce fichier
├── 01_CONVENTIONS.md            ← arborescence, style, TDD, commits, env, sécurité
├── 02_GLOSSAIRE_TECH.md         ← termes métier FR ↔ identifiants de code EN
├── 03_DECISIONS.md              ← registre des décisions techniques et leur pourquoi
├── 04_VERSIONS.md               ← versions figées (rempli au bloc 00)
├── blocs/B00_… à B17_…          ← un fichier par bloc
└── annexes/
    ├── A_corpus_questions_v1.md ← 60 questions FR séquencées
    ├── B_modele_donnees.md      ← tables, colonnes, index, contraintes
    └── C_regles_moteur.md       ← les 11 règles du moteur, paramètres et événements
```
