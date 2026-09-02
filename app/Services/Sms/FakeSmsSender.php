<?php

declare(strict_types=1);

namespace App\Services\Sms;

use PHPUnit\Framework\Assert;

/**
 * Expéditeur de test : rien ne sort de la machine.
 *
 * Aucun test n'appelle le réseau (convention §5) ; `Http::preventStrayRequests()`
 * garde la porte, cet expéditeur rend les envois observables.
 */
final class FakeSmsSender implements SmsSender
{
    /** @var list<SmsMessage> */
    private array $messages = [];

    public function send(string $toE164, string $body, ?string $dedupeKey = null): SmsResult
    {
        $this->messages[] = new SmsMessage($toE164, $body, $dedupeKey);

        return SmsResult::accepted('fake-'.count($this->messages));
    }

    /** @return list<SmsMessage> */
    public function messages(): array
    {
        return $this->messages;
    }

    public function assertSentTo(string $toE164, ?string $contains = null): void
    {
        $matches = array_filter(
            $this->messages,
            fn (SmsMessage $message): bool => $message->to === $toE164
                && ($contains === null || str_contains($message->body, $contains)),
        );

        Assert::assertNotEmpty($matches, "Aucun SMS envoyé à {$toE164}.");
    }

    public function assertNothingSent(): void
    {
        Assert::assertSame([], $this->messages, 'Un SMS a été envoyé alors qu’aucun n’était attendu.');
    }
}
