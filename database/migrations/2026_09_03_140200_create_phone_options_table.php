<?php

declare(strict_types=1);

use App\Enums\PhoneOptionEntry;
use App\Enums\PhoneOptionStatus;
use App\Enums\PromptSlot;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'option « enregistrement par téléphone ».
 *
 * La seule promesse **humaine** du produit : un membre de l'équipe appelle le
 * narrateur chaque semaine et l'enregistre. D'où le plafond, et d'où le fait
 * qu'une ligne `requested` occupe déjà un créneau — le compter seulement une
 * fois active ferait accepter onze familles pour dix appels possibles.
 *
 * `entry` distingue l'achat au tunnel du rattrapage sur alerte du moteur :
 * ce ne sont pas les mêmes chiffres, et ils ne mènent pas aux mêmes
 * décisions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();

            $table->string('entry', 16);
            $table->string('status', 16)->default(PhoneOptionStatus::Requested->value);

            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->smallInteger('call_day')->nullable();
            $table->string('call_slot', 16)->nullable();
            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->index(['status', 'entry']);
            $table->index('project_id');
        });

        EnumCheck::add('phone_options', 'entry', EnumCheck::of(PhoneOptionEntry::class));
        EnumCheck::add('phone_options', 'status', EnumCheck::of(PhoneOptionStatus::class));
        EnumCheck::add('phone_options', 'call_slot', EnumCheck::of(PromptSlot::class), nullable: true);
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_options');
    }
};
