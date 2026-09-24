# Le choix des questions selon le profil

23 septembre 2026. La dernière pièce de structure avant les écrans du tunnel d'après-achat.

## Ce que le tunnel remplira

Une ligne `project_profiles` par projet (`App\Models\ProjectProfile`) :

| Champ | Contenu | Vide veut dire |
|---|---|---|
| `facts` | les conditions de vie — `partner`, `children`, `grandchildren`, `career`, `knew_parents`, `siblings`, `migration`, `rural` — à vrai ou faux | on ne sait pas |
| `avoided_topics` | les sujets à éviter, étiquettes de question (`bereavement`, `war`…) | rien à éviter |
| `favored_themes` | les thèmes à mettre en avant | aucun |
| `buyer_relation` | l'acheteur est son enfant, son petit-enfant, son conjoint, un autre proche, ou elle-même | on ne sait pas |
| `buyer_focus` | des questions sur l'acheteur seul, ou sur tous ses enfants | pas de questions sur l'acheteur |
| `buyer_first_name`, `buyer_gender` | pour nommer l'acheteur et s'accorder à lui | pas de questions sur l'acheteur |
| `completed_at`, `skipped_at` | le tunnel fini, ou passé | — |

Le genre de la narratrice vit sur `narrators.grammatical_gender`. Un achat pour soi crée le profil dès la commande (`buyer_relation = self`), parce que le tunnel viendra quand le brouillon d'achat aura disparu.

## Les règles (`App\Support\QuestionProfile`, `App\Actions\PickNextQuestion`)

1. **Seul un « non » explicite retire une question.** Un profil vide, un tunnel passé, une case blanche : la question part comme avant. Le cas par défaut ne coûte rien à personne — un test vérifie qu'un profil vide envoie exactement ce qu'envoie un projet sans profil.
2. **Un sujet à éviter retire toutes les questions qui le portent.**
3. **Un thème choisi passe devant** : son rang dans le corpus est multiplié par 0,6. Les autres thèmes restent, et le livre en garde assez pour être prêt (5 thèmes, vérifié par test).
4. **Les questions sur l'acheteur demandent un « oui »** : « sur moi », un prénom, et un lien enfant, petit-enfant ou autre. Ni le conjoint, ni un achat pour soi, ni « sur tous ses enfants ».
5. **Leur rythme** : la première à la sixième question, puis une toutes les huit, six au plus — quatre à six sur cinquante-deux.
6. Les règles d'avant restent : une question avancée par l'Initiateur·rice passe devant, l'intime attend la sixième histoire validée, le moteur peut demander plus doux.

**La file affichée rejoue l'envoi.** `queue()` ne trie plus à part : elle choisit question après question avec les mêmes règles que `handle()`. Les questions sur l'acheteur tombent à des rangs, pas à des places du corpus ; un tri les aurait montrées ailleurs que là où elles partiront. Un test compare la file aux quarante premiers envois.

## Le tunnel qui remplit le profil (24 septembre 2026)

`/espace/projets/{projet}/personnaliser` (`PersonalizeController`, page `initiator/Personalize`, mise en page `personalize` sans navigation). Prototype validé : https://claude.ai/artifact/8KUsUyXNbD7oPuwcZZGuou.

- **Les écrans** : accueil (un paquet de trois questions du corpus qui se mélange), le lien, « Et vous ? » (sauf pour un conjoint), sa vie (oui / non / rien), les sujets à laisser de côté, les thèmes (trois au plus), puis le choix de la toute première question, puis la fin. Chaque écran se passe, et le tout aussi.
- **Une seule écriture** à la fin des thèmes remplit le profil et le genre de la narratrice. Les trois premières questions montrées ensuite sont calculées par le vrai choix des questions, sans les questions sur l'acheteur ; celle qu'on choisit devient une question avancée.
- **Trois portes** : la page de merci (`/espace/personnaliser`, qui mène au projet), un bandeau sur le tableau de bord tant que le tunnel n'a été ni fait ni passé, un lien sur la page des questions pour le refaire. Aucune pour qui raconte sa propre histoire.
- « A connu ses parents » n'est pas demandé : trop intrusif pour deux minutes, trop rare pour le poser à tous. La condition reste dans le corpus.
