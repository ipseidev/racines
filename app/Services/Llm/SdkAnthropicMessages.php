<?php

declare(strict_types=1);

namespace App\Services\Llm;

use Anthropic\Client;
use Anthropic\Messages\Message;

/**
 * Le port `AnthropicMessages`, servi par le SDK officiel.
 */
final class SdkAnthropicMessages implements AnthropicMessages
{
    private ?Client $client = null;

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
    ): Message {
        return $this->client()->messages->create(
            model: $model,
            maxTokens: $maxTokens,
            system: $system,
            messages: $messages,
            outputConfig: $outputConfig,
        );
    }

    private function client(): Client
    {
        return $this->client ??= new Client(apiKey: (string) config('services.anthropic.key'));
    }
}
