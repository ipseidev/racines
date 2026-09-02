# Bloc 13 — Livre : book-ready, BAT, PDF, QR, impression

Statut : ☐ non commencé · Dépend de : 12 · Tag de fin : `bloc-13-done`
Références dossier : PRD P0-13, P0-14, P0-15, §10 (sortie honorable : livre, livret, chapitre fondateur, prolongation, crédit d'impression), R-6 (book-ready), R-2 (M+12 à M+15), doc 04 §7 (QR : lecture non authentifiée par défaut, code famille optionnel, D-8), §10 (BAT obligatoire, « l'imprimé est définitif ») ; décisions T-11, T-12.

## 1. Objectif

Le projet sait quand il est « book-ready » selon R-6, propose le format adapté à la matière, génère un PDF de BAT paginé avec un QR par chapitre, fait relire le lexique, recueille l'accord explicite « l'imprimé est définitif », et transmet la commande à l'imprimeur (manuellement au pilote). Les QR mènent à une page d'écoute lisible sans compte, protégeable par un code famille.

## 2. Pourquoi

Le livre est la promesse tangible. Le dossier interdit un seuil binaire d'histoires et exige un résultat adaptable. Le QR est la partie du livre qui dépend de nous dans la durée ; il doit être révocable et documenté.

## 3. Livrables

