<?php

declare(strict_types=1);

use App\Enums\CohortPhase;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cohortes du pilote. Créée ici parce que `projects` la référence ; elle n'est
 * remplie qu'au bloc 17.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phase', 16);
            $table->timestampTz('started_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        EnumCheck::add('cohorts', 'phase', EnumCheck::of(CohortPhase::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};
