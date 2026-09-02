# Spike ASR — choix du fournisseur de transcription

**Bloc 06.** Ce document fixe la **règle de choix** et la façon de la rejouer.
Les chiffres, eux, vivent dans les comptes rendus datés produits par la
commande : `docs/spikes/asr-AAAA-MM-JJ.md`.

## La règle

> Le fournisseur par défaut est celui dont le **WER médian** est le plus bas
> sur le corpus. **Si l'écart est inférieur ou égal à 2 points, Gladia
> l'emporte** — hébergement UE.

Deux points, pas zéro : en dessous, l'écart n'est pas distinguable du bruit
d'un corpus d'une dizaine d'enregistrements, et la juridiction des données de
voix de personnes âgées pèse plus qu'une décimale de WER.

Le WER médian, pas le moyen : un seul enregistrement très bruité tire une
moyenne et fait choisir le mauvais fournisseur. Le **p90** est reporté à côté
pour surveiller la queue — c'est lui qui dit combien de familles reçoivent une
transcription qu'il faudra reprendre à la main.

## Le corpus

`tests/bench/asr/corpus/` — **ignoré par git**, et ce n'est pas un détail :
ce sont des voix de personnes identifiables, avec leur consentement, qui ne
partent pas sur GitHub.

Une paire par enregistrement :

```
tests/bench/asr/corpus/
├── 01-marie-crepes.mp3     ← l'audio
├── 01-marie-crepes.txt     ← la transcription de référence, relue à l'oreille
├── 02-…
```

Composition attendue (Phase 0A) :

- **au moins 10** enregistrements de personnes de **65 ans et plus** ;
- enregistrés **sur leur smartphone**, dans leur pièce habituelle, pas en studio ;
- au moins deux avec un bruit de fond réaliste (télévision, cuisine) ;
- au moins un accent régional marqué ;
- **+ 3 enregistrements téléphoniques** si l'option D-9 démarre : la bande
  passante téléphonique dégrade le WER, et c'est un fournisseur différent qui
  peut gagner sur ce sous-corpus.

La référence est **relue**, pas générée : une référence produite par un ASR
mesure la ressemblance entre deux ASR, pas la justesse.

## Rejouer la mesure

```bash
sail artisan asr:bench tests/bench/asr/corpus --providers=gladia,deepgram
```

La commande téléverse chaque audio sous le préfixe `bench/` du bucket, soumet
à chaque fournisseur, calcule le WER, **supprime les objets téléversés**, puis
écrit `docs/spikes/asr-AAAA-MM-JJ.md`.

Le WER est calculé sur des mots normalisés : minuscules, ponctuation retirée,
apostrophes typographiques ramenées à l'apostrophe droite, accents conservés,
nombres laissés tels quels. Deux transcriptions qui ne diffèrent que par la
casse ou la ponctuation ont un WER de 0 — c'est voulu : la ponctuation est
posée par la mise au propre (bloc 06 §6.4), pas par l'ASR.

## Ce que la mesure ne dit pas

- **La qualité perçue du livre** dépend de la mise au propre, pas seulement du
  WER. Un WER de 12 % sur des mots-outils se lit ; un WER de 6 % qui écorche
  trois noms propres ne se lit pas. D'où le lexique par projet.
- **Les noms propres** ne sont pas comptés à part dans le WER. Ils sont traités
  en amont : `lexicon_entries` alimente le vocabulaire envoyé au fournisseur,
  et corrige le texte après coup.
- **Le coût** figure dans le compte rendu à titre indicatif, tarif public au
  jour de la mesure. Il n'entre pas dans la règle de choix : à ce volume, la
  qualité de la transcription pèse infiniment plus que quelques euros.

## Changer de fournisseur

`ASR_PROVIDER` suffit. L'interface `TranscriptionProvider` ne change pas, et
les transcriptions déjà produites gardent leur `provider` en base : on peut
donc lire, plus tard, quel fournisseur a produit quel texte, et refaire une
transcription si un fournisseur s'avère mauvais sur un profil de voix.
