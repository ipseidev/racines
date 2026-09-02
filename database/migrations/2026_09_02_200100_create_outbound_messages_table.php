<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\OutboundMessageStatus;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tout message parti du produit, et ce qu'il est devenu.
 *
 * Sans cette table, le moteur de complétion (bloc 09) ne peut pas distinguer
 * « lien non ouvert » de « SMS jamais reçu » — et c'est toute la différence
 * entre relancer un narrateur et lui envoyer un reproche injuste.
 *
 * Le destinataire n'y figure pas en clair : une empreinte pour dédupliquer, et
 * une forme masquée pour que le support puisse dire « envoyé au 06 •• •• •• 12 ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->nullable()->constrained()->nullOnDelete();

            $table->string('channel', 16);
            $table->char('to_hash', 64);
            $table->string('to_masked', 64);

            $table->string('template', 64);
            $table->jsonb('payload')->nullable();

            $table->string('provider', 32)->nullable();
            $table->string('provider_message_id')->nullable();

            $table->string('status', 16)->default(OutboundMessageStatus::Queued->value);
            $table->string('status_detail')->nullable();

            // Deux exécutions de `prompts:dispatch-due` dans la même minute ne
            // doivent pas produire deux SMS.
            $table->string('dedupe_key')->unique();

            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('created_at')->nullable();

            $table->index('provider_message_id');
            $table->index(['status', 'created_at']);
        });

        EnumCheck::add('outbound_messages', 'channel', Channel::outboundValues());
        EnumCheck::add('outbound_messages', 'status', EnumCheck::of(OutboundMessageStatus::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_messages');
    }
};
