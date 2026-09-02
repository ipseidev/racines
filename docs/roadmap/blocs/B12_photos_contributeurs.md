# Bloc 12 — Photos, réponse écrite, contributeurs

Statut : ☐ non commencé · Dépend de : 11 · Tag de fin : `bloc-12-done`
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

- [ ] `config/media-library.php` : `disk_name` `r2`, `queue_name` `media`, `image_driver` `imagick`.
- [ ] `Story implements HasMedia` ; `registerMediaCollections()` : `photos` (`acceptsMimeTypes jpeg,png,heic,heif,webp`, `useDisk('r2')`) ; `registerMediaConversions()` : `thumb` (400 px, `performOnCollections('photos')`), `web` (1600 px) ; propriété personnalisée `print_ready`, `caption`, `depositor_type/id`.
- [ ] `App\Services\Images\Sanitizer::process(UploadedFile): UploadedFile` : `imagick` → `autoOrient`, `stripImage()` (retire GPS et métadonnées), conversion HEIC → JPEG si le décodeur est présent ; sinon exception `UnsupportedImage`.
- [ ] Règle de validation `['file', 'max:20480', 'mimetypes:image/jpeg,image/png,image/heic,image/heif,image/webp', 'clamav']` ; `App\Services\Antivirus\Scanner` interface avec `ClamavScanner` (via le package) et `FakeScanner` (marque tout fichier contenant `X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR`).
- [ ] `AttachPhoto(Story, UploadedFile, Model $depositor, ?string $caption)` : consentement `photo_rights` (une fois par déposant et par projet), sanitize, `addMedia`, `print_ready`, audit.
- [ ] Routes : `POST /r/{token}/photos` (narrateur), `POST /n/{token}/stories/{story}/photos`, `POST /l/{token}/stories/{story}/photos` (contributeur), `POST /espace/projets/{project}/histoires/{story}/photos` (Initiateur·rice) ; `DELETE` et `PATCH caption` équivalents.
- [ ] UI : `components/PhotoUploader` (sélection depuis la galerie ou l'appareil, aperçu, légende, message « Cette photo est un peu petite pour l'impression, elle restera lisible en ligne » si non `print_ready`) ; `components/PhotoGallery` ; intégration dans `narrator/Record` (écran facultatif « Ajouter une photo » après confirmation), `narrator/Space`, `family/Story` (bouton visible seulement si `can_contribute`), `initiator/Dashboard`.
- [ ] Les URL des photos passent par `temporaryUrl` (privé), régénérées à chaque chargement.
- [ ] Annexe B (`media` + propriétés), `04_VERSIONS.md`, `.env.example` (`CLAMAV_*`).
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts ; commit `chore(bloc-12): terminé`, tag `bloc-12-done`.

## 7. Checkpoint démontrable

1. Depuis un téléphone, après un enregistrement, ajouter une photo HEIC prise à l'instant : elle apparaît en JPEG, sans coordonnées GPS (vérifier avec `exiftool` sur l'original stocké), orientée correctement.
2. Un proche avec `can_contribute` ajoute une photo depuis `/l/…` ; un autre proche ne voit pas le bouton et reçoit 403 en POST.
3. Téléverser le fichier de test EICAR : refus avec message, rien dans R2, ligne dans l'audit.
4. Une photo de 800 px : message « un peu petite pour l'impression », `print_ready = false`.

## 8. Critères de sortie

- [ ] Aucune photo n'est servie sans passer par `VisibleStoriesForFamilyMember` côté famille.
- [ ] L'original est conservé ; les conversions sont dérivées.
- [ ] ClamAV réel scanne en local et sur Forge ; `FakeScanner` uniquement en CI.

## 9. Règle de décision par défaut

Si `imagick` ne décode pas le HEIC sur le serveur, refuser le fichier avec le message « Réglez votre appareil photo sur “Le plus compatible” ou envoyez la photo depuis votre galerie » plutôt que d'ajouter un service de conversion. Noter dans `03_DECISIONS.md`.

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
