# Direction artistique

**Créé le 3 septembre 2026.** Ce dossier tient la direction visuelle du produit :
ses choix, leur pourquoi, et les maquettes qui les éprouvent. La méthode fixée
par le fondateur : **une page d'exemple d'abord**, validée ensemble, puis
propagation à l'application, une famille de pages à la fois.

```
docs/design/
├── README.md              ← ce fichier : les décisions
└── landing/
    ├── index.html         ← maquette autonome de la page d'accueil (à ouvrir dans un navigateur)
    └── img/               ← photos de banque (Unsplash, licence libre) et leurs crédits
```

Ouvrir la maquette : `open docs/design/landing/index.html`, ou en ligne, images
incluses : <https://claude.ai/code/artifact/d50fedb3-795c-4c1f-9a88-7adfb2f13d13>.

## Ce qu'on a décidé, et pourquoi

Six questions posées le 3 septembre, six réponses du fondateur :

| Question | Décision |
|---|---|
| Quelle page en premier ? | **La landing**, quelques sections seulement — assez pour juger la direction, pas toute la page |
| Les images ? | **Photos de banque pour l'instant.** Les vraies photos viendront des familles pilotes |
| Jusqu'où ressembler à Remento ? | **Inspiré, pas copié.** On prend le meilleur, on adapte le reste |
| Le registre ? | **Adapté au français** : sobre, chaleureux, « vous » sur la landing |
| Le mode sombre ? | **Thème clair uniquement** |
| Le périmètre ? | Les 25 pages Inertia. **Filament hors périmètre** — il a son thème, et le back-office doit être juste, pas beau |

## La palette

Issue de l'**analyse colorimétrique du fondateur** (3 septembre 2026), qui
supplante les tokens du kit. Le raisonnement qui la porte : la teinte d'un
bouton compte moins que son **isolation** — l'effet Von Restorff — donc la
couleur d'action est la seule couleur chaude saturée de la page, et elle ne
sert à rien d'autre. Et l'œil qui vieillit voit mieux les longueurs d'onde
chaudes que les bleus et les pastels : ce que la double cible — l'acheteur et la
narratrice — impose comme contrainte, pas comme goût.

| Rôle | Nom | Hex | Règle d'emploi |
|---|---|---|---|
| Fond de page | Crème chaud | `#FBF6EE` | |
| Surface | Blanc | `#FFFFFF` | Cartes, panneaux |
| Section chaude | Lin | `#F3EADB` | Un fond de section sur deux au plus |
| Filets | Sable | `#E0D6C7` | Bordures, séparateurs, champs |
| Marque | Vert forêt chaud | `#2F4A3F` | Titres, navigation, texte des cartes ; 9:1 sur crème |
| Bandeau sombre | Forêt profond | `#24392F` | Fond du bandeau « ce qui ne change pas » — dérivé du précédent |
| **Action** | **Terracotta brûlée** | **`#B0432A`** | **Les boutons d'action, et rien d'autre.** Texte blanc, 5,7:1 |
| Décor | Sauge | `#7C9A8E` | Onde sonore, aplats sans texte |
| Détail | Or chaud | `#C9A24B` | Filets, numéros d'étape, liseré — **jamais en petit texte** |
| Texte | Charbon chaud | `#26211C` | 14,8:1 sur crème |
| Texte secondaire | Brun-gris | `#5A5049` | ≈ 6,9:1 sur crème |

Ce que cela change par rapport aux tokens actuels de `config/brand.php` : le
vert acide `#D9E76C` **disparaît** — c'était la couleur d'accent de Remento, et
un accent vert sur une marque verte se fond au lieu de se voir. Le vert sapin
`#1F3D2B` se réchauffe en `#2F4A3F`, le crème `#F7F5EF` s'éclaircit en
`#FBF6EE`. Ces trois valeurs sont à porter dans `BrandSettings` au moment de la
propagation — pas avant que la maquette soit validée.

## La typographie

- **Titres : Fraunces**, déjà présente, mais poussée dans ses axes variables —
  `SOFT 50, WONK 1`, taille optique 144 pour les titres. C'est ce qui fait un
  serif de **livre** plutôt qu'un serif de site : la même famille que le kit
  livre, réglée autrement. Les italiques portent les mots qui comptent.
- **Texte : Inter**, 400/500/600, **19 px**, interligne 1,6, aligné à gauche.
  Dix-neuf et non seize, parce que la seconde cible a quatre-vingts ans.
- **Étiquettes** : Inter 600, 13 px, capitales espacées de 0,1 em.
- On charge **deux** familles, pas quatre : Instrument Sans et Newsreader, que
  le kit avait laissées, ne servent à rien et pèsent sur une page en 4G.

Toutes libres (OFL), toutes auto-hébergées dans l'application (T-40) — la
maquette seule passe par Google Fonts, pour rester un fichier autonome.

## Ce qu'on a pris à Remento, et ce qu'on a laissé

**Pris, et adapté** : l'air entre les sections ; le sérieux du tunnel d'achat
(deux colonnes, un pas à la fois, un serif par titre d'étape) ; l'idée d'une
section chaude pour montrer la page du livre avec son code ; le mockup
livre-plus-téléphone comme seul objet visuel répété ; le rappel des inclusions
sous le prix.

**Laissé** : le vert acide ; les quatre mentions de Shark Tank et les cinq de
Trustpilot ; les citations de célébrités ; la popup de réduction et la
newsletter contre remise ; le guide comparatif contre les concurrents ; les
vidéos de clients en carrousel ; l'abonnement.

**Ajouté, parce que c'est le fond du produit** : la question de la semaine
comme **objet du héros** — c'est le rituel qu'on vend ; le **mot à mot et le
texte mis au propre côte à côte**, tirés de notre propre corpus
(`docs/corpus/essai-01-pain.txt`) — la preuve plutôt que la promesse ; les
**trois choix** de la narratrice ; **un paiement, pas d'abonnement** ; les
appels à l'action à la **première personne** (« J'offre ce livre »,
« Je commence son livre »), avec une ligne de réassurance sous chaque bouton.

## Ce que la maquette ne règle pas

- **Le nom.** « Racines » est un nom de code ; la maquette le dit en toutes
  lettres. Le vrai passera par `BrandSettings`, jamais en dur.
- **Les photos.** De banque, donc provisoires. Crédits dans
  `landing/img/credits.json` — licence Unsplash, attribution non requise mais
  due.
- **Le prix.** L'analyse colorimétrique évoque 89 € ; le pilote est configuré à
  **49 €** (`PilotSettings`), et c'est ce chiffre que porte la maquette.
- **Le délai de rétractation** affiché — quatorze jours — est celui du droit de
  la consommation ; le texte exact attend la relecture juridique du bloc 10.
- **La propagation.** Rien n'est encore porté dans l'application. Ce sera fait
  famille de pages par famille de pages, en commençant par celles que voient
  les narrateurs, et en tenant le budget de 150 Ko.

## Ce que la propagation devra tenir

Budget **150 Ko** de JavaScript sur les pages narrateur et famille · polices
**libres et auto-hébergées** · cibles tactiles **44 px** · **zéro** violation
axe sérieuse · nom de marque **jamais en dur** (`BrandAgnosticTest`) · thème
clair seul.