- Tables `books`, `book_chapters` (+ colonnes `print_credit_expires_at`, `extension_granted_at`, `proposed_format`).
- `ComputeBookReadiness`, `ProposeBookFormat`, jobs `RenderBookPdf`, commande `books:evaluate` (quotidienne).
- Gabarit `classic` (Blade + CSS Paged.js), polices embarquées, QR par chapitre.
- Espace Initiateur·rice `/espace/livre` : jauge, sélection et ordre des chapitres, version du texte, avant-propos, contrôle du lexique, génération du BAT, accord, commande, exemplaires supplémentaires.
- `PrintProvider` interface + `ManualPrintProvider` (ticket support avec PDF).
- Pages QR `/q/{token}` avec code famille optionnel.
- Ressource Filament `BookResource` (statuts d'impression, réimpression).

## 4. Packages

```bash
sail composer require spatie/browsershot endroid/qr-code
sail npm i pagedjs
```

Sail et Forge : Node ≥ 22, Chromium pour Puppeteer (`npx puppeteer browsers install chrome` dans un répertoire connu, chemin dans `BROWSERSHOT_CHROME_PATH`), `poppler-utils` (`pdfinfo`) pour compter les pages. Polices : télécharger les fichiers OFL des polices de `config/brand.php` dans `resources/fonts/` (chargées en `@font-face` locales, jamais depuis Internet au moment du rendu).

## 5. Tests à écrire d'abord

- `tests/Unit/Books/ComputeBookReadinessTest.php` : chaque seuil R-6 pris isolément (mots, minutes, pages estimées, thèmes, éléments sensibles validés) ; combinaison `ou` mots/minutes ; seules les histoires `validated|shared|in_book` comptent ; les `hidden|archived|trashed` ne comptent pas.
- `tests/Unit/Books/ProposeBookFormatTest.php` : `book` si ready ; `booklet` si ≥ 3 000 mots ou ≥ 25 min ; `founding_chapter` sinon ; à M+12 sans BAT → proposition + prolongation 3 mois posée une fois ; à M+15 → `dormant` + crédit d'impression 24 mois.
- `tests/Feature/Books/ChapterSelectionTest.php` : par défaut toutes les histoires validées dans l'ordre chronologique ; exclusion possible ; **impossible d'inclure une histoire non validée** (exception) ; version du texte = `edited` courant sinon `fluide` sinon `verbatim`.
- `tests/Feature/Jobs/RenderBookPdfTest.php` (Browsershot mocké via `App\Services\Pdf\HtmlToPdf` interface, `FakeHtmlToPdf` écrit un PDF minimal) : HTML rendu contient couverture, sommaire, un chapitre par histoire incluse, un QR par chapitre pointant vers `/q/{token}`, colophon avec la mention D-8 ; `page_count_estimate` mis à jour ; fichier stocké en `books/{book}/proof-vN.pdf`.
- `tests/Feature/Books/QrTokensTest.php` : un jeton `qr` par chapitre, émis une fois, réutilisé aux regénérations, révocable par la famille (`/espace/livre` et espace narrateur), pas d'expiration technique.
- `tests/Feature/Family/QrPageTest.php` : `/q/{token}` sans code famille → page d'écoute ; avec `family_code_hash` posé → demande de code, 5 essais/heure, cookie 30 jours ; histoire `hidden` après impression → page « Cette histoire n'est plus disponible en ligne » (l'imprimé reste) ; événement `listen_events.token_type = qr`.
- `tests/Feature/Books/ProofApprovalTest.php` : l'approbation exige les deux cases (`acknowledged_final_print`, `acknowledged_lexicon_reviewed`) ; pose `proof_approved_at/by` ; verrouille la sélection ; `ManualPrintProvider` crée un ticket avec le lien du PDF ; `printed_in_book = true` sur les histoires incluses au passage `ordered`.
- `tests/Feature/Books/LexiconCheckTest.php` : liste les noms propres détectés (metadata des rendus) absents du lexique ; marquer « vérifié » est requis avant approbation.
- `tests/Feature/Books/ExtraCopiesTest.php` : Checkout `extra_copy` × n, ticket d'impression complémentaire.
- `tests/e2e/book-proof.spec.ts` (tag `@slow`, local uniquement, Browsershot réel) : générer un BAT sur le projet de démonstration, ouvrir le PDF, scanner le QR (décodage via `jsQR` sur la capture) → la page d'écoute s'ouvre.

## 6. Étapes

### 6.1 Tables et calculs
- [ ] Migrations `create_books_table`, `create_book_chapters_table` (annexe B) + colonnes `proposed_format`, `extension_granted_at`, `print_credit_expires_at`, `acknowledged_final_print`, `acknowledged_lexicon_reviewed`, `foreword` text, `text_version_policy` (`edited_or_fluide|verbatim`).
- [ ] `ComputeBookReadiness(Project): BookReadiness` (mots, minutes, pages estimées = `mots/280 + photos×0,5 + chapitres×0,5`, thèmes, `sensitive_reviewed` = toutes les histoires avec `sensitive_flags` ont `visibility` posée explicitement par le narrateur) ; seuils dans `config('product.book_ready')`.
- [ ] `books:evaluate` (`daily()`) : met à jour `books` (créé à la première histoire validée), `book_ready_at`, `proposed_format`, applique les règles M+12 et M+15 de PRD §10, notifie `notifications.book.ready` (une fois) et `notifications.book.format_proposal`.

### 6.2 Gabarit `classic`
- [ ] `resources/views/book/classic/layout.blade.php` + `chapter.blade.php`, CSS `resources/css/book-classic.css` avec `@page { size: 200mm 250mm; margin: 18mm 16mm 20mm 16mm }` (taille dans `config('product.book.trim_size')`, à confirmer avec le devis 0A), pages de garde, titre courant, folios, `break-before: page` par chapitre, styles `.chapter-title`, `.question`, `.date`, `.photo` (pleine largeur, légende), `.qr-box` (QR 28 mm + « Scannez pour entendre {Prénom} raconter »), sommaire généré par Paged.js (`target-counter`).
- [ ] Colophon : nom de marque, URL, « Les QR de ce livre fonctionnent jusqu'au {date} et peuvent être prolongés ; un pack hors-ligne des enregistrements vous a été remis. » (date = `collection_started_at + PilotSettings::qr_commitment_years`, défaut 10, D-8), mention « Texte mis au propre à partir de la voix de {Prénom}, relu et validé par {Prénom}. »
- [ ] `App\Services\Pdf\HtmlToPdf` (interface) : `render(string $html, PdfOptions): string $path` ; `BrowsershotHtmlToPdf` (`->setNodeBinary()`, `->setChromePath()`, `->paperSize(200, 250, 'mm')` selon config, `->margins(0,0,0,0)` (les marges sont en CSS), `->waitForFunction('window.PAGEDJS_DONE === true')`, `->timeout(300)`) ; `FakeHtmlToPdf`.
- [ ] Le HTML inclut `pagedjs` depuis les assets Vite (`resources/js/book/paged.ts` qui pose `window.PAGEDJS_DONE = true` à la fin) et les polices locales en `@font-face`.
- [ ] `RenderBookPdf(Book)` (file `exports`, `timeout 900`) : sélection des chapitres, émission des jetons `qr` manquants, rendu, `pdfinfo` pour `page_count_estimate`, stockage `books/{book}/proof-v{n}.pdf`, notification `notifications.book.proof_ready`.

### 6.3 QR
- [ ] `App\Services\Qr\QrImage::svg(string $url): string` (endroid, niveau de correction `M`, marge 2 modules).
- [ ] Route `GET /q/{token}` (`resolve.token:qr`) → si `projects.family_code_hash` : page `family/FamilyCode` puis cookie signé ; page `family/Story` en mode `qr` (pas de liste, pas de réactions, bouton « Voir toutes les histoires » qui demande un lien personnel).
- [ ] Révocation : espace narrateur et `/espace/livre` (« Désactiver le QR de cette histoire ») ; réémission par le support.

### 6.4 Espace `/espace/livre`
- [ ] `initiator/Book.tsx` : jauge de matière (mots, minutes, pages, thèmes) avec explication « Le livre se déclenche quand la matière suffit, pas à un nombre d'histoires » ; format proposé et alternatives ; liste des chapitres (inclure/exclure, glisser-déposer, version du texte) ; avant-propos (texte ≤ 1 500 caractères) ; « Contrôle des noms propres » (liste, ajout au lexique → régénération) ; « Générer le BAT » (asynchrone, état) ; visionneuse PDF ; deux cases d'accord ; « Approuver et commander » ; après commande : suivi (`ordered`, `printed`, `delivered`), « Exemplaires supplémentaires », « Signaler un défaut » (ticket `print_defect` → réimpression gratuite, doc 04 §10).
- [ ] L'éditeur désigné a les mêmes droits que l'Initiateur·rice sur cette page.

### 6.5 Impression
- [ ] `App\Services\Print\PrintProvider { order(Book): PrintOrder; status(PrintOrder): PrintStatus; }` ; `ManualPrintProvider` : ticket `print_order` avec lien temporaire du PDF (24 h, régénérable), champs à remplir par le support (`print_order_ref`) ; l'adaptateur API d'un imprimeur viendra après le devis 0A.
- [ ] `BookResource` Filament : statuts, `Marquer imprimé`, `Marquer livré` (déclenche l'export proactif du bloc 14), `Réimpression` (nouveau ticket, motif).
- [ ] Passage `ordered` → `IncludeInBook` sur chaque histoire incluse, `printed_in_book = true`.

### 6.6 Clôture
- [ ] Annexe B, `04_VERSIONS.md` (browsershot, endroid, pagedjs), `.env.example` (`BROWSERSHOT_*`), `config/product.php` (`book`).
- [ ] `docs/runbooks/impression.md` : procédure manuelle du pilote (récupérer le PDF, passer commande, saisir la référence, suivre).
- [ ] `sail composer check`, `sail npm run check`, `sail npm run e2e`, CI verts ; commit `chore(bloc-13): terminé`, tag `bloc-13-done`.

## 7. Checkpoint démontrable

1. Sur le projet de démonstration enrichi à 10 histoires validées (seeder `DemoBookSeeder`), `sail artisan books:evaluate` → `proposed_format = booklet` (matière intermédiaire) ; forcer 12 000 mots → `book`.
2. `/espace/livre` : exclure une histoire, ajouter un nom au lexique, générer le BAT → PDF ouvert, sommaire correct, un QR par chapitre.
3. Scanner un QR avec un téléphone : page d'écoute sans compte ; poser un code famille, rescanner : le code est demandé.
4. Approuver sans cocher les cases : refus ; cocher, approuver : ticket d'impression dans Filament avec le PDF.
5. Marquer « livré » : l'export proactif du bloc 14 est mis en file (vérifiable après le bloc 14).

## 8. Critères de sortie

- [ ] `ChapterSelectionTest` prouve qu'aucune histoire non validée ne peut entrer dans un livre.
- [ ] Le PDF ne charge aucune ressource distante (polices et Paged.js locaux) ; vérifié par un test qui inspecte le HTML rendu.
- [ ] La mention D-8 et le pack hors-ligne sont dans le colophon.

## 9. Règle de décision par défaut

Tant que l'imprimeur n'est pas connu, le PDF reste en RGB sans passe PDF/X ; le format 200 × 250 mm est un placeholder configurable. Aucune promesse de délai de livraison n'apparaît dans l'interface avant le devis (doc 03 P0-14).

## 10. Note de checkpoint

_Date, exécutant, résultat, écarts :_
