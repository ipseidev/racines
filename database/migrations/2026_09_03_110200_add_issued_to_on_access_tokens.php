<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * À **qui** un lien a été remis.
 *
 * `issued_by` dit qui l'a émis ; il manquait qui le détient. Un lien
 * d'histoire (`listen_story`) porte une histoire comme sujet : sans porteur,
 * il devient anonyme — et un lien anonyme contredit la règle du bloc 08
 * (« un lien par personne, jamais un lien famille commun ») et rend la
 * visibilité restreinte inapplicable, puisqu'on ne sait plus qui écoute.
 *
 * Nullable : un lien d'enregistrement, lui, porte déjà son narrateur par son
 * sujet, et n'a rien à répéter ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_tokens', function (Blueprint $table): void {
            $table->string('issued_to_type')->nullable()->after('issued_by_id');
            $table->string('issued_to_id', 64)->nullable()->after('issued_to_type');

            $table->index(['issued_to_type', 'issued_to_id']);
        });
    }

    public function down(): void
    {
        Schema::table('access_tokens', function (Blueprint $table): void {
            $table->dropIndex(['issued_to_type', 'issued_to_id']);
            $table->dropColumn(['issued_to_type', 'issued_to_id']);
        });
    }
};
