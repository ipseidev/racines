<?php

declare(strict_types=1);

use App\Enums\QuestionTheme;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corpus de questions éditorialisé. Les données arrivent au bloc 05 (annexe A).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->text('text');
            $table->string('theme', 32);
            $table->smallInteger('difficulty')->default(1);
            $table->integer('order_hint')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('locale', 8)->default('fr');
            $table->timestampsTz();

            $table->index(['theme', 'order_hint']);
        });

        EnumCheck::add('questions', 'theme', EnumCheck::of(QuestionTheme::class));

        DB::statement('alter table questions add constraint questions_difficulty_check check (difficulty between 1 and 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
