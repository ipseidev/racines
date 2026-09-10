<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La langue d'un projet, et la contrainte sur celle d'un compte (T-238).
 *
 * **Le projet** porte une langue parce que les pages à jeton n'ont pas de
 * compte : la personne qui raconte ouvre un lien, et rien dans la requête ne
 * dit dans quelle langue lui parler — l'en-tête d'un vieux téléphone dit la
 * langue du téléphone, pas celle de la famille. C'est la personne qui offre
 * qui la choisit, au tunnel, et elle vaut pour tout le projet : les pages
 * narrateur, les pages famille, le livre.
 *
 * **Le compte** avait déjà sa colonne, sans contrainte : `fr` par défaut et
 * n'importe quoi d'autre accepté. Une valeur hors énumération y produirait
 * une page à moitié traduite plutôt qu'une erreur, donc une contrainte
 * `check`, comme partout ailleurs (conventions §13).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('locale', 8)->default(Locale::default()->value)->after('timezone');
        });

        EnumCheck::add('projects', 'locale', EnumCheck::of(Locale::class));

        // Les comptes créés avant cette migration valent `fr` ; la valeur par
        // défaut de la colonne le disait déjà, la contrainte l'exige.
        DB::table('users')->whereNotIn('locale', EnumCheck::of(Locale::class))->update([
            'locale' => Locale::default()->value,
        ]);

        EnumCheck::add('users', 'locale', EnumCheck::of(Locale::class));
    }

    public function down(): void
    {
        EnumCheck::drop('users', 'locale');
        EnumCheck::drop('projects', 'locale');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
