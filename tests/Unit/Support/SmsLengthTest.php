<?php

declare(strict_types=1);

use App\Support\SmsLength;

it('reconnaît un texte que l’alphabet GSM sait écrire', function (): void {
    expect(SmsLength::isGsm7('Marie, votre question vous attend : https://liens.example/r/abc'))->toBeTrue()
        ->and(SmsLength::segmentLimit('Bonjour'))->toBe(160);
});

it('bascule à soixante-dix caractères dès qu’un seul sort de l’alphabet', function (): void {
    // L'apostrophe typographique suffit : c'est le piège courant.
    $body = 'Marie, votre question de la semaine vous attend';

    expect(SmsLength::segmentLimit($body))->toBe(160)
        ->and(SmsLength::segmentLimit($body.' — merci'))->toBe(70)
        ->and(SmsLength::isGsm7('l’apostrophe'))->toBeFalse();
});

it('compte double les caractères étendus de l’alphabet GSM', function (): void {
    expect(SmsLength::length('abc'))->toBe(3)
        ->and(SmsLength::length('a€c'))->toBe(4)
        ->and(SmsLength::length('[]{}'))->toBe(8);
});

it('détecte le débordement d’un segment', function (): void {
    expect(SmsLength::exceedsSingleSegment(str_repeat('a', 160)))->toBeFalse()
        ->and(SmsLength::exceedsSingleSegment(str_repeat('a', 161)))->toBeTrue()
        // En UCS-2, la limite tombe à 70.
        ->and(SmsLength::exceedsSingleSegment('—'.str_repeat('a', 69)))->toBeFalse()
        ->and(SmsLength::exceedsSingleSegment('—'.str_repeat('a', 70)))->toBeTrue();
});

it('compte les segments facturés, en-tête de réassemblage compris', function (): void {
    expect(SmsLength::segments(''))->toBe(0)
        ->and(SmsLength::segments(str_repeat('a', 160)))->toBe(1)
        // Au-delà d'un segment, la place utile tombe à 153 : 161 caractères
        // n'en font pas 160 + 1, mais 153 + 8.
        ->and(SmsLength::segments(str_repeat('a', 161)))->toBe(2)
        ->and(SmsLength::segments(str_repeat('a', 306)))->toBe(2)
        ->and(SmsLength::segments(str_repeat('a', 307)))->toBe(3);
});

it('compte les segments d’un message tombé en UCS-2', function (): void {
    // Tous les accents ne coûtent pas : « é », « è » et « à » sont dans
    // l'alphabet GSM, « ç », « ê », « ô », « ï » n'y sont pas. « Françoise »
    // fait donc basculer tout le message à 70 caractères, « Hélène » non.
    expect(SmsLength::isGsm7('Hélène'))->toBeTrue()
        ->and(SmsLength::isGsm7('Françoise'))->toBeFalse()
        ->and(SmsLength::segments('ç'.str_repeat('a', 69)))->toBe(1)
        ->and(SmsLength::segments('ç'.str_repeat('a', 70)))->toBe(2)
        ->and(SmsLength::segments('ç'.str_repeat('a', 133)))->toBe(2)
        ->and(SmsLength::segments('ç'.str_repeat('a', 134)))->toBe(3);
});

it('raccourcit un prénom sans le rendre méconnaissable', function (): void {
    expect(SmsLength::shorten('Marie'))->toBe('Marie')
        ->and(SmsLength::shorten('Marie-Christine'))->toBe('Marie')
        ->and(SmsLength::shorten('Jean Baptiste Emmanuel'))->toBe('Jean')
        ->and(SmsLength::shorten('Bartholomeusverylong'))->toBe('Bartholomeus');
});
