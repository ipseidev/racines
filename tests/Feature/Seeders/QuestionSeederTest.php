<?php

declare(strict_types=1);

use App\Enums\QuestionTheme;
use App\Models\Question;
use Database\Seeders\QuestionSeeder;

it('sème les soixante questions du corpus, sans doublon de slug', function (): void {
    expect(Question::query()->count())->toBe(60)
        ->and(Question::query()->distinct()->count('slug'))->toBe(60);
});

it('commence par une question très facile', function (): void {
    $first = Question::query()->orderBy('order_hint')->firstOrFail();

    expect($first->difficulty)->toBe(1)
        ->and($first->slug)->toBe('naissance-recit');
});

it('couvre les dix thèmes du référentiel', function (): void {
    $themes = Question::query()->distinct()->pluck('theme');

    expect($themes)->toHaveCount(10)
        ->and($themes->contains(QuestionTheme::Legacy))->toBeTrue()
        ->and($themes->contains(QuestionTheme::Childhood))->toBeTrue();
});

it('gradue la difficulté du facile vers l’intime', function (): void {
    $byDifficulty = Question::query()
        ->selectRaw('difficulty, min(order_hint) as first_order, count(*) as total')
        ->groupBy('difficulty')
        ->orderBy('difficulty')
        ->get();

    // Chaque palier de difficulté apparaît plus loin que le précédent : le
    // corpus ne demande pas « qu'aimeriez-vous que l'on retienne de vous ? »
    // à la deuxième semaine.
    $orders = $byDifficulty->pluck('first_order')->all();

    expect($orders)->toBe(array_values(collect($orders)->sort()->all()))
        ->and($byDifficulty->firstWhere('difficulty', 1)?->total)->toBeGreaterThanOrEqual(8);
});

it('reste rejouable sans dupliquer', function (): void {
    $this->seed(QuestionSeeder::class);

    expect(Question::query()->count())->toBe(60);
});

it('n’emploie aucune expression proscrite par R-11', function (): void {
    $forbidden = ['pour toujours', 'illimité', 'garanti à vie'];

    foreach (Question::query()->pluck('text') as $text) {
        foreach ($forbidden as $expression) {
            expect(mb_strtolower((string) $text))->not->toContain($expression);
        }
    }
});
