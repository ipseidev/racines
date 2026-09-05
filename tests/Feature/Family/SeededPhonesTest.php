<?php

declare(strict_types=1);

use App\Models\FamilyMember;
use App\Models\Narrator;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Les numéros du décor sont de vrais numéros.
 *
 * Trouvé pendant le checkpoint du bloc 08 : le narrateur du scénario d'écoute
 * portait `+336000175de`. Le décor construisait ses numéros avec `md5()`, dont
 * les chiffres hexadécimaux vont jusqu'à `f` — une lettre sur deux numéros,
 * en moyenne.
 *
 * Ce n'est pas cosmétique. En local le journal accepte n'importe quoi et le
 * défaut ne se voit pas ; **Twilio, lui, refuse**, et ce refus arriverait au
 * premier envoi réel du bloc 05 — c'est-à-dire au pire moment, sur le
 * checkpoint qui attend justement de voir un SMS partir. Un décor qui ment sur
 * la forme d'une donnée ne prépare pas le terrain, il le mine.
 */
it('ne sème que des numéros E.164 valides', function (): void {
    $this->seed(E2ELinksSeeder::class);

    $numeros = Narrator::query()->pluck('phone_e164')
        ->concat(FamilyMember::query()->pluck('phone_e164'))
        ->filter()
        ->values();

    expect($numeros)->not->toBeEmpty();

    foreach ($numeros as $numero) {
        expect($numero)->toMatch('/^\+[1-9]\d{7,14}$/');
    }
});
