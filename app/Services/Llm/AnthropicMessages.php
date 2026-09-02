<?php

declare(strict_types=1);

namespace App\Services\Llm;

use Anthropic\Messages\Message;

/**
 * L'appel dont le renderer a besoin, et rien de plus.
 *
 * Le SDK expose son service de messages en classe `final`, non doublable, et
 * le renderer n'a de toute façon aucune raison de connaître l'arborescence
 * d'objets du SDK. Ce port réduit la dépendance à cinq paramètres, ce qui
 * rend le renderer éprouvable sans réseau — convention §5, aucun test
 * n'appelle un fournisseur.
 */
interface AnthropicMessages
{
    /**
     * @param  list<array{type: 'text', text: string, cacheControl: array{type: 'ephemeral'}}>  $system
     * @param  list<array{role: 'user', content: string}>  $messages
     * @param  array<string, mixed>  $outputConfig
     */
    public function create(
        string $model,
        int $maxTokens,
        array $system,
        array $messages,
        array $outputConfig,
    ): Message;
}
