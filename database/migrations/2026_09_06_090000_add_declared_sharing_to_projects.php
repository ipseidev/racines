<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le partage déclaré d'avance (D-10).
 *
 * Une **date** et non un booléen : « depuis quand » est ce qu'on doit pouvoir
 * répondre à une famille qui conteste, et un `true` sans date ne répond à
 * rien. La révocation remet la colonne à `null` ; la trace du consentement,
 * elle, reste dans `consents`, où elle est immuable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->timestampTz('declared_sharing_at')->nullable()->after('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('declared_sharing_at');
        });
    }
};
