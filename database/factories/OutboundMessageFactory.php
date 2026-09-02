<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Channel;
use App\Enums\OutboundMessageStatus;
use App\Models\OutboundMessage;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OutboundMessage>
 */
final class OutboundMessageFactory extends Factory
{
    /** @var class-string<OutboundMessage> */
    protected $model = OutboundMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'channel' => Channel::Sms,
            'template' => 'prompt',
            'payload' => [],
            'provider' => 'fake',
            'status' => OutboundMessageStatus::Sent,
            'dedupe_key' => 'test:'.Str::uuid7(),
            'sent_at' => now(),
        ];
    }

    /**
     * Le destinataire n'est pas assignable en masse : il est haché et masqué
     * par le canal d'envoi, jamais recopié tel quel.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (OutboundMessage $message): void {
            $message->to_hash ??= OutboundMessage::hashRecipient('+33600000000');
            $message->to_masked ??= '+336•• •• •• 00';
        });
    }

    public function failed(string $detail = 'refusé par l’opérateur'): static
    {
        return $this->state(fn (): array => [
            'status' => OutboundMessageStatus::Failed,
            'status_detail' => $detail,
            'sent_at' => null,
            'failed_at' => now(),
        ]);
    }
}
