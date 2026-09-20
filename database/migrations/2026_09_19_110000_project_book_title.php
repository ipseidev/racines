<?php

declare(strict_types=1);

use App\Enums\BookTitle;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le titre imprimé sur la couverture, choisi à l'achat.
 *
 * Deux colonnes et non une. La formule est une liste fermée — elle se traduit
 * en cinq langues et se compose au moment du BAT, avec le prénom tel qu'il
 * sera alors. Le titre libre est un texte, et il ne se traduit pas : ce que
 * la famille a écrit est ce qui s'imprime.
 *
 * Par défaut le prénom seul, qui est ce qu'imprimaient toutes les couvertures
 * avant ce choix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('book_title', 24)->default(BookTitle::default()->value);
            $table->string('book_title_custom', 80)->nullable();
        });

        EnumCheck::add('projects', 'book_title', EnumCheck::of(BookTitle::class));
    }

    public function down(): void
    {
        EnumCheck::drop('projects', 'book_title');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['book_title', 'book_title_custom']);
        });
    }
};
