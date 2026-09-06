<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les exports (bloc 14, R-10.2).
 *
 * La non-captivité est un engagement écrit : la famille obtient gratuitement
 * l'intégralité de ce qu'elle a confié, à tout moment, et sans avoir à le
 * demander aux deux moments où elle risquerait de l'oublier — à la livraison
 * du livre et avant la fin de l'hébergement.
 *
 * La table garde une **trace** de chaque export : qui l'a demandé, ce qu'il
 * contenait, quand il a expiré. C'est ce qui permet de répondre à « on m'a
 * dit que j'avais reçu mes données » trois mois plus tard, et c'est aussi ce
 * qui évite d'en refabriquer un tous les jours pour rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();

            // `full`, `offline_pack`, `gdpr_access` : ce qui distingue les
            // trois n'est pas le format mais la **portée** et le destinataire.
            $table->string('kind');
            $table->string('status')->default('queued');

            /*
             * Qui a demandé, en polymorphe : une Initiateur·rice, un
             * narrateur, ou personne — les exports proactifs n'ont pas de
             * demandeur, et c'est justement ce qui les définit.
             */
            /*
             * Une **chaîne** et non un UUID : le demandeur est le plus
             * souvent un `User`, dont l'identifiant est un entier, et parfois
             * un narrateur, qui porte un UUID. Même raison que
             * `consents.subject_id` (T-203).
             */
            $table->string('requested_by_type')->nullable();
            $table->string('requested_by_id', 64)->nullable();
            $table->index(['requested_by_type', 'requested_by_id']);

            // La portée décide de ce qui entre : l'Initiateur·rice ne reçoit
            // que ce que le narrateur a validé, lui reçoit tout sauf la
            // corbeille. Stockée pour qu'un export se relise sans deviner.
            $table->string('scope');

            $table->string('object_path')->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->jsonb('manifest')->nullable();

            $table->timestamp('built_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            // Les exports proactifs interrogent « ce projet a-t-il reçu un
            // export récemment ? » tous les jours.
            $table->index(['project_id', 'kind', 'built_at']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
