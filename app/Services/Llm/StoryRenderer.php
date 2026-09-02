<?php

declare(strict_types=1);

namespace App\Services\Llm;

use App\Models\Transcript;

/**
 * Mise au propre d'un récit, derrière une interface.
 *
 * « L'IA range, elle n'invente pas » (doc 04 §1). Le rendu est étiqueté,
 * réversible, et posé **à côté** du verbatim, jamais à sa place.
 */
interface StoryRenderer
{
    public function render(Transcript $verbatim, RenderingContext $context): FluideResult;
}
