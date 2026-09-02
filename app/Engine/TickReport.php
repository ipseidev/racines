<?php

declare(strict_types=1);

namespace App\Engine;

/**
 * Ce qu'un tick a fait, pour le journal et pour la commande.
 */
final class TickReport
{
    public int $fired = 0;

    public int $suppressed = 0;

    public int $skipped = 0;

    public int $failed = 0;

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'fired' => $this->fired,
            'suppressed' => $this->suppressed,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
        ];
    }
}
