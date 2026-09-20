<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;

/**
 * Une adresse peut désormais venir de l'aperçu du tunnel de découverte.
 *
 * La contrainte vit en base et pas seulement dans le code (convention §13) :
 * c'est elle qui a refusé la première écriture du quiz, et c'est exactement
 * ce qu'on lui demande. On l'élargit plutôt que de la retirer — l'origine
 * d'un contact est une liste fermée, et savoir combien d'adresses arrivent
 * après neuf questions plutôt qu'après trois secondes est la moitié de
 * l'intérêt de la mesurer.
 */
return new class extends Migration
{
    public function up(): void
    {
        EnumCheck::drop('leads', 'source');
        EnumCheck::add('leads', 'source', [Lead::SOURCE_LANDING, Lead::SOURCE_QUIZ]);
    }

    public function down(): void
    {
        EnumCheck::drop('leads', 'source');
        EnumCheck::add('leads', 'source', [Lead::SOURCE_LANDING]);
    }
};
