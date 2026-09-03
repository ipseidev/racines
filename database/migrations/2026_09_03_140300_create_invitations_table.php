<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les invitations envoyées à un narrateur, et ce qu'il en a fait.
 *
 * Une ligne par envoi, et **au plus trois** : l'invitation, puis deux
 * relances. La contrainte vit en base (`attempt` de 1 à 3, unique par
 * narrateur et tentative) parce que le doc 04 §2 en fait une limite dure —
 * au-delà, ce n'est plus une invitation, c'est une insistance.
 *
 * `opened_at` sépare « jamais vu » de « vu et pas répondu », et c'est toute
 * la différence entre relancer et respecter un silence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('narrator_id')->constrained()->cascadeOnDelete();

            $table->string('channel', 16);
            $table->smallInteger('attempt')->default(1);
            $table->foreignUuid('token_id')->nullable()->constrained('access_tokens')->nullOnDelete();

            $table->timestampTz('sent_at');
            $table->timestampTz('opened_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('refused_at')->nullable();

            $table->timestampsTz();

            // Au plus trois envois, et un seul par tentative.
            $table->unique(['narrator_id', 'attempt']);
            $table->index(['project_id', 'sent_at']);
        });

        EnumCheck::add('invitations', 'channel', EnumCheck::of(Channel::class));
        EnumCheck::add('invitations', 'attempt', ['1', '2', '3']);
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
