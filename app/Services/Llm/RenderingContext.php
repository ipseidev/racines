<?php

declare(strict_types=1);

namespace App\Services\Llm;

use App\Enums\AddressForm;

/**
 * Ce que le modèle doit savoir pour mettre un récit au propre.
 *
 * Volontairement pauvre : la question, le prénom, la forme d'adresse, le
 * lexique, les thèmes possibles. Rien sur la famille, rien sur les autres
 * histoires. Le modèle n'a pas à connaître la vie de quelqu'un pour
 * ponctuer ses phrases.
 */
final readonly class RenderingContext
{
    /**
     * @param  array<string, string>  $lexicon  terme → graphie attendue
     * @param  list<string>  $themes
     */
    public function __construct(
        public ?string $question,
        public string $firstName,
        public AddressForm $addressForm,
        public array $lexicon = [],
        public array $themes = [],
    ) {}
}
