<?php

declare(strict_types=1);

use App\Metrics\Threshold;

/**
 * Les seuils, et le troisième état.
 *
 * Un tableau de bord qui n'a que « atteint » et « pas atteint » force une
 * décision de gate sur du bruit : H0 ≥ 60 % sur cinq invitations, c'est trois
 * acceptations — un résultat que le hasard produit une fois sur trois. Le
 * troisième état, « échantillon trop petit », sera longtemps le seul vrai
 * pendant un pilote, et c'est une information, pas une lacune.
 */
it('refuse de se prononcer sur un échantillon minuscule', function (): void {
    $seuil = Threshold::for('h0_acceptance_14d');

    // Trois acceptations sur trois : 100 %, et cela ne veut rien dire.
    expect($seuil->verdict(1.0, 3))->toBe('too_small');
});

it('déclare atteint quand l’échantillon suffit', function (): void {
    expect(Threshold::for('h0_acceptance_14d')->verdict(0.62, 40))->toBe('met');
});

it('déclare manqué sans adoucir', function (): void {
    // Pas de « presque » : 58 % n'est pas 60 %, et arrondir vers le haut sur
    // un tableau de comité est la façon la plus banale de se mentir.
    expect(Threshold::for('h0_acceptance_14d')->verdict(0.58, 40))->toBe('missed');
});

it('inverse le sens pour la charge de l’Initiateur·rice', function (): void {
    $seuil = Threshold::for('initiator_requests_per_month');

    // Ici, moins est mieux : quatre sollicitations par mois est un plafond,
    // pas un objectif à atteindre.
    expect($seuil->verdict(3.0, 20))->toBe('met')
        ->and($seuil->verdict(6.0, 20))->toBe('missed');
});
