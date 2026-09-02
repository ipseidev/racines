<?php

declare(strict_types=1);

use App\Enums\ConsentKind;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Texte exact affiché au moment du consentement, versionné.
 *
 * Un consentement ne vaut que si l'on peut réafficher ce qui a été lu : la
 * ligne de `consents` garde la version, jamais une copie du texte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_texts', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 32);
            $table->string('version', 16);
            $table->string('locale', 8)->default('fr');
            $table->text('body');
            $table->timestampTz('effective_from');
            $table->timestampTz('created_at')->nullable();

            $table->unique(['kind', 'version', 'locale']);
        });

        EnumCheck::add('consent_texts', 'kind', EnumCheck::of(ConsentKind::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_texts');
    }
};
