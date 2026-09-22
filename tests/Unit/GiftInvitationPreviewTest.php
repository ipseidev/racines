<?php

declare(strict_types=1);

/**
 * L'aperçu du quiz dit **mot pour mot** le SMS qui partira.
 *
 * La page de résultat du quiz montre le message d'invitation et le promet
 * exact : « c'est le message réel, mot pour mot ». C'est une promesse tenue
 * par une recopie, et une recopie tient jusqu'à la première retouche de
 * l'original — celle-là vient d'avoir lieu, et l'aperçu annonçait encore
 * « vous offre un livre » quand le vrai message disait autre chose.
 *
 * Le test compare les **gabarits**, avant substitution : c'est là que la
 * divergence naît, et la comparer après coup demanderait de fabriquer un
 * projet pour lire une phrase.
 */
it('montre exactement le message qui partira', function (): void {
    $apercu = trans('public.quiz.preview.invitation', [], 'fr');
    $reel = trans('notifications.gift_invitation.sms', [], 'fr');

    expect($apercu)->toBe($reel);
});
