<?php

declare(strict_types=1);

use App\Enums\AnswerType;
use App\Enums\DeletionRequestedBy;
use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\ValidatedVia;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une histoire naît de la proposition d'une question et porte la machine
 * d'états R-4.
 *
 * Les onze états sont écrits en dur ici, et non dérivés des classes
 * `App\States\Story\*` : une migration déjà jouée ne doit pas changer de
 * comportement parce que le code a évolué. Un test vérifie que les deux
 * listes coïncident (`tests/Feature/Database/ConstraintsTest.php`).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $states = [
        'proposed', 'recorded', 'transcribed', 'to_review', 'validated',
        'shared', 'in_book', 'hidden', 'archived', 'trashed', 'deleted',
    ];

    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('narrator_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('question_id')->nullable()->constrained()->nullOnDelete();
            $table->text('custom_question_text')->nullable();

            $table->integer('sequence');

            $table->string('state', 32);
            // Permet de revenir de `hidden`, `archived` ou `trashed` à l'état d'avant.
            $table->string('previous_state', 32)->nullable();

            $table->timestampTz('proposed_at')->nullable();
            $table->timestampTz('recorded_at')->nullable();
            $table->timestampTz('transcribed_at')->nullable();
            $table->timestampTz('validated_at')->nullable();
            $table->timestampTz('shared_at')->nullable();

            $table->string('validated_via', 32)->nullable();
            $table->string('share_decision', 16)->nullable();
            $table->timestampTz('share_decided_at')->nullable();

            $table->string('visibility', 16)->default(StoryVisibility::AllFamily->value);
            $table->string('answer_type', 16)->nullable();
            $table->text('written_answer')->nullable();
            $table->string('title')->nullable();

            $table->timestampTz('hidden_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampTz('trashed_at')->nullable();
            $table->timestampTz('deleted_at')->nullable();
            $table->string('deletion_requested_by', 16)->nullable();

            $table->boolean('printed_in_book')->default(false);

            $table->timestampsTz();

            $table->index(['project_id', 'state']);
            $table->index('narrator_id');
            $table->unique(['project_id', 'sequence']);
        });

        EnumCheck::add('stories', 'state', $this->states);
        EnumCheck::add('stories', 'previous_state', $this->states, nullable: true);
        EnumCheck::add('stories', 'visibility', EnumCheck::of(StoryVisibility::class));
        EnumCheck::add('stories', 'share_decision', EnumCheck::of(ShareDecision::class), nullable: true);
        EnumCheck::add('stories', 'validated_via', EnumCheck::of(ValidatedVia::class), nullable: true);
        EnumCheck::add('stories', 'answer_type', EnumCheck::of(AnswerType::class), nullable: true);
        EnumCheck::add('stories', 'deletion_requested_by', EnumCheck::of(DeletionRequestedBy::class), nullable: true);

        // Une histoire vient toujours d'une question, du corpus ou personnalisée.
        DB::statement('alter table stories add constraint stories_question_present_check check (question_id is not null or custom_question_text is not null)');

        // La corbeille se purge par balayage : l'index ne porte que sur elle.
        DB::statement('create index stories_trashed_at_index on stories (trashed_at) where trashed_at is not null');
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
