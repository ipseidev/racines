<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $values = implode("','", array_column(UserRole::cases(), 'value'));

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 32)->default(UserRole::default()->value)->index();
            $table->string('locale', 8)->default('fr');
        });

        // La contrainte vit aussi en base : le code n'est pas le seul garde-fou.
        Schema::getConnection()->statement(
            "alter table users add constraint users_role_check check (role in ('{$values}'))"
        );
    }

    public function down(): void
    {
        Schema::getConnection()->statement('alter table users drop constraint if exists users_role_check');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'locale']);
        });
    }
};
