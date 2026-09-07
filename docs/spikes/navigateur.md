# Spike navigateur — survie de l'enregistrement sur appareils réels

**Statut : à exécuter.** Ce document est le protocole ; les résultats se
remplissent à la main, sur des téléphones réels. Aucun émulateur ne répond aux
questions posées ici : la purge d'onglet iOS, l'appel entrant et la coupure de
flux à la mise en veille ne se simulent pas honnêtement.

Référence : bloc 04 §6.7, doc 04 §11 (échec de capture < 2 % avant
confirmation, **zéro perte après confirmation**), PRD US-01.

## 1. Pourquoi ce spike existe

Le dossier interdit de promettre la reprise automatique sur iOS avant de
l'avoir prouvée. C'est le risque technique n°1 du projet et la première mesure
de la Gate 0A (réussite du premier enregistrement non assisté ≥ 85 %).

Ce que le code garantit déjà, prouvé par les tests automatiques :

- chaque tranche de cinq secondes est écrite dans IndexedDB **avant** tout
  envoi, en octets bruts et non en `Blob` — Safari iOS invalide les références
  de `Blob` quand il purge un onglet ;
- une interruption de flux ouvre un nouveau segment au lieu de perdre ce qui
  précède, et les segments sont recollés côté serveur par copie de flux ;
- aucun écran ne dit « votre histoire est enregistrée » avant qu'un
  `HeadObject` par segment ait confirmé que le stockage détient l'objet ;
- un envoi coupé reprend aux parts manquantes, sans renvoyer les autres.

Ce que seul un appareil réel peut dire : si ces garanties tiennent quand le
système d'exploitation décide de reprendre sa mémoire.

## 2. Prérequis

- **HTTPS obligatoire** : hors `localhost`, aucun navigateur ne donne le micro
  en clair. Deux voies : `./vendor/bin/sail share` (tunnel, le plus rapide) ou
  l'environnement de recette du bloc 16 s'il existe déjà.
- Un lien d'enregistrement valable. En local : `sail artisan migrate:fresh --seed`
  puis `/r/demo-record-linkxxxxxxxxxxxxxxxxxxxxxxxxxxx`.
- Pour observer sans toucher au téléphone : `sail artisan pail` d'un côté,
  `select event, count(*) from client_events group by 1;` de l'autre.

## 3. Matrice d'appareils

Quatre appareils au minimum pour clore le bloc. iOS et Android en version
courante et précédente, plus Samsung Internet, qui a son propre moteur de
permissions.

| # | Appareil | Système | Navigateur | Version |
|---|---|---|---|---|
| A | iPhone (à préciser) | iOS N | Safari | |
| B | iPhone (à préciser) | iOS N-1 | Safari | |
| C | Android (à préciser) | Android N | Chrome | |
| D | Android (à préciser) | Android N-1 | Chrome | |
| E | Samsung (à préciser) | Android | Samsung Internet | |

## 4. Scénarios

Chaque scénario se joue sur chaque appareil. La colonne qui compte est
**« perte »** : elle distingue une perte *avant* confirmation à l'écran, qui a
un objectif chiffré (< 2 %), d'une perte *après* confirmation, qui est
bloquante et interdit de clore le bloc.

| # | Scénario | Ce qu'on fait | Ce qu'on attend |
|---|---|---|---|
| S1 | Nominal | Parler 2 min, terminer, envoyer | Six écrans sans aide, confirmation affichée, objet présent au stockage |
| S2 | Appel entrant | Se faire appeler après 45 s, refuser l'appel, revenir | Écran « l'enregistrement s'est interrompu », reprise en un bouton, deux segments recollés |
| S3 | Verrouillage 2 min | Verrouiller l'écran au milieu, attendre 2 min, déverrouiller | Idem S2, aucune tranche manquante |
| S4 | Changement d'application 5 min | Basculer sur une autre application 5 min, revenir | Idem S2 |
| S5 | Purge d'onglet | Ouvrir dix onglets lourds, revenir sur la page | Brouillon proposé au chargement, envoi qui aboutit |
| S6 | Rechargement | Recharger la page en pleine phrase | « Reprendre mon enregistrement » |
| S7 | 4G bridée | Brider à 1 Mb/s pendant l'envoi | Progression qui avance, aucun abandon, confirmation à la fin |
| S8 | Coupure réseau | Couper le réseau pendant l'envoi, le rétablir | « Réessayer », reprise aux parts manquantes |
| S9 | Micro refusé puis autorisé | Refuser, suivre l'aide, réautoriser | Aide propre à la plateforme, un seul nouvel essai, puis enregistrement |
| S10 | Réponse écrite | Refuser le micro, choisir l'écrit | Histoire à l'état `recorded`, `answer_type = text` |
| S11 | Vidéo nominale (T-210) | Choisir « En vous filmant », se filmer 2 min, terminer, envoyer | Aperçu de soi avant de commencer, confirmation affichée, `kind = video`, `answer_type = video`, dérivés MP3 **et** MP4 produits |
| S12 | Vidéo interrompue (T-210) | Rejouer S2 en vidéo : appel entrant à 45 s | Deux segments recollés sans perte — c'est le scénario le plus lourd de tout le protocole, et celui qui a le plus de raisons d'échouer |
| S13 | Vidéo, poids et chaleur (T-210) | Filmer jusqu'à l'arrêt ferme de 20 min, relever la taille du fichier, la température du téléphone et le niveau de batterie | Fichier sous 400 Mo (borne serveur) ; **noter** si le téléphone chauffe au point d'inquiéter ou si `MediaRecorder` s'arrête de lui-même : la vidéo sollicite l'encodeur matériel, l'audio non |
| S14 | Vidéo lue par la famille (T-210) | Depuis un **autre** appareil que celui du tournage, ouvrir l'histoire partagée | La vidéo se lit. À jouer impérativement **Android → iPhone** : c'est le couple que le dérivé MP4 existe pour sauver |
| S15 | Caméra refusée (T-210) | Refuser la caméra, suivre l'aide, réautoriser | Aide de la **caméra**, pas celle du micro ; la voix seule reste offerte comme issue avant l'écrit |

## 5. Relevé

Une ligne par couple appareil × scénario.

| Appareil | Scénario | Résultat | Segments produits | Perte (avant / après confirmation) | Remarques |
|---|---|---|---|---|---|
| | | | | | |

## 6. Règle de clôture

- **Une seule perte après confirmation affichée est bloquante.** On corrige
  avant de clore le bloc 04, quel que soit le calendrier.
- **La vidéo ne bloque pas la clôture du bloc, la voix si.** Si S11-S15
  échouent là où S1-S10 passent, la conclusion n'est pas « le bloc n'est pas
  fini » mais « la vidéo n'est pas proposée sur cet appareil » : le bouton
  disparaît (`isRecordingSupported('video')`), la voix reste, et le motif part
  dans `03_DECISIONS.md`. Promettre de se filmer sur un téléphone où le
  fichier n'arrive pas serait la même faute que promettre la reprise iOS avant
  ce spike.
- Une perte avant confirmation se chiffre et se compare à l'objectif de 2 %
  (doc 04 §11). Au-delà, on documente la cause et la mitigation.
- Si `MediaRecorder` manque sur un appareil de la matrice : afficher l'aide et
  la réponse écrite, journaliser `recorder_unsupported`, **ne pas** ajouter de
  polyfill lourd ni de WebAssembly dans ce bloc, et consigner le besoin dans
  `03_DECISIONS.md` (règle de décision par défaut du bloc 04 §9).

## 7. Conclusion

_À remplir : date, exécutant, appareils réellement testés, verdict, décisions._
