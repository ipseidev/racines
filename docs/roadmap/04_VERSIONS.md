# Versions figées

Rempli au bloc 00 (étape 6.9) puis à chaque ajout de dépendance. La colonne « Version » est celle effectivement installée (`composer show <pkg>` / `npm ls <pkg>`), pas celle du manifeste. Mettre à jour une dépendance majeure est une décision à consigner dans `03_DECISIONS.md`.

## Runtime

| Composant | Version | Où | Note |
|---|---|---|---|
| PHP | _à remplir_ | Sail, Forge | ≥ 8.3 |
| Node.js | _à remplir_ | Sail, Forge | 22 LTS |
| Postgres | _à remplir_ | Sail, DO managé | ≥ 16 |
| Redis | _à remplir_ | Sail, Forge | ≥ 7 |
| ffmpeg | _à remplir_ | Sail, Forge | ≥ 6 |
| Chromium (Browsershot) | _à remplir_ | Sail, Forge | bloc 13 |
| ClamAV | _à remplir_ | Sail, Forge | bloc 12 |

## Composer

| Package | Version | Bloc |
|---|---|---|
| laravel/framework | _à remplir_ | 00 |
| laravel/sail | _à remplir_ | 00 |
| inertiajs/inertia-laravel | _à remplir_ | 00 |
| laravel/fortify | _à remplir_ | 00 |
| pestphp/pest, pestphp/pest-plugin-laravel | _à remplir_ | 00 |
| larastan/larastan | _à remplir_ | 00 |
| laravel/pint | _à remplir_ | 00 |
| laravel/telescope | _à remplir_ | 00 |
| filament/filament | _à remplir_ | 01 |
| spatie/laravel-settings, filament/spatie-laravel-settings-plugin | _à remplir_ | 01 |
| spatie/laravel-model-states | _à remplir_ | 02 |
| spatie/laravel-permission | _à remplir_ | 02 |
| laravel/pennant | _à remplir_ | 02 |
| league/flysystem-aws-s3-v3 | _à remplir_ | 04 |
| laravel/horizon | _à remplir_ | 05 |
| resend/resend-laravel | _à remplir_ | 05 |
| twilio/sdk | _à remplir_ | 05 |
| svix/svix | _à remplir_ | 05 |
| anthropic-ai/sdk | _à remplir_ | 06 |
| pbmedia/laravel-ffmpeg | _à remplir_ | 06 |
| laravel/cashier | _à remplir_ | 10 |
| spatie/laravel-medialibrary | _à remplir_ | 12 |
| sunspikes/clamav-validator | _à remplir_ | 12 |
| spatie/browsershot | _à remplir_ | 13 |
| endroid/qr-code | _à remplir_ | 13 |
| maennchen/zipstream-php | _à remplir_ | 14 |
| posthog/posthog-php | _à remplir_ | 15 |
| spatie/laravel-backup | _à remplir_ | 16 |
| spatie/laravel-ignition (+ Flare) | _à remplir_ | 16 |

## npm

| Package | Version | Bloc |
|---|---|---|
| react, react-dom | _à remplir_ | 00 |
| @inertiajs/react | _à remplir_ | 00 |
| typescript | _à remplir_ | 00 |
| tailwindcss, @tailwindcss/vite | _à remplir_ | 00 |
| vite, @vitejs/plugin-react | _à remplir_ | 00 |
| vitest, @testing-library/react, @testing-library/jest-dom, @testing-library/user-event, jsdom | _à remplir_ | 00 |
| @playwright/test, @axe-core/playwright | _à remplir_ | 00 |
| eslint, prettier | _à remplir_ | 00 |
| dexie | _à remplir_ | 04 |
| pagedjs | _à remplir_ | 13 |
| posthog-js | _à remplir_ | 15 |
