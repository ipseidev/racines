# Bloc 12 — Photos, réponse écrite, contributeurs

Statut : ◐ en cours · Dépend de : 11 · Tag de fin : `bloc-12-done`
**⏳ Checkpoint jouable en local** — il demande un téléphone pour la photo HEIC (point 1) et un premier démarrage du service ClamAV (point 3). Détail dans [`05_A_FAIRE_HUMAIN.md`](../05_A_FAIRE_HUMAIN.md).

Références dossier : PRD P0-5, P0-6, P0-12, doc 04 §3 (photos : le déposant garantit ses droits, licence limitée), §12 (contrôle antivirus et format). La réponse écrite existe depuis le bloc 04 ; ce bloc la complète.

## 1. Objectif

Le narrateur, l'Initiateur·rice et les proches autorisés peuvent joindre des photos à une histoire, en sécurité (antivirus, formats, métadonnées de géolocalisation retirées), avec une légende, et ces photos suivent la visibilité de l'histoire et entrent dans le livre.

## 2. Pourquoi

« La photo, l'histoire et la voix sur une même page » est le cœur du produit imprimé. Les seniors envoient des photos de photos : il faut accepter ce qui arrive du téléphone et prévenir quand la qualité ne suffit pas pour l'impression.

## 3. Livrables

- spatie/laravel-medialibrary configuré sur le disque `r2`, collection `photos` sur `Story`, conversions `thumb` et `web`, original conservé.
- Antivirus ClamAV (service Sail, démon sur Forge) via règle de validation ; `FakeScanner` pour la CI.
- Actions `AttachPhoto`, `RemovePhoto`, `UpdatePhotoCaption` ; consentement `photo_rights` au premier dépôt.
- UI : ajout de photo dans la page d'enregistrement (facultatif après confirmation), l'espace narrateur, l'espace Initiateur·rice, la page famille pour les contributeurs.
- Galerie accessible dans `family/Story`.

## 4. Packages

```bash
sail composer require spatie/laravel-medialibrary sunspikes/clamav-validator
sail artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
sail artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"
```

Sail : service `clamav` (`image: clamav/clamav:stable`, port 3310, volume pour la base de signatures) ; variables `CLAMAV_HOST=clamav`, `CLAMAV_PORT=3310`. Forge : `apt-get install clamav-daemon` (bloc 16). Extension PHP `imagick` requise dans l'image Sail publiée (ajouter au Dockerfile) et sur Forge.

## 5. Tests à écrire d'abord

- `tests/Feature/Photos/AttachPhotoTest.php`
  - `it('attaches a jpeg to a story from the record token')`
  - `it('refuses files over 20 MB and non image mimes')`
  - `it('converts heic to jpeg when imagick supports it, otherwise refuses with a clear message')`
  - `it('strips gps exif and fixes orientation')`
  - `it('flags print_ready when the shortest side is at least 1200 px')`
  - `it('requires the photo_rights consent once per depositor')`
  - `it('queues thumb and web conversions on the media queue')`
- `tests/Feature/Photos/AntivirusTest.php` : un fichier contenant la chaîne EICAR est refusé (scanner `fake` en CI, ClamAV réel en local) ; le refus est journalisé sans stocker le fichier.
- `tests/Feature/Photos/PermissionsTest.php` : le narrateur peut retirer toute photo ; un contributeur (`can_contribute`) ajoute et retire les siennes seulement ; un proche sans droit ne peut pas ajouter ; l'Initiateur·rice ajoute et retire ; une photo sur une histoire non visible n'est pas servie par les routes famille.
- `tests/Feature/Photos/CaptionTest.php` : légende ≤ 200 caractères, modifiable par le déposant, le narrateur et l'éditeur.
- `resources/js/components/PhotoGallery.test.tsx` : miniatures ≥ 88 px, plein écran au clavier, texte alternatif = légende ou « Photo jointe par {prénom} ».
- `tests/e2e/photos-narrator.spec.ts`, `photos-contributor.spec.ts`.

## 6. Étapes

