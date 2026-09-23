<?php

declare(strict_types=1);

use App\Enums\GrammaticalGender;
use App\Enums\QuestionTone;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les questions apprennent à tutoyer, à s'accorder et à dire ce qu'elles
 * supposent.
 *
 * `text` reste le vouvoiement et `text_tu` le tutoiement ; les deux portent
 * des marqueurs de genre (`né{|e}`) que `QuestionWording` résout à l'envoi.
 * Le projet demandait « tu » ou « vous » depuis le tunnel d'achat, et les
 * questions vouvoyaient quand même : toutes les narratrices de production
 * avaient été tutoyées à l'achat.
 *
 * Les étiquettes (ton, conditions, sujets sensibles) arrivent vides : c'est
 * `corpus:sync` qui les remplit depuis `database/corpus/questions.php`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->text('text_tu')->nullable()->after('text');
            $table->string('tone', 16)->nullable()->after('difficulty');
            $table->jsonb('conditions')->default('[]')->after('tone');
            $table->jsonb('sensitive_topics')->default('[]')->after('conditions');
        });

        EnumCheck::add('questions', 'tone', EnumCheck::of(QuestionTone::class), nullable: true);

        Schema::table('narrators', function (Blueprint $table): void {
            $table->string('grammatical_gender', 16)->nullable()->after('tech_comfort');
        });

        EnumCheck::add('narrators', 'grammatical_gender', EnumCheck::of(GrammaticalGender::class), nullable: true);
    }

    public function down(): void
    {
        EnumCheck::drop('narrators', 'grammatical_gender');

        Schema::table('narrators', function (Blueprint $table): void {
            $table->dropColumn('grammatical_gender');
        });

        EnumCheck::drop('questions', 'tone');

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn(['text_tu', 'tone', 'conditions', 'sensitive_topics']);
        });
    }
};
