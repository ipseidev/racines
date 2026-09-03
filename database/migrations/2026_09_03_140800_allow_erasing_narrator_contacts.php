<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'effacement des coordonnées devient possible.
 *
 * `narrators_reachable_check` exigeait un courriel **ou** un téléphone. La
 * contrainte est juste — sans coordonnée, aucun lien ne part — mais elle
 * rendait impossible l'obligation du doc 04 §2 : effacer les coordonnées d'une
 * personne qui n'a jamais dit oui, trente jours après la dernière relance. La
 * commande `narrators:delete-unaccepted-contacts` échouait sur une violation
 * de contrainte, et le test l'a découvert (écart T-109).
 *
 * Même forme de correction qu'au bloc 07 pour l'audio d'origine (T-80) : on ne
 * retire pas la garde, on nomme l'exception. `contact_deleted_at` la nomme, et
 * la **date** l'accompagne : « quand ces coordonnées ont-elles été
 * effacées ? » est exactement ce qu'une demande RGPD demande, et un
 * enregistrement de journal ne survit pas à une rotation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('narrators', function (Blueprint $table): void {
            $table->timestampTz('contact_deleted_at')->nullable()->after('contact_deletion_due_at');
        });

        DB::statement('alter table narrators drop constraint narrators_reachable_check');

        DB::statement(<<<'SQL'
            alter table narrators add constraint narrators_reachable_check check (
                contact_deleted_at is not null
                or email is not null
                or phone_e164 is not null
            )
            SQL);
    }

    public function down(): void
    {
        DB::statement('alter table narrators drop constraint narrators_reachable_check');

        DB::statement(
            'alter table narrators add constraint narrators_reachable_check check (email is not null or phone_e164 is not null)'
        );

        Schema::table('narrators', function (Blueprint $table): void {
            $table->dropColumn('contact_deleted_at');
        });
    }
};
