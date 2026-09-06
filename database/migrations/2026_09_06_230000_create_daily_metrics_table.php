<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les métriques du pilote, une ligne par jour et par cohorte (bloc 15).
 *
 * **Pourquoi une table et pas un outil tiers.** Les gates 0A et Phase 1 se
 * décident sur ces chiffres. Un outil d'analytique échantillonne, applique sa
 * propre rétention, et ne se rejoue pas — or une définition de métrique se
 * corrige toujours en cours de pilote, et il faut alors pouvoir **recalculer
 * le passé**. C'est aussi la seule façon d'auditer un chiffre : la requête
 * est dans le dépôt, elle se relit.
 *
 * **Numérateur et dénominateur sont stockés**, pas seulement le taux. Un
 * « 62 % » ne dit pas s'il porte sur cinquante familles ou sur trois, et le
 * dossier fixe des seuils qu'il serait absurde de déclarer atteints sur un
 * échantillon de trois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_metrics', function (Blueprint $table): void {
            $table->id();
            $table->date('date');

            // Nulle pour un chiffre global : le pilote se lit par cohorte,
            // deux vagues de familles n'ayant pas reçu le même produit, mais
            // certaines mesures n'ont de sens qu'agrégées.
            $table->string('cohort_id')->nullable();

            $table->string('metric');
            $table->decimal('value', 12, 4)->nullable();
            $table->unsignedInteger('numerator')->nullable();
            $table->unsignedInteger('denominator')->nullable();

            $table->timestamps();

            // L'idempotence est **structurelle** : recalculer un jour écrase
            // au lieu d'empiler. Une commande planifiée qui tourne deux fois
            // ne doit pas doubler un chiffre de pilote.
            $table->unique(['date', 'cohort_id', 'metric']);
            $table->index(['metric', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_metrics');
    }
};
