<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Par où l'option téléphone est arrivée.
 *
 * La distinction compte pour la mesure : une option achetée au tunnel
 * (`checkout`) dit qu'on peut la vendre ; une option ajoutée depuis l'espace
 * après coup (`complement`) dit que l'envie était là mais que le tunnel l'a
 * ratée ; une option demandée en rattrapage (`rescue`, sur alerte du moteur au
 * bout de trois semaines de silence) dit qu'elle sauve des projets. Ce ne sont
 * pas les mêmes chiffres, et ils ne mènent pas aux mêmes décisions (D-9).
 *
 * Les confondre coûterait cher : compter un complément comme un achat
 * gonflerait le taux d'attache au tunnel, qui est précisément le nombre sur
 * lequel se décide l'automatisation de la téléphonie en Phase 2.
 */
enum PhoneOptionEntry: string
{
    use HasTranslatedLabel;

    case Checkout = 'checkout';
    case Complement = 'complement';
    case Rescue = 'rescue';
}
