<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;

/**
 * Un narrateur peut vouloir les deux canaux.
 *
 * Beaucoup de personnes âgées lisent leurs SMS mais consultent aussi leur
 * boîte mail avec leurs enfants : envoyer sur les deux double les chances que
 * la question soit vue, et le lien est le même — il n'y a donc pas deux
 * histoires.
 */
return new class extends Migration
{
    public function up(): void
    {
        EnumCheck::drop('narrators', 'preferred_channel');
        EnumCheck::add('narrators', 'preferred_channel', Channel::narratorPreferences());
    }

    public function down(): void
    {
        EnumCheck::drop('narrators', 'preferred_channel');
        EnumCheck::add('narrators', 'preferred_channel', Channel::outboundValues());
    }
};
