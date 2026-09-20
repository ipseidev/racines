<?php

declare(strict_types=1);

use App\Enums\BookCover;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La couverture choisie à l'achat.
 *
 * Sur le projet et non sur le livre : le choix se fait au tunnel de
 * découverte, des mois avant que le livre existe, et une colonne sur `books`
 * n'aurait nulle part où attendre. `RenderBookHtml` la lit au moment du BAT.
 *
 * Par défaut l'ivoire, qui est la teinte qu'avaient toutes les couvertures
 * avant ce choix : aucun projet en cours ne change d'aspect.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('book_cover', 24)->default(BookCover::default()->value);
        });

        EnumCheck::add('projects', 'book_cover', EnumCheck::of(BookCover::class));
    }

    public function down(): void
    {
        EnumCheck::drop('projects', 'book_cover');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('book_cover');
        });
    }
};
