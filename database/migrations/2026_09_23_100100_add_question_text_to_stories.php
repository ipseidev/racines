<?php

declare(strict_types=1);

use App\Support\QuestionWording;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'intitulé de la question, photographié à l'enregistrement.
 *
 * Jusqu'ici une histoire lisait le texte de sa question en direct : reformuler
 * une question du corpus aurait changé, dans le livre, l'export et l'espace
 * famille, la question à laquelle la narratrice avait répondu.
 *
 * Les histoires déjà enregistrées reçoivent le texte courant, qui est
 * exactement celui qu'elles ont lu : cette migration passe avant tout
 * changement du corpus. Celles qui attendent encore restent nulles et se
 * calculent jusqu'à leur enregistrement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->text('question_text')->nullable()->after('custom_question_text');
        });

        // Le vouvoiement au genre inconnu : c'est ce que toutes les histoires
        // ont lu jusqu'ici, le tutoiement n'ayant jamais été appliqué aux
        // questions. Résolu plutôt que copié, pour qu'une base où le corpus a
        // déjà reçu ses marqueurs ne photographie pas « né{|e} ».
        DB::table('stories')
            ->join('questions', 'questions.id', '=', 'stories.question_id')
            ->whereNull('stories.custom_question_text')
            ->where('stories.state', '<>', 'proposed')
            ->select(['stories.id', 'questions.text'])
            ->orderBy('stories.id')
            ->each(function (object $row): void {
                /** @var object{id: string, text: string} $row */
                DB::table('stories')
                    ->where('id', $row->id)
                    ->update(['question_text' => QuestionWording::resolve($row->text, null)]);
            });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->dropColumn('question_text');
        });
    }
};
