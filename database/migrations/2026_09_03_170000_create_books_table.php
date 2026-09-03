<?php

declare(strict_types=1);

use App\Enums\BookFormat;
use App\Enums\BookStatus;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le livre d'un projet.
 *
 * Un par projet, garanti par l'unicité : une famille n'a pas deux livres en
 * cours, et une réimpression est un état du même livre, pas un second.
 *
 * Deux colonnes portent des engagements et non des données.
 * `proof_acknowledged_final_print` est l'accord explicite « l'imprimé est
 * définitif » — sans lui, la commande ne part pas, et un défaut découvert
 * après impression n'aurait aucune trace de ce qu'on avait dit. Et
 * `print_credit_expires_at` porte le crédit d'impression de vingt-quatre mois
 * du PRD §10 : la sortie honorable d'un projet qui n'a pas abouti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // Unique : une famille n'a pas deux livres en cours.
            $table->foreignUuid('project_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('template', 24)->default('classic');
            $table->string('format', 24)->default(BookFormat::FoundingChapter->value);
            $table->string('status', 24)->default(BookStatus::Draft->value);

            // Le format que le moteur propose, distinct de celui retenu : la
            // famille peut choisir autrement, et on veut savoir laquelle des
            // deux valeurs a été suivie.
            $table->string('proposed_format', 24)->nullable();

            $table->integer('page_count_estimate')->default(0);
            $table->timestampTz('book_ready_at')->nullable();

            // L'avant-propos de la famille, au plus 1 500 caractères.
            $table->text('foreword')->nullable();
            // `edited_or_fluide` par défaut : c'est le texte mis au propre qui
            // se lit dans un livre. Le mot à mot reste accessible en ligne.
            $table->string('text_version_policy', 24)->default('edited_or_fluide');

            $table->string('proof_pdf_path')->nullable();
            $table->integer('proof_version')->default(0);
            $table->timestampTz('proof_generated_at')->nullable();
            $table->timestampTz('proof_approved_at')->nullable();
            $table->foreignId('proof_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            /*
             * Les deux accords du bon à tirer, séparés et tous deux
             * obligatoires. Le premier engage sur l'irréversible ; le second
             * dit que les noms propres ont été relus — c'est la faute la plus
             * fréquente et la plus blessante d'un livre de famille.
             */
            $table->boolean('proof_acknowledged_final_print')->default(false);
            $table->boolean('proof_acknowledged_lexicon_reviewed')->default(false);

            $table->string('print_order_ref')->nullable();
            $table->timestampTz('ordered_at')->nullable();
            $table->timestampTz('printed_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();

            // Sortie honorable (PRD §10) : une prolongation de trois mois
            // incluse, puis un crédit d'impression de vingt-quatre mois.
            $table->timestampTz('extension_granted_at')->nullable();
            $table->timestampTz('print_credit_expires_at')->nullable();

            $table->timestampsTz();

            $table->index('status');
        });

        EnumCheck::add('books', 'template', ['classic']);
        EnumCheck::add('books', 'format', EnumCheck::of(BookFormat::class));
        EnumCheck::add('books', 'proposed_format', EnumCheck::of(BookFormat::class), nullable: true);
        EnumCheck::add('books', 'status', EnumCheck::of(BookStatus::class));
        EnumCheck::add('books', 'text_version_policy', ['edited_or_fluide', 'verbatim']);

        Schema::create('book_chapters', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('story_id')->constrained()->cascadeOnDelete();

            $table->integer('position');
            $table->boolean('included')->default(true);

            /*
             * Le jeton du QR imprimé, **réutilisé** d'une génération à
             * l'autre : un QR déjà imprimé doit continuer de fonctionner, et
             * régénérer le BAT ne peut pas invalider les livres déjà sur les
             * étagères.
             */
            $table->foreignUuid('qr_token_id')->nullable()->constrained('access_tokens')->nullOnDelete();

            $table->timestampsTz();

            // Une histoire n'apparaît qu'une fois dans un livre.
            $table->unique(['book_id', 'story_id']);
            $table->index(['book_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_chapters');
        Schema::dropIfExists('books');
    }
};
