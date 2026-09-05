<?php

declare(strict_types=1);

use App\Services\Sms\AllowlistSmsSender;
use App\Services\Sms\FakeSmsSender;

/**
 * Hors production, un vrai fournisseur de SMS n'écrit qu'à des numéros permis.
 *
 * Le décor sème des numéros de la forme `+336000xxxx` — des mobiles français
 * **plausibles**, appartenant peut-être à quelqu'un. Ils étaient malformés
 * jusqu'au 2026-09-05, ce qui les protégeait par accident ; les rendre valides
 * (T-166) a retiré cette protection au moment même où les clés Twilio
 * arrivaient dans le `.env`.
 *
 * Un tour de moteur ou un `prompts:dispatch-due` sur ce décor suffirait alors
 * à envoyer de vrais SMS à des inconnus — pour un produit qui écrit à des
 * personnes de plus de quatre-vingts ans, c'est la faute qu'on ne rattrape
 * pas. La liste blanche est donc **obligatoire hors production** : vide, rien
 * ne part (T-174).
 */
it('laisse passer un numéro de la liste', function (): void {
    $vrai = new FakeSmsSender;
    $garde = new AllowlistSmsSender($vrai, ['+33612345678']);

    $garde->send('+33612345678', 'Bonjour.');

    expect($vrai->messages())->toHaveCount(1);
});

it('retient un numéro absent de la liste', function (): void {
    $vrai = new FakeSmsSender;
    $garde = new AllowlistSmsSender($vrai, ['+33612345678']);

    $resultat = $garde->send('+33600000000', 'Bonjour.');

    expect($vrai->messages())->toBeEmpty()
        ->and($resultat->accepted)->toBeFalse();
});

it('retient tout quand la liste est vide', function (): void {
    $vrai = new FakeSmsSender;
    $garde = new AllowlistSmsSender($vrai, []);

    $garde->send('+33612345678', 'Bonjour.');

    expect($vrai->messages())->toBeEmpty();
});

/*
 * La liste tolère les espaces et les séparateurs : un numéro recopié depuis un
 * carnet d'adresses en porte, et une garde qui échoue sur une espace ferait
 * croire à une panne de Twilio.
 */
it('compare les numéros sans leur mise en forme', function (): void {
    $vrai = new FakeSmsSender;
    $garde = new AllowlistSmsSender($vrai, ['+33 6 12 34 56 78']);

    $garde->send('+33612345678', 'Bonjour.');

    expect($vrai->messages())->toHaveCount(1);
});
