<?php

declare(strict_types=1);

use App\Enums\BuyerFocus;
use App\Enums\BuyerRelation;
use App\Enums\GrammaticalGender;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que la famille a dit de la narratrice après l'achat, pour choisir ses
 * questions.
 *
 * Tout y est facultatif, et l'absence vaut « on ne sait pas » : un projet sans
 * profil, ou dont le tunnel a été passé, reçoit les questions comme avant.
 * Seul un « non » explicite retire une question.
 *
 * `facts` porte les conditions de vie (`partner`, `children`…) à vrai ou faux ;
 * `avoided_topics` des étiquettes de question, jamais une donnée de santé ou
 * de croyance sur la personne — « éviter la maladie » ne dit pas de quoi elle
 * souffre. Le genre de la narratrice vit sur `narrators`, pas ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('facts')->default('{}');
            $table->jsonb('avoided_topics')->default('[]');
            $table->jsonb('favored_themes')->default('[]');
            $table->string('buyer_relation', 16)->nullable();
            $table->string('buyer_focus', 16)->nullable();
            $table->string('buyer_first_name', 80)->nullable();
            $table->string('buyer_gender', 16)->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('skipped_at')->nullable();
            $table->timestampsTz();
        });

        EnumCheck::add('project_profiles', 'buyer_relation', EnumCheck::of(BuyerRelation::class), nullable: true);
        EnumCheck::add('project_profiles', 'buyer_focus', EnumCheck::of(BuyerFocus::class), nullable: true);
        EnumCheck::add('project_profiles', 'buyer_gender', EnumCheck::of(GrammaticalGender::class), nullable: true);
    }

    public function down(): void
    {
        Schema::dropIfExists('project_profiles');
    }
};
