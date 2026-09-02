<?php

declare(strict_types=1);

it('expose les limites d’enregistrement', function (): void {
    expect(config('product.recording.hard_stop_seconds'))->toBe(1200)
        ->and(config('product.recording.soft_warning_seconds'))->toBe(600)
        ->and(config('product.recording.max_bytes'))->toBe(209_715_200)
        ->and(config('product.recording.accepted_mimes'))->toContain('audio/mp4', 'audio/webm');
});

it('expose les critères book-ready du référentiel R-6', function (): void {
    expect(config('product.book_ready.min_words'))->toBe(12_000)
        ->and(config('product.book_ready.min_audio_minutes'))->toBe(90)
        ->and(config('product.book_ready.min_pages'))->toBe(60)
        ->and(config('product.book_ready.min_themes'))->toBe(5);
});

it('expose le plafond de l’option téléphone D-9', function (): void {
    expect(config('product.pilot.phone_option_cap'))->toBe(10);
});

it('n’expose aucun nom de marque en dur', function (): void {
    expect(config('brand.product_name'))->toBe(env('BRAND_PRODUCT_NAME', 'Product'));
});
