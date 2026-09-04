<?php

declare(strict_types=1);

use App\Support\Percent;

it('écrit un pourcentage à la française, espace fine insécable comprise', function (): void {
    expect(Percent::format(10))->toBe("10\u{202F}%")
        ->and(Percent::format(0))->toBe("0\u{202F}%");
});