- [x] `config/media-library.php` : `disk_name` `r2`, `queue_name` `media`, `image_driver` `imagick`.
- [x] `Story implements HasMedia` ; `registerMediaCollections()` : `photos` (`acceptsMimeTypes jpeg,png,heic,heif,webp`, `useDisk('r2')`) ; `registerMediaConversions()` : `thumb` (400 px, `performOnCollections('photos')`), `web` (1600 px) ; propriété personnalisée `print_ready`, `caption`, `depositor_type/id`.
- [x] `App\Services\Images\Sanitizer::process(UploadedFile): UploadedFile` : `imagick` → `autoOrient`, `stripImage()` (retire GPS et métadonnées), conversion HEIC → JPEG si le décodeur est présent ; sinon exception `UnsupportedImage`.
- [x] Validation `['file', 'max:20480', 'mimetypes:…']` **sans** règle `clamav` : le scan passe par le port `Scanner` appelé dans `AttachPhoto`, et non par une règle de validation. Le paquet prévu ne déclare pas Laravel 13, et une règle de validation ne se double pas (décision T-118). `FakeScanner` reconnaît la chaîne EICAR, coupée en deux dans le code source — un fichier qui la contient en entier est détecté comme un virus par les antivirus des postes de travail, et le dépôt devient impossible à cloner.
- [x] `AttachPhoto(Story, UploadedFile, Model $depositor, ?string $caption)` : consentement `photo_rights` (une fois par déposant et par projet), sanitize, `addMedia`, `print_ready`, audit.
- [x] Routes : `POST /r/{token}/photos` (narrateur), `POST /n/{token}/stories/{story}/photos`, `POST /l/{token}/stories/{story}/photos` (contributeur), `POST /espace/projets/{project}/histoires/{story}/photos` (Initiateur·rice) ; `DELETE` et `PATCH caption` équivalents.
- [x] `PhotoUploader` et `PhotoGallery` (7 tests Vitest), branchés dans les **quatre** espaces : `family/Story`, `narrator/Space`, `narrator/Record` (écran facultatif replié, après la confirmation et jamais avant — proposer une photo au milieu ferait abandonner le récit à mi-chemin) et `initiator/Dashboard`. Le budget de 150 Ko de la page d'enregistrement tient toujours (113,9 Ko).
- [x] Les URL des photos passent par `temporaryUrl` (privé), régénérées à chaque chargement.
- [x] Annexe B (`media` + propriétés), `04_VERSIONS.md`, `.env.example` (`CLAMAV_*`).
- [x] `sail composer check`, `sail npm run check` et les 65 tests bout en bout verts. `photos-contributor` couvre les deux faces : le contributeur dépose, et le proche sans droit **ne voit pas le bouton**.
- [ ] Commit `chore(bloc-12): terminé` et tag `bloc-12-done` — après le checkpoint §7.

## 7. Checkpoint démontrable

1. Depuis un téléphone, après un enregistrement, ajouter une photo HEIC prise à l'instant : elle apparaît en JPEG, sans coordonnées GPS (vérifier avec `exiftool` sur l'original stocké), orientée correctement.
2. Un proche avec `can_contribute` ajoute une photo depuis `/l/…` ; un autre proche ne voit pas le bouton et reçoit 403 en POST.
3. Téléverser le fichier de test EICAR : refus avec message, rien dans R2, ligne dans l'audit.
4. Une photo de 800 px : message « un peu petite pour l'impression », `print_ready = false`.

## 8. Critères de sortie

- [x] Aucune photo n'est servie sans passer par `VisibleStoriesForFamilyMember` — `VisibilityTest` attaque **la route** pour chacun des neuf états qui ne sont ni `shared` ni `in_book`, et attend un 404. Une photo est un fichier, et un fichier se sert par une URL qu'on peut oublier de protéger.
- [x] L'original est conservé ; `thumb` et `web` en sont dérivées. C'est l'original qui partira à l'imprimeur, et une conversion ne remonte jamais en qualité.
- [x] `ANTIVIRUS_SCANNER=clamav` par défaut, forcé à `fake` par `phpunit.xml` seulement. Le service `clamav` est déclaré dans `compose.yaml` ; **il reste à le démarrer une première fois** (deux à trois minutes, un demi-gigaoctet de signatures) — point 3 du checkpoint.

## 9. Règle de décision par défaut

Si `imagick` ne décode pas le HEIC sur le serveur, refuser le fichier avec le message « Réglez votre appareil photo sur “Le plus compatible” ou envoyez la photo depuis votre galerie » plutôt que d'ajouter un service de conversion. Noter dans `03_DECISIONS.md`.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_

**2026-09-03 — code livré, checkpoint non joué.** Le chemin serveur est complet et éprouvé : scan antivirus par un port, assainissement qui retire **tout** l'EXIF, consentement une fois par déposant, stockage privé avec original conservé, droits en une seule porte, quatre espaces de dépôt, et la garde de visibilité vérifiée état par état sur la route famille. **53 tests Pest** pour le seul bloc 12 ; porte qualité verte à **1 088 tests Pest / 5 489 assertions**, PHPStan niveau 8, **107 Vitest**.

L'interface est branchée dans les quatre espaces, et le budget de 150 Ko de la page d'enregistrement tient toujours. **1 090 tests Pest / 5 494 assertions**, **107 Vitest**, **65 Playwright**.

Un invariant a été précisé en route, et il ne figurait nulle part : une photo est du **contenu**, comme le texte et la voix. Sur une histoire que le narrateur n'a pas partagée, l'Initiateur·rice ne voit donc que **ses propres** dépôts. Il aurait été facile de le perdre — le tableau de bord est « son » espace, et rien n'y rappelle qu'une photo jointe par le narrateur à un récit non partagé ne lui appartient pas encore.

**Écarts consignés : T-118 à T-122.** Deux à lire avant de reprendre. **T-118** remplace le paquet d'antivirus prévu par quarante lignes de protocole, avec deux choix de comportement qui comptent — `INSTREAM` parce que le démon ne voit pas notre disque, et **refus en cas de panne** parce qu'un fichier non scanné n'est pas un fichier propre. **T-119** dit ce qui n'est pas éprouvé : la conversion HEIC de bout en bout, faute de pouvoir écrire un HEIC dans cet environnement — c'est le point 1 du checkpoint, sur une vraie photo d'iPhone, et c'est de toute façon la seule preuve qui vaille.
