<?php

declare(strict_types=1);

use App\Models\Story;

/**
 * Régression : Eloquent écrit les dates sans décalage, Postgres les lit dans le
 * fuseau de la session. Sans alignement des deux, un instant enregistré depuis
 * une application à l'heure de Paris ressortait deux heures plus tôt — de quoi
 * fausser toutes les fenêtres du moteur de complétion.
 */
it('relit exactement l’instant enregistré, quel que soit le fuseau applicatif', function (): void {
    expect(config('app.timezone'))->toBe('Europe/Paris');

    $this->freezeTime();

    $story = Story::factory()->create(['proposed_at' => now()]);

    expect($story->fresh()?->proposed_at?->getTimestamp())->toBe(now()->getTimestamp());
});
