# Versions figées

Relevé au bloc 00, le 2 septembre 2026. Mettre à jour à chaque ajout de dépendance. La colonne « Version » est celle effectivement installée, pas celle du manifeste. Une montée de version majeure est une décision à consigner dans `03_DECISIONS.md`.

## Runtime

| Composant | Version | Où | Note |
|---|---|---|---|
| PHP | 8.5.7 | Sail, CI, Forge | Le kit exige ^8.3 |
| Node.js | 24.18.0 | Sail, CI, Forge | Fourni par l'image Sail |
| PostgreSQL | 18.6 | Sail ; managé DigitalOcean en production | CI : image `postgres:18` |
| Redis | 8.10.1 | Sail ; Forge en production | CI : image `redis:7` |
| ffmpeg | 6.1.1 | Inclus dans l'image Sail | Bloc 06 |
| imagick | inclus | Extension `php8.5-imagick` de l'image Sail | Bloc 12 |
| Chromium | à installer | Bloc 13 (Browsershot) et Playwright | |
| ClamAV | à installer | Bloc 12 | |
| poppler-utils | à installer | Bloc 13 (`pdfinfo`) | |

## Composer

| Package | Version | Bloc |
|---|---|---|
| laravel/framework | v13.30.1 | 00 |
| laravel/sail | v1.67.0 | 00 |
| laravel/fortify | v1.39.0 | 00 |
| laravel/wayfinder | v0.1.21 | 00 |
| laravel/tinker | v3.0.2 | 00 |
| laravel/pail | v1.2.7 | 00 |
| laravel/pao | v1.1.4 | 00 |
| laravel/chisel | v0.1.1 | 00 |
| inertiajs/inertia-laravel | v3.3.2 | 00 |
| pestphp/pest | v5.1.3 | 00 |
| pestphp/pest-plugin-laravel | v5.0.1 | 00 |
| larastan/larastan | v3.11.0 | 00 |
| laravel/pint | v1.30.5 | 00 |
| filament/filament | v5.7.8 | 01 |
| spatie/laravel-settings | v3.4.6 | 01 |
| filament/spatie-laravel-settings-plugin | v5.7.8 | 01 |
| laravel-lang/common (dev) | v6.8 | 01 |
| spatie/laravel-model-states | v2.14.2 | 02 |
| spatie/laravel-permission | v8.3.0 | 02 |
| laravel/pennant | v1.26.0 | 02 |
| league/flysystem-aws-s3-v3 | v3.35.3 | 04 |
| laravel/horizon | v5.48.3 | 05 |
| resend/resend-laravel | v1.4.0 | 05 |
| twilio/sdk | v8.12.0 | 05 |
| svix/svix | v2.2.0 | 05 |
| anthropic-ai/sdk | v0.46.0 | 06 |
| laravel/cashier | _à installer_ | 10 |
| spatie/laravel-medialibrary | _à installer_ | 12 |
| sunspikes/clamav-validator | _à installer_ | 12 |
| spatie/browsershot | _à installer_ | 13 |
| endroid/qr-code | _à installer_ | 13 |
| maennchen/zipstream-php | _à installer_ | 14 |
| posthog/posthog-php | _à installer_ | 15 |
| spatie/laravel-backup, spatie/laravel-health | _à installer_ | 16 |

Telescope n'est pas installé : `laravel/pail` couvre le suivi des journaux en local. À réévaluer au bloc 11 si le besoin d'inspection des requêtes se confirme. S'il l'est, `ignore_paths` doit reprendre les sept préfixes de `TokenType::urlPrefixes()` (décision T-48).

Le bloc 03 n'installe **aucun** paquet : `random_bytes`, `hash`, Monolog et Laravel suffisent.

`pbmedia/laravel-ffmpeg` était prévu au bloc 06 et n'a **pas** été retenu : le transcodage tient en un appel `ffmpeg` et une lecture `ffprobe`, que `Process::fake()` éprouve directement (décision T-69). `ffmpeg` reste requis sur la machine ; les chemins des binaires viennent de `FFMPEG_BINARIES` / `FFPROBE_BINARIES`.

## npm

| Package | Version | Bloc |
|---|---|---|
| react, react-dom | 19.2.8 | 00 |
| @inertiajs/react | 3.7.0 | 00 |
| typescript | 7.0.2 | 00 |
| tailwindcss | 4.3.3 | 00 |
| vite | 8.2.2 | 00 |
| vite-plus | 0.3.0 | 00 |
| @vitejs/plugin-react | 6.1.1 | 00 |
| @testing-library/react | 16.3.3 | 00 |
| jsdom | 30.0.1 | 00 |
| @playwright/test | 1.62.1 | 00 |
| wait-on | dernière | 00 (CI) |
| @axe-core/playwright | 4.13.0 | 00 |
| dexie | 4.4.5 | 04 |
| pagedjs | _à installer_ | 13 |
| posthog-js | _à installer_ | 15 |
| fake-indexeddb (dev) | 6.2.5 | 04 |
| @testing-library/user-event (dev) | 14.6.7 | 04 |

`vite-plus` fournit oxlint, oxfmt et Vitest : ni ESLint, ni Prettier, ni Vitest ne sont installés séparément (décision T-30).
