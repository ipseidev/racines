<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qui a réellement été écouté, et par qui.
 *
 * Le maillon central de la chaîne H2 : sans lui, on ne distingue pas un
 * proche qui a ouvert la page d'un proche qui a écouté. Le dossier refuse de
 * présumer la causalité entre l'attention des proches et l'élan du narrateur,
 * donc il faut mesurer les deux séparément.
 *
 * `reached_30s` est un booléen et non un calcul refait à la lecture : le
 * moment où le seuil est franchi est un fait daté, et l'événement analytics
 * ne part qu'une fois.
 *
 * Une ligne par proche et par histoire : `family_member_id` reste nullable
 * pour l'écoute par QR imprimé (bloc 13), où l'on ne sait pas qui écoute — et
 * où l'on ne cherche pas à le savoir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listen_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('story_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('family_member_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('token_type', 32);
            $table->integer('seconds_listened')->default(0);
            $table->boolean('reached_30s')->default(false);

            $table->timestampTz('started_at')->nullable();
            $table->timestampsTz();

            $table->index(['story_id', 'reached_30s']);
            $table->unique(['story_id', 'family_member_id']);
        });

        EnumCheck::add('listen_events', 'token_type', EnumCheck::of(TokenType::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('listen_events');
    }
};
