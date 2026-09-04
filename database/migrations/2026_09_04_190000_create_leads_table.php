<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les personnes qui ont laissé leur adresse contre un code de réduction : la
 * fenêtre de bienvenue de la page d'accueil (T-141).
 *
 * L'adresse est chiffrée au repos et doublée d'une empreinte : on la retrouve
 * par l'empreinte, on ne la déchiffre qu'au moment d'écrire. Le code est
 * unique, à usage unique, et porte sa propre date de fin. Le pourcentage est
 * **copié** au moment de la demande, comme le prix d'une commande : changer
 * le réglage ne change pas ce qu'on a promis à quelqu'un.
 *
 * La demande de nouvelles est une date, pas une case : on sait quand elle a
 * été faite, avec la version du texte lu, comme pour tout consentement
 * (doc 04 §2). Nulle tant qu'elle n'a pas été demandée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->text('email');
            $table->char('email_hash', 64)->unique();

            $table->string('discount_code', 16)->unique();
            $table->smallInteger('discount_percent');
            $table->string('source', 32);

            $table->timestampTz('news_opted_in_at')->nullable();
            $table->string('consent_text_version', 16)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestampTz('code_expires_at');
            $table->timestampTz('code_used_at')->nullable();
            $table->foreignUuid('order_id')->nullable()->constrained()->nullOnDelete();

            $table->timestampsTz();

            $table->index('code_used_at');
        });

        EnumCheck::add('leads', 'source', [Lead::SOURCE_LANDING]);
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
