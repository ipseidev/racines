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
`#FBF6EE`. **Porté le 4 septembre 2026** (T-132) : migration de réglages,
tokens de dessin en CSS, Fraunces variable auto-hébergée, mode sombre retiré.

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
sous le prix. Depuis le 4 septembre (T-134), **la structure entière de la page
d'accueil** : le bandeau d'offre en une ligne au-dessus de la barre, l'ordre des
sections, la répétition de l'appel à l'action à chaque palier, le secondaire
« Comment ça marche » dans le héros. Décision du fondateur : « ce sont les
leaders, alors copions-les ». Ce qui n'existe pas chez nous (presse, avis,
vidéos de clients) n'a pas d'emplacement vide ; il viendra avec les familles
pilotes.

**Laissé** : le vert acide ; les quatre mentions de Shark Tank et les cinq de
Trustpilot ; les citations de célébrités ; le guide comparatif contre les
concurrents ; les vidéos de clients en carrousel ; l'abonnement. La popup de
réduction, laissée le 3, a été **reprise le 4 au soir** (T-141), dans sa forme
et pas dans ses conditions : voir « La fenêtre de bienvenue » plus bas.

**Ajouté, parce que c'est le fond du produit** : le **mot à mot et le
texte mis au propre côte à côte**, tirés de notre propre corpus
(`docs/corpus/essai-01-pain.txt`) — la preuve plutôt que la promesse ; les
**trois choix** de la narratrice ; **un paiement, pas d'abonnement** ; les
appels à l'action à la **première personne** (« J'offre ce livre »,
« Je commence son livre »), avec une ligne de réassurance sous chaque bouton.

## Ce que la maquette ne règle pas

- **Le nom.** Arrêté le 4 septembre 2026 : Narrae, sur narrae.fr (T-143). La
  maquette le porte depuis ce jour ; « Racines » n'était qu'un nom de code. Dans
  l'application il vient de `BrandSettings`, jamais en dur.
- **Les photos.** De banque, donc provisoires. Crédits dans
  `landing/img/credits.json` — licence Unsplash, attribution non requise mais
  due.
- **Le prix.** 89 € depuis le 4 septembre (T-134), par migration de réglages ;
  la maquette du 3 portait encore les 49 € du réglage pilote.
- **La garantie.** « Satisfait ou remboursé pendant trente jours », sans
  justification, remplace les quatorze jours de rétractation du droit de la
  consommation (T-134). C'est un engagement commercial qui dépasse la loi ; il
  est dans la relecture juridique du bloc 10 (05 §6, ligne 12quinquies).
- **La propagation** est faite (4 septembre 2026, T-132 et T-133) : fondations,
  puis narrateur, famille, public, Initiateur·rice, dans cet ordre. Chaque
  famille a été relue à l'écran à 390 px et passée par ses tests bout en bout
  sur les assets construits. Reste hors du dessin : les pages
  d'authentification du kit, en anglais et en dur.

## Ce que la propagation devra tenir

Budget **150 Ko** de JavaScript sur les pages narrateur et famille · polices
**libres et auto-hébergées** · cibles tactiles **44 px** · **zéro** violation
axe sérieuse · nom de marque **jamais en dur** (`BrandAgnosticTest`) · thème
clair seul.

## Les composants produit et le mouvement (passe du 4 septembre, T-135)

À partir du tunnel d'achat, les pages produit n'utilisent plus les contrôles
natifs du navigateur. Les composants vivent dans `resources/js/components/form`
et ne dépendent d'aucune bibliothèque (budget de 150 Ko des pages narratrice) :

| Composant | Rôle |
|---|---|
| `Field`, `TextField`, `TextAreaField`, `PasswordField`, `SelectField` | libellé au-dessus, aide dessous, erreur en fondu à l'endroit du champ |
| `ChoiceCard` | un vrai bouton radio, dessiné, dans une carte entièrement cliquable |
| `CheckField` | une case dessinée et son texte d'un seul tenant |
| `Counter` | un entier borné : moins, la valeur, plus ; le bouton qui ne mène nulle part se désactive |
| `SubmitButton` | l'action de la page ; pendant l'envoi, une roue et « Un instant… » |
| `Stepper` | la progression : « Étape 2 sur 6 » et une barre sur téléphone, les noms sur bureau |
| `OptionCard` | une option à ajouter, comme chez le leader : image, titre, prix, « Ajouter », puis « Ajouté » et « Retirer » |

