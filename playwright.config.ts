import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        // Playwright tourne dans le conteneur Sail : l'application y écoute sur
        // le port 80. Depuis le Mac, lancer avec E2E_BASE_URL=http://localhost:8001.
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost',
        locale: 'fr-FR',
        timezoneId: 'Europe/Paris',
        trace: 'on-first-retry',
        // Le micro doit être accordé sans clic pour les tests d'enregistrement (bloc 04).
        permissions: ['microphone'],
    },
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                launchOptions: {
                    args: [
                        '--use-fake-ui-for-media-stream',
                        '--use-fake-device-for-media-stream',
                    ],
                },
            },
        },
    ],
});
