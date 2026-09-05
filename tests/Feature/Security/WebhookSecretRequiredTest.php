<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use RuntimeException;

/**
 * En production, l'endpoint de paiement n'est jamais ouvert.
 *
 * Cashier n'installe `VerifyWebhookSignature` **que si un secret est
 * configuré** (`WebhookController::__construct`). Un `STRIPE_WEBHOOK_SECRET`
 * vide ne dégrade donc pas la vérification : il la supprime. N'importe qui
 * connaissant l'adresse pourrait alors forger un `checkout.session.completed`,
 * faire naître une commande payée, un projet, et déclencher une invitation
 * vers un numéro de son choix.
 *
 * Le runbook le disait en prose depuis le bloc 10. La prose ne défend rien :
 * une variable oubliée dans l'environnement de Forge passerait inaperçue,
 * puisque tout continuerait de fonctionner. On échoue donc bruyamment au
 * démarrage, au moment du déploiement, là où la faute se corrige en une ligne
 * (T-171).
 */
it('refuse de démarrer en production sans secret de webhook', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config()->set('cashier.webhook.secret', '');

    expect(fn () => AppServiceProvider::guardWebhookSecret())
        ->toThrow(RuntimeException::class, 'STRIPE_WEBHOOK_SECRET');
});

it('démarre en production quand le secret est là', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config()->set('cashier.webhook.secret', 'whsec_peu_importe');

    AppServiceProvider::guardWebhookSecret();
})->throwsNoExceptions();

/*
 * Hors production, le secret reste facultatif : `stripe listen` en fournit un
 * qui change de session en session, et exiger sa présence rendrait
 * l'application indémarrable pour qui ne travaille pas sur le paiement.
 */
it('laisse le développement démarrer sans secret', function (): void {
    config()->set('cashier.webhook.secret', '');

    AppServiceProvider::guardWebhookSecret();
})->throwsNoExceptions();