**Le mouvement** est en CSS pur, dans `app.css` : une courbe (`--ease-soft`),
deux entrées (`rise-in` 0,45 s, `pop-in`), `.enter` pour ce qui arrive à
l'écran, `.press` pour ce qu'on presse (2 % de moins sous le doigt). Tout
s'éteint sous `prefers-reduced-motion`. Un seul geste appuyé : le livre qui
s'ouvre sur la page de merci (`.book-*`). Les cases et radios cochés prennent la
couleur de marque, jamais celle de l'action : cocher n'est pas envoyer.

**Les pages de la narratrice (T-138)** ajoutent trois pièces : le grand bouton
rond d'enregistrement et son halo qui respire (`.record-halo`, éteint sous
`prefers-reduced-motion`), la barre de progression (`.progress-bar`) et le
lecteur audio partagé (`AudioPlayer`, libellés dans `common.player`). Règle
d'écran : une seule chose à la fois, la question en carte blanche sous un filet
d'or, puis un seul geste. Une confirmation est une coche qui apparaît
(`animate-pop-in`) et une phrase qui dit ce qui vient.

**L'ouverture du cadeau (8 septembre, T-232)** est le second geste appuyé de
l'interface, après le livre qui s'ouvre. La page d'invitation — le moment H0,
le seul écran que quelqu'un découvre sans l'avoir demandé — commence par un
rideau crème (`.overture`) : le nom de marque seul sous un filet d'or, comme
une page de titre, puis « Bonjour Odette, », puis ce qu'on lui offre, une
phrase à la fois, chacune montant en fondu depuis un léger flou et s'effaçant
vers le haut. Sous douze secondes en tout (`lib/overture.ts`, deux cent
soixante millisecondes par mot, rallongé d'un quart par le fondateur après
l'avoir vu sur son téléphone : « ça va être lu par une personne âgée »), puis
le rideau tombe et la page monte pendant qu'il s'efface. La page est rendue dessous dès le départ et redit tout ce que
l'ouverture a dit : le rideau est un décor `aria-hidden`, pas une étape. Un tap
écourte la phrase en cours, un Tab fait tomber le rideau, et il ne se lève pas
sous `prefers-reduced-motion`. Sur la page, le salut passe en Fraunces italique
et la lettre de la personne qui offre aussi : les italiques portent les mots qui
comptent.

**Les accords sans cases (8 septembre, T-233).** Sur la même page, les cinq
cases à cocher disparaissent. L'ordre, arrêté par le fondateur après deux
passes : les réglages en clair (déjà posés par la personne qui offre), une
phrase qui nomme les cinq accords que le bouton donne, les deux boutons, puis
un accordéon « Vos accords » replié — un `<details>` natif dans une carte, le
« + » qui pivote comme sur la foire aux questions — où chaque titre d'accord
est lui-même un bouton qui ouvre son texte et sa version, en lignes de 44 px.
Rien n'est pré-coché : une case cochée d'avance n'est pas un consentement, et
c'est le geste sur « J'accepte », sous la phrase qui le décrit, qui donne les
cinq. Pour la personne, le résultat est celui que le fondateur demandait : rien
à cocher, un bouton. Dernière passe (T-234) : les titres de section en
Fraunces comme le titre de la page, les réglages dans une carte blanche, et le
champ de contact qui suit le canal choisi, déjà rempli.

**Les confettis des deux oui (8 septembre, T-235).** Sur l'écran de bienvenue
de la narratrice et sur la page de merci de l'acheteur, une seule salve façon
pétard : deux bouffées depuis les coins du bas, aux couleurs de la marque (or,
sauge, terracotta, lin), sans son ni boucle (`lib/celebrate.ts`,
`canvas-confetti` chargé à l'instant du tir). Douce pour la narratrice,
généreuse pour l'acheteur, où elle part quand la couverture du livre bascule.
Rien sous `prefers-reduced-motion`, et jamais deux fois pour le même oui.

**Rien à gérer (8 septembre, T-236).** Sous « Vos accords », un second
accordéon « Paramètres avancés », fermé, porte les souhaits pour plus tard avec
« Transmettre à ma famille » coché d'avance et la ligne « Après vous : vos
histoires pourront être transmises à votre famille, sauf choix contraire »
lisible sans l'ouvrir. L'écran de bienvenue ne pose plus de question : il dit
ce qui vaut. Le serveur n'écrit une directive que si la personne choisit autre
chose ou désigne quelqu'un.

## Les courriels (passe du 9 septembre, T-237)

Jusqu'ici, tout courriel partait dans le gabarit gris de Laravel : « Narrae »
en texte brut au-dessus d'une carte blanche, un bouton bleu, « Tous droits
réservés » en pied, et des objets restés en anglais sur les courriels du
framework (« Reset your password »). Le gabarit est
désormais le nôtre, et il suit une règle simple : **un courriel de la marque
ressemble à la page qu'il ouvre.**

- **Une lettre sur papier crème**, pas une carte sur du gris : le fond est le
  crème de la page, le texte est posé dessus, en Inter 19 px comme sur les
  pages de la narratrice. En tête, le pictogramme (en PNG : Gmail et Outlook
  ne dessinent pas un SVG) et le nom en Fraunces, centrés comme une page de
  titre, sous le filet d'or de l'ouverture. Le nom reste du texte : si la
  messagerie bloque les images, il reste lisible, et le pictogramme est
  décoratif au sens des lecteurs d'écran.
- **Le salut en Fraunces italique** (« Bonjour Odette, »), comme sur la page
  d'invitation : les italiques portent les mots qui comptent. La lettre de la
  personne qui offre est une citation, en Fraunces italique sous un filet
  d'or.
- **La question de la semaine dans sa carte** : blanche, à filet sable, un
  filet d'or, la question en Fraunces à 26 px, exactement `.record-card`. Un
  code à recopier (code à usage unique, code de réduction) est posé seul, en
  grand, sur lin, sans aucun lien à côté.
- **Un bouton, terracotta, et rien d'autre en terracotta.** Le « niveau » que
  Laravel attache à une notification (`success`, `error`) ne colore rien : un
  bouton se voit par son isolement. Sur téléphone il prend toute la largeur.
  Sous le message, le lien en clair pour qui ne peut pas presser le bouton.
- **Le pied est le même partout** et porte trois repères d'anti-hameçonnage
  (doc 04 §9) : l'adresse du support, le domaine dont partent nos liens, le
  nom de la marque avec « Données hébergées dans l'Union européenne », et la
  mention légale dès que l'administration la renseigne. Rien en or dans le
  pied : l'or est un filet, jamais un petit texte.
- **Thème clair seul**, déclaré au client (`color-scheme: light`). Les polices
  sont chargées depuis nos fichiers là où la messagerie le permet — Apple
  Mail, Mail d'iOS — et tombent sur Georgia et Arial ailleurs, y compris dans
  Word-Outlook, à qui on nomme les replis.

Ce que cela change dans le code : les composants sont dans
`resources/views/vendor/mail/html` (plus `question` et `code`, qui n'existent
pas chez Laravel), le thème est **une vue Blade** (`mail/themes/brand`) qui
lit les couleurs et les polices dans `BrandSettings` à chaque rendu — changer
la charte dans l'administration change les courriels sans déploiement —, et
`vendor/notifications/email` remplace le gabarit anglais. Les tons de dessin
(lin, sable, or) sont fixes, comme dans `app.css`. Vérifié à l'écran à 390 et
720 px sur huit courriels, et dans Mailpit. Pour relire : `sail artisan
demo:courriels --envoyer` rend les dix courriels du produit sur un décor
jetable et les poste dans Mailpit.

## L'espace Initiateur·rice (passe du 5 septembre, T-149)

Le checkpoint du bloc 10 l'a dit sans détour : l'espace avait la palette mais
ni le socle ni le mouvement du tunnel, « tout faisait formulaire basique ». La
passe applique une règle avant toute autre : **chaque geste répond là où il
est fait.** Le lien de la semaine apparaît dans la carte où l'on a cliqué, avec
« Copier » qui devient « Copié » ; « Écouter comme un proche » ouvre la page
d'écoute dans un nouvel onglet au lieu de remplir une boîte ailleurs ; l'ordre
des questions part tout seul ; ce que le serveur répond arrive en **toast**,
en bas et au centre, là où l'œil revient sur un téléphone tenu d'une main.

Les pièces vivent dans `resources/js/components/space` et ne dépendent
d'aucune bibliothèque :

| Composant | Rôle |
|---|---|
| `PageHeader` | l'œillet qui dit où l'on est, le titre en Fraunces, une ligne d'intention ; le même sur les cinq pages |
| `Pill` | une pastille d'état : sauge pour ce qui va, or pour ce qui attend un geste, marque pour l'acquis, sable pour ce qui dort |
| `IconButton` | un rond de 44 px pour un seul geste (monter, descendre, retirer), libellé lu et montré au survol |
| `ConfirmDialog` | avant un geste qui engage : retirer un accès, se rétracter ; le focus va sur « Annuler » |
| `ShareSheet` | le lien, « Copier » puis « Copié », WhatsApp, SMS prérempli |
| `Avatar` | les initiales d'un proche, sur lin |
| `Toasts` | les retours du serveur, trois secondes et demie, un message identique remplace le précédent |

Les onglets de la barre portent un soulignement d'or qui glisse (`.tab`), et la
barre défile au doigt sous 640 px avec un fondu à droite. Les sections entrent
en fondu décalées de quatre-vingts millisecondes (`lib/motion.ts`). La page des
questions montre **les cinq prochaines dans l'ordre du moteur**, avec des
flèches et « Poser en premier » plutôt qu'un glisser-déposer, qui coûte au
clavier et au doigt plus qu'il ne rend ; « Voir 10 de plus » déroule le reste,
« Écartées » et « Déjà posées » se replient dessous.

Deux leçons : une bibliothèque qui injecte sa feuille de style en JavaScript
(`sonner`) arrive nue sous une politique `style-src` à nonce, d'où des toasts
maison ; et une date française s'écrit « 1er septembre », ce qu'`Intl` ne sait
pas, d'où `lib/dates.ts` pour tout l'espace.

## La fenêtre de bienvenue (passe du 4 septembre au soir, T-141)

Demande du fondateur, captures de Remento à l'appui : « donner une réduction
en échange d'un email ». La **forme** est celle du leader, en deux temps : la
promesse et un seul bouton (« 10 % offerts », « Je prends ma réduction »),
puis le champ et « Recevoir mon code » ; texte à gauche sur crème, photo à
droite, croix dans un disque blanc. Le **fond** suit nos règles :

- **Elle attend six secondes**, jamais au chargement : on lit la promesse
  avant qu'on propose autre chose. Elle entre comme tout le reste, dix pixels
  en fondu (`enter`) : le pop-in et son rebond, essayés d'abord, étaient
  « beaucoup trop violents » pour une fenêtre entière. Fermée, elle se tait trente jours ; le code
  demandé, elle se tait pour de bon (mémoire du navigateur, et cookie côté
  serveur).
- **Une seule action terracotta** par écran, la croix et « Non merci » en
  couleur de marque ou en texte souligné. Le titre porte Fraunces, l'œillet et
  le filet d'or de la page.
- **La case des nouvelles est à part, décochée, jamais requise** : l'adresse
  sert à envoyer le code, et à rien d'autre sans la case. Le leader conditionne
  le code à l'accord marketing ; nous non, par cohérence avec la case marketing
  du tunnel (bloc 10 §6.3). La ligne de petits caractères le dit tel quel.
- **La réduction est un pourcentage**, 10 % de toute la commande, décidé le
  soir même à la place des 10 € du premier jet : c'est ce que le coupon Stripe
  applique, et le récapitulatif l'écrit en euros.
- **Le code part par courriel, jamais à l'écran** : c'est ce qui fait qu'une
  adresse laissée existe. Il se pose au récapitulatif du tunnel (« J'ai un code
  de réduction »), ou tout seul si la commande se fait depuis le même appareil.
- **Un `<dialog>` natif**, pas une bibliothèque : le navigateur tient le piège
  du focus, Échap et l'arrière-plan inerte, et il n'injecte aucune feuille de
  style, que la politique de sécurité des pages publiques refuserait. Le focus
  est posé sur l'action, pas sur la croix.
- **Relue à 390 px** : la photo passe en bandeau au-dessus, tout tient sans
  défilement sur un écran de 780 px de haut.

Trouvé au passage par l'analyse d'accessibilité : les utilitaires
`animate-rise-in` et `animate-pop-in` n'obéissaient pas à
`prefers-reduced-motion`, parce que la règle qui les éteint vivait dans la
couche `components` et que Tailwind les émet dans `utilities`, qui l'emporte.
La règle est désormais hors couche, et vaut partout.

## Le premier retour d'un prospect (4 septembre au soir, T-142)

« On ne comprend pas clairement ce que le site fait avant de scroller jusqu'à
Comment ça marche. » Le fondateur demande de reprendre le héros du leader,
dans **l'ordre** et le **choix** des informations. Ce qui change :

- **La photo d'abord sur téléphone**, à droite sur bureau : ce qu'on voit
  avant de lire doit déjà dire « une personne, sa voix ». Une vraie photo de
  famille autour du livre fera plus que toute phrase ; elle viendra des
  familles pilotes.
- **Le texte sous le titre dit qui parle, qui fait quoi, et ce qu'on reçoit**,
  dans cet ordre et en nommant la marque, comme le leader : « Votre proche
  parle, c'est tout. Chaque semaine, [marque] lui envoie une question,
  enregistre sa réponse, la met au propre et la relie dans un livre… ». Le
  titre reste la promesse arrêtée le 4 (T-134).
- **La carte « question de la semaine » quitte le héros, puis y revient le
  soir même (T-144).** Elle décrivait un rituel avant qu'on ait compris le
  produit, et le leader n'a rien de tel à cet endroit ; mais le fondateur y
  tient, et elle reste posée sur la photo, à cheval sur son bord bas.
- **Les en-têtes de section centrés passent à gauche sur téléphone** (Comment
  ça marche, ce que comprend l'achat, la double page) : centrés au-dessus de
  contenus alignés à gauche, ils cassaient la colonne de lecture. Ils restent
  centrés sur bureau, au-dessus des colonnes.
- **La maquette du livre s'empile sur téléphone** : la page d'écoute est posée
  sous la couverture et la chevauche un peu, comme une carte glissée dedans ;
  côte à côte, elle n'avait plus la place de ses mots.
- **La coche dans la pastille est au centre** : elle portait le décalage prévu
  pour s'aligner sur une ligne de texte.

## Les règles de propagation, pour les pages à venir

Trois règles suffisent à habiller une nouvelle page, et une relecture à l'écran
les corrige là où un script ne peut pas savoir ce que la page veut faire faire.

1. **L'action de la page, et elle seule, est en terracotta** — classe
   `bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep`,
   ou `.btn-primary`. Deux boutons terracotta sur un écran sont acceptables
   quand ce sont deux formes d'un même geste (« J'ai aimé » / « Merci »),
   jamais deux gestes différents.
2. **Une commande n'est pas une action** : l'onglet actif, le bouton de
   lecture, le dépôt d'une photo restent en couleur de marque (`bg-brand`).
   Un secondaire prend un contour dans la couleur de marque
   (`border-brand text-brand border-2`, ou `.btn-secondary`) — jamais un filet
   sable, qui ne se voit plus sur le crème.
3. **Le fond est le crème de la page.** Les panneaux de mise en avant sont sur
   lin (`.panel`), les cartes sont blanches à filet sable (`.card`), les
   champs sont blancs (`.input`). Les titres portent Fraunces et la couleur
   de marque par la règle de base sur `.font-display`.
4. **Les mots suivent les mêmes règles que les couleurs.** Aucun tiret long
   dans un texte visible : une virgule, un point ou deux points (décision du
   fondateur, T-134 : « ça fait trop IA »). Les appels à l'action sont à la
   première personne (« J'offre ce livre », « Je commence son livre »), et une
   ligne de réassurance les accompagne. Rien de ce que R-11 interdit, même
   traduit du leader : « pour toujours » n'est pas une promesse qu'on tient.
